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
function is_loggedin($username, $token){
    $sql = "SELECT user FROM user_sessions where user = :username AND accesskey = :token";
    try {
        $db = Db::instance();
        return $db->query($sql, array('username' => $username, 'token' => $token))->fetch();
    } catch(PDOException $e) {
        $app->response->setStatus(401);
        //error_log($e->getMessage(), 3, '/var/tmp/phperror.log'); //Write error log
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
}

//testing
$app->get('/test/:data', function($data) use($app) {
    $app->response->setStatus(200);
    echo "Welcome to Slim 3.0 based API". $data;
}); 

//is logged in
$app->get('/isloggedin/:username/:token', function($username, $token) use($app) {
    $user = is_loggedin($username, $token);
    $response = array();
    
    if(isset($user->user)){
        $app->response->setStatus(200);
        $response['user'] = $user->user;
        $response['success'] = true;
    }else{
        $app->response->setStatus(204);
        $response['user'] = "no user";
        $response['success'] = false;
    }

    echo json_encode($response);
});

//signout
$app->get('/signout/:username/:token', function($username,$token) use($app) {
    $sql = "DELETE FROM user_sessions where user = :username AND accesskey = :token";
    try {
        $db = Db::instance();
        $db->query($sql, array('username' => $username, 'token' => $token));
        
        $response = array("signout" => true);
        echo json_encode($response);
    } catch(PDOException $e) {
        //error_log($e->getMessage(), 3, '/var/tmp/phperror.log'); //Write error log
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});

//login
$app->post('/signin', function() use($app) {
    $app->response->setStatus(200);
    
    // Get request object
    $request = $app->request();

    //POST variable
    $username = $request->post('username');
    $password = $request->post('password');
    
    $sql = "SELECT username FROM user where username = :username AND password = :password";
    try {
        $response = array();
        
        $db = Db::instance();
        $user = $db->query($sql,array('username' => $username, 'password' => $password))->fetch();
        
        
        if(isset($user->username)){
            $key = md5(microtime().rand());
            $sql = "INSERT INTO user_sessions (user, accesskey) VALUES (:username,:key)";
            $db->query($sql, array('username' => $username, 'key' => $key));
            $db = null;
            $app->response->setStatus(200);
            $response['user']       = $user->username;
            $response['token']      = $key;
            $response['success']    = true;
        }else{
            $app->response->setStatus(200);
            $response['user'] = "no user";
            $response['success'] = false;
        }
        
        echo json_encode($response);
    } catch(PDOException $e) {
        //error_log($e->getMessage(), 3, '/var/tmp/phperror.log'); //Write error log
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});

///add station
$app->post('/station/:username/:token', function($username, $token) use($app) {
    $user = is_loggedin($username, $token);
    if(!isset($user->user)){
        $app->response->setStatus(401);
        echo '{"error":{"text":"FAILED_AUTH"}}';
        
        return false;
    }
    
    $app->response->setStatus(200);
    
    // Get request object
    $request    = $app->request();

    //POST variable
    $address    = $request->post('address');
    $level      = $request->post('level');
    $status     = $request->post('status') == "true";
    $occupied   = $request->post('occupied') == "true";
    $id         = $request->post('id');
    
    $db = Db::instance();
    if($id == "0"){
        $sql = "INSERT INTO stations (address, status, level, occupied) VALUES (:address, :status, :level, :occupied);";
    
        $id = $db->query($sql,array('address' => $address, 'status' => $status, 'level' => $level, 'occupied' => $occupied))->id();
    }else{
        $sql = "UPDATE stations SET address = :address, status = :status, level = :level, occupied = :occupied WHERE id = :id";
    
        $db->query($sql,array('address' => $address, 'status' => $status, 'level' => $level, 'occupied' => $occupied, 'id' => $id));
    }
    try {
        
        echo json_encode(array("id" => $id));
    } catch(PDOException $e) {
        //error_log($e->getMessage(), 3, '/var/tmp/phperror.log'); //Write error log
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});

//get stations
$app->get('/stations/:username/:token', function($username, $token) use($app) {
    $user = is_loggedin($username, $token);
    if(!isset($user->user)){
        $app->response->setStatus(401);
        echo '{"error":{"text":"FAILED_AUTH"}}';
        
        return false;
    }
    $app->response->setStatus(200);
    
    $sql = "SELECT * FROM stations";
    try {
        $db = Db::instance();
        $response = $db->query($sql,array())->all();
        
        echo json_encode($response);
    } catch(PDOException $e) {
        //error_log($e->getMessage(), 3, '/var/tmp/phperror.log'); //Write error log
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});

//get stations conditionally
$app->get('/selectedstations/:username/:token/:leve1/:level2/:level3/:active/:occupied', function($username, $token, $level1, $level2, $level3, $active, $occupied) use($app) {
    $user = is_loggedin($username, $token);
    if(!isset($user->user)){
        $app->response->setStatus(401);
        echo '{"error":{"text":"FAILED_AUTH"}}';
        
        return false;
    }
    $app->response->setStatus(200);
    
    $in = "";
    
    if($level1 == "1"){
        $in .= "'Level 1',";
    }
    if($level2 == "1"){
        $in .= "'Level 2',";
    }
    if($level3 == "1"){
        $in .= "'Level 3',";
    }
    $in = substr($in, 0, -1);
    
    if($active == "1"){
        $active = "";
    }else{
        $active = " AND status = 1";
    }
    if($occupied == "1"){
        $occupied = "";
    }else{
        $occupied = " AND occupied = 0";
    }
    
    $sql = "SELECT * FROM stations WHERE level IN($in)" . $active . $occupied;
    try {
        $db = Db::instance();
        $response = $db->query($sql,array())->all();
        
        echo json_encode($response);
    } catch(PDOException $e) {
        //error_log($e->getMessage(), 3, '/var/tmp/phperror.log'); //Write error log
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});

//get station
$app->get('/station/:username/:token/:id', function($username, $token, $id) use($app) {
    $user = is_loggedin($username, $token);
    if(!isset($user->user)){
        $app->response->setStatus(401);
        echo '{"error":{"text":"FAILED_AUTH"}}';
        
        return false;
    }
    $app->response->setStatus(200);
    
    $sql = "SELECT * FROM stations WHERE id = :id";
    try {
        $db = Db::instance();
        $response = $db->query($sql,array("id" => $id))->fetch();
        
        echo json_encode($response);
    } catch(PDOException $e) {
        //error_log($e->getMessage(), 3, '/var/tmp/phperror.log'); //Write error log
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});

//delete station
$app->post('/deletestation/:username/:token', function($username, $token) use($app) {
    $user = is_loggedin($username, $token);
    if(!isset($user->user)){
        $app->response->setStatus(401);
        echo '{"error":{"text":"FAILED_AUTH"}}';
        
        return false;
    }
    
    $app->response->setStatus(200);
    
    // Get request object
    $request    = $app->request();

    //POST variable
    $id    = $request->post('id');
    
    $sql = "DELETE FROM stations where id = :id";
    try {
        $db = Db::instance();
        $db->query($sql,array('id' => $id));
        echo json_encode(array("deleted" => true));
    } catch(PDOException $e) {
        //error_log($e->getMessage(), 3, '/var/tmp/phperror.log'); //Write error log
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});

//update stations' location
$app->post('/locations/:username/:token', function($username, $token) use($app) {
    $user = is_loggedin($username, $token);
    if(!isset($user->user)){
        $app->response->setStatus(401);
        echo '{"error":{"text":"FAILED_AUTH"}}';
        
        return false;
    }
    
    $app->response->setStatus(200);
    
    // Get request object
    $request    = $app->request();

    //POST variable
    $stations    = $request->post('stations');
    $db = Db::instance();
    
    try {
        if(!empty($stations)){
            foreach($stations as $id => $location){
                if($location != "" && $location != "{}"){
                    $sql = "UPDATE stations SET location = :location WHERE id = :id";

                    $db->query($sql,array('location' => $location, 'id' => $id));
                }
            }
            echo json_encode(array("updated" => true));
        }else{
            echo json_encode(array("stations" => "empty"));
        }
    } catch(PDOException $e) {
        //error_log($e->getMessage(), 3, '/var/tmp/phperror.log'); //Write error log
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
});
 
$app->run();