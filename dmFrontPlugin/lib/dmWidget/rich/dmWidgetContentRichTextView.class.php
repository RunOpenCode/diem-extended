<?php

class dmWidgetContentRichTextView extends dmWidgetPluginView {

    public function configure()
    {
        parent::configure();
        $this->addRequiredVar('content');
    }

    protected function doRender()
    {
        if ($this->isCachable() && $cache = $this->getCache()) {
            return $cache;
        }

        $vars = $this->getViewVars();
        $html = $this->getHelper()->parseRichContent($vars['content']);

        if ($this->isCachable()) {
            $this->setCache($html);
        }

        return $html;
    }

    protected function doRenderForIndex()
    {
        $vars = $this->getViewVars();
        return strip_tags($vars['content']);
    }

}