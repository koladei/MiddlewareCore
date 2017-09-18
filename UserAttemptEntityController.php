<?php

/**
 * Extending the EntityAPIController for the Project entity.
 */
class UserAttemptEntityController extends EntityAPIController {

    public function save($entity, DatabaseTransaction $transaction = NULL) {
        
        $entity->modified = (new DateTime())->getTimestamp();
        return parent::save($entity, $transaction);
    }

    public function create(array $values = array()) {
        // Add is_new property if it is not set.
        $values += ['created' => (new DateTime())->getTimestamp(), 'modified' => (new DateTime())->getTimestamp()];
        if (isset($this->entityInfo['entity class']) && $class = $this->entityInfo['entity class']) {
            return new $class($values, $this->entityType);
        }
        return (object) $values;
    }

    public function buildContent($entity, $view_mode = 'full', $langcode = NULL, $content = array()) {

        $build = parent::buildContent($entity, $view_mode, $langcode, $content);

        // Our additions to the $build render array
        $build['action'] = array(
            '#type' => 'markup',
            '#markup' => check_plain($entity->action),
            '#prefix' => '<div class="form-group"> <strong>Action performed</strong>',
            '#suffix' => '</div>',
        );

        $date = new DateTime();
        $date->setTimestamp($entity->created);
        $build['created'] = array(
            '#type' => 'markup',
            '#markup' => $date->format('Y-m-d H:i:s'),
            '#prefix' => '<div class="form-group"> <strong>Created</strong>',
            '#suffix' => '</div>',
        );

        if ($entity->modified) {
            $date->setTimestamp($entity->modified);
        }
        $build['modified'] = array(
            '#type' => 'markup',
            '#markup' => $date->format('Y-m-d H:i:s'),
            '#prefix' => '<div class="form-group"> <strong>Modified</strong>',
            '#suffix' => '</div>',
        );

        return $build;
    }

}
