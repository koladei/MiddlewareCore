<?php

namespace Drupal\middleware_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller routines for test_api routes.
 */
class JSEngineController extends ControllerBase {

    /**
     * Callback for `my-api/get.json` API method.
     */
    public function response(Request $request) {
        // Get the requested path
        $response = [];

        $paths = [];
        $params = explode(':', $request->attributes->get('middlware_core_path'));
        $counter = count($params);
        
        while($counter > 0) {
            $path = array_slice($params, 0, $counter);
            $paths[] = implode('/', $path);
            $counter--;
        };

        // Retrieved the executable code located at the path.
        $driver = middleware_core__get_driver('sql');
        if($driver){        
            $urlInString = implode("','", $paths);
            $matches = $driver->getItems('code_base', 'URL,Name,Logic,Status', "URL IN('{$urlInString}') and Status eq 'Active'", '', [
                '$orderBy' => 'URL desc'
            ]);          

            if(count($matches) > 0) {
                $c = $matches[0];

                // $response['status'] = 'failure';   
                // $response['path'] = $x;
            } else {                
                $response['status'] = 'failure';         
                $response['message'] = 'The controller for the specified path could not be found.';
            }
        } 
        // If it was not found throw an error.
        else {
            $response['status'] = 'failure';
            $response['messagee'] = 'An internal error occured while trying to load the SQL driver.';
        }

        return new JsonResponse($response);
    }

    /**
     * Display the markup.
     *
     * @return array
     */
    public function content(Request $request) {

        return [
            '#type' => 'markup',
            '#markup' => $this->t('Hello, World!')
        ];
    }

}
