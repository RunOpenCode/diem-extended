<?php

require_once 'lessphp/lessc.class.php';

/**
 * @author TheCelavi
 */
class dmLessAdapterPHP extends dmLessAdapter
{

    /**
     * Compiles LESS code into CSS code using <em>lessphp</em>, http://leafo.net/lessphp
     * 
     * @param string $less Less code
     * @return string CSS code
     * @throws dmInvalidLessException
     */
    public function compile($less, array $importDirs = array())
    {
        $lessCompiler = new lessc();
        $lessCompiler->setImportDir($importDirs);
        try {
            return $lessCompiler->compile($less);
        } catch (Exception $e) {
            throw new dmInvalidLessException($e->getMessage());
        }
    }

}

