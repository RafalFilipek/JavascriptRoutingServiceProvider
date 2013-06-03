<?php

namespace Rafal\JavascriptRoutingServiceProvider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class JavascriptRoutingServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
    }
    
    public function boot(Application $app)
    {
        $app->before(function(Request $request) use($app) {
            if (!isset($app['jsrouting.path'])) {
                throw new \Exception('Missing `jsrouting.path` option!');
            }
            $path = $app['jsrouting.path'];
            $file_name = isset($app['jsrouting.file_neme']) ? $app['jsrouting.path'] : 'router.js';
            $refresh = isset($app['jsrouting.refresh']) ? (bool)$app['jsrouting.refresh'] : true;
            $add_basepath = isset($app['jsrouting.basepath']) ? (bool)$app['jsrouting.basepath'] : true;

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
                
                file_put_contents($path . '/' . $file_name, $this->jsContent(json_encode($routes), json_encode($add_basepath), $request->getBasePath()));
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