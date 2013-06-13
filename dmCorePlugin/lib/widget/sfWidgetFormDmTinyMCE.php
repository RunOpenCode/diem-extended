<?php
/**
 * sfWidgetFormDmTinyMCE represents a Tiny MCE widget.
 */

class sfWidgetFormDmTinyMCE extends sfWidgetFormTextarea
{

    /**
     * Constructor.
     *
     * Available options:
     *
     * width:  Width
     * height: Height
     * config: TinyMCE configuration array
     *
     * @param array $options     An array of options
     * @param array $attributes  An array of default HTML attributes
     *
     * @see sfWidgetForm
     */
    protected function configure($options = array(), $attributes = array())
    {
        $this->addOption('width');
        $this->addOption('height');
        $this->addOption('config', array());


        $this->setOption('config', array_merge($this->getOption('config'), sfConfig::get('dm_tiny_mce_config')));
    }


    /**
     * @param  string $name         The element name
     * @param  string $value        The value selected in this widget
     * @param  array $attributes    An array of HTML attributes to be merged with the default HTML attributes
     * @param  array $errors        An array of errors for the field
     *
     * @return string An HTML tag string
     *
     * @see sfWidgetForm
     */
    public function render($name, $value = null, $attributes = array(), $errors = array())
    {
        $data = $this->parseConfiguration();

        if (isset($attributes['class'])) {
            $attributes['class'] = $attributes['class'] . json_encode(array('tiny_mce_config' => $data));
        } else {
            $attributes['class'] = json_encode(array('tiny_mce_config' => $data));
        }

        $attributes['data-dme-richeditor'] = 'tiny_mce';

        return parent::render($name, $value, $attributes, $errors);
    }

    public function getJavaScripts()
    {
        return array(
            'lib.tinymce',
            'lib.jquery-tinymce',
            'lib.sfWidgetFormDmTinyMCE'
        );
    }


    protected function parseConfiguration()
    {
        $config = $this->getOption('config');

        if ($this->getOption('width')) {
            $config['width'] = $this->getOption('width');
        }

        if ($this->getOption('height')) {
            $config['height'] = $this->getOption('height');
        }

        if (isset($config['content_css'])) {
            if (sfConfig::get('sf_environment') != 'prod') {
                $config['content_css'] = public_path($config['content_css']) . '?_tinyMceCache=' . strtotime('now');
            } else {
                $config['content_css'] = public_path($config['content_css']);
            }
        }

        $config['tiny_mce_base_path'] = dirname(dmContext::getInstance()->getResponse()->calculateAssetPath('js', 'lib.tinymce'));

        return $config;
    }
}