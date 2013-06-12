<?php

/**
 * dmConfig stores all configuration information for a Diem application in database.
 * It uses sfConfig as param storage
 */
class dmConfig {

    protected static
        $dispatcher,
        $culture,
        $loaded = false;

    /**
     * Retrieves a config parameter.
     * If config parameter does not exist, will create it, assign given $default value and return its value
     *
     * @param string $name    A config parameter name
     * @param mixed  $default A default config parameter value
     *
     * @return mixed A config parameter value, if the config parameter exists, otherwise null
     */
    public static function get($name, $default = null) {
        if (!self::has($name)) {
            return self::set($name, $default);
        }

        return sfConfig::get(sprintf('dm_dmConfig_cache_%s_%s', self::getCulture(), $name), $default);
    }

    /**
     * Indicates whether or not a config parameter exists.
     *
     * @param string $name A config parameter name
     *
     * @return bool true, if the config parameter exists, otherwise false
     */
    public static function has($name) {
        if (!self::$loaded) {
            self::load();
        }
        return sfConfig::has(sprintf('dm_dmConfig_cache_%s_%s', self::getCulture(), $name));
    }

    /**
     * Sets a config parameter.
     *
     * If a config parameter with the name already exists the value will be overridden.
     * If config parameter does not exist, one will be created, name & value will be
     * assigned and value will be returned
     *
     * @param string $name  A config parameter name
     * @param mixed  $value A config parameter value
     */
    public static function set($name, $value) {
        if (!self::$loaded) {
            self::load();
        }
        /*
         * Convert booleans to 0, 1 not to fail doctrine validation
         */
        if (is_bool($value)) {
            $value = (string) (int) $value;
        }

        sfConfig::set(sprintf('dm_dmConfig_cache_%s_%s', self::getCulture(), $name), $value);

        $setting = dmDb::query('DmSetting s')->where('s.name = ?', $name)->withI18n(self::getCulture())->fetchOne();

        if (!$setting) {
            $setting = new DmSetting();
            $setting->set('name', $name);
        }

        $setting->set('value', $value);

        $setting->save();

        self::dump();

        self::$dispatcher->notify(new sfEvent(null, 'dm.config.updated', array(
            'setting' => $setting,
            'culture' => self::getCulture()
        )));

        return sfConfig::get(sprintf('dm_dmConfig_cache_%s_%s', self::getCulture(), $name));
    }

    /**
     * Retrieves all configuration parameters.
     *
     * @return array An associative array of configuration parameters.
     */
    public static function getAll() {
        if (!self::$loaded) {
            self::load();
        }

        $config = sfConfig::getAll();
        $dump = array();
        $pattern = sprintf('dm_dmConfig_cache_%s_', self::$culture);
        $patternSize = strlen($pattern);
        foreach ($config as $key => $value) {
            if (substr($key, 0, $patternSize) == $pattern) {
                $dump[str_replace($pattern, '', $key)] = $value;
            }
        }

        return $dump;
    }

    public static function initialize(sfEventDispatcher $dispatcher) {
        self::$dispatcher = $dispatcher;
        self::connect();
    }

    public static function connect() {
        self::$dispatcher->connect('user.change_culture', array('dmConfig', 'listenToChangeCultureEvent'));
    }

    /**
     * Listens to the user.change_culture event.
     *
     * @param sfEvent An sfEvent instance
     */
    public static function listenToChangeCultureEvent(sfEvent $event) {
        self::$culture = $event['culture'];
    }

    public static function load($useCache = true) {
        if ($useCache && file_exists(self::getCacheFileName())) {
            require_once self::getCacheFileName();
        } else {
            if (sfConfig::has('dm_i18n_cultures')) {
                $cultures = sfConfig::get('dm_i18n_cultures');
            } else {
                $tmp = sfYaml::load(file_get_contents(dmOs::join(sfConfig::get('sf_config_dir'), 'dm/config.yml')));
                $cultures = $tmp['all']['i18n']['cultures'];
            }

            $results = dmDb::pdo(sprintf('SELECT s.name, s.type, t.value, t.default_value, t.lang FROM dm_setting s LEFT JOIN dm_setting_translation t ON t.id=s.id AND t.lang IN (\'%s\')', implode('\', \'', $cultures)))->fetchAll(PDO::FETCH_NUM);
            $config = array();

            foreach ($results as $result) {
                $value = ($result[2] != '') ? $result[2] : $result[3];
                switch ($result[1]) {
                    case 'boolean':
                        if ($value == '1') {
                            $value = true;
                        } else {
                            $value = false;
                        }
                        break;
                    case 'number':
                        $value = floatval($value);
                        break;
                    case 'integer':
                        $value = intval($value);
                        break;
                }
                // sfConfig::set(sprintf('dm_dmConfig_cache_%s_%s', $result[4], $result[0]), $value);
                // $config[sprintf('dm_dmConfig_cache_%s_%s', $result[4], $result[0])] = $value;
                $config[$result[0]][$result[4]] = $value;
            }
            unset($results);
            self::toMemory($config, $cultures);
            self::dump();
        }
        self::$loaded = true;
    }

    protected static function toMemory($configs, $cultures) {
        $noOfCultures = count($cultures);
        foreach ($configs as $configKey => $langValues) {
            if (count($langValues) < $noOfCultures) {
                // Missing translations
                $default = reset($langValues);
                // Lets see which is missing...
                foreach ($cultures as $culture) {
                    if (!isset($langValues[$culture])) {
                        $langValues[$culture] = $default;
                    }
                }
            }
            foreach ($langValues as $lang => $configValue) {
                sfConfig::set(sprintf('dm_dmConfig_cache_%s_%s', $lang, $configKey), $configValue);
            }
        }
    }

    protected static function dump($config = null) {
        $dump = array();

        $config = sfConfig::getAll();
        foreach ($config as $key => $value) {
            if (substr($key, 0, 18) == 'dm_dmConfig_cache_') {
                if (is_string($value)) {
                    $dump[] = sprintf('\'%s\' => \'%s\'', $key, addslashes($value));
                } elseif (is_bool($value)) {
                    $dump[] = sprintf('\'%s\' => %s', $key, ($value) ? 'true' : 'false');
                } else {
                    $dump[] = sprintf('\'%s\' => %s', $key, $value);
                }
            }
        }

        $content = sprintf('<?php sfConfig::add(array(%s));', implode(", \n\r", $dump));

        if (!file_exists(dirname(self::getCacheFileName()))) {
            @mkdir(dirname(self::getCacheFileName()));
            @chmod(dirname(self::getCacheFileName()), 0777);
        }

        @file_put_contents(self::getCacheFileName(), $content, LOCK_EX);
        @chmod(self::getCacheFileName(), 0777);
    }

    public static function getCulture() {
        if (!self::$culture) {
            if (class_exists('dmContext', false) && dmContext::hasInstance() && $user = dmContext::getInstance()->getUser()) {
                self::$culture = $user->getCulture();
            } else {
                self::$culture = sfConfig::get('sf_default_culture');
            }
        }

        return self::$culture;
    }

    public static function setCulture($culture) {
        self::$culture = $culture;
    }

    public static function isCli() {
        return defined('STDIN');
    }

    public static function canSystemCall() {
        if (function_exists('exec')) {
            try {
                $canSystemCall = (bool) sfToolkit::getPhpCli();
            } catch (sfException $e) {
                $canSystemCall = false;
            }
        } else {
            $canSystemCall = false;
        }

        return false;
    }

    public static function getCacheFileName() {
        return dmOs::join(sfConfig::get('sf_cache_dir'), 'dm', sprintf('dmConfigCache.php'));
    }

}
