<?php
/**
 * Created by PhpStorm.
 * User: RiteshReddy
 * Date: 12/29/14
 * Time: 7:22 PM
 */

require_once("../vendor/autoload.php"); #set-up autoloader
define('WEB_ROOT', getcwd());
use app\includes\Router;

$router = new Router();
$router->match();