<?php

namespace Drupal\middleware_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Drupal\middleware_core\MiddlewareCore\V8\V8Wrapper;
use Drupal\Core\Logger\RfcLogLevel;

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
        
        // Create a permutation for likely controller matches based on the path.
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

                try {
                    $wrapper = new V8Wrapper($c, function(){
                        return middleware_core__get_driver(...func_get_args());
                    }, function($title, $message){
                        \Drupal::logger("{$title}")->log(RfcLogLevel::NOTICE, $message, []);
                    });

                    $headers = get_object_vars($request->headers);
                    
                    // Try converting to JSON
                    $body = NULL;

                    // Try converting to JSON	
                    if(!isset($headers['content-type']) || isset($headers['content-type']) && in_array('application/json', explode(';', $headers['content-type']))){
                        $body = json_decode($request->getContent());					
                    }
                    else { 
                        $body = $request->getContent();
                    }

                    $return = $wrapper->{$_SERVER['REQUEST_METHOD']}([
                        'BODY' => $body
                        , 'PATH' => $params
                        , 'HEADERS' => $headers
                        , 'BASE_URL' => $request->getBaseUrl()
                        , 'CURRENT_USER' => \Drupal::currentUser()
                        , 'CLEANER' => function(&$response) use(&$p_arg){
                            $p_arg = array_merge($p_arg, $response);						
                        }
                    ], function(&$response) use(&$p_arg){
                        $p_arg = !is_array($p_arg)?[]:$p_arg;
                        $p_arg = array_merge($p_arg, $response);						
                    });

                    // return the response
                    if(isset($return['custom_format'])){
                        $responseObj = Response::create('', 200, $return['headers']);

                        // Decode the supplied data before echoing it.
                        if(!isset($return['custom_format']['encoded']) || (isset($return['custom_format']['encoded']) && $return['custom_format']['encoded'] == true))
                        {
                            $responseObj->setContent(base64_decode($return['d']));
                        } else {
                            $responseObj->setContent($return['d']);
                        }

                        return $responseObj;
                    }

                    $response = $return;

                } catch(\Exception $exp){
                    $response['status'] = 'failure';
                    $response['message'] = $exp->getMessage();
                }
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

        return new JsonResponse($response, 200, ['content-type' => 'application/json']);
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
