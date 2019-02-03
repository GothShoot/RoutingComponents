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
            $type = $cacheHandler->setCache('App/route', $routes);
        }
        return $cacheHandler->getCache('App/route', $type);
    }

    public function getController()
    {
        $start = microtime(true);
        $routes = $this->loadRoute();
		$url = str_replace('/NitroPHP/Public', '', $_SERVER['REQUEST_URI']);
        $route = null;
        $args = [];

        foreach($routes as $route){
            $route['path'] = str_replace('/', '\/', $route['path']);
			if (preg_match( '#^'.$route['path'].'$#', $url, $args ) ) {
                break;
            }
        }

        if($route){
            array_shift($args);
            var_dump($args);
            $class = 'Module\\'.$route['module'].'\Controller\\'.$route['controller']; $methode = $route['methode'];
            $end = microtime(true);
            $controllerstart = microtime(true);
            $controller = new $class();
            $controller->$methode(...$args);
            $controllerend = microtime(true);
        } else {
            $end = microtime(true);
            header('Location: /error', true, 404);
        }
        Profiler::getInstance()->setTime(['name'=>'Router', 'start'=>$start, 'end'=>$end]);
        Profiler::getInstance()->setTime(['name'=>$class.'::'.$methode, 'start'=>$controllerstart, 'end'=>$controllerend]);
    }
}