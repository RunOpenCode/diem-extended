<?php

/**
 * dmMailTemplate admin form
 *
 * @package    diem-commerce
 * @subpackage dmMailTemplate
 * @author     Your name here
 */
class DmMailTemplateAdminForm extends BaseDmMailTemplateForm
{
    public function configure()
    {
        parent::configure();

        if ('embed' == sfConfig::get('dm_i18n_form')) {

            $cultures = sfConfig::get('dm_i18n_cultures');

            foreach ($cultures as $culture) {
                $this->widgetSchema[$culture]['subject'] = new sfWidgetFormInputText();
                $this->widgetSchema[$culture]['body']->setAttribute('rows', 15);
                $this->widgetSchema[$culture]['description']->setAttribute('rows', 2);
            }
        } else {

            $this->widgetSchema['subject'] = new sfWidgetFormInputText();
            $this->widgetSchema['body']->setAttribute('rows', 15);
            $this->widgetSchema['description']->setAttribute('rows', 2);
        }

        $this->widgetSchema['dm_mail_decorator_id']->setOption('add_empty', true);

        if (class_exists('sfWidgetFormTextareaDmTinyMce')) {
            $this->widgetSchema['body'] = new sfWidgetFormTextareaDmTinyMce();
            $this->widgetSchema['body']->setOption('config', sfConfig::get('dm_mail_template_rich_editor_config'));
            $this->widgetSchema['body']->setAttribute('rows', 15);
        }

        // Unset automatic fields like 'created_at', 'updated_at', 'created_by', 'updated_by'
        $this->unsetAutoFields();

        unset($this['vars']);
    }

    public function getJavascripts()
    {
        return array_merge(
            parent::getJavaScripts(),
            array(
                'admin.mailTemplate'
            )
        );
    }

}