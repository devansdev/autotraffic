<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require 'libs/vendor/autoload.php';
require 'libs/vendor/slim/db.php';

$app = new \Slim\Slim();
$app->response->headers->set('Access-Control-Allow-Origin', '*');

//testing
$app->get('/test/:data', function($data) use($app) {
    $app->response->setStatus(200);
    echo "Welcome to Slim 3.0 based API". $data;
}); 

$app->run();