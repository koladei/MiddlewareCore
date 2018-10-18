<?php

namespace Drupal\middleware_core\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * Provides a Demo Resource
 *
 * @RestResource(
 *   id = "centerware_system",
 *   label = @Translation("Centerware System Information"),
 *   uri_paths = {
 *     "canonical" = "/mw/{system}/_api/web"
 *   }
 * )
 */
class CenterwareSystemResource extends ResourceBase {
  /**
   * Responds to entity GET requests.
   * @return \Drupal\rest\ResourceResponse
   */
  public function get($system) {
    $response = func_get_args();
    return new ResourceResponse($response);
  }

  /**
   * Responds to entity POST requests.
   * @return \Drupal\rest\ResourceResponse
   */
  public function post($system) {
    $response = func_get_args();
    return new ResourceResponse($response);
  }
}