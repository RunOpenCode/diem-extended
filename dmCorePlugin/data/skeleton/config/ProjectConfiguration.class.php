<?php

require_once ##DIEM_CORE_STARTER##;
require_once 'dmLoadPluginsConfiguration.class.php';
dm::start();

class ProjectConfiguration extends dmProjectConfiguration
{

  public function setup()
  {
    parent::setup();
    
    $this->enablePlugins(dmLoadPluginsConfiguration::getPlugins());

    $this->setWebDir(##DIEM_WEB_DIR##);
  }
  
}