<?php

class dmPageCacheLoader 
{
    
    private static $instance;

    protected $cacheDir;
    protected $environment;
    public $start;

    /**
     * @return dmPageCacheLoader
     */
    public static function getInstance()
    {        
        if (!is_object(self::$instance)) {
            self::$instance = new dmPageCacheLoader();
        }
        return self::$instance;
    }

    public function setCacheDir($dir)
    {
        $this->cacheDir = $dir;
    }

    public function setEnvironment($env)
    {
        $this->environment = $env;
    }

    public static function load($cacheDir, $environment)    {
        
        $loader = self::getInstance();
        $loader->start = microtime(true);
        $loader->setCacheDir($cacheDir);
        $loader->setEnvironment($environment);
        
        session_name('symfony');
        session_start();
        if (
            (isset($_SESSION['symfony/user/sfUser/superAdmin']) && $_SESSION['symfony/user/sfUser/superAdmin']) || 
            $loader->can($loader->getUserPageEditPermissions())            
        ) {
            return; // If this user can edit page, do not use cache in session
        }
        $params = serialize(array_merge(((!empty($_GET)) ? $_GET : array()), ((!empty($_POST)) ? $_POST : array())));
        $uri = $loader->getUri();
        $loader->loadStaticPage($uri, $params);
        $loader->loadDynamicPage($uri, $params);        
    }
    
    /**
     * Checks if user has permission to se this page
     * @param string $perms of cached page
     * @return boolean 
     */
    protected function can($perms)
    {
        $userPerms = isset($_SESSION['symfony/user/sfUser/credentials']) ? $_SESSION['symfony/user/sfUser/credentials'] : array();
        if(is_string($userPerms)) {
            $userPerms = array_map('trim', explode(',', $userPerms));
        }
    
        if(is_array($userPerms)){
            return (bool) count(array_intersect($perms, $userPerms));
        }
        
        return false;
    }

    
    
    /**
     * Gets the cache dir
     * @return string
     */
    protected function getCacheDir()
    {
        return $this->cacheDir . 'front/' . $this->environment . '/page/';
    }
    
    /**
     * Defined permissions which enables to the user to administer pages
     * @return array
     */
    public function getUserPageEditPermissions()
    {
        return array(
            'media_bar_front', 
            'page_add',
            'page_bar_front',
            'page_delete',
            'page_edit',
            'tool_bar_front',
            'widget_add',
            'widget_delete',
            'widget_edit',
            'widget_edit_fast',
            'widget_edit_fast_content_image',
            'widget_edit_fast_content_link',
            'widget_edit_fast_content_text',
            'widget_edit_fast_content_title',
            'widget_edit_fast_navigation_menu',
            'widget_edit_fast_record',
            'zone_add',
            'zone_delete',
            'zone_edit',
            'behavior_add',	
            'behavior_delete', 
            'behavior_edit',
            'behavior_sort',
            'clear_cache',
            'code_editor',
            'manual_metas'
        );
    }
    
    /**
     * Generates cache name using MD5 algorithm
     * @param string $uri URI for cache
     * @param string $params GET and POST params
     * @param type $isSecure Do we fetch a secure page
     * @param type $credentials Credentials of page
     * @param type $type Type of cache HTML or PHP
     * @return string Cache name
     */
    public function getCacheName($uri, $params, $isSecure, $credentials = '', $type = 'html')
    {
        if ($isSecure) {
            return sprintf('%s.%s._secure_page_%s.%s', md5($uri), md5($params), str_replace(',', '.', $credentials), $type);
        } else {
            return sprintf('%s.%s.%s', md5($uri), md5($params), $type);
        }
    }
    
    public function getURI()
    {
        return str_replace('?'.$_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']);         
    }

    protected function loadStaticPage($uri, $params)
    {
        $content = null;
        // Check if there is no secure cached page
        $fileName = $this->getCacheName($uri, $params, false, '', 'html');
        if (file_exists($this->getCacheDir() . $fileName)) {
            $content = @file_get_contents($this->getCacheDir() . $fileName);
            if ($content) { // We found content, echo it!
                $this->log();
                echo $content;                
                exit; // Since we found it, no other execution is required
            }
        }
        
        
        // No cache - check if there is a secure version
        if (isset($_SESSION['symfony/user/sfUser/authenticated']) && $_SESSION['symfony/user/sfUser/authenticated']) { // User is authenticated
            $pattern = $this->getCacheName($uri, $params, false, '', '');
            $search = $this->getCacheDir() . $pattern . '_secure_page_' . "*" . '.html';
            $files = glob($search);
            if (count($files)) {
                $fileName = $files[0];
                $requiredPerms = explode('.', str_replace($this->getCacheDir() . $pattern . '_secure_page_', '', $fileName));
                array_pop($requiredPerms);
                if ($this->can($requiredPerms)) {
                    $content = file_get_contents($fileName);
                    if ($content) {
                        $this->log();
                        echo $content;                        
                        exit;
                    }
                }
            }
        }
    }
    
    protected function loadDynamicPage($uri, $params)
    {
        $fileName = $this->getCacheDir() . $this->getCacheName($uri, $params, false, '', 'php');        
        if (file_exists($fileName)) {
            $content = @file_get_contents($fileName);
            if ($content) { // We found content, echo it!
                $_SESSION['symfony/page_cache/template'] = $fileName;
            }
        }
        
        
        // No cache - check if there is a secure version
        if (isset($_SESSION['symfony/user/sfUser/authenticated']) && $_SESSION['symfony/user/sfUser/authenticated']) { // User is authenticated
            $pattern = $this->getCacheName($uri, $params, false, '', 'php');
            $search = $this->getCacheDir() . $pattern . '_secure_page_' . "*" . '.php';
            $files = glob($search);
            if (count($files)) {
                $fileName = $files[0];
                $requiredPerms = explode('.', str_replace($this->getCacheDir() . $pattern . '_secure_page_', '', $fileName));
                array_pop($requiredPerms);
                if ($this->can($requiredPerms)) {
                    $content = file_get_contents($fileName);
                    if ($content) {
                        $_SESSION['symfony/page_cache/template'] = $fileName;
                    }
                }
            }
        }
    }
    
    protected function log()
    {
        require_once(dirname(__FILE__).'/dmStaticPageLog.class.php');
        dmStaticPageLog::log($this->environment);
    }
}