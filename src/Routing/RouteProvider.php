<?php
/**
 * @file
 * Contains \Drupal\example\Routing\ExampleRoutes.
 */

namespace Drupal\middleware_core\Routing;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Defines dynamic routes.
 */
class RouteProvider {

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $routes = [];

    
    // try {
    //   $driver = middleware_core__get_driver('sql');
    //   if($driver){        
    //     $x = $driver->getItems('code_base', 'URL,Name,Logic', '', '');
    //     foreach($x as $code_path){
    //       $routes["middleware_core.{$code_path->Name}"] = new Route(
    //         // Path to attach this route to:
    //         "rest/{$code_path->URL}",
    //         // Route defaults:
    //         [
    //           '_controller' => '\Drupal\middleware_core\Controller\JSEngineController::response',
    //           '_title' => $code_path->Name
    //         ],
      
    //         // Route requirements:
    //         [
    //           '_permission'  => 'access content',
    //         ]
    //       );
    //     }
    //   } else {
    //     throw new \Exception("Unable to find a matching driver for '{$system}'");
    //   }
    // } catch (\Exception $exp) {
    //   $response ['status'] = 'failure';
    //   $response ['message'] = $exp->getMessage();
    //   $response['d'] = [];
    // }

    // Declares a single route under the name 'example.content'.
    // Returns an array of Route objects. 
    $routes['middleware_core.rest'] = new Route(
      // Path to attach this route to:
      '/rest/{middlware_core_path}',

      // Route defaults:
      [
        '_controller' => '\Drupal\middleware_core\Controller\JSEngineController::response',
        '_title' => 'Middleware REST API'
      ],

      // Route requirements:
      [
        'middlware_core_path' => '^[^\?]*$',
        '_permission'  => 'access content'
      ]
    );

    $routes['middleware_core.rest']->setRequirement('middlware_core_path','^[^\?]*$');

    \Drupal::service('router.builder')->setRebuildNeeded();
    return $routes;
  }  
}