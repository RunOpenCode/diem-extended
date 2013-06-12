<?php
/**
 * @author TheCelavi
 */
abstract class dmLessAdapter
{
    /**
     * Compiles LESS code into CSS code
     * @param string $less Less code
     * @return string CSS code
     * @throws dmInvalidLessException
     */
    abstract public function compile($less, array $importDirs = array());
}

