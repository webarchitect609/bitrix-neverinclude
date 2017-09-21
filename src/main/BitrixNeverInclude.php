<?php

namespace WebArch\BitrixNeverInclude;

use Bitrix\Main\Loader;
use RuntimeException;
use WebArch\BitrixCache\BitrixCache;

class BitrixNeverInclude
{
    /**
     * Тег для кеша маппинга глобальных классов из старых модулей
     */
    const CACHE_TAG = 'BitrixNeverInclude';

    /**
     * @var string[]
     */
    protected static $excludedNamespaces = [
        //С этим модулем пакет заведомо несовместим
        'Sprint\Migration',
    ];

    /**
     * Установить список игнорируемых namespace
     *
     * @param array $namespaces
     */
    public static function addExcluded(array $namespaces)
    {
        foreach ($namespaces as $namespace) {
            if (trim($namespace) != '') {
                self::$excludedNamespaces[] = trim($namespace);
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
     * @return array
     */
    public static function getClassMapping()
    {
        $closure = function () {
            $tools = new Tools();
            $tools->includeAllInstalledModules();

            return $tools->getModuleByClassNameMapping($tools->getAutoLoadClasses());
        };

        return (new BitrixCache())
            ->withTime(86400)
            ->withTag(self::CACHE_TAG)
            ->resultOf($closure);
    }

    /**
     * @param string $class
     */
    protected function autoloadModule($class)
    {
        if ($this->isExcluded($class)) {
            return;
        }

        $moduleName = $this->recognizeOldModule($class);

        if (!$moduleName) {
            $moduleName = $this->recognizeNewModule($class);
        }

        if (!$moduleName) {
            return;
        }

        if (Loader::includeModule($moduleName)){
            Loader::autoLoad($class);
        }
    }

    /**
     * Определение модуля для старых классов из глобальной области
     *
     * @param string $class
     *
     * @return string Пустая строка, если не удалось определить имя модуля
     */
    protected function recognizeOldModule($class)
    {
        if (strpos($class, '\\') !== false) {
            return '';
        }

        return $this->checkClassMapping($class);
    }

    /**
     * @param string $class
     *
     * @return string
     */
    private function checkClassMapping($class)
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
     * Производит проверку, что класс относится к списку исключённых из автолоадинга
     *
     * @param $class
     *
     * @return bool
     */
    private function isExcluded($class)
    {
        foreach (self::$excludedNamespaces as $namespace) {
            if (strpos($class, $namespace) === 0) {
                return true;
            }
        }

        return false;
    }
}
