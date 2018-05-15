import {Nymph, Entity} from 'nymph-client';

export class Rendition extends Entity {
  // === Constructor ===

  constructor (id) {
    super(id);
    this.data.enabled = true;
    this.data.acOther = 1;
  }
}

// === Static Properties ===

// The name of the server class
Rendition.class = '\\uMailPHP\\Entities\\Rendition';

Nymph.setEntityClass(Rendition.class, Rendition);

export default Rendition;
