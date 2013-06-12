<?php

class dmWebResponse extends sfWebResponse
{
  protected
  $isHtmlForHuman = true,
  $assetAliases,
  $cdnConfig,
  $javascriptConfig,
  $culture,
  $theme,
  $xmlns,
  $lessCompiler;
  
  public function initialize(sfEventDispatcher $dispatcher, $options = array())
  {
    parent::initialize($dispatcher, $options);
    
    $this->javascriptConfig = array();
    
    $this->dispatcher->connect('user.change_culture', array($this, 'listenToChangeCultureEvent'));
    
    $this->dispatcher->connect('user.change_theme', array($this, 'listenToChangeThemeEvent'));
    
    $this->dispatcher->connect('user.remember_me', array($this, 'listenToRememberMeEvent'));
    
    $this->dispatcher->connect('user.sign_out', array($this, 'listenToSignOutEvent'));
    
    $this->xmlns = array();
  }

  /**
   * Listens to the user.change_culture event.
   *
   * @param sfEvent An sfEvent instance
   */
  public function listenToChangeCultureEvent(sfEvent $event)
  {
    $this->culture = $event['culture'];
  }

  /**
   * Listens to the user.change_theme event.
   *
   * @param sfEvent An sfEvent instance
   */
  public function listenToChangeThemeEvent(sfEvent $event)
  {
    $this->setTheme($event['theme']);
  }
  
  /**
   * Listens to the user.remember_me event.
   *
   * @param sfEvent An sfEvent instance
   */
  public function listenToRememberMeEvent(sfEvent $event)
  {
    $this->setCookie($this->getRememberCookieName(), $event['remember_key'], time() + $event['expiration_age']);
  }
  
  /**
   * Listens to the user.sign_out event.
   *
   * @param sfEvent An sfEvent instance
   */
  public function listenToSignOutEvent(sfEvent $event)
  {
    $this->setCookie($this->getRememberCookieName(), '', time() - $event['expiration_age']);
  }
  
  public function getRememberCookieName()
  {
    return sfConfig::get('dm_security_remember_cookie_name', 'dm_remember_'.dmProject::getHash());
  }
  
  public function setTheme(dmTheme $theme)
  {
    $this->theme = $theme;
  }

  public function getTheme()
  {
    if($this->theme)
    {
      return $this->theme;
    }
    // quick ugly fix
    else
    {
      return sfContext::getInstance()->getServiceContainer()->getParameter('user.theme');
    }
  }

  public function getAssetAliases()
  {
    if (null === $this->assetAliases)
    {
      $this->assetAliases = include(dmContext::getInstance()->get('config_cache')->checkConfig('config/dm/assets.yml'));
    }
    
    return $this->assetAliases;
  } 
  
  /**
   * Sets the asset configuration
   *
   * @param dmAssetConfig the asset configuration
   */
  public function setAssetConfig(dmAssetConfig $assetConfig)
  {
    foreach($assetConfig->getStylesheets() as $stylesheet)
    {
      $this->addStylesheet($stylesheet, 'first');
    }
    
    foreach($assetConfig->getJavascripts() as $javascript)
    {
      $this->addJavascript($javascript, 'first');
    }
  }
  
  public function getCdnConfig()
  {
    if (null === $this->cdnConfig)
    {
      $this->cdnConfig = array(
        'css' => sfConfig::get('dm_css_cdn',  array('enabled' => false)),
        'js'  => sfConfig::get('dm_js_cdn',   array('enabled' => false))
      );
    }
    
    return $this->cdnConfig;
  }
  
  public function getJavascriptConfig()
  {
    return $this->javascriptConfig;
  }
  
  public function addJavascriptConfig($key, $value)
  {
    return $this->javascriptConfig[$key] = $value;
  }
  
  public function isHtml()
  {
    return strpos($this->getContentType(), 'text/html') === 0;
  }

  public function calculateAssetPath($type, $asset)
  {
    if ($asset{0} === '/' || strpos($asset, 'http://') === 0 || 0 === strncmp($asset, 'https://', 8))
    {
      $path = $asset;
    }
    else
    {
      $cdnConfig = $this->getCdnConfig();
      $assetAliases = $this->getAssetAliases();
      
      if(isset($cdnConfig[$type]) && $cdnConfig[$type]['enabled'] && isset($cdnConfig[$type][$asset]))
      {
        $path = $cdnConfig[$type][$asset];
      }
      elseif(isset($assetAliases[$type.'.'.$asset]))
      {
        $path = $assetAliases[$type.'.'.$asset];
      }
      elseif($type === 'css')
      {
        if ((substr($asset, -5) == '.less') || (substr($asset, -5) == '.sass') || (substr($asset, -5) == '.scss'))
        {
          $path = $this->getTheme()->getPath('css/'.$asset); // Leave extension to intercept it for LESS and SASS complier...
        } 
        else
        {
          $path = $this->getTheme()->getPath('css/'.$asset.'.css');
        }
      }
      else
      {
        $path = '/'.$type.'/'.$asset.'.'.$type;
      }
      
      if (strpos($path, '%culture%') !== false)
      {
        $path = str_replace('%culture%', $this->culture, $path);
      }
    }
    
    return $path;
  }
  
  public function calculateCSSPathFromLess($path) { 
      $isRemote = false;
      // Load configuration
      if (strpos($path, 'http://') === 0 || 0 === strncmp($path, 'https://', 8)) {
          $config = sfConfig::get('dm_less_remote');
          $isRemote = true;
      } else {
          $config = sfConfig::get('dm_less_local');
      }
      // Where do compailing takes place?
      if ($config['compiler'] == 'client') {
          $this->addJavascript('lib.less');
          return $path;
      }
      // Load from cache
      if ($config['cache'] && $this->getLessCompiler()->hasCache($path)) {
          return $this->getLessCompiler()->getCache($path, dmLessCompiler::DM_LESS_COMPILER_IO_TYPE_FILE);
      }
      // Compile      
      try {
        return $this->getLessCompiler()->compile(
            $path,
            (($isRemote) ? dmLessCompiler::DM_LESS_COMPILER_IO_TYPE_REMOTE : dmLessCompiler::DM_LESS_COMPILER_IO_TYPE_FILE)
        );        
      } catch (Exception $e) {
          if ($config['error_fail_safe']) {
              $this->addJavascript('lib.less');
              return $path;
          } else throw $e;
      }
  }
  
  public function calculateCSSPathFromSass($asset) {
      throw new dmException('The support for SASS is not implemented jet');
  }

  /**
   * Adds javascript code to the current web response.
   *
   * @param string $file      The JavaScript file
   * @param string $position  Position
   * @param string $options   Javascript options
   */
  public function addJavascript($asset, $position = '', $options = array())
  {
    if(!$this->isHtmlForHuman)
    {
      return $this;
    }

    if(in_array($asset, sfConfig::get('dm_js_head_inclusion')))
    {
      $options['head_inclusion'] = true;
    }
    
    $this->validatePosition($position);

    $file = $this->calculateAssetPath('js', $asset);

    $this->javascripts[$position][$file] = $options;

    return $this;
  }

  /**
   * Adds a stylesheet to the current web response.
   *
   * @param string $file      The stylesheet file
   * @param string $position  Position
   * @param string $options   Stylesheet options
   */
  public function addStylesheet($asset, $position = '', $options = array())
  {
    if(!$this->isHtmlForHuman)
    {
      return $this;
    }    
    $this->validatePosition($position);
    $file = $this->calculateAssetPath('css', $asset);
    if (substr($file, -5) == '.less') { // If it is a LESS or SASS file, we do callculation diferently
        $file = $this->calculateCSSPathFromLess($file);
    } elseif (substr($file, -5) == '.sass') {
        $file = $this->calculateCSSPathFromSass($file);
    } elseif (substr($file, -5) == '.scss') {
        $file = $this->calculateCSSPathFromSass($file);    
    }

    $this->stylesheets[$position][$file] = $options;

    return $this;
  }

  public function clearStylesheets()
  {
    $this->stylesheets = array_combine($this->positions, array_fill(0, count($this->positions), array()));

    return $this;
  }

  public function clearJavascripts()
  {
    $this->javascripts = array_combine($this->positions, array_fill(0, count($this->positions), array()));

    return $this;
  }

  public function getHeadJavascripts()
  {
    $headInclusion = sfConfig::get('dm_js_head_inclusion');
    
    if(empty($headInclusion))
    {
      return array();
    }
    
    $javascripts = array();
    foreach($this->getJavascripts() as $webPath => $options)
    {
      if (isset($options['head_inclusion']))
      {
        $javascripts[$webPath] = $options;
      }
    }

    return $javascripts;
  }
  
  /**
   * Means that request has been sent by a human, and the application will send html for a browser.
   * CLI, ajax and flash are NOT human.
   * @return boolean $human
   */
  public function isHtmlForHuman()
  {
    return $this->isHtmlForHuman;
  }
  
  public function setIsHtmlForHuman($val)
  {
    $this->isHtmlForHuman = (bool) $val;
  }  
  
  public function addXmlNs(dmXmlNamespace $namespace) 
  {
      return $this->xmlns[$namespace->getNamespace()] = $namespace;
  }
  
  public function setXmlNs(dmXmlNamespace $namespace) 
  {
      return $this->xmlns[$namespace->getNamespace()] = $namespace;
  }
  
  public function removeXmlNs($namespace)
  {
      if ($namespace instanceof dmXmlNamespace) $namespace = $namespace->getNamespace ();
      unset ($this->xmlns[$namespace]);
      return $this;
  }
  
  public function clearXmlNs()
  {
      $this->xmlns = array();
      return $this;
  }
  
  public function getXmlNs($namespace)
  {
      if (isset ($this->xmlns[$namespace])) return $this->xmlns[$namespace];
      return null;
  }
  
  public function getAllXmlNs() {
      return $this->xmlns;
  }
  
  public function getLessCompiler() {
      if ($this->lessCompiler) {
          return $this->lessCompiler;
      } else {
          $this->lessCompiler = dmContext::getInstance()->getServiceContainer()->getService('less_compiler');
          return $this->lessCompiler;
      }
  }
  
}