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
            if (!isset($app["jsrouting.path"])) {
                throw new \Exception("Missing `jsrouting.path` option!");
            }
            $path = $app["jsrouting.path"];
            $file_name = isset($app["jsrouting.file_name"]) ? $app["jsrouting.file_name"] : "router.js";
            $refresh = isset($app["jsrouting.refresh"]) ? (bool)$app["jsrouting.refresh"] : true;
            $add_basepath = isset($app["jsrouting.basepath"]) ? (bool)$app["jsrouting.basepath"] : true;
            $skip = array();
            if (isset($app["jsrouting.skip"])) {
                if (is_array($app["jsrouting.skip"])) {
                    $skip = $app["jsrouting.skip"];
                } else {
                    $skip = array($app["jsrouting.skip"]);
                }
            }

            if ($refresh === true) {
                if (!file_exists($path . "/" . $file_name)) {
                    touch($path . "/" . $file_name);
                }
                $routes = array();
                foreach ($app["routes"]->all() as $name => $route) {
                    $pattern = $route->getPattern();
                    $continue = false;
                    foreach ($skip as $p) {
                        if (preg_match($p, $pattern)) {
                            $continue = true;
                            break;
                        }
                    }
                    if ($continue) {
                        continue;
                    }
                    $routes[$name] = array(
                        "pattern"       => $pattern,
                        "requirements"  => $route->getRequirements(),
                        "defaults"      => $route->getDefaults(),
                        "variables"     => $route->compile()->getVariables()
                    );
                    unset($routes[$name]["requirements"]["_method"], $routes[$name]["defaults"]["_controller"]);
                }
                
                file_put_contents($path . "/" . $file_name, $app['jsrouting']->jsContent(json_encode($routes), json_encode($add_basepath), $request->getBasePath()));
            }
        });
    }
    
    private function jsContent($data, $add_basepath, $basePath) {
        return <<<JS
var Router = {
    routes: {$data},
    basepath: "{$basePath}",
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
                param,
                val;
            for (param in variables) {
                param = variables[param];
                val = params[param] != undefined ? params[param] : defaults[param];
                if (val == undefined) {
                    throw "Missing '"+param+"' parameter for route '"+name+"'!";
                }
                if (requirements[param] && !new RegExp(requirements[param]).test(val)) {
                    throw "Parameter '"+param+"' for route '"+name+"' must pass '"+requirements[param]+"' test!";
                }
                result = result.replace("{"+param+"}", val);
            }
            return (add_basepath ? this.basepath : "") + result;
        } else {
            throw "Undefined route '"+name+"'!";
        }
    }
}

if (typeof module !== undefined && module.exports) {
    /* CommonJS (Node, browserify, etc.) */
    module.exports = Router;
} else {
    /* Add to the global object. */
    this.Router = Router;
}
JS;
    }
}
