<?php

/**
 * dmMailTemplate module helper.
 *
 * @package    diem-commerce
 * @subpackage dmMailTemplate
 * @author     Your name here
 * @version    SVN: $Id: helper.php 12474 2008-10-31 10:41:27Z fabien $
 */
class dmMailTemplateGeneratorHelper extends BaseDmMailTemplateGeneratorHelper
{

    public function linkTo_preview($object, $params)
    {
        if($this->module->getSecurityManager()->userHasCredentials('edit', $object))
        {
            $title = __(isset($params['title']) ? $params['title'] : $params['label'], array('%1%' => dmString::strtolower(__($this->getModule()->getName()))), 'dm');
            return '<li class="sf_admin_action_preview">'.link_to1(__($params['label'], array(), $this->getI18nCatalogue()), $this->getRouteArrayForAction('preview', $object),
                array(
                    'class' => 's16 s16_right_little dm_preview_link sf_admin_action',
                    'title' => $title,
                    'method' => 'get'
                )).'</li>';
        }
        return '';
    }
}
