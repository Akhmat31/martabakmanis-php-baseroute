<?php
require("./martabakmanis/index.php");

/**
 * HTTPSERVER-REBUILD
 * PHP + PACKAGIST
 */

use MyLib\Routing\Router;
use MyLib\Routing\Url;
use MyLib\Http\Request;
use MyLib\Http\Response;

$router = new Router($GLOBALS['framework_logger']);

$router(Url::path('/'), function  (Request $request) {
    return Response::view("./view/main.php", "UTF-8");
})->get();

$router(Url::path("/first-installation"), function (Request $request) {
    return Response::view("./view/main/first_installation.php","UTF-8");
})->get();

$router(Url::path("/basic-usages"), function (Request $request) {
    return Response::view("./view/main/basic_usages.php","UTF-8");
})->get();
$router(Url::path("/tes"), function (Request $request) {

    $dat = array (
        "main" => "m",
        "j" => "jii"
    );
    echo $dat;
})->get();
$router->run();