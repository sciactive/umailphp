import {Nymph, Entity} from 'nymph-client';

export class Template extends Entity {
  // === Constructor ===

  constructor (id) {
    super(id);
    this.data.enabled = true;
    this.data.replacements = [];
    this.data.acOther = 1;
  }

  // === Instance Methods ===

  defaultContent (...args) {
    return this.serverCall('defaultContent', args);
  }
}

// === Static Properties ===

// The name of the server class
Template.class = '\\uMailPHP\\Entities\\Template';

Nymph.setEntityClass(Template.class, Template);

export default Template;
