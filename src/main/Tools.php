<?php

namespace WebArch\BitrixNeverInclude;

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ModuleManager;
use ReflectionClass;
use ReflectionException;

class Tools
{
    /**
     * Подключить все установленные в системе модули
     * @throws LoaderException
     */
    public function includeAllInstalledModules()
    {
        foreach (ModuleManager::getInstalledModules() as $module) {
            if (BitrixNeverInclude::isExcludedModule($module['ID'])) {
                continue;
            }
            Loader::includeModule($module['ID']);
        }
    }

    /**
     * Вернуть все маппинги загрузки классов
     *
     * @throws ReflectionException
     * @return array
     */
    public function getAutoLoadClasses()
    {
        $staticProperties = (new ReflectionClass(Loader::class))->getStaticProperties();

        if (isset($staticProperties['autoLoadClasses'])) {
            return $staticProperties['autoLoadClasses'];
        } elseif (isset($staticProperties['arAutoLoadClasses'])) {
            return $staticProperties['arAutoLoadClasses'];
        }

        return [];
    }

    /**
     * Вернуть индекс модуля по имени класса
     *
     * @param array $autoLoadClasses
     *
     * @return array
     */
    public function getModuleByClassNameMapping(array $autoLoadClasses)
    {
        $map = [];

        foreach ($autoLoadClasses as $className => $data) {

            /**
             * Нас не интересует модуль `main`, т.к. всегда подключён.
             * Также не интересуют классы не из глобального namespace
             */
            if (
                !isset($data['module'])
                || 'main' === $data['module']
                || strpos($className, '\\') !== false
            ) {
                continue;
            }

            $map[$className] = $data['module'];
        }

        return $map;
    }
}
