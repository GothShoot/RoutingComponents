<?php

namespace Alzundaz\Router\Services;

use Alzundaz\NitroPHP\Services\ConfigLoader;
use Module\ProfilerModule\Services\Profiler;

class Router
{
    private $start;

    public function __construct()
    {
        $this->start = microtime(true);
    }

    private function loadRoute(bool $force = null):array
    {
        $configLoader = ConfigLoader::getInstance();
        if( !file_exists(ROOT_DIR.'/Var/Cache/App/route.json') || $force || $configLoader->getAppConf()['dev'] ){
            $modules = $configLoader->getModule();
            $routes = [];
            foreach($modules as $module){
                if( $module['enabled'] && file_exists(ROOT_DIR.'/Module/'.$module['name'].'/Config/Routes/') ){
                    $routes = array_merge($routes, $configLoader->loadJsonConfig(ROOT_DIR.'/Module/'.$module['name'].'/Config/Routes/'));
                }
            }
            file_put_contents(ROOT_DIR.'/Var/Cache/App/route.json', json_encode($routes));
        }
        return json_decode(file_get_contents(ROOT_DIR.'/Var/Cache/App/route.json'), true);
    }

    private function parse(string $url):?array
    {
        $routes = $this->loadRoute();
        foreach($routes as $route){
            if($route['path'] == $url) {
                return $route;
            }
        }
        return null;
    }

    public function getController()
    {
        $start = microtime(true);
        $route = $this->parse($_GET['url']);
        if($route){
            $class = 'Module\\'.$route['module'].'\Controller\\'.$route['controller']; $methode = $route['methode'];
            $controller = new $class();
            $end = microtime(true);
            $controllerstart = microtime(true);
            $controller->$methode();
            $controllerend = microtime(true);
        } else {
            $end = microtime(true);
            header('Location: /error', true, 404);
        }
        Profiler::getInstance()->setTime(['name'=>'Router', 'start'=>$start, 'end'=>$end]);
        Profiler::getInstance()->setTime(['name'=>$class.'::'.$methode, 'start'=>$controllerstart, 'end'=>$controllerend]);
    }
}