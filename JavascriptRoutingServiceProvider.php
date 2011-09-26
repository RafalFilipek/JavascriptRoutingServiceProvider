<?php

namespace Rafal\JavascriptRoutingServiceProvider;

use Silex\Application;
use Silex\ServiceProviderInterface;

class JavascriptRoutingServiceProvider implements ServiceProviderInterface {
    
    public function register(Application $app)
    {
        $app->before(function() use($app){
            if (!isset($app['jsrouting.path'])) {
                throw new \Exception('Missing `jsrouting.path` option!');
            }
            $path = $app['jsrouting.path'];
            $file_name = isset($app['jsrouting.file_neme']) ? $app['jsrouting.path'] : 'router.js';
            $refresh = isset($app['jsrouting.refresh']) ? (bool)$app['jsrouting.refresh'] : true;

            if ($refresh === true) {
                if (!file_exists($path . '/' . $file_name)) {
                    touch($path . '/' . $file_name);
                }
                $routes = array();
                foreach ($app['routes']->all() as $name => $route) {
                    $routes[$name] = array(
                        'pattern'       => $route->getPattern(),
                        'requirements'  => $route->getRequirements(),
                        'defaults'      => $route->getDefaults(),
                        'variables'     => $route->compile()->getVariables()
                    );
                    unset($routes[$name]['requirements']['_method']);
                }
                $app['twig.loader']->addPath(__DIR__.'/Resources');
                $content = $app['twig']->render('templates/routing.twig', array(
                    'data'  => json_encode($routes)
                ));
                file_put_contents($path . '/' . $file_name, $content);
            }
        });

    }
}