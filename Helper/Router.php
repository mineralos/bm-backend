<?php
namespace DragonMint\Helper;



class Router {
    private $request;
    public function __construct($request){
        $this->request = $request;
    }

    /*
     * Receive the URI, the controller on charge to handle that URI and an Action (function name).
     * If the URI matches with the get variable "action" the route is activated
     */
    public function get($route, $controller, $action){

        $routeParts=explode("/",$route);
        $routeAction=$routeParts[2];

        if(!is_null($routeAction)&&isset($_GET)&&isset($_GET["action"])&&$routeAction==$_GET["action"]) {
            if (file_exists(__DIR__."/../Controller/".$controller . 'Controller.php')) {
                require __DIR__."/../Controller/".$controller . 'Controller.php';
                $controllerClass = $controller."Controller";
                $controller = new $controllerClass();
                if (method_exists($controller,$action."Action")) {
                    $method=$action."Action";
                    $controller->$method();
                    exit(0); //Application is done, dirty exit but fast for the miner
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }
    }
}