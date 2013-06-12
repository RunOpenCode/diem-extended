<?php

require_once(realpath(dirname(__FILE__).'/dmStaticLogView.php'));

class dmStaticLogViewLittle extends dmStaticLogView
{

    protected
        $rows = array(
        'user' => 'renderIpAndBrowser',
        'location' => 'renderUri',
    );

    protected function renderIpAndBrowser(dmStaticLogEntry $entry)
    {
        $browser = $entry->get('name');
        return sprintf('<div class="browser %s">%s<br />%s %s</div>',
          strtolower($browser),
          $this->renderIp($entry->get('ip')),
          ucfirst($browser),
          $this->getBrowserVersion($entry->get('version'))
        );
    }
}
