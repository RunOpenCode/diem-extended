<?php
/**
 * Invalid LESS code exception
 * 
 * @author TheCelavi
 */
class dmInvalidLessException extends dmLessException
{
    /**
     * Constructs dmInvalidLessException
     * 
     * @param string $compilerErrorOutput LESS compiler error output message
     */
    public function __construct($compilerErrorOutput)
    {
        parent::__construct('Invalid LESS code! Compiler error output: ' . $compilerErrorOutput);
    }
}