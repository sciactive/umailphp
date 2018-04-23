import {Nymph, Entity} from 'nymph-client';

export class Rendition extends Entity {
  // === Constructor ===

  constructor (id) {
    super(id);
    this.data.enabled = true;
    this.data.ac_other = 1;
  }

  // === Instance Methods ===

  isReady (...args) {
    return this.serverCall('ready', args);
  }
}

// === Static Properties ===

Rendition.etype = 'umailphp_rendition';
// The name of the server class
Rendition.class = 'uMailPHP\\Entities\\Rendition';


Nymph.setEntityClass(Rendition.class, Rendition);

export default Rendition;
