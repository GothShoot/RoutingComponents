<?php

namespace Alzundaz\Router\Services;

use Alzundaz\NitroPHP\Services\ConfigHandler;
use Module\ProfilerModule\Services\Profiler;
use Alzundaz\NitroPHP\Services\CacheHandler;

class Router
{
    private $start;

    public function __construct()
    {
        $this->start = microtime(true);
    }

    private function loadRoute():array
    {
        $ConfigHandler = ConfigHandler::getInstance();
        $cacheHandler = CacheHandler::getInstance();
        $type = $cacheHandler->cacheExists('App/route');
        if( !$type || $ConfigHandler->getAppConf()['dev'] ){
            $modules = $ConfigHandler->getModule();
            $routes = [];
            foreach($modules as $module){
                if( $module['enabled'] && file_exists(ROOT_DIR.'/Module/'.$module['name'].'/Config/Routes/') ){
                    $routes = array_merge($routes, $ConfigHandler->loadJsonConfig(ROOT_DIR.'/Module/'.$module['name'].'/Config/Routes/'));
                }
            }
            $type = $cacheHandler->setCache('App/route', $route);
        }
        return $cacheHandler->getCache('App/route', $type);
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