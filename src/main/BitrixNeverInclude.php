<?php

namespace WebArch\BitrixNeverInclude;

use Bitrix\Main\Loader;
use RuntimeException;

class BitrixNeverInclude
{

    /**
     * @var BitrixNeverInclude
     */
    protected static $instance;

    /**
     * @return array
     */
    protected function getModulePrefixMap()
    {
        //TODO Наполнить карту
        return [

            'iblock' => [
                'CIBlock',
                '_CIB',
                'CAllIBlock',
                'CEventIblock',
                'CRatingsComponentsIBlock',
            ],

            'catalog' => [
                'CCatalog',
                'CExtra',
                'CPrice',
                'CGlobalCond',
            ],

            'sale' => [
                'CSale',
                'CBaseSale',
                'IBXSale',
                'IPayment',
                'IShipment',
                'CAdminSale',
            ],

            'form' => [
                'CForm',
                'CAllForm',
            ],

            'highloadblock' => [
                'CUserTypeHlblock',
                'CIBlockPropertyDirectory',
            ],

            'idea' => [
                'CIdeaManagment',
            ],

        ];
    }

    protected function __construct()
    {

    }

    /**
     * @return BitrixNeverInclude
     */
    protected function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Зарегистрировать автолоадер модулей Битрикс
     * и  **НАВСЕГДА ЗАБЫТЬ** про CModule::IncludeModule и Loader::includeModule
     *
     * @throws RuntimeException
     */
    public static function registerModuleAutoload()
    {
        if (!spl_autoload_register([static::getInstance(), 'autoloadModule'])) {
            throw new RuntimeException('Error register autoloader ' . __CLASS__);
        }
    }

    /**
     * @param string $class
     */
    protected function autoloadModule($class)
    {
        $moduleName = $this->recognizeOldModule($class);

        if (!$moduleName) {
            $moduleName = $this->recognizeNewModule($class);
        }

        if (!$moduleName) {
            return;
        }

        Loader::includeModule($moduleName);
        Loader::autoLoad($class);

    }

    /**
     * Определение имени модуля по префиксу класса
     *
     * @param string $class
     *
     * @return string Пустая строка, если не удалось определить имя модуля
     */
    protected function checkPrefix($class)
    {

        foreach (static::getModulePrefixMap() as $moduleName => $prefixList) {
            foreach ($prefixList as $prefix) {
                if (strpos($class, $prefix) === 0) {
                    return (string)$moduleName;
                }
            }
        }

        return '';
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
        /*
         * Если содержит обратный слеш или не начинается с 'C' или 'I',
         * то это не старый класс из глобальной области
         */
        $first = substr($class, 0, 1);
        if (
            ($first !== 'C' && $first !== 'I')
            || strpos($class, '\\') !== false
        ) {
            return '';
        }

        return $this->checkPrefix($class);
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
            return sprintf(
                '%s.%s',
                strtolower($chunks[0]),
                strtolower($chunks[1])
            );
        }

        return '';
    }
}
