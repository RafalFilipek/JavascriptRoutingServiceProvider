JavascriptRouting Service Provider
==================================
JavascriptRouting Service Provider provides easy way to access your routes inside Javacript files. It's cool because putting your routes inside js files as static strings sucks.
    
Installation (composer)
------------------------
    require: "rafal/javascriptroutingserviceprovider": "1.0.*@dev"

Options
-------
* ```jsrouting.path``` - Required. Path where ```router.js``` ( name by default ) will be created.
* ```jsrouting.file_neme``` - Output file name. Default ```router.js```.
* ```jsrouting.refresh``` - If true routes will be generated on each request. Default ```true```. 
* ```jsrouting.basepath``` - If true request basepath will be inserted before each generated route. Default ```false```. 

Example
-------
Lets say you have:

    $app['jsrouting'] = function () {
        return new Rafal\JavascriptRoutingServiceProvider\JavascriptRoutingServiceProvider();
    };
    $app->register($app['jsrouting'], array(
        'jsrouting.path'        => __DIR__.'/public/js',
        'jsrouting.file_name'   => 'router.js',
        'jsrouting.refresh'     => $app['debug'],
        'jsrouting.basepath'    => true,
    ));

    $app->get('/{name}/extensions/are/{what}', function() use($app) {
        // your cool code
    })->bind('opinion')->value('name', 'Rafals')->assert('what', '(cool|lame)');

Now you have to remeber to include ```router.js``` file in your layout.

From now you can use ```Router``` class inside your JavaScript files. For example:

    Router::get('opinion', {name:'Johns', what:'lame'}) # => /project/web/Johns/extensions/are/lame
    Router::get('opinion', {what:'cool'}) # => /project/web/Rafals/extensions/are/cool
    Router::get('opinion', {what:'cool'}, false) # => /Rafals/extensions/are/cool
    Router::get('opinion', {what:'bazinga'}) # ERROR !

As you can se Router class will respect all requirements, and default values defined for your routes.
You can overwrite the default request basepath inserting option by the third parameter.

License
-------
JavascriptRouting Service Provider is licensed under the MIT license.