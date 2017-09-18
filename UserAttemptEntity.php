<?php

 /**
 * Project entity class extending the Entity class
 */
class UserAttemptEntity extends Entity {

  /**
   * Change the default URI from default/id to project/id
   */
  protected function defaultUri() {
    return array('path' => 'admin/config/administration/mware/events/' . $this->identifier());
  }

}
