<?php

namespace Drupal\middleware_core\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\Core\Extension;
use Drupal;


/**
 * Provides a Demo Resource
 *
 * @RestResource(
 *   id = "centerware_items",
 *   label = @Translation("Centerware Item Collection"),
 *   uri_paths = {
 *     "canonical" = "/mw/{system}/_api/web/lists/{entity_name}/items",
 *     "https://www.drupal.org/link-relations/create" = "/mw/{system}/_api/web/lists/{entity_name}/items"
 *   }
 * )
 */
class CenterwareItemCollectionResource extends ResourceBase {

  /**
   * Responds to entity GET requests.
   * @return \Drupal\rest\ResourceResponse
   */
  public function get($system, $entity_name) {
    $response = ['status' => 'success'];
    
    // Note when this request started processing
    $startTime = new \DateTime();
    $response['Stats'] =['StartTime' => $startTime->format('Y-m-d\TH:i:s')];

    try {
      $driver = middleware_core__get_driver($system);
      if($driver){
        $params = Drupal::request()->query->all();
        $select = isset($params['$select'])?$params['$select']:'Id';
        $expand = isset($params['$expand'])?$params['$expand']:'';
        $filter = isset($params['$filter'])?$params['$filter']:'';
        
        $otherOptions = array_merge([], $params);
        $x = $driver->getItems(strtolower($entity_name), $select, $filter, $expand, $otherOptions);
        
        $response['d'] = json_decode(json_encode($x), true);
      } else {
        throw new \Exception("Unable to find a matching driver for '{$system}'");
      }
    } catch (\Exception $exp) {
      $response ['status'] = 'failure';
      $response ['message'] = $exp->getMessage();
      $response['d'] = [];
    }

    // Note when we finished.
    $endTime = new \DateTime();
    $response['Stats']['CompleteTime'] = $endTime->format('Y-m-d\TH:i:s');
    $response['Stats']['Duration'] = $endTime->diff($startTime)->format('%s');
    
    return (new ResourceResponse($response));
  }

  /**
   * Responds to entity POST requests.
   * @return \Drupal\rest\ResourceResponse
   */
  public function post($system, $entity_name) {
    $response = ['status' => 'success'];
    
    // Note when this request started processing
    $startTime = new \DateTime();
    $response['Stats'] =['StartTime' => $startTime->format('Y-m-d\TH:i:s')];

    try {
      $driver = middleware_core__get_driver($system);
      if($driver){

        // Get the query params and the request body.
        $requestBody = json_decode(Drupal::request()->getContent());
        $params = Drupal::request()->query->all();
        $select = isset($params['$select'])?$params['$select']:'Id';
        $expand = isset($params['$expand'])?$params['$expand']:'';
        $filter = isset($params['$filter'])?$params['$filter']:'';
        
        $otherOptions = array_merge([], $params);
        $x = $driver->createItem(strtolower($entity_name), $requestBody);
        $response['d'] = json_decode(json_encode($x), true);
      } else {
        throw new \Exception("Unable to find a matching driver for '{$system}'");
      }
    } catch (\Exception $exp) {
      $response ['status'] = 'failure';
      $response ['message'] = $exp->getMessage();
      $response['d'] = [];
    }

    // Note when we finished.
    $endTime = new \DateTime();
    $response['Stats']['CompleteTime'] = $endTime->format('Y-m-d\TH:i:s');
    $response['Stats']['Duration'] = $endTime->diff($startTime)->format('%s');
    
    return (new ResourceResponse($response));
  }
}