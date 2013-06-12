<?php

require_once dirname(__FILE__).'/../lib/dmMailTemplateGeneratorConfiguration.class.php';
require_once dirname(__FILE__).'/../lib/dmMailTemplateGeneratorHelper.class.php';

/**
 * dmMailTemplate actions.
 *
 * @package    diem-commerce
 * @subpackage dmMailTemplate
 * @author     Your name here
 * @version    SVN: $Id: actions.class.php 12474 2008-10-31 10:41:27Z fabien $
 */
class dmMailTemplateActions extends autoDmMailTemplateActions
{
    public function executePreview(dmWebRequest $request)
    {
        $this->record = $this->getObjectOrForward404($request);

        $this->content = $this->record->get('body');
        if ($this->record->get('dm_mail_decorator_id')) {
            $decorator = $this->record->get('Decorator');
            if ($decorator->get('is_active')) {
                $this->content = str_replace('{{%body%}}', $this->content, $decorator->get('template'));
            }
        }

        $this->dispatcher->connect('dm.bread_crumb.filter_links', array($this, 'previewListenToBreadCrumbFilterLinksEvent'));
    }

    public function previewListenToBreadCrumbFilterLinksEvent(sfEvent $event, array $links)
    {
        unset($links['action']);
        $links['object'] = array('object' => $this->record);
        $links[] = $this->getHelper()->tag('h1', $this->getI18n()->__('Preview'));
        return $links;
    }
}
