<?php

namespace Drupal\middleware_core\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * Provides a Demo Resource
 *
 * @RestResource(
 *   id = "centerware_entities",
 *   label = @Translation("Centerware Entity Information"),
 *   uri_paths = {
 *     "canonical" = "/mw/{system}/_api/web/lists/{entity}"
 *   }
 * )
 */
class CenterwareEntityResource extends ResourceBase {
  /**
   * Responds to entity GET requests.
   * @return \Drupal\rest\ResourceResponse
   */
  public function get($system, $entity) {
    $response = func_get_args();
    return new ResourceResponse($response);
  }

  /**
   * Responds to entity POST requests.
   * @return \Drupal\rest\ResourceResponse
   */
  public function post($system, $entity) {
    $response = func_get_args();
    return new ResourceResponse($response);
  }
}