<?php

namespace WebArch\BitrixNeverInclude;

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use ReflectionException;
use RuntimeException;
use WebArch\BitrixCache\BitrixCache;

class BitrixNeverInclude
{
    /**
     * Тег для кеша маппинга глобальных классов из старых модулей
     */
    const CACHE_TAG = 'BitrixNeverInclude';

    /**
     * @var string[] Список несовместимых модулей
     */
    protected static $excludedModules = [];

    /**
     * @var array Индекс исключаемых модулей для максимально быстрого поиска
     */
    protected static $excludedModulesIndex = null;

    /**
     * Добавить исключение модулей из обработки
     *
     * @param array $modules
     */
    public static function addExcludedModules(array $modules)
    {
        $excludedModulesIndex = null;
        foreach ($modules as $module) {
            if (trim($module) != '') {
                self::$excludedModules[] = trim($module);
            }
        }
    }

    /**
     * Зарегистрировать автолоадер модулей Битрикс
     * и  **НАВСЕГДА ЗАБЫТЬ** про CModule::IncludeModule и Loader::includeModule
     *
     * @throws RuntimeException
     */
    public static function registerModuleAutoload()
    {
        if (!spl_autoload_register([(new static()), 'autoloadModule'])) {
            throw new RuntimeException('Error register autoloader ' . __CLASS__);
        }
    }

    /**
     * Возвращает маппинг имени модуля по имени класса. Имя класса всегда в нижнем регистре, т.к. сам Битрикс к нему
     * преобразует.
     *
     * Рекомендуется назначить вызов этого метода в конце сброса всего кеша, чтобы при следующем хите он уже был создан.
     *
     * @throws ReflectionException
     * @return array
     */
    public static function getClassMapping(): array
    {
        $closure = function () {
            $tools = new Tools();
            $tools->includeAllInstalledModules();

            return $tools->getModuleByClassNameMapping($tools->getAutoLoadClasses());
        };

        return (new BitrixCache())->setTime(86400)
                                  ->setTag(self::CACHE_TAG)
                                  ->callback($closure);
    }

    /**
     * Подключает модуль, определяя его по имени класса.
     *
     * @param string $class
     *
     * @throws LoaderException
     * @throws ReflectionException
     */
    protected function autoloadModule(string $class)
    {
        $moduleName = $this->recognizeOldModule($class);

        if (!$moduleName) {
            $moduleName = $this->recognizeNewModule($class);
        }

        if (!$moduleName) {
            return;
        }

        if (!self::isExcludedModule($moduleName) && Loader::includeModule($moduleName)) {
            Loader::autoLoad($class);
        }
    }

    /**
     * Определение модуля для старых классов из глобальной области.
     *
     * @param string $class
     *
     * @throws ReflectionException
     * @return string Пустая строка, если не удалось определить имя модуля
     */
    protected function recognizeOldModule(string $class): string
    {
        if (strpos($class, '\\') !== false) {
            return '';
        }

        return $this->checkClassMapping($class);
    }

    /**
     * @param string $class
     *
     * @throws ReflectionException
     * @return string
     */
    private function checkClassMapping(string $class): string
    {
        $lowClass = strtolower(trim($class));

        $classMapping = static::getClassMapping();

        if (!isset($classMapping[$lowClass])) {
            return '';
        }

        return (string)$classMapping[$lowClass];
    }

    /**
     * Определение модуля по namespace класса
     *
     * @param string $class
     *
     * @return string Пустая строка, если не удалось определить имя модуля
     */
    protected function recognizeNewModule($class)
    {
        $chunks = explode('\\', $class);

        /*
         * Это стандартный битриксовый модуль
         */
        if ('Bitrix' === $chunks[0] && isset($chunks[1])) {
            return strtolower($chunks[1]);
        }

        /*
         * Иной кастомный модуль
         */
        if (isset($chunks[0], $chunks[1])) {
            return strtolower($chunks[0] . '.' . $chunks[1]);
        }

        return '';
    }

    /**
     * Проверяет, не является ли модуль исключённым
     *
     * @param string $moduleName
     *
     * @return bool
     */
    public static function isExcludedModule($moduleName)
    {
        if (is_null(self::$excludedModulesIndex)) {
            self::$excludedModulesIndex = array_flip(self::$excludedModules);
        }

        return isset(self::$excludedModulesIndex[$moduleName]);
    }
}
