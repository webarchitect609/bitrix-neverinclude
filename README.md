Автозагрузчик модулей Битрикс, который поможет вам навсегда забыть про вызовы CModule::IncludeModule и Loader::includeModule

Как использовать:

1 Установите через composer: 

`composer require webarchitect609/bitrix-neverinclude`

2 В init.php после подключения `vendor/autoload.php` добавьте вызов: 

`\WebArch\BitrixNeverInclude\BitrixNeverInclude::registerModuleAutoload();`

3 И наслаждайтесь более интересными заботами, чем подключение модулей то здесь, то там! :)
