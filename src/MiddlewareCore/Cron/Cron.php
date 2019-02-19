<?php

namespace Drupal\middleware_core\MiddlewareCore\Cron;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\RfcLogLevel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Drupal\middleware_core\MiddlewareCore\V8\V8Wrapper;
use Drupal\middleware_core\MiddlewareCore\Utility\Functions;

/**
 * Controller routines for test_api routes.
 */
class Cron {
    static $config = null;

    private static function __static(){
        $config = \Drupal::service('config.factory')->getEditable('middleware_core.v8cron');
    }

    public static function run($id = NULL, $return = false, $bypass_schedule = NULL){
        
        set_time_limit (0);    
        $tokenOption = [
            CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2
            , CURLOPT_PROTOCOLS => CURLPROTO_HTTPS
            , CURLOPT_SSL_VERIFYPEER => FALSE
            , CURLOPT_SSL_VERIFYHOST => 0
            , CURLOPT_FOLLOWLOCATION => TRUE
            , CURLOPT_HTTPPROXYTUNNEL => TRUE
            , CURLOPT_VERBOSE => TRUE
        ];
        $scheduledTasks = [];
        $sql = Functions::GetMiddlewareDriver('sql');
        $now = new \DateTime('now');

        // If no specific executable id is specified, get all executables due for execution.
        if(is_null($id)) {
            global $base_url;
            try {
                $scheduledTasks = $sql->getItems('code_base', 'Id,Name,NextRun', '(NextRun le $now$ or NextRun eq $null$) and (Frequency ne $null$ and EnableScheduling eq $true$)', '', ['$all' => '1', '$logQuery' => '1']);
                
                foreach($scheduledTasks as $scheduledTask){
                    $nextRun = (!is_null($scheduledTask->NextRun)? \DateTime::createFromFormat('Y-m-d\TH:i:s', $scheduledTask->NextRun):$now);
                    if ($nextRun <= $now || $bypass_schedule == TRUE){
                        mware_http_request("{$base_url}/middlware-bridge/v8/{$scheduledTask->Id}", ['options' => $tokenOption, 'callback' => function($event) use($scheduledTask){
                            
                        }]);
                    } else  {
                        Functions::Log("OUT OF SCHEDULE RUN: {$scheduledTask->Name}", "Waiting till {$scheduledTask->NextRun}");
                    }
                }
            } catch(\Exception $e){
                Functions::Log('BACKGROUND EXCEPTION: ', "Message {$e->getMessage()}");
            }
            return;
        }

        // For each of them execute their logic against the emails
        $scheduledTask = $sql->getItemById('code_base', $id, 'Name,Logic,NextRun,Frequency', '', ['$all' => '1']);
        try{
            $nextRun = \DateTime::createFromFormat('Y-m-d\TH:i:s', $scheduledTask->NextRun);
            
            try{
                // Set the next run date.
                $update = new \stdClass();
                $update->NextRun = $now->add(new \DateInterval("PT{$scheduledTask->Frequency}M"))->format('Y-m-d\TH:i:s');
                $update->Id = $scheduledTask->Id;
                $sql->updateItem('code_base', $scheduledTask->Id, $update);

                $driverLoader   = Functions::GetMiddlewareDriver;
                $logger         = Functions::Log;

                $v8 = new MiddlewareV8Wrapper($scheduledTask, $driverLoader, $logger);

                $response = $v8->execute([]);
            } catch (\Exception $ex){
                Functions::Log("SCHEDULE FAILURE: {$scheduledTask->Name}", "Waiting till {$scheduledTask->NextRun}: ".$ex->getMessage());
            }       

            // If this is a onetime task, delete it.
            if($scheduledTask->Name == '__ONETIME__'){
                Functions::Log("DELETE SCHEDULE TASK: {$scheduledTask->Name}", 'DEBUG DELETE');
            }
            
            if($return){
                return $response;
            }
        } catch(\Exception $exp){
            Functions::Log('v8 TASK PROCESSOR', "There was a problem processing the scheduled task.  Error: {$exp->getMessage()}");
            echo "There was a problem processing the scheduled task.  Error: {$exp->getMessage()}";
            if($return){
                return "There was a problem processing the scheduled task.  Error: {$exp->getMessage()}";
            }
        }
    }
}