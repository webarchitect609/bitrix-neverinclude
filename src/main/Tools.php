<?php

namespace WebArch\BitrixNeverInclude;

use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use ReflectionClass;

class Tools
{
    /**
     * Подключить все установленные в системе модули
     */
    public function includeAllInstalledModules()
    {
        foreach (ModuleManager::getInstalledModules() as $module) {
            Loader::includeModule($module['ID']);
        }
    }

    public function getAllSystemClassNames()
    {
        $this->includeAllInstalledModules();

        $loaderClass = new ReflectionClass(Loader::class);

        $arAutoLoadClasses = $loaderClass->getStaticPropertyValue('arAutoLoadClasses');

        return $arAutoLoadClasses;
    }
}
