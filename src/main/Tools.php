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

    public function getClassNameMap(array $autoLoadClasses)
    {
        $map = [];

        foreach ($autoLoadClasses as $className => $data) {
            if (!isset($data['module'])) {
                continue;
            }

            $map[$data['module']][] = $className;
        }

        return $map;
    }
}
