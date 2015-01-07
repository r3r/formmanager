<?php
/**
 * Created by PhpStorm.
 * User: RiteshReddy
 * Date: 12/29/14
 * Time: 10:15 PM
 */

namespace app\includes;

class Router
{
    public static $additionalRoutes = NULL;

    public function match()
    {


        $request_method = $_SERVER['REQUEST_METHOD'];
        $request_uri = explode("/", $_SERVER['REDIRECT_URL']); //Get request url with leading slash removed.
        $script = explode("/", $_SERVER['SCRIPT_NAME']);

        array_pop($script); #remove filename from script path
        for ($i = 0; $i < count($script); $i++) { #remove the index.php script base path from request_uri
            array_shift($request_uri);
        }

        if (end($request_uri) == "") { #handle trailing slash or parameters
            array_pop($request_uri);
        }


        if (count($request_uri) < 2) {
            return JsonIO::emitError("No route available: Missing Component/Controller/Method in route", json_encode($_SERVER['REQUEST_URI']), 404);
        }

        $component = $request_uri[0];
        $controller = $request_uri[1];

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

        switch ($request_method) {
            case 'GET' :
                $method = 'read';
                break;
            case 'POST' :
                $method = 'create';
                break;
            case 'PUT' :
                $method = 'update';
                break;
            case 'DELETE' :
                $method = 'delete';
                break;
            default:
                $method = 'read';
                break;
        }

        $classPath = WEB_ROOT . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "src" . DIRECTORY_SEPARATOR . "components" . DIRECTORY_SEPARATOR . $component . DIRECTORY_SEPARATOR . $controller . ".php";
        if (file_exists($classPath)) {
            require_once($classPath);
            $fully_qualified_name = "src\\components\\" . $component . "\\" . $controller;
            if (class_exists($fully_qualified_name)) {
                if (method_exists($fully_qualified_name, $method)) {
                    return call_user_func_array(array($fully_qualified_name, $method), array($params, $_FILES, $_COOKIE, $_SERVER));
                } else {
                    return JsonIO::emitError("Method Doesn't Exist " . $method);
                }
            } else {
                return JsonIO::emitError("Class Doesn't Exist " . $controller);
            }
        } else {
            return JsonIO::emitError("File Doesn't Exist " . $classPath);
        }
    }


    public function addRoute($route, $controller, $method)
    {

    }

    public function addRoutesFromFile($filename)
    {

    }

} 