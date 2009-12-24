jQueryToolkit documentation

#- Creating a new plugin:
	$.toolkit('namespace.pluginName',{..protoype..});
creating your plugin with toolkit like describe above you know may instanciate
your plugin by doing:
	$('selector').pluginName({options});
access any plugin method that don't start with an _ doing
  $('selector').pluginName('methodName'[,arg1,arg2...,argn]);
if the method name start with 'get' then the method is considered as a getter
so the method returned values are return as an Array instead of a jQuery object.
any element on wich the plugin is applied will be ensured to have a className
namespace-pluginName applied on them if not already applied in the original markup.

#- reading options directly from class element attribute.
jQueryToolkit plugins will most of the time allow you to set some options
directly inside the class attribute of the element.
For example let's imagine we have a plugin named "resizer" that set image width
and height attribute to 3 predefined size. It will have  a size option that can
take small|normal|big as values and a zoomHover options that can take values
true|false
if in our original markup we have this:
				<img src="myimage.png" class="tk-resizer" />
we will instanciate the plugin like this
				$('.tk-resizer').resizer({size:'small',zoomHover:true})

If we've previously declared a property '_classNameOptions' in our plugin
prototype just like this:
$.toolkit('tk.resizer',{
				_classNameOptions:{
								size:'small|normal|big',
								zoomHover:'|zoom', //-- starting our expression with a '|' make it optional
				},
				... rest of the plugin prototype ...
});

now we can achieve the same as before doing this:
				<img src="myimage.png" class="tk-resizer-small-zoom" />
				$('.tk-resizer').resizer()
(this suppose our plugin to understand that zoom as a value for zoomHover has to
be considered as true)