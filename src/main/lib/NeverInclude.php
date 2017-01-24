<?php


namespace WebArch\NeverInclude;

use RuntimeException;

class NeverInclude
{
    public function __construct()
    {
        if (!spl_autoload_register([$this, 'autoloadModule'])) {
            throw new RuntimeException('Error register autoloader ' . __CLASS__);
        }
    }

    private function autoloadModule($class)
    {
        /*
         * Кейсы:
         *
         */


        
    }
}
