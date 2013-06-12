<?php

/**
 * @author TheCelavi
 */
class dmLessCompiler extends dmConfigurable
{

    const DM_LESS_COMPILER_IO_TYPE_CODE = 0;
    const DM_LESS_COMPILER_IO_TYPE_FILE = 1;
    const DM_LESS_COMPILER_IO_TYPE_REMOTE = 2;

    protected $adapter;

    public function __construct(array $options = array())
    {
        $this->configure(array_merge(
                array(
                'adapter' => 'dmLessAdapterPHP', // The default adapter
                'cache_dir' => 'cache'
                ), $options
            ));
    }

    public function configure(array $options = array())
    {
        parent::configure($options);
        return $this;
    }

    public function hasOutput($key)
    {
        return file_exists($this->getFullPath($key));
    }

    public function hasCache($key)
    {
        return $this->hasOutput($key);
    }

    public function getOutput($key, $outputType = 1)
    {
        if ($outputType == dmLessCompiler::DM_LESS_COMPILER_IO_TYPE_FILE) {
            return $this->getWebPath($key);
        } else {
            $code = file_get_contents($this->getFullPath($key));
            if ($code) {
                return $code;
            } else {
                throw new dmLessException(sprintf('There is no cached output for LESS code on path "%s".', $key));
            }
        }
    }

    public function getCache($key, $outputType = 1)
    {
        return $this->getOutput($key, $outputType);
    }

    public function compile($key, $inputType = 1, $code = null, $outputType = 1)
    {   
        switch ($inputType) {
            case dmLessCompiler::DM_LESS_COMPILER_IO_TYPE_FILE: 
                $source = $this->loadLocalCode($key);
                $importDirs = $this->getImportDirs($key);
                $compiled = $this->getAdapter()->compile($source, $importDirs);
                break;
            case dmLessCompiler::DM_LESS_COMPILER_IO_TYPE_REMOTE:
                $source = $this->loadRemoteCode($key);
                $compiled = $this->getAdapter()->compile($source); 
                break;
            case dmLessCompiler::DM_LESS_COMPILER_IO_TYPE_CODE:
                $source = $code;
                $compiled = $this->getAdapter()->compile($source); 
                break;
        }        
        // Cache it
        $this->setOutput($key, $compiled);
        // Return result
        if ($outputType == dmLessCompiler::DM_LESS_COMPILER_IO_TYPE_CODE) {
            return $compiled;
            
        } else {
            return $this->getOutput($key, $outputType);
        }
    }

    protected function loadRemoteCode($uri)
    {
        $browser = dmContext::getInstance()->getServiceContainer()->getService('web_browser');
        try {
            if (!$browser->get($uri)->responseIsError()) {
                return $browser->getResponseText();
            } else {
                throw new dmException();
            }
        } catch (Exception $e) {
            throw new dmUnreachableRemoteLessException(sprintf('LESS code from source "%s" can not be reached.', $uri));
        }
    }

    protected function loadLocalCode($src)
    {
        if (!file_exists($src)) {
            $src = dmOs::join(sfConfig::get('sf_web_dir'), $src);
        }
        if (!file_exists($src)) {
            throw new dmLessIOException(sprintf('LESS file on path "%s" can not be found.', $src));
        }
        if (!($code = file_get_contents($src))) {
            throw new dmLessIOException(sprintf('LESS file content on path "%s" can not be read.', $src));
        } else {
            return $code;
        }       
    }

    protected function getImportDirs($src) {
        // TODO Improve for several different dirs...
        if (!file_exists($src)) {
            $src = dmOs::join(sfConfig::get('sf_web_dir'), $src);
        }        
        return array(dirname($src));
    }

    protected function setOutput($key, $code)
    {
        if (!file_put_contents($this->getFullPath($key), $code)) {
            throw new dmLessIOException(sprintf('Compiled LESS code can not be saved on path "%s".', $this->getFullPath($key)));
        }
        return $this;
    }

    protected function getFullPath($key)
    {
        $filename = 'compiled' . str_replace('/', '.', str_replace('\\', '.', $key)).'.css';
        return dmOs::join(sfConfig::get('sf_web_dir'), $this->getOption('cache_dir'), $filename);
    }
    
    protected function getWebPath($key)
    {
        $filename = 'compiled' . str_replace('/', '.', str_replace('\\', '.', $key)).'.css';
        return dmOs::join($this->getOption('cache_dir'), $filename);
    }

    protected function getAdapter()
    {
        if ($this->adapter) {
            return $this->adapter;
        } else {
            $className = $this->getOption('adapter');
            $this->adapter = new $className();
            return $this->adapter;
        }
    }

}
