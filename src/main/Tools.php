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

    /**
     * Вернуть все маппинги загрузки классов
     *
     * @return array
     */
    public function getAutoLoadClasses()
    {
        $staticProperties = (new ReflectionClass(Loader::class))->getStaticProperties();

        if (!isset($staticProperties['arAutoLoadClasses'])) {
            return [];
        }

        return $staticProperties['arAutoLoadClasses'];
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
