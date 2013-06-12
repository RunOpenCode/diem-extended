<?php
/**
 * @author TheCelavi
 */
class dmLessIOException extends dmLessException
{
    /**
     * Constructs dmLessIOException
     * 
     * @param string $message
     */
    public function __construct($message)
    {
        parent::__construct($message);
    }
}
