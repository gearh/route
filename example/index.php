<?php
use Gearh\Route\Router;
use Gearh\Route\Rule;

require '../vendor/autoload.php';

$router = new Router;

$router->group(function($router){

    $router->add((new Rule)
        ->name('post_detail')
        ->from('post-:id(/page-:page)')
        ->to(function ($param){
            var_dump($param);
        }));

}, function ($next, $param){
    return $next($param);
});



$router->run('/post-12', 'POST', function ($handle, $param){
    $handle($param);
});