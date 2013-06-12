<?php
/**
 * This class emulates DIEM way of logging
 * For this purposes, it is not convinient to use it since it is slow...
 */
class dmStaticLogEntry extends dmLogEntry
{

    public function configure(array $data)
    {
        $this->data = array(
            'time' => '',
            'uri' => '',
            'code' => '',
            'env' => '',
            'ip' => '',
            'user_agent' => '',
            'mem' => '',
            'duration' => '',
            'name' => '',
            'version' => '',
            'platform' => '',
        );
    }

}
