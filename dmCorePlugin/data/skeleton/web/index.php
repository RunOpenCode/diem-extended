<?php

require_once(dirname(__FILE__).'/../diem-extended/dmFrontPlugin/lib/cache/page/dmPageCacheLoader.class.php');
dmPageCacheLoader::load(dirname(__FILE__).'/../cache/', 'prod');

require_once(dirname(__FILE__).'/../config/ProjectConfiguration.class.php');

$configuration = ProjectConfiguration::getApplicationConfiguration('front', 'prod', false);

dm::createContext($configuration)->dispatch();