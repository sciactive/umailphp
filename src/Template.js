// Uses AMD or browser globals.
(function (factory) {
  if (typeof define === 'function' && define.amd) {
    // AMD. Register as a module.
    define('NymphTemplate', ['NymphEntity'], factory);
  } else {
    // Browser globals
    factory(Entity);
  }
}(function(Entity){
  Template = function(id){
    this.constructor.call(this, id);
    this.data.enabled = true;
    this.data.replacements = [];
    this.data.ac_other = 1;
  };
  Template.prototype = new Entity();

  var thisClass = {
    // === The Name of the Server Class ===
    class: '\\µMailPHP\\Template',

    // === Class Variables ===
    etype: "umailphp_template",

    // === Class Methods ===

    defaultContent: function(){
      return this.serverCall('defaultContent', arguments);
    },
    isReady: function(){
      return this.serverCall('ready', arguments);
    }
  };
  for (var p in thisClass) {
    if (thisClass.hasOwnProperty(p)) {
      Template.prototype[p] = thisClass[p];
    }
  }

  return Template;
}));
