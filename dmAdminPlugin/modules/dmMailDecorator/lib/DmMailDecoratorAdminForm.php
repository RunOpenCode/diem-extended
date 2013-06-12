<?php

/**
 * dmMailDecorator admin form
 *
 * @package    Groups to the Snow
 * @subpackage dmMailDecorator
 * @author     Nikola Svitlica a.k.a TheCelavi
 */
class DmMailDecoratorAdminForm extends BaseDmMailDecoratorForm
{
    public function configure()
    {
        parent::configure();

        if ('embed' == sfConfig::get('dm_i18n_form')) {

            $cultures = sfConfig::get('dm_i18n_cultures');

            foreach ($cultures as $culture) {
                $this->widgetSchema[$culture]['template']->setAttribute('rows', 15);
            }
        } else {
            $this->widgetSchema['template']->setAttribute('rows', 15);
        }
        if (is_null($this->getDefault('template'))) {
            $this->setDefault('template', '{{%body%}}');
        }

        if (class_exists('sfWidgetFormTextareaDmTinyMce')) {
            $this->widgetSchema['template'] = new sfWidgetFormTextareaDmTinyMce();
            $this->widgetSchema['template']->setOption('config', sfConfig::get('dm_mail_template_rich_editor_config'));
        }

        $this->mergePostValidator(new sfValidatorCallback(array('callback' => array($this, 'isValidTemplate'))));
    }

    public function isValidTemplate($validator, $values)
    {
        if (trim($values['template']) == '') {
            return $values; // The template is required, it will throw exception
        }

        if (strpos($values['template'], '{{%body%}}') === false) {
            throw new sfValidatorErrorSchema($this->getValidator('template'), array(
                'template' => new sfValidatorError($this->getValidator('template'), 'The template must contain %body% variable.'),
            ));
        }

        if (strpos($values['template'], '{{%body%}}') != strrpos($values['template'], '{{%body%}}')) {
            throw new sfValidatorErrorSchema($this->getValidator('template'), array(
                'template' => new sfValidatorError($this->getValidator('template'), 'The template must contain %body% variable only once.'),
            ));
        }


        return $values;

    }

}