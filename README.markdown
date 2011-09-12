JavascriptRouting Extension
===========================
JavascriptRouting Extension provides easy way to access your routes inside Javacript files. It's cool because putting your routes inside js files as static strings sucks.

Installation (clone)
--------------------
    cd /path/to/your/project
    git clone git://github.com/RafalFilipek/JavascriptRoutingExtension.git vendor/rafal/src/Rafal/JavascriptRoutingExtension

Installation (submodule)
------------------------
    cd /path/to/your/project
    git submodule add git://github.com/RafalFilipek/JavascriptRoutingExtension.git vendor/rafal/src/Rafal/JavascriptRoutingExtension

Registering
-----------
    $app['autoloader']->registerNamespace('Rafal', __DIR__.'/vendor/rafal/src');
    $app->register(new Rafal\JavascriptRoutingExtension\JavascriptRoutingExtension());

Options
-------
* ```jsrouting.path``` - Required. Path where ```router.js``` ( name by default ) will be created.
* ```jsrouting.file_neme``` - Output file name. Default ```router.js```.
* ```jsrouting.refresh``` - If true routes will be generated on each request. Default ```true```. 

Example
-------
Lets say you have:

    $app->register(new Rafal\JavascriptRoutingExtension\JavascriptRoutingExtension(), array(
        'jsrouting.path'        => __DIR__.'/public/js',
        'jsrouting.file_name'   => 'router.js',
        'jsrouting.refresh'     => $app['debug']
    ));

    $app->get('/{name}/extensions/are/{what}', function() use($app) {
        // your cool code
    })->bind('opinion')->value('name', 'Rafals')->assert('what', '(cool|lame)');

Now you have to remeber to include ```router.js``` file in your layout.

From now you can use ```Router``` class inside your JavaScript files. For example:

    Router::get('opinion', {name:'Johns', what:'lame'}) # => /Johns/extensions/are/lame
    Router::get('opinion', {what:'cool'}) # => /Rafals/extensions/are/cool
    Router::get('opinion', {what:'bazinga'}) # ERROR !

As you can se Router class will respect all requirements, and default values defined for your routes.

License
-------
JavascriptRouting Extension is licensed under the MIT license.