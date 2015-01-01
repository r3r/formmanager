<?php
/**
 * Created by PhpStorm.
 * User: RiteshReddy
 * Date: 12/29/14
 * Time: 10:15 PM
 */

namespace app\includes;

use Exception;

class Router
{
    public static $additionalRoutes = NULL;

    public function match()
    {
        $request_uri = explode("/", $_SERVER['REQUEST_URI']);
        $script = explode("/", $_SERVER['SCRIPT_NAME']);
        array_pop($script); #remove filename from script path
        for ($i = 0; $i < count($script); $i++) { #remove the script base path from request_uri
            array_shift($request_uri);
        }
        if (end($request_uri) == "") { #handle trailing slash
            array_pop($request_uri);
        }

        if (count($request_uri) < 3) {
            throw new Exception("No route available: Missing Component/Controller/Method in route");
        }

        $component = $request_uri[0];
        $controller = $request_uri[1];
        $method = $request_uri[2];
        array_shift($request_uri);
        array_shift($request_uri);
        array_shift($request_uri); #remove route from request_uri leaving only parameters

        $params = $_REQUEST;
        foreach ($request_uri as $param) {
            if (strpos($param, "?") !== FALSE) { #if ? is found, then stop extracting parameters
                break;
            }
            if (($pos = strpos($param, "=")) === FALSE) {
                $params[] = $param;
            } else {
                $params[substr($param, 0, $pos)] = substr($param, $pos + 1);
            }
        }

        $classPath = WEB_ROOT . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "src" . DIRECTORY_SEPARATOR . "components" . DIRECTORY_SEPARATOR . $component . DIRECTORY_SEPARATOR . $controller . ".php";
        if (file_exists($classPath)) {
            require_once($classPath);
            $fully_qualified_name = "src\\components\\" . $component . "\\" . $controller;
            if (class_exists($fully_qualified_name)) {
                if (method_exists($fully_qualified_name, $method)) {
                    return call_user_func_array(array($fully_qualified_name, $method), array($params, $_FILES, $_COOKIE, $_SERVER));
                } else {
                    return JsonIO::emit("Method Doesn't Exist " . $method);
                }
            } else {
                return JsonIO::emit("Class Doesn't Exist " . $controller);
            }
        } else {
            return JsonIO::emit("File Doesn't Exist " . $classPath);
        }
    }


    public function addRoute($route, $controller, $method)
    {

    }

    public function addRoutesFromFile($filename)
    {

    }

} 