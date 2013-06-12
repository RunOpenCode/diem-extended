<?php

class dmStaticLog extends dmFileLog
{

    protected
        $fields = array(
        'time',
        'uri',
        'code',
        'env',
        'ip',
        'user_agent',
        'mem',
        'duration',
        'name',
        'version',
        'platform'
    );

    public function getDefaultOptions()
    {
        return array_merge(parent::getDefaultOptions(), array(
            'name' => 'Static',
            'file' => 'data/dm/log/static.log',
            'entry_service_name' => 'static_log_entry'
            ));
    }

}

