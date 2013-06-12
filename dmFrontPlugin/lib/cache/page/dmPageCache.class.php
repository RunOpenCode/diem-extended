<?php
require_once dmOs::join(sfConfig::get('sf_root_dir'), 'diem-extended/dmCorePlugin/lib/vendor/simplehtmldom/simple_html_dom.php');
/*
 * @author TheCelavi
 */

class dmPageCache
{

    protected $filesystem;
    protected $dispacher;
    protected $context;

    public function __construct(dmContext $context, sfEventDispatcher $dispacher, dmFilesystem $filesystem)
    {
        $this->filesystem = $filesystem;
        $this->dispacher = $dispacher;
        $this->context = $context;
    }

    public function connect()
    {
        if (sfConfig::get('dm_page_cache_enabled')) {            
            $this->getDispacher()->connect('response.filter_content', array($this, 'listenToResponseFilterContent'));
        }
    }

    public function listenToResponseFilterContent(sfEvent $event, $content)
    {   
        if ($this->context->getUser()->can(dmPageCacheLoader::getInstance()->getUserPageEditPermissions())) {
            return $content;
        }        
        $page = $this->getPage();
        if ($page->is404() || $page->isSignin()) {
            // We can not cache 404 page - it will damage the cache pool and could potentialy damage
            // Server performance for that attack
            // It is much cheaper to just skip it
            // We can not cache signin page as well since the URI can be forwarded to it
            return $content;
        }        
        if  ($page->getIsStatic()) {            
            $this->saveCache($page, $content);
        } else {            
            $dynamicContent = $this->parsePageDynamicContent($content);
            if (is_null($dynamicContent)) { // There is no dynamic content, cache it as normal page
                $this->saveCache($page, $content);
            } else {
                $this->saveCache($page, $dynamicContent, true);
            }
        }
        return $content;
    }

    protected function saveCache($page, $content, $dynamic = false)
    {
        $this->checkCachePool(); // First we check the cache...
        file_put_contents(dmOs::join(
            $this->getCacheDir(),
            $this->getCacheName(
                $this->getURI(),
                $params = serialize(array_merge(((!empty($_GET)) ? $_GET : array()), ((!empty($_POST)) ? $_POST : array()))),
                $page->getIsSecure(),
                $page->getCredentials(),
                (($dynamic) ? 'php' : 'html')
            )), 
            $content
        );
    }

    protected function parsePageDynamicContent($content)
    {
        $html = str_get_html($content);
        $count = 0; // There are no dynamic widgets...
        foreach ($widgets = $html->find('.dm_widget') as $widget)
        {
            $widgetId = intval(str_replace('dm_widget_', '', $widget->id));
            
            if ($widgetId) {
                $cache = $widget->find('.dm_widget_cacheable', 0);
                if ($cache) {
                    $count++; // We have found a dynamic widget...
                    $cache->innertext = sprintf('{#page#%s#page#}{#widget#%s#widget#}', $this->getPage()->getId(), $widgetId);
                }
            }
        }        
        if ($count == 0) {
            // The page did not had any dynamic content...
            return null;
        }
        
        $code = $html->innertext;        
        $code = str_replace('{#page#', '<?php echo $helper->renderWidgetInner(array(\'page_id\'=>', $code);
        $code = str_replace('#page#}{#widget#', ', \'widget_id\'=>', $code);
        $code = str_replace('#widget#}', ')); ?>', $code);
        
        return $code;
    }

    protected function parseJavascriptsAndStylesheets()
    {
        $code = '<?php ';
        $response = $this->getContext()->getServiceContainer()
                    ->getService('response');
        foreach ($js = $response->getJavascripts() as $key=>$value) {
            $code .= sprintf('$sf_response->addJavascript (\'%s\');', $key);
        }
        
        foreach ($js = $response->getStylesheets() as $key=>$value) {
            
        }
    }

    protected function getCacheDir()
    {
        $cacheDir = dmOs::join(sfConfig::get('sf_cache_dir'), 'front', sfConfig::get('sf_environment'), 'page');
        if (!file_exists($cacheDir)) {
            $this->getFileSystem()->mkdir($cacheDir);
        }
        // create for log entry as well        
        if (!file_exists(dmOs::join(sfConfig::get('sf_data_dir'), 'dm/log/static.log'))) {
            $this->getFileSystem()->touch(dmOs::join(sfConfig::get('sf_data_dir'), 'dm/log/static.log'));
        }        
        return $cacheDir;
    }

    public function getCacheName($uri, $params, $isSecure, $credentials = '', $type = 'html')
    {
        return dmPageCacheLoader::getInstance()->getCacheName($uri, $params, $isSecure, $credentials, $type);
    }

    public function getURI()
    {
        return dmPageCacheLoader::getInstance()->getURI();
    }

    public function emptyCache()
    {
        $files = $this->getFileSystem()->find('file')->in($this->getCacheDir());
        return $this->getFileSystem()->unlink($files);
    }


    public function deleteCacheByKey($key)
    {
        $files = $this->getFileSystem()->find('file')->name($key.'.*')->in($this->getCacheDir());
        return $this->getFileSystem()->unlink($files);
    }
    
    public function deleteCache($uri)
    {
        $files = $this->getFileSystem()->find('file')->name(md5($uri).'.*')->in($this->getCacheDir());
        return $this->getFileSystem()->unlink($files);
    }

    public function deleteThisCache()
    {
        $this->deleteCache($_SERVER['PATH_INFO']);
    }

    /**
     * @return dmFilesystem
     */
    protected function getFileSystem()
    {
        return $this->filesystem;
    }

    /**
     * @return dmContext
     */
    protected function getContext()
    {
        return $this->context;
    }

    /**
     * @return sfEventDispatcher
     */
    protected function getDispacher()
    {
        return $this->dispacher;
    }

    /**
     * @return DmPage
     */
    protected function getPage()
    {
        return $this->getContext()->getPage();
    }
    /**
     * Splits pool by half according to the last accessed time...
     */
    protected function checkCachePool()
    {
        $files = $this->getFileSystem()->find('file')->in($this->getCacheDir());
        if (count($files) > sfConfig::get('dm_page_cache_max_pool_size')) {
            $all = array();
            foreach ($files as $file) {
                $all[fileatime($file)] = $file;
            }
            krsort($all);
            var_dump($all);
            $delete = array_slice($all, round(sfConfig::get('dm_page_cache_max_pool_size')/2, 0));
            var_dump($delete);
            $this->getFileSystem()->unlink($delete);
        }
    }
}

