// Uses AMD, CommonJS, or browser globals.
(function(root, factory){
  'use strict';
  if (typeof define === 'function' && define.amd) {
    // AMD. Register as a module.
    define('NymphTemplate', ['NymphEntity'], factory);
  } else if (typeof exports === 'object' && typeof module !== 'undefined') {
      // CommonJS
      module.exports = factory(require('Entity'));
  } else {
    // Browser globals
    factory(root.Entity, root);
  }
}(typeof window !== "undefined" ? window : this, function(Entity, context){
  'use strict';
  if (typeof context === "undefined") {
    context = {};
  }
  context.Template = function(id){
    this.constructor.call(this, id);
    this.data.enabled = true;
    this.data.replacements = [];
    this.data.ac_other = 1;
  };
  context.Template.prototype = new Entity();

  var thisClass = {
    // === The Name of the Server Class ===
    class: '\\uMailPHP\\Template',

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
      context.Template.prototype[p] = thisClass[p];
    }
  }

  return context.Template;
}));
