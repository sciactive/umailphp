import {Nymph, Entity} from 'nymph-client';

export class Template extends Entity {
  // === Constructor ===

  constructor (id) {
    super(id);
    this.data.enabled = true;
    this.data.replacements = [];
    this.data.ac_other = 1;
  }

  // === Instance Methods ===

  defaultContent (...args) {
    return this.serverCall('defaultContent', args);
  }

  isReady (...args) {
    return this.serverCall('ready', args);
  }
}

// === Static Properties ===

Template.etype = 'umailphp_template';
// The name of the server class
Template.class = 'uMailPHP\\Entities\\Template';

Nymph.setEntityClass(Template.class, Template);

export default Template;
