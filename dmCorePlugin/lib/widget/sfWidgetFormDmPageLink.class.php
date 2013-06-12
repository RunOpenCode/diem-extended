<?php

/**
 * @author TheCelavi
 */
class sfWidgetFormDmPageLink extends sfWidgetFormInputText
{
    public function render($name, $value = null, $attributes = array(), $errors = array())
    {        
        $i18n = dmContext::getInstance()->getServiceContainer()->getService('i18n');
        
        $page = '';
        if ($value) {
            try {
                $page = DmPageTable::getInstance()->findOneById($value);
                $page = $page->getTitle();
            } catch (Exception $e) {
                $page = $i18n->__('ERROR: This page does not exist anymore.');
            }
        }
        
        $data = array(
            'page' => $page, 
            'title' => $i18n->__('Drag & Drop page from PAGE bar.'),
            'clear_page_message' => $i18n->__('Clear page'),
            'goto_page_message' => $i18n->__('Open page')
        );      
        
        if (isset($attributes['class'])){
            $attributes['class'] .= ' sfWidgetFormDmPageLink ' . str_replace('"', "'", json_encode($data));
        } else {
            $attributes['class'] = 'sfWidgetFormDmPageLink ' . str_replace('"', "'", json_encode($data));
        }
        return parent::render($name, $value, $attributes, $errors);
    }
    
    public function getJavaScripts()
    {
        return array_merge(
            parent::getJavaScripts(),
            array(
                'lib.ui-core',
                'lib.ui-widget',
                'lib.ui-mouse',
                'lib.ui-draggable',
                'lib.ui-droppable',
                'lib.sfWidgetFormDmPageLink'
                )
        );           
    }
    
    public function getStylesheets()
    {
        return array_merge(
            parent::getStylesheets(),
            array(
                'lib.sfWidgetFormDmPageLink'=>null
                )
        );
    }
}
