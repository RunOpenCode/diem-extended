<?php

class dmStaticLogView extends dmLogView
{

    protected
        $rows = array(
        'time' => 'renderTime',
        'ip' => 'renderUsr', 
        'name' => 'renderBrowser',
        'uri' => 'renderUri',
        'env' => 'renderEnv'
    );

    protected function renderTime(dmStaticLogEntry $entry)
    {
        return str_replace(' CEST', '', $this->dateFormat->format($entry->get('time')));
    }
    
    protected function renderUri(dmStaticLogEntry $entry)
    {
        return sprintf('<span class="dm_nowrap">%s</span><br />%s<span class="light">%s ms</span>&nbsp;<span class="light">%s Kb</span>%s',
            $this->renderLink($entry),
            sprintf('<span class="s16 s16_%s">%s</span>',
                'status_ok',
                ' &nbsp;'
            ),
            round(floatval($entry->get('duration'))*1000),
            round($entry->get('mem') / 1024),
            '<span class="s16 s16_lightning_small"></span>'
        );        
        
    }
    protected function renderLink(dmStaticLogEntry $entry)
    {
        $uri = ltrim($entry->get('uri'), '/');
        $text = $uri ? $uri : 'front home';    
        return $this->helper->link('app:front/'.$uri)->text(dmString::escape($text));
    }
    
    protected function renderUsr(dmStaticLogEntry $entry)
    {
        return '<strong>-</strong></br>'.$entry->get('ip');
        
    }
    
    protected function renderBrowser(dmStaticLogEntry $entry)
    {
        $browser = $entry->get('name');

        return sprintf('<div class="clearfix"><div class="browser browser_block %s fleft"></div><strong class="mr10">%s %s</strong><span class="light">%s</span>',
            strtolower($browser),
            ucfirst($browser),
            $this->getBrowserVersion($entry->get('version')),
            $entry->get('user_agent')
        );
    }
    
    protected function getBrowserVersion($version)
    {
        $version = explode('.', $version);
        return $version[0].'.'.$version[1];
    }
    
    protected function renderEnv(dmStaticLogEntry $entry)
    {
        return 'front '.$entry->get('env');
    }
        
}

