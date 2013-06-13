<?php

class sfWidgetFormDmRichEditor extends sfWidgetFormDmTinyMCE {

    public static function build($options = array(), $attributes = array())
    {
        $class = sfConfig::get('dm_rich_editor_class');
        return new $class($options, $attributes);
    }

}