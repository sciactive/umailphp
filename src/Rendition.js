// Uses AMD or browser globals.
(function (factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as a module.
        define('NymphRendition', ['NymphEntity'], factory);
    } else {
        // Browser globals
        factory(Entity);
    }
}(function(Entity){
	Rendition = function(id){
		this.constructor.call(this, id);
		this.data.enabled = true;
		this.data.ac_other = 1;
	};
	Rendition.prototype = new Entity();

	var thisClass = {
		// === The Name of the Class ===
		class: '\\µMailPHP\\Rendition',

		// === Class Variables ===
		etype: "umailphp_rendition",

		// === Class Methods ===

		isReady: function(){
			return this.serverCall('ready', arguments);
		}
	};
	for (var p in thisClass) {
		if (thisClass.hasOwnProperty(p)) {
			Rendition.prototype[p] = thisClass[p];
		}
	}

	return Rendition;
}));
