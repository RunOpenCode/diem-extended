<?php
/**
 * @author TheCelavi
 */
class dmUnreachableRemoteLessException extends dmLessException
{
    /**
     * Constructs dmUnreachableRemoteLessException
     * 
     * @param string $remoteURI LESS file source
     */
    public function __construct($remoteURI)
    {
        parent::__construct('Remote LESS file could not be loaded from URI: ' . $remoteURI);
    }
}
