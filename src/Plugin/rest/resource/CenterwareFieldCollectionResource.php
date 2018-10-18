<?php

namespace Drupal\middleware_core\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\Core\Extension;


/**
 * Provides a Demo Resource
 *
 * @RestResource(
 *   id = "centerware_fields",
 *   label = @Translation("Centerware Item"),
 *   uri_paths = {
 *     "canonical" = "/mw/{system}/_api/web/lists/{entity_name}/fields"
 *   }
 * )
 */
class CenterwareFieldCollectionResource extends ResourceBase {
  /**
   * Responds to entity GET requests.
   * @return \Drupal\rest\ResourceResponse
   */
  public function get($system, $entity_name, $item_id) {
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
        
        $otherOptions = array_merge([], $params);
        $x = $driver->getItemById(strtolower($entity_name), $item_id, $select, $expand, $otherOptions);
        
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

    return new ResourceResponse($response);
  }
}