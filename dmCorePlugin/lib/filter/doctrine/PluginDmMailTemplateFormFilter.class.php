<?php

/**
 * PluginDmMailTemplate form.
 *
 * @package    ##PROJECT_NAME##
 * @subpackage filter
 * @author     ##AUTHOR_NAME##
 * @version    SVN: $Id$
 */
abstract class PluginDmMailTemplateFormFilter extends BaseDmMailTemplateFormFilter
{
    public function setup()
    {
        parent::setup();
        if($this->needsWidget('dm_mail_decorator_id')){
            $this->setWidget('dm_mail_decorator_id', new sfWidgetFormDoctrineChoice(array('multiple' => false, 'model' => 'DmMailDecorator', 'expanded' => false)));
            $this->setValidator('dm_mail_decorator_id', new sfValidatorDoctrineChoice(array('multiple' => false, 'model' => 'DmMailDecorator', 'required' => false)));
        }
    }
}
