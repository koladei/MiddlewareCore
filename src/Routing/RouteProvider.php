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

    // This route leads to the JS REST controller
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

    // This is route leads to the object schema form
    $routes['middleware_core.systems'] = new Route(      
      '/_systems',

      [
        '_title' => 'System List',
        '_form' => '\Drupal\middleware_core\Form\SystemConfigListForm'
      ],
  
      [
        '_permission'=> 'access content'
      ]      
    );

    // This is route leads to the object schema form
    $routes['middleware_core.system'] = new Route(      
      '/_systems/{system_id}',

      [
        '_title' => 'Manage System',
        '_form' => '\Drupal\middleware_core\Form\SystemConfigForm'
      ],
  
      [
        'system_id' => '^[\w\d]*$',
        '_permission'=> 'access content'
      ]      
    );

    // This is route leads to the object schema form
    $routes['middleware_core.object_schema'] = new Route(      
      '/_systems/{system_id}/objects/{object_id}',

      [
        '_title' => 'Manage Object Schema',
        '_form' => '\Drupal\middleware_core\Form\ObjectSchemaForm'
      ],
  
      [
        'system_id' => '^[\w\d]*$',
        'object_id' => '^[\w\d]*$',
        '_permission'=> 'access content'
      ]      
    );

    // // This is route leads to the object schema form
    // $routes['middleware_core.field_systems'] = new Route(      
    //   '/_systems/{system_id}/objects/{object_id}/fields/{fields_id}',
    //   [
    //     '_title' => 'Example form',
    //     '_form' => '\Drupal\middleware_core\Form\FieldSchemaForm'
    //   ],
  
    //   [
    //     'system_id' => '^[\w\d]*$',
    //     'object_id' => '^[\w\d]*$',
    //     'field_id' => '^[\w\d]*$',
    //     '_permission'=> 'access content'
    //   ]      
    // );

    // $routes['middleware_core.rest']->setRequirement('middlware_core_path','^[^\?]*$');

    \Drupal::service('router.builder')->setRebuildNeeded();
    return $routes;
  }  
}