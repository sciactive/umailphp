import Nymph from "Nymph";
import Entity from "NymphEntity";

export default class Rendition extends Entity {

  // === Static Properties ===

  static etype = "umailphp_rendition";
  // The name of the server class
  static class = "\\uMailPHP\\Rendition";

  // === Constructor ===

  constructor(id) {
    super(id);
    this.data.enabled = true;
    this.data.ac_other = 1;
  }

  // === Instance Methods ===

  isReady(...args) {
    return this.serverCall('ready', args);
  }
}

Nymph.setEntityClass(Rendition.class, Rendition);
export {Rendition};
