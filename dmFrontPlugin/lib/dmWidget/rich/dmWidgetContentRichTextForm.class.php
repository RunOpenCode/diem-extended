<?php

class dmWidgetContentRichTextForm extends dmWidgetPluginForm {

    public function configure()
    {
        $this->widgetSchema['content'] = new sfWidgetFormDmRichEditor();
        $this->widgetSchema['content']->setAttribute('rows', 20);
        $this->validatorSchema['content'] = new sfValidatorString(array(
            'required' => true
        ));

        parent::configure();
    }

    public function render($attributes = array())
    {
        $attributes = dmString::toArray($attributes, true);

        return
            $this->open($attributes).
            $this->getHelper()->open('ul.dm_form_elements').
                $this->getHelper()->tag('li.dm_form_element clearfix', $this['content']->field()->error()).
                $this->getHelper()->tag('li.dm_form_element clearfix', $this['cssClass']->label()->field()->error()).
            $this->getHelper()->close('ul').
            $this->renderActions().
            $this->close();
    }
}