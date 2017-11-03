import Nymph from "Nymph";
import Entity from "NymphEntity";

export default class Template extends Entity {

  // === Static Properties ===

  static etype = "umailphp_template";
  // The name of the server class
  static class = "uMailPHP\\Entities\\Template";

  // === Constructor ===

  constructor(id) {
    super(id);
    this.data.enabled = true;
    this.data.replacements = [];
    this.data.ac_other = 1;
  }

  // === Instance Methods ===

  defaultContent(...args) {
    return this.serverCall('defaultContent', args);
  }

  isReady(...args) {
    return this.serverCall('ready', args);
  }
}

Nymph.setEntityClass(Template.class, Template);
export {Template};
