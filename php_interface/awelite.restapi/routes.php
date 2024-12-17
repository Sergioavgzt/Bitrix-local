<?php
/**
 * Данный файл подключается при установленном модуле и запросе к API
 */
use Awelite\RestApi\Route\Route;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
//include_once (__DIR__ . '/Controllers/ExampleController.php'); // образец подключения файла контроллера
$route = new Route();

// Роут получения сведения попользователя.
/*
return $route->get('/user/{user}', \Awelite\RestApi\Controllers\IndexController::class)
    ->middleware('user')
    ->regexp('(\d+)');
*/


// Образцы добавления новых маршрутов. Более подробно смотрите документацию модуля
//$route->get('/fr', [\Custom\RestApi\Controllers\ExampleController::class, 'stub'])->name('Новый пример метод stub класса ExampleController');
//$route->get('/fr', \Custom\RestApi\Controllers\ExampleController::class)->name('Новый пример метод __invoke класса ExampleController');