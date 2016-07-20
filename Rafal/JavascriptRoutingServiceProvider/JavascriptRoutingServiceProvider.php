<?php

namespace Rafal\JavascriptRoutingServiceProvider;

use Silex\Application;
use Pimple\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Pimple\Container;
use Silex\Api\BootableProviderInterface;

class JavascriptRoutingServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{
    public function register(Container $pimple)
    {
    }

    public function boot(Application $app)
    {
        $app->before(function(Request $request) use($app) {
            if (!isset($app['jsrouting.path'])) {
                throw new \Exception('Missing `jsrouting.path` option!');
            }
            $path = $app['jsrouting.path'];
            $fileName = isset($app['jsrouting.file_name']) ? $app['jsrouting.file_name'] : 'router.js';
            $refresh = isset($app['jsrouting.refresh']) ? (bool)$app['jsrouting.refresh'] : true;
            $addBasepath = isset($app['jsrouting.basepath']) ? (bool)$app['jsrouting.basepath'] : true;

            if ($refresh === true) {
                if (!file_exists($path . '/' . $fileName)) {
                    touch($path . '/' . $fileName);
                }
                $routes = array();

                foreach ($app['routes']->all() as $name => $route) {
                    $routes[$name] = array(
                        'pattern'       => $route->getPath(),
                        'requirements'  => $route->getRequirements(),
                        'defaults'      => $route->getDefaults(),
                        'variables'     => $route->compile()->getVariables()
                    );
                    unset($routes[$name]['requirements']['_method']);
                }

                file_put_contents($path . '/' . $fileName, $this->jsContent(json_encode($routes), json_encode($addBasepath), $request->getBasePath()));
            }
        });
    }

    private function jsContent($data, $add_basepath, $basePath) {
        return <<<JS
var Router = {
    routes: {$data},
    basepath: '{$basePath}',
    add_basepath: {$add_basepath},

    get: function(name, params, add_basepath) {
        if (typeof add_basepath == "undefined") {
            add_basepath = this.add_basepath;
        }
        if (this.routes[name]) {
            params = params == undefined ? {} : params;
            var route = this.routes[name],
                requirements = route.requirements,
                defaults = route.defaults,
                variables = route.variables,
                result = route.pattern,
                val;
            for (param in variables) {
                param = variables[param];
                val = params[param] != undefined ? params[param] : defaults[param];
                if (val == undefined) {
                    throw 'Missing "'+param+'" parameter for route "'+name+'"!';
                }
                if (requirements[param] && !new RegExp(requirements[param]).test(val)) {
                    throw 'Parameter "'+param+'" for route "'+name+'" must pass "'+requirements[param]+'" test!';
                }
                result = result.replace('{'+param+'}', val);
            }
            return (add_basepath ? this.basepath : '') + result;
        } else {
            throw 'Undefined route "'+name+'"!';
        }
    }
}
JS;
    }
}