<?php

include_once(dmOs::join(sfConfig::get('dm_core_dir'), 'modules/dmRichEditor/lib/dmBaseRichEditorActions.php'));

class dmRichEditorActions extends dmBaseRichEditorActions {

    protected function isAuthorized($type)
    {
        switch ($type) {
            case 'page':
                return $this->getUser()->can('page_bar_admin');
                break;
            case 'media':
                return $this->getUser()->can('media_bar_admin');
                break;
            default:
                return false;
                break;
        }
    }
}