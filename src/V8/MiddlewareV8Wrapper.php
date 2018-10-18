<?php

require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/functions/get_functions/middlewarefunctions.inc');

use phpDocumentor\Reflection\DocBlockFactory;

// Spreadsheet classes
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;

// Doc classes
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;

use JonnyW\PhantomJs\Client;
use JonnyW\PhantomJs\DependencyInjection\ServiceContainer;

class FriendlyException extends \Exception
{
}

class MiddlewareV8Wrapper //extends \V8Js
{
    private $service =  null;
    private $loader = null;

    public function __construct(\stdClass $service, callable $driverLoader = null, callable $logger = null)
    {
        // parent::__construct('_$', []);
        $this->service = $service;
        $this->v8 = $this;
        // $this->loader = $driverLoader;
        $this->loader = function () use ($driverLoader) {
            if (!is_null($driverLoader) && is_callable($driverLoader)) {
                return $driverLoader(...func_get_args());
            } else {
                return false;
            }
        };
        
        $this->logger = function () use ($logger) {
            if (!is_null($logger) && is_callable($logger)) {
                return $logger(...func_get_args());
            } else {
                return false;
            }
        };
    }

    public function __call($methodName, $overall_args)
    {

        // Try sending the SMS.
        $defaultOptions = [
            CURLOPT_HTTPHEADER => []
            , CURLOPT_PROTOCOLS => CURLPROTO_HTTPS
            , CURLOPT_SSL_VERIFYPEER => 0
            , CURLOPT_SSL_VERIFYHOST => 0
            , CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2
        ];

        $v8 = new \V8Js('_$', []);
        
        /**
         * Extends execution timeout
         * 
         * Extends the execution timeout of the currently executing script.
         * 
         * @param int $timeout
         * 
         * @return null
         */
        $v8->__extendTimeout = function(int $timeout) {            
            set_time_limit($timeout);
        };

        $v8->__base64Encode = function(){
            $args = func_get_args();
            return base64_encode(...$args);
        };

        $v8->__base64Decode = function(){
            $args = func_get_args();
            return base64_decode(...$args);
        };
        
        /**
         * Sends a synchronous HTTP POST request.
         * 
         * Sends a synchronous HTTP POST request to the remote server at the specified url.
         * 
         * @param object $a An object containing the parameters for the GET request.
         * The object should have the following structure: <br/>
         * {
         *      url: string (required), // The url to call.
         *      responseType: string (optional) (default: 'json'), // The data type to expect as response. Other options
         *      getCookies: boolean (optional) (default: false), // Whether to get cookies or not.
         *      setCookies: object (optional) (default: null), // An object representing the cookie to send to the remote server.
         *      headers: array (optional), // An array of header values to send to the remote server.
         *      connectionTimeout: int (optional),
         *      executionTimeout: int (optional)
         * }
         * 
         * @return object An object containing the result of the POST request.
         */
        $v8->post = function (\V8Object $a = null) use ($defaultOptions, $v8) {
            $log = $v8->log;
            $return = new \stdClass();
            $return->Type = 'exception';
            $return->Value = null;
            try {
                $a = \is_null($a)?(new \stdClass()): (object)$a;
                $respFormat = (property_exists($a, 'responseType')) ? $a->responseType: 'json';
                $dataType = (property_exists($a, 'dataType')) ? $a->dataType: 'json';
                $setCookies = (property_exists($a, 'setCookies')) ? $a->setCookies: null;
                $getCookies = (property_exists($a, 'getCookies')) ? $a->getCookies: false;
                $getHeaders = (property_exists($a, 'getHeaders')) ? $a->getHeaders: false;
                $followRedirection = (property_exists($a, 'followRedirection')) ? $a->followRedirection: false;
                $headers = (property_exists($a, 'headers'))?$a->headers:[];
                $connectionTimeout = (property_exists($a, 'connectionTimeout'))?$a->connectionTimeout:FALSE;
                $executionTimeout = (property_exists($a, 'executionTimeout'))?$a->executionTimeout:FALSE;

                $url = (property_exists($a, 'url'))?$a->url:null;
                if (is_null($url)) {
                    throw new \Exception('The post URL is missing');
                }

                // Prepare the form data
                $data = (property_exists($a, 'data'))?$a->data:new \stdClass();

                // Set the connection timeouts
                if($connectionTimeout != FALSE){
                    $defaultOptions[CURLOPT_CONNECTTIMEOUT] = $connectionTimeout;
                    set_time_limit($connectionTimeout);
                }
                
                if($executionTimeout != FALSE){
                    $defaultOptions[CURLOPT_TIMEOUT] = $executionTimeout;
                    set_time_limit($executionTimeout);
                }

                // Inform CURL that data should be sent as POST
                $defaultOptions[CURLOPT_POST] = TRUE;
                $defaultOptions[CURLOPT_RETURNTRANSFER] = 1;
                
                // format the sent data appropriately.
                if($dataType == 'form'){
                    $data = json_decode(json_encode($data), true);
                    $defaultOptions[CURLOPT_POSTFIELDS] = http_build_query($data);
                    if(count($headers) < 1) {
                        $headers['content-type'] = 'application/x-www-form-urlencoded';
                    }
                } else {
                    $defaultOptions[CURLOPT_POSTFIELDS] = json_encode($data);       
                    if(count($headers) < 1) {
                        $headers['content-type'] = 'application/json';
                    }       
                }

                // Prepare the request header
                foreach ($headers as $headerName => $header) {
                    $defaultOptions[CURLOPT_HTTPHEADER][] = "{$headerName}: {$header}";
                }

                // Set the cookies that are sent
                if($setCookies){
                    $setCookies = is_array($setCookies)? implode(';', $setCookies): ($setCookies);
                    $defaultOptions[CURLOPT_COOKIE] = is_string($setCookies)? ($setCookies):'';
                }                

                // Follow redirection
                if($followRedirection){
                    $defaultOptions[CURLOPT_FOLLOWLOCATION] = false;
                    $defaultOptions[CURLOPT_RETURNTRANSFER] = true;
                }

                // Try getting the cookies
                $cookies = [];
                $location = $url;
                $responseHeaders = [];
                
                $curlResponseHeaderCallback = function ($ch, $headerLine = '') use (&$cookies, &$responseHeaders, $getCookies, $log) { 
                    
                    if($getCookies){
                        $cookie = NULL;
                        if (preg_match('/^Set-Cookie:\s*([^;]*)/mi', $headerLine, $cookie) == 1){
                            $cookies[] = $cookie[1];
                        }
                    }

                    if(trim($headerLine) > 0){
                        list($name, $value) = explode(': ', $headerLine, 2);
                        
                        if(strlen(trim($name)) > 0){
                            $name = strtolower(trim($name));
                            if(isset($responseHeaders[$name])) {
                                if(!is_array($responseHeaders[$name])) {
                                    $responseHeaders[$name] = [$responseHeaders[$name]];
                                }

                                $responseHeaders[$name][] = trim($value);
                            }
                            else {
                                $responseHeaders[$name] = trim($value);
                            }
                        }
                    }
                    
                    return strlen($headerLine);
                };

                $defaultOptions[CURLOPT_HEADERFUNCTION] = $curlResponseHeaderCallback;
                
                // Send the request.
                $feed = mware_blocking_http_request($url, ['options' => $defaultOptions]);

                // Format the returned data
                if($respFormat != 'json'){
                    $return->Value = $feed->getContent();
                } else {
                    $return->Value = json_decode($feed->getContent());
                }

                $return->Type = 'success';
                $return->Cookies = $cookies;//count($cookies) > 0?$cookies[0]:[];
                $return->Location = $location;
                $return->Headers = $responseHeaders;
                return $return;
            } catch (\Exception $exp) {
                $return->Exception = $exp->getMessage(); 
                $return->StackTrace = $exp->getTraceAsString();                  
                $return->File = $exp->getFile();
            }
            return $return;
        };
        
        /**
         * Sends an asynchronous HTTP POST request.
         * 
         * Sends an asynchronous HTTP POST request to the remote server at the specified url.
         * 
         * @param object $a An object containing the parameters for the GET request.
         * The object should have the following structure: <br/>
         * {
         *      url: string (required), // The url to call.
         *      responseType: string (optional) (default: 'json'), // The data type to expect as response. Other options
         *      setCookies: object (optional) (default: null), // An object representing the cookie to send to the remote server.
         *      headers: array (optional), // An array of header values to send to the remote server.
         *      connectionTimeout: int (optional),
         *      executionTimeout: int (optional)
         * }
         * 
         * @return null
         */
        $v8->postAsync = function (\V8Object $a = null) use ($defaultOptions) {
            $return = new \stdClass();
            $return->Type = 'exception';
            $return->Value = null;
            try {
                $a = \is_null($a)?(new \stdClass()):$a;
                $respFormat = (property_exists($a, 'responseType')) ? $a->responseType: 'json';
                $dataType = (property_exists($a, 'dataType')) ? $a->dataType: 'json';
                $setCookies = (property_exists($a, 'setCookies')) ? $a->setCookies: null;
                $getCookies = (property_exists($a, 'getCookies')) ? $a->getCookies: false;
                $headers = (property_exists($a, 'headers'))?$a->headers:[];
                $connectionTimeout = (property_exists($a, 'connectionTimeout'))?$a->connectionTimeout:FALSE;
                $executionTimeout = (property_exists($a, 'executionTimeout'))?$a->executionTimeout:FALSE;

                $url = (property_exists($a, 'url'))?$a->url:null;

                // Prepare the form data
                $data = (property_exists($a, 'data'))?$a->data:new \stdClass();
                if (is_null($url)) {
                    throw new \Exception('The post URL is missing');
                }

                // Set the connection timeouts
                if($connectionTimeout != FALSE){
                    $defaultOptions[CURLOPT_CONNECTTIMEOUT] = $connectionTimeout;
                    set_time_limit($connectionTimeout);
                }
                
                if($executionTimeout != FALSE){
                    $defaultOptions[CURLOPT_TIMEOUT] = $executionTimeout;
                    set_time_limit($executionTimeout);
                }

                // Inform CURL that data should be sent as POST
                $defaultOptions[CURLOPT_POST] = TRUE;
                $defaultOptions[CURLOPT_RETURNTRANSFER] = 1;
                
                // format the sent data appropriately.
                if($dataType == 'form'){
                    $data = get_object_vars(((object) $data));
                    $defaultOptions[CURLOPT_POSTFIELDS] = drupal_http_build_query($data);
                } else{
                    $defaultOptions[CURLOPT_POSTFIELDS] = json_encode($data);                
                }

                // Prepare the request header
                foreach ($headers as $headerName => $header) {
                    $defaultOptions[CURLOPT_HTTPHEADER][] = "{$headerName}: {$header}";
                }

                // Set the cookies that are sent
                if($setCookies){
                    $setCookies = is_array($setCookies)? implode(';', $setCookies): ($setCookies);
                    $defaultOptions[CURLOPT_COOKIE] = is_string($setCookies)? ($setCookies):'';
                }

                // Try getting the cookies
                $cookies = [];
                if($getCookies){
                    $curlResponseHeaderCallback = function ($ch, $headerLine) use (&$cookies) { 
                        if (preg_match('/^Set-Cookie:\s*([^;]*)/mi', $headerLine, $cookie) == 1){
                            $cookies[] = $cookie;
                        }
                        return strlen($headerLine);
                    };

                    $defaultOptions[CURLOPT_HEADERFUNCTION] = $curlResponseHeaderCallback;
                }
                
                // Send the request.
                $feed = mware_http_request($url, ['options' => $defaultOptions]);

                $return->Value = NULL;
                $return->Type = 'success';
                $return->Cookies = count($cookies) > 0?$cookies[0]:[];
                return $return;
            } catch (\Exception $exp) {
                $return->Exception = $exp->getMessage(); 
                $return->StackTrace = $exp->getTraceAsString();                  
                $return->File = $exp->getFile();
            }
            
            return $return;
        };

        /**
         * Sends a synchronous HTTP GET request.
         * 
         * Sends a synchronous HTTP GET request to the remote server at the specified url.
         * 
         * @param object $a An object containing the parameters for the GET request.
         * The object should have the following structure: <br/>
         * {
         *      url: string (required), // The url to call.
         *      responseType: string (optional) (default: 'json'), // The data type to expect as response. Other options
         *      getCookies: boolean (optional) (default: false), // Whether to get cookies or not.
         *      setCookies: object (optional) (default: null), // An object representing the cookie to send to the remote server.
         *      headers: array (optional), // An array of header values to send to the remote server.
         *      connectionTimeout: int (optional),
         *      executionTimeout: int (optional)
         * }
         * 
         * @return object An object containing the result of the GET request.
         */
        $v8->get = function (\V8Object $a = null) use ($defaultOptions) {
            $return = new \stdClass();
            $return->Type = 'exception';
            $return->Value = null;
            try {
                $a = \is_null($a)?(new \stdClass()):(object)$a;
                $url = (property_exists($a, 'url')) ? $a->url:null;
                $respFormat = (property_exists($a, 'responseType')) ? $a->responseType: 'json';
                $setCookies = (property_exists($a, 'setCookies')) ? $a->setCookies: null;
                $getCookies = (property_exists($a, 'getCookies')) ? $a->getCookies: false;
                $getHeaders = (property_exists($a, 'getHeaders')) ? $a->getHeaders: false;
                $followRedirection = (property_exists($a, 'followRedirection')) ? $a->followRedirection: false;
                $headers = (property_exists($a, 'headers'))?$a->headers:[];
                $connectionTimeout = (property_exists($a, 'connectionTimeout'))?$a->connectionTimeout:FALSE;
                $executionTimeout = (property_exists($a, 'executionTimeout'))?$a->executionTimeout:FALSE;

                if (is_null($url)) {
                    $return->Exception = new \Exception('The post URL is missing');
                }

                // Set the connection timeouts
                if($connectionTimeout != FALSE){
                    $defaultOptions[CURLOPT_CONNECTTIMEOUT] = $connectionTimeout;
                    set_time_limit($connectionTimeout);
                }
                
                if($executionTimeout != FALSE){
                    $defaultOptions[CURLOPT_TIMEOUT] = $executionTimeout;
                    set_time_limit($executionTimeout);
                }

                // Set the request type
                $defaultOptions[CURLOPT_CUSTOMREQUEST] = 'GET';

                // Set the request headers
                foreach ($headers as $headerName => $header) {
                    $defaultOptions[CURLOPT_HTTPHEADER][] = "{$headerName}: {$header}";
                }

                // Set the cookies that are sent
                if($setCookies){
                    $setCookies = is_array($setCookies)? implode(';', $setCookies): ($setCookies);
                    $defaultOptions[CURLOPT_COOKIE] = is_string($setCookies)? ($setCookies):'';
                }

                // Follow redirection
                if($followRedirection){
                    $defaultOptions[CURLOPT_FOLLOWLOCATION] = false;
                    $defaultOptions[CURLOPT_RETURNTRANSFER] = true;
                }

                // Try getting the cookies
                $cookies = [];
                $location = $url;
                $responseHeaders = [];
                // if($getCookies){
                $curlResponseHeaderCallback = function ($ch, $headerLine) use (&$cookies, &$responseHeaders, $getCookies) { 
                    $cookie = [];
                    if($getCookies){
                        if (preg_match('/^Set-Cookie:\s*([^;]*)/mi', $headerLine, $cookie) == 1){
                            $cookies[] = $cookie[1];
                        }
                    }

                    list($name, $value) = explode(': ', $headerLine, 2);
                    
                    if(strlen(trim($name)) > 0){
                        $name = strtolower(trim($name));
                        if(isset($responseHeaders[$name])) {
                            if(!is_array($responseHeaders[$name])) {
                                $responseHeaders[$name] = [$responseHeaders[$name]];
                            }

                            $responseHeaders[$name][] = trim($value);
                        }
                        else {
                            $responseHeaders[$name] = trim($value);
                        }
                    }
                    
                    return strlen($headerLine);
                };

                $defaultOptions[CURLOPT_HEADERFUNCTION] = $curlResponseHeaderCallback;
                // }

                $feed = mware_blocking_http_request($url, ['options' => $defaultOptions]);
                                
                if($followRedirection && isset($responseHeaders['location'])){
                    $location = $responseHeaders['location'];
                }

                if($respFormat != 'json'){
                    $return->Value = $feed->getContent();
                } else {
                    $return->Value = json_decode($feed->getContent());
                }
                $return->Type = 'success';
                // $return->Cookies = $cookies;//count($cookies) > 0?$cookies[0]:[];
                $return->Cookies = $cookies;//count($cookies) > 0 ? $cookies[0]:[];
                $return->Location = $location;
                $return->Headers = $responseHeaders;
                return $return;
            } catch (\Exception $exp) {
                $return->Exception = $exp->getMessage(); $return->StackTrace = $exp->getTraceAsString();                  $return->File = $exp->getFile();
            }
            return $return;
        };

        /**
         * Sends an asynchronous HTTP GET request.
         * 
         * Sends an asynchronous HTTP GET request to the remote server at the specified url.
         * 
         * @param object $a An object containing the parameters for the GET request.
         * The object should have the following structure: <br/>
         * {
         *      url: string (required), // The url to call.
         *      responseType: string (optional) (default: 'json'), // The data type to expect as response. Other options
         *      setCookies: object (optional) (default: null), // An object representing the cookie to send to the remote server.
         *      headers: array (optional), // An array of header values to send to the remote server.
         *      connectionTimeout: int (optional),
         *      executionTimeout: int (optional)
         * }
         * 
         * @return object An object containing the result of the GET request.
         */
        $v8->getAsync = function (\V8Object $a = null) use ($defaultOptions) {
            $return = new \stdClass();
            $return->Type = 'exception';
            $return->Value = null;
            try {
                $a = \is_null($a)?(new \stdClass()):$a;
                $url = (property_exists($a, 'url')) ? $a->url:null;
                $respFormat = (property_exists($a, 'responseType')) ? $a->responseType: 'json';
                $setCookies = (property_exists($a, 'setCookies')) ? $a->setCookies: null;
                $getCookies = (property_exists($a, 'getCookies')) ? $a->getCookies: false;
                $headers = (property_exists($a, 'headers'))?$a->headers:[];
                $connectionTimeout = (property_exists($a, 'connectionTimeout'))?$a->connectionTimeout:FALSE;
                $executionTimeout = (property_exists($a, 'executionTimeout'))?$a->executionTimeout:FALSE;

                if (is_null($url)) {
                    $return->Exception = new \Exception('The post URL is missing');
                }

                // Set the connection timeouts
                if($connectionTimeout != FALSE){
                    $defaultOptions[CURLOPT_CONNECTTIMEOUT] = $connectionTimeout;
                    set_time_limit($connectionTimeout);
                }
                
                if($executionTimeout != FALSE){
                    $defaultOptions[CURLOPT_TIMEOUT] = $executionTimeout;
                    set_time_limit($executionTimeout);
                }

                // Set the request type
                $defaultOptions[CURLOPT_CUSTOMREQUEST] = 'GET';

                // Set the request headers
                foreach ($headers as $headerName => $header) {
                    $defaultOptions[CURLOPT_HTTPHEADER][] = "{$headerName}: {$header}";
                }

                // Set the cookies that are sent
                if($setCookies){
                    $setCookies = is_array($setCookies)? implode(';', $setCookies): ($setCookies);
                    $defaultOptions[CURLOPT_COOKIE] = is_string($setCookies)? ($setCookies):'';
                }

                // Try getting the cookies
                $cookies = [];
                if($getCookies){
                    $curlResponseHeaderCallback = function ($ch, $headerLine) use (&$cookies) { 
                        if (preg_match('/^Set-Cookie:\s*([^;]*)/mi', $headerLine, $cookie) == 1){
                            $cookies[] = $cookie;
                        }
                        return strlen($headerLine);
                    };

                    $defaultOptions[CURLOPT_HEADERFUNCTION] = $curlResponseHeaderCallback;
                }

                $feed = mware_http_request($url, ['options' => $defaultOptions]);

                $return->Value = NULL;
                $return->Type = 'success';
                
                $return->Cookies = count($cookies) > 0?$cookies[0]:[];
                return $return;
            } catch (\Exception $exp) {
                $return->Exception = $exp->getMessage(); $return->StackTrace = $exp->getTraceAsString();                  $return->File = $exp->getFile();
            }
            return $return;
        };

        /**
         * Logs data to the system log repository.
         * 
         * Logs data to the system log repository.
         * 
         * @param string $label The name of the log item.
         * @param string $log The log value to write to the repository.
         */
        $v8->log = function ($label, $log) {
            $logger = $this->logger;

            try {
                $args = func_get_args();
                $logger(...$args);
            } catch (\Exception $exp) {
                $logger('V8WRAPPER LOGGER ERROR', $exp->getMessage());
            }
        };

        /**
         * Invokes a V8JS function.
         * 
         * Using this function, you can invoke another V8JS function and use the returned result in the 
         * current V8JS function.
         * 
         * @param string $controller_name The <b>API Name</b> of the function to be invoked.
         * @param object $params An object of the parameters to be passed to the invoked function.
         */
        $v8->invokeFunction = function ($controller_name, $params){
            $return = new \stdClass();
            $return->Type = 'exception';
            $return->Value = NULL;

            $loader = $this->loader;
            $logger = $this->logger;

            try {
                // Connect to the SQL server
                $ad = $loader('sql');
                if ($ad) {
                    // Search for the function
                    $functions = $ad->getItems(
                        'adhocsoapservice',
                        'Name,Description,Logic,URL,APIName',
                        "APIName eq '{$controller_name}'"
                    );

                    if(count($functions) > 0){
                        $service = $functions[0];
                        $wrapper = new MiddlewareV8Wrapper($service, $loader, $logger);
    
                        $body = \json_decode(\json_encode($params), TRUE);
    
                        // $return->Type = 'success';
                        $p_arg = [];
                        $res = $wrapper->{"_invoke_{$controller_name}"}($body, function(&$response) use(&$p_arg){
                            $p_arg = array_merge($p_arg, $response);						
                        });
                        
                        if($res['status'] == 'success') {
                            $return->Type = 'success';
                            $return->Value = $p_arg['d'];
                        } else  {
                            throw new FriendlyException($res['message'][0]);
                        }
                    } else {
                        $return->Value = NULL;
                    }

                    return $return;
                } else {
                    $return->Exception = ('There was a problem loading the function');
                }
            } catch (\Exception $exep) {
                $return->Exception = $exep->getMessage();
            }


            return $return;
        };

        /**
         * Returns LDAP users with match email addresses.
         *
         * Returns LDAP users with match email addresses.
         *
         * @param array $emails An array of email addresses to search for
         * 
         * @return array An array of objects representing the AD users matched.
         * The objects return will have this format:
         * {
         *      Id: '<The Id of the user>',
         *      DisplayName: '<The display name of the user>',
         *      Mobile: '<The mobile number of the user>'
         * }
         */
        $v8->getADUsersByEmail = function ($emails = []) {
            $return = new \stdClass();
            $return->Type = 'exception';
            $return->Value = [];
            $emails = is_array($emails)?$emails:explode(';', $emails);

            $loader = $this->loader;
            $logger = $this->logger;

            if (count($emails) > 1) {
                $emails = implode('\',\'', $emails);
                try {
                    // Connect to LDAP
                    $ad = $loader('ldap');
                    if ($ad) {
                        // Search for the accounts on LDAP
                        $return->Value = json_encode(
                            $ad->getItems(
                                'objects',
                                'Id,DisplayName,Mobile',
                                "EMail IN('{$emails}')"
                            )
                        );

                        $return->Type = 'success';
                        return $return;
                    } else {
                        $return->Exception = ('There was a problem loading the LDAP driver');
                    }
                } catch (\Exception $exep) {
                    $return->Exception = $exep->getMessage();
                }
            } else {
                $return->Exception = 'You must specify at least one email address';
            }

            return $return;
        };

        /*
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         */
        
        /**
         * Delivers SMS.
         * 
         * Sends an SMS through an SMS Gateway to the specified array of recipients.
         * 
         * @param string $message The message to be delivered.
         * @param array $recipients The list of phone numbers to deliver the message to.
         * @param string $senderAs The MSISDN to display as the sender on the receiving end.
         */
        $v8->sendSMS = function ($message, array $recipients, $senderAs = null) {
            $return = new \stdClass();
            $return->Type = 'exception';
            $return->Value = null;
            try {
                $loader = $this->loader;
                $driver = $loader('smsgateway');
                
                if ($driver) {
                    $return->Value = json_encode($driver->executeFunction('sendsms', [
                        'recipients'=> $recipients
                        , 'message'=> $message
                        , 'senderid' => $senderAs
                    ]));
                    $return->Type = 'success';
                    return $return;
                }
                $return->Exception = new \Exception('Unable to retrieve the requested information');
            } catch (\Exception $exp) {
                $return->Exception = $exp->getMessage(); $return->StackTrace = $exp->getTraceAsString();                  $return->File = $exp->getFile();
            }

            return $return;
        };

        /*
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         */
        
        $v8->sendEmail = function ($subject, $body, array $recipients, array $cc = [], array $bcc = [], array $attachments = []) {
            $return = new \stdClass();
            $return->Type = 'exception';
            $return->Value = null;
            try {
                $loader = $this->loader;
                $driver = $loader('emailgateway');
                if ($driver) {
                    // $return->Value = json_encode($driver->executeFunction('sendEmailMessage', [
                    //     'to'=> $recipients,
                    //     'cc'=> $cc,
                    //     'bc'=> $bcc,
                    //     'subject' => $subject,
                    //     'body' => $body
                    // ]));

                    $return->Value = json_encode($driver->executeFunction('sendEmailMessage2', [
                        'to'=> $recipients,
                        'cc'=> $cc,
                        'bc'=> $bcc,
                        'subject' => $subject,
                        'body' => $body,
                        'attachments' => $attachments
                    ]));
                    
                    $return->Type = 'success';
                    return $return;
                }
                $return->Exception = new \Exception('Unable to retrieve the requested information');
            } catch (\Exception $exp) {
                $return->Type = 'exception';
                $return->Exception = $exp->getMessage(); $return->StackTrace = $exp->getTraceAsString();                  $return->File = $exp->getFile();
                echo $exp->getMessage();
            }
            return $return;
        };

        /*
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         */
        
        $v8->sendEmailAs = function ($subject, $body, $sendAs, array $recipients, array $cc = [], array $bcc = []) {
            $return = new \stdClass();
            $return->Type = 'exception';
            $return->Value = null;
            try {
                $loader = $this->loader;
                $driver = $loader('emailgateway');
                if ($driver) {
                    $return->Value = json_encode($driver->executeFunction('sendEmailMessage', [
                        'to'=> $recipients,
                        'cc'=> $cc,
                        'bc'=> $bcc,
                        'subject' => $subject,
                        'body' => $body,
                        'from' => $sendAs
                    ]));
                    
                    $return->Type = 'success';
                    return $return;
                }
                $return->Exception = new \Exception('Unable to retrieve the requested information');
            } catch (\Exception $exp) {
                $return->Type = 'exception';
                $return->Exception = $exp->getMessage(); 
                $return->StackTrace = $exp->getTraceAsString();                  
                $return->File = $exp->getFile();
            }
            return $return;
        };

        /*
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         */

        /**
         * Converts a spreadsheet into an array of records.
         * 
         * Loads a spreadsheet into memory and converts it into an array of objects with keys that match the values in the corresponding cells of the first row.
         * 
         * Note that the first row is not considered part of the data. It field values are used as keys for the object constituting the other records.
         * 
         * @param mixed $blob The content of the spreadsheet.
         * 
         * @return array The array of objects.
         */
        $v8->readSpreadsheet = function ($blob){  
            $return = new \stdClass();
            $return->Type = 'exception';
            $return->Value = null;
            try {
                $loader = $this->loader;

                // Write the file to temp file.
                $temp_file = tempnam(sys_get_temp_dir(), 'SPST');
                list($type, $blob) = explode(';', $blob);
                list(, $blob)      = explode(',', $blob);
                file_put_contents($temp_file, base64_decode($blob));

                // Workbook
                $excel_data_outer = [];

                // Load the file into a spreadsheet
                $workbook = SpreadsheetIOFactory::load($temp_file);

                $sheetNames = $workbook->getSheetNames();

                foreach($sheetNames as $sheetName){
                    $sheet = $workbook->getSheetByName($sheetName);
                    $highestRowInSheet = $sheet->getHighestRow();
                    $highestColumnInSheet = $sheet->getHighestColumn();
    
                    // Convert the spreadsheet into a multi-dimentional array.
                    $excel_data_outer[$sheet->getTitle()] = $sheet->rangeToArray("A1:{$highestColumnInSheet}{$highestRowInSheet}", NULL, TRUE, FALSE);
                }

                // Close the workbook
                $workbook->disconnectWorkSheets();
                unset($workbook);

                // Convert the multi-dimentional array into an array of objects.
                $object_array = [];
                foreach($excel_data_outer as $excel_name => $excel_data){
                    $object_array[$excel_name] = [];
                    if(count($excel_data) > 0){
                        $row1 = $excel_data[0];
                        foreach($excel_data as $i => $data){
                            if($i > 0){
                                $row  = new \stdClass();
                                $col_count = 0;
                                foreach($data as $ci => $col){
                                    if(!is_null($row1[$ci]) && !empty($row1[$ci])){
                                        $row->{$row1[$ci]} = $col;
                                        $col_count += 1;
                                    }
                                }
                                
                                if($col_count > 0){
                                    $object_array[$excel_name][] = $row;
                                }
                            }
                        }
                    }
                }

                unset($excel_data);
                $return->Value = json_encode($object_array);
                $return->Type = 'success';
                unlink($temp_file);

                return $return;                
            } catch (\Exception $exp) {
                $return->Type = 'exception';
                $return->Exception = $exp->getMessage(); 
                $return->StackTrace = $exp->getTraceAsString();                  
                $return->File = $exp->getFile();
            }

            return $return;
        };

        /*
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         */

        /**
         * Creates a spreadsheet from an array of records.
         * 
         * Creates a spreadsheet from an associative array and returns it as a file blob.
         * 
         * The array is 2 levels deep with the first level being the 
         * {
         *      'Tab 1': {
         *          'Rows': [
         *              [Col 1, Col 2, Col 3],
         *              [Col 1, Col 2, Col 3],
         *              [Col 1, Col 2, Col 3],
         *          ],
         *          'ColumnWidths': [{
         *              Columns: [col1, col2],
         *              Width: 35
         *          }],
         *          'RowHeights': [{
         *              Rows: [row1, row2],
         *              Width: 35
         *          }],
         *          'Formatting': [{
         *              'Range':{
         *                  'From': {Col, Row},
         *                  'To':   {Col, Row}
         *              },
         *              'Color': 'Color value',
         *              'FontStyle': 'Font style value',
         *              'FontSize': 'Font size value',
         *              'BackGroundColor': 'Font size value'
         *          }, {
         *              'Range':{
         *                  'From': {Col, Row},
         *                  'To':   {Col, Row}
         *              },
         *              'Color': 'Color value',
         *              'FontStyle': 'Font style value',
         *              'FontSize': 'Font size value',
         *              'BackGroundColor': 'Font size value'
         *          }]
         *      }, 'Tab 2..n':{}
         * }
         * 
         * @param mixed $blob The content of the spreadsheet.
         * 
         * @return array The array of objects.
         */
        $v8->createSpreadsheet = function (\V8Object $book){  
            $return = new \stdClass();
            $return->Type = 'exception';
            $return->Value = null;
            try {
                // Write the file to temp file.
                $temp_file = tempnam(sys_get_temp_dir(), 'SPST');

                // Workbook                
                $workbook = new Spreadsheet();
                $sheetCount = 1;
                $tabs = get_object_vars($book);

                $columnIndexToRangeName = function ($index) {
                    if(is_int($index)){
                        $dividend = $index + 1;
                        $name = '';
                        $modulo;
                        while ($dividend > 0) {
                            $modulo = ($dividend - 1) % 26;
                            $name = chr(65 + $modulo).$name;
                            $dividend = round(($dividend - $modulo) / 26);
                        }

                        return $name;
                    }

                    return $index;
                };

                foreach($tabs as $name => $tab){
                    $sheet = null;
                    if($sheetCount ==  1){
                        $sheet = $workbook->getActiveSheet();
                    } else {                        
                        $sheet = $workbook->createSheet();
                    }
                    
                    $sheet->setTitle($name);
                    foreach($tab->Rows as $row => $cols){
                        foreach($cols as $col => $cell){
                            $sheet->setCellValueByColumnAndRow($col + 1, $row + 1, $cell);
                        }
                    }

                    if(isset($tab->ColumnWidths)){
                        foreach($tab->ColumnWidths as $def){
                            foreach($def->Columns as $col){
                                $col = $columnIndexToRangeName($col);
                                $sheet
                                    ->getColumnDimension($col)
                                    ->setWidth($def->Width);
                            }
                        }
                    }

                    if(isset($tab->RowHeights)){
                        foreach($tab->RowHeights as $def){
                            foreach($def->Rows as $row){
                                $sheet
                                    ->getRowDimension($row)
                                    ->setHeight($def->Height);
                            }
                        }
                    }

                    $sheetCount = $sheetCount + 1;
                }
                
                $writer = new Xlsx($workbook);
                $writer->save($temp_file);

                $workbook->disconnectWorksheets();
                unset($workbook);

                // Read the content of the file into a binary string.
                $content = file_get_contents($temp_file);
                
                $return->Value = base64_encode($content);                
                unlink($temp_file);
                $return->Type = 'success';

                return $return;                
            } catch (\Exception $exp) {
                $return->Type = 'exception';
                $return->Exception = $exp->getMessage(); 
                $return->StackTrace = $exp->getTraceAsString();                  
                $return->File = $exp->getFile();
            }

            return $return;
        };

        /*
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         */

        /**
         * Creates a spreadsheet from an array of records.
         * 
         * Creates a spreadsheet from an associative array and returns it as a file blob.
         * 
         * The array is 2 levels deep with the first level being the 
         * {
         *      'Tab 1': {
         *          'Rows': [
         *              [Col 1, Col 2, Col 3],
         *              [Col 1, Col 2, Col 3],
         *              [Col 1, Col 2, Col 3],
         *          ],
         *          'ColumnWidths': [{
         *              Columns: [col1, col2],
         *              Width: 35
         *          }],
         *          'RowHeights': [{
         *              Rows: [row1, row2],
         *              Width: 35
         *          }],
         *          'Formatting': [{
         *              'Range':{
         *                  'From': {Col, Row},
         *                  'To':   {Col, Row}
         *              },
         *              'Color': 'Color value',
         *              'FontStyle': 'Font style value',
         *              'FontSize': 'Font size value',
         *              'BackGroundColor': 'Font size value'
         *          }, {
         *              'Range':{
         *                  'From': {Col, Row},
         *                  'To':   {Col, Row}
         *              },
         *              'Color': 'Color value',
         *              'FontStyle': 'Font style value',
         *              'FontSize': 'Font size value',
         *              'BackGroundColor': 'Font size value'
         *          }]
         *      }, 'Tab 2..n':{}
         * }
         * 
         * @param mixed $blob The content of the spreadsheet.
         * 
         * @return array The array of objects.
         */
        $v8->scrapePage = function (\V8Object $a){  
            $return = new \stdClass();
            $return->Type = 'exception';
            $return->Value = null;
            try {
                // Initialize the settings parameters
                $a->extension = property_exists($a, 'extension')?$a->extension:'pdf';
                $a->orientation = property_exists($a, 'orientation')?$a->orientation:'portrait';
                $a->pageSize = property_exists($a, 'pageSize')?$a->pageSize: null;
                $a->paperWidth = property_exists($a, 'paperWidth')?$a->paperWidth:'21';
                $a->paperHeight = property_exists($a, 'paperHeight')?$a->paperHeight:'29.7';
                $a->paperColor = property_exists($a, 'paperColor')?$a->paperColor:'#ffffff';
                $a->margin = property_exists($a, 'margin')?$a->margin:'0';
                $a->footerMargin = property_exists($a, 'footerMargin')?$a->footerMargin:'0.5';
                $a->viewportSizeX = property_exists($a, 'viewportSizeX')?$a->viewportSizeX: 1920;
                $a->viewportSizeY = property_exists($a, 'viewportSizeY')?$a->viewportSizeY: 1080;
                $a->dimensionUnits = property_exists($a, 'dimensionUnits')?$a->dimensionUnits:'cm';
                $a->setCache = property_exists($a, 'setCache')?$a->setCache:FALSE;
                $a->footer = property_exists($a, 'footer')?$a->footer:"<footer style='background: #474747; color: #ffffff; font-size: small; padding: 10px 10px ; margin: -12px; height: 30px; border: 1px solid red;'>Page <span style='float:right;'>\${CurrentPage} of \${TotalPages}</span></footer>";
                $a->footer = str_replace(['${CurrentPage}', '${TotalPages}'],['%pageNum%', '%pageTotal%'], $a->footer);

                // Write the file to temp file.
                $temp_file = tempnam(sys_get_temp_dir(), 'SPST');              
                unlink($temp_file);
                $temp_file = "{$temp_file}.{$a->extension}";

                $client = Client::getInstance();   
                $client->getEngine()->setPath('/usr/local/bin/phantomjs');
                $client->getEngine()->addOption('--load-images=true');
                $client->getEngine()->addOption('--ignore-ssl-errors=yes');
                $client->getEngine()->addOption('--ssl-protocol=any');
                $client->isLazy();

                $request = $client->getMessageFactory()->createPdfRequest($a->url, 'GET');
                $request->setOutputFile($temp_file);
                $request->setFormat($a->pageSize);
                $request->setOrientation($a->orientation);
                $request->setMargin("{$a->margin}{$a->dimensionUnits}");
                if(is_null($a->pageSize)){
                    $request->setPaperSize("{$a->paperWidth}{$a->dimensionUnits}", "{$a->paperHeight}{$a->dimensionUnits}");
                }
                $request->setViewportSize($a->viewportSizeX, $a->viewportSizeY);
                
                $request->setBodyStyles([
                    'backgroundColor' => $a->paperColor
                ]);

                // Set the footer margin
                if($a->footer){
                    $request->setRepeatingFooter(
                        $a->footer
                        , "{$a->footerMargin}{$a->dimensionUnits}"
                    );
                }

                $response = $client->getMessageFactory()->createResponse();    
                $request->setTimeout(10000);   

                // Send the request
                $r = $client->send($request, $response);

                // Read the content of the file into a binary string.
                $content = file_get_contents($temp_file);
                
                if($response->getStatus() == 200){
                    $return->Value = base64_encode($content);   
                    $return->Type = 'success';  
                } else {
                    $return->Type = 'exception';
                    $return->Exception = $response->getStatus(); 
                    $return->StackTrace = $response->getConsole();                  
                    $return->File = $response->getUrl();
                }
                $return->Headers = $response->getHeaders();

                try { 
                    unlink($temp_file);
                }catch(\Exception $e){}

                return $return;                
            } catch (\Exception $exp) {
                $return->Type = 'exception';
                $return->Exception = $exp->getMessage(); 
                $return->StackTrace = $exp->getTraceAsString();                  
                $return->File = $exp->getFile();
            }

            return $return;
        };
        
        $v8->convertPageToDoc = function (\V8Object $a) use($defaultOptions){  
            $return = new \stdClass();
            $return->Type = 'exception';
            $return->Value = null;
            try {
                // Write the file to temp file.
                $temp_file = tempnam(sys_get_temp_dir(), 'SPST');

                // Document
                $pw = new PhpWord();
                $feed = mware_blocking_http_request($a->url, ['options' => $defaultOptions]);                
                $html = $feed->getContent();
                $section = $pw->addSection();
                
                Html::addHtml($section, $html, false, false);

                $writer = WordIOFactory::createWriter($pw, 'Word2007');
                $writer->save($temp_file);

                unset($pw);

                // Read the content of the file into a binary string.
                $content = file_get_contents($temp_file);
                
                $return->Value = base64_encode($content);                
                unlink($temp_file);
                $return->Type = 'success';

                return $return;                
            } catch (\Exception $exp) {
                $return->Type = 'exception';
                $return->Exception = $exp->getMessage(); 
                $return->StackTrace = $exp->getTraceAsString();                  
                $return->File = $exp->getFile();
            }

            return $return;
        };

        $v8->addResponseHeader = function($name, $value){
            $return = new \stdClass();
            $return->Type = 'success';
            $return->Value = null;

            try {
                if(is_string($value)){
                    drupal_add_http_header($name, $value);
                } else if(is_array($value)){
                    foreach($value as $v){
                        drupal_add_http_header($name, $v);
                    }
                }
            } catch (\Exception $exp) {
                $return->Type = 'exception';
                $return->Exception = $exp->getMessage(); 
                $return->StackTrace = $exp->getTraceAsString();                  
                $return->File = $exp->getFile();
            }

            return $return;
        };

        /*
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         */

        /**
         * Returns a connection token for the specified user.
         *
         * Returns the connection token for the specified user. If the user already has an active connection token,
         * a new one is not generated but the existing one is sent.
         * 
         * @param int $duration The duration for which the token should last before expiring.
         * @param string $user_name The user name of the user the token is meant for.
         * @param string $pass The password of the user.
         * 
         * @return string The token.
         *
         */
        $v8->getToken = function ($duration = 20, $user_name = null, $pass = null) use($v8){  
            $return = new \stdClass();
            $return->Type = 'exception';
            $return->Value = null;
                
            $token = '';

            // Initialize duration
            $duration = is_null($duration)?20:$duration;

            try {
                // Get the id of the logged-in user.
                $user_id = 0;
                if(!is_null($user_name)){
                    $user_id = user_authenticate($user_name, $pass);
                    if($user_id == FALSE){
                        throw new \Exception('Invalid username and/or password.');
                    }
                } else {
                    global $user;
                    $user_id = $user->uid;
                }

                // Check if the user has an existing token
                $retrieveValue = $v8->retrieveValue;
                $removeValue = $v8->removeValue;
                $prevToken = $retrieveValue("{$user_id}_token", NULL);
                if(!is_null($prevToken)) {
                    $now = new \DateTime();
                    if($prevToken['expiry'] > $now){
                        $token = $prevToken['token'];
                    }

                    // If the existing token has expired.
                    else {
                        $removeValue($prevToken['token']);
                        $token = drupal_get_token($user_id);
                    }
                }

                // If there is no existing token.
                else {
                    $token = drupal_get_token($user_id);
                }

                // Store the token value for future reference purposes.
                $expiry = (new \DateTime())->add((new \DateInterval("PT{$duration}M"))); 
                
                $storeValue = $v8->storeValue;               
                $storeValue($token, ['user_id' => $user_id, 'expiry' => $expiry]);
                $storeValue("{$user_id}_token", ['user_id' => $user_id, 'expiry' => $expiry, 'token' => $token]);


                // Schedule an automatic cleanup
                $scheduleTask = $v8->scheduleTask;
                $scheduleTask("{$user_id}_clear_token", 
                    "
                        \$.removeValue('{$token}').then(function(){
                            \$.removeValue('{$user_id}_token').then(function(){
                                \$.return(true);
                            });
                        });
                    ", 
                    $expiry->format('Y-m-d\TH:i:s')
                );

                $return->Value = $token;
                $return->Type = 'success';

                return $return;                
            } catch (\Exception $exp) {
                $return->Type = 'exception';
                $return->Exception = $exp->getMessage(); 
                $return->StackTrace = $exp->getTraceAsString();                  
                $return->File = $exp->getFile();
            }

            return $return;
        };

        /*
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         */

        /**
         * Checks whether a token is valid.
         *
         * Checks whether the specified token is valid or not. Returns false if the token is not found or is no longer valid.
         * 
         * @param string $token The token to validate.
         * 
         * @return mixed either the user's id (if the token is still valid) otherwise false.
         *
         */
        $v8->validateToken = function ($token) use($v8){  
            $return = new \stdClass();
            $return->Type = 'exception';
            $return->Value = null;

            $removeValue = $v8->removeValue;
            try {
                // Check if the token exists.
                $retrieveValue = $v8->retrieveValue;
                $prevToken = $retrieveValue($token, NULL);
                if(is_null($prevToken)) {          
                    $return->Value = false;
                } else {
                    $now = new \DateTime();
                    if($prevToken['expiry'] < $now){
                        
                        $return->Value = true;
                    }

                    // If the existing token has expired.
                    else {
                        $removeValue($prevToken['token']);
                        $removeValue("{$prevToken['token']}_token");
                        $return->Value = false;
                    }
                }
                
                $return->Type = 'success';
                return $return;                
            } catch (\Exception $exp) {
                $return->Type = 'exception';
                $return->Exception = $exp->getMessage(); 
                $return->StackTrace = $exp->getTraceAsString();                  
                $return->File = $exp->getFile();
            }

            return $return;
        };

        /*
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         */

        /**
         * Schedules a script to be executed at a later.
         * 
         * Schedules the specified script and deletes it subsequently.
         *
         * @param string $script The script to execute.
         * @param string $executionTime The time to execute the script in 'YYYY-MM-DDTHH:mm:ss' format.
         * 
         * @return int The id of the scheduled script function.
         */
        $v8->scheduleTask = function ($identifier, $script, $executionTime){  
            $return = new \stdClass();
            $return->Type = 'exception';
            $return->Value = null;
            try {
                $load = $this->loader;

                $commissions = mware_cp_bridge__add_middlewarefunction([
                    'name' => '__ONETIME__',
                    'type' => 'REST',
                    'logic' => $script,
                    'url' => $identifier,
                    'next_run' => $executionTime,
                    'enable_scheduling' => true,
                    'match_url' => '1'
                ]);

                return $return;                
            } catch (\Exception $exp) {
                $return->Type = 'exception';
                $return->Exception = $exp->getMessage(); 
                $return->StackTrace = $exp->getTraceAsString();                  
                $return->File = $exp->getFile();
            }

            return $return;
        };

        /*
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         */
        
        $v8->markEmailAsRead = function ($id) {
            $return = new \stdClass();
            $return->Type = 'exception';
            $return->Value = null;
            try {
                $loader = $this->loader;
                $driver = $loader('emailgateway');
                if ($driver) {
                    $return->Value = json_encode($driver->executeEntityItemFunctionInternal('mails', $id, 'markMessageAsRead'));
                    $return->Type = 'success';
                    return $return;
                }
                $return->Exception = new \Exception('Unable to retrieve the requested information');
            } catch (\Exception $exp) {
                $return->Type = 'exception';
                $return->Exception = $exp->getMessage(); $return->StackTrace = $exp->getTraceAsString();                  $return->File = $exp->getFile();
            }
            return $return;
        };

        /*
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         */
        
        /**
         * Returns an array of items matching a query.
         * 
         * Returns an array of items matching the specified query from the specified connection driver.
         * 
         * @param string $driverName The name of the connection driver / data source.
         * @param string $entityName The name of the data entity to query within the data source.
         * @param string $select (optional) A comma separated list of fields to return.
         * @param string $filter (optional)A filter statement that determines the records to be returned.
         * @param object $otherOptions (optional)An additional dictionary of parameters to pass to the datasource.
         * The following is the structure of the object:
         * {
         *      $top: (optional), // The maximum number of records that can be returned.
         *      $skipCache: (optional)
         * }
         */
        $v8->getItems = function ($driverName, $entityName, $select = '', $filter = '', $expand = '', $otherOptions = []) {
            $return = new \stdClass();
            $return->Type = 'exception';
            $return->Value = null;
            try {
                $loader = $this->loader;
                $driver = $loader($driverName);
                if ($driver) {
                    $args = func_get_args();
                    $args = json_decode(json_encode(array_splice($args, 1)), true);
                    
                    $return->Value = json_encode($driver->getItems(...$args));
                    $return->Type = 'success';
                    return $return;
                }
                $return->Exception = new \Exception('Unable to retrieve the requested information');
            } catch (\Exception $exp) {
                $return->Type = 'exception';
                $return->Exception = $exp->getMessage(); 
                $return->StackTrace = $exp->getTraceAsString();                  
                $return->File = $exp->getFile();
            }
            return $return;
        };

        /*
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         */
        
        $v8->syncFromDate = function ($driverName, $entityName, $date = '$today$') {
            $return = new \stdClass();
            $return->Type = 'exception';
            $return->Value = null;
            try {
                $loader = $this->loader;
                $driver = $loader($driverName);
                if ($driver) {
                    $args = func_get_args();
                    $args = json_decode(json_encode(array_splice($args, 1)), true);
                    $return->Value = json_encode($driver->syncFromDate(...$args));
                    $return->Type = 'success';
                    return $return;
                }
                $return->Exception = new \Exception('Unable to sync the data source');
            } catch (\Exception $exp) {
                $return->Type = 'exception';
                $return->Exception = $exp->getMessage(); $return->StackTrace = $exp->getTraceAsString();                  $return->File = $exp->getFile();
            }
            return $return;
        };

        /*
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         */
        
        $v8->syncFromThisMonth = function ($driverName, $entityName) use ($v8) {
            $sync = $v8->syncFromDate;
            return $sync($driverName, $entityName, '$month$');
        };

        /*
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         */
        
        $v8->syncFromThisYear = function ($driverName, $entityName) use ($v8) {
            $sync = $v8->syncFromDate;
            return $sync($driverName, $entityName, '$year$');
        };

        /*
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         */
        
        $v8->syncFromToday = function ($driverName, $entityName) use ($v8) {
            $sync = $v8->syncFromDate;
            return $sync($driverName, $entityName, '$today$');
        };

        /*
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         */
        
        $v8->syncFromTheHour = function ($driverName, $entityName) use ($v8) {
            $sync = $v8->syncFromDate;
            return $sync($driverName, $entityName, '$1HR$');
        };

        /*
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         */
        
        $v8->syncFrom6HoursAgo = function ($driverName, $entityName) use ($v8) {
            $sync = $v8->syncFromDate;
            return $sync($driverName, $entityName, '$6HR$');
        };

        /*
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         */
        
        $v8->getItemById = function ($driverName, $entityBrowser, $id, $select, $expands = '', $otherOptions = []) {
            $return = new \stdClass();
            $return->Type = 'exception';
            $return->Value = null;
            try {
                $loader = $this->loader;
                $driver = $loader($driverName);
                if ($driver) {
                    $args = func_get_args();
                    $args = json_decode(json_encode(array_splice($args, 1)), true);
                    $return->Value = json_encode($driver->getItemById(...$args));
                    $return->Type = 'success';
                    return $return;
                }
                $return->Exception = new \Exception('Unable to retrieve the requested information');
            } catch (\Exception $exp) {
                $return->Type = 'exception';
                $return->Exception = $exp->getMessage(); $return->StackTrace = $exp->getTraceAsString();                  $return->File = $exp->getFile();
            }
            return $return;
        };        

        /*
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         */

        $v8->getItemsByFieldValues = function ($driverName, $entityBrowser, $fieldName, array $values, $select, $expands = '', $otherOptions = []) {
            $return = new \stdClass();
            $return->Type = 'exception';
            $return->Value = null;
            try {
                $loader = $this->loader;
                $driver = $loader($driverName);
                if ($driver) {
                    $args = func_get_args();
                    $args = json_decode(json_encode(array_splice($args, 1)), true);
                    $return->Value = json_encode($driver->getItemsByFieldValues(...$args));
                    $return->Type = 'success';
                    return $return;
                }
                $return->Exception = new \Exception('Unable to retrieve the requested information');
            } catch (\Exception $exp) {
                $return->Type = 'exception';
                $return->Exception = $exp->getMessage(); 
                $return->StackTrace = $exp->getTraceAsString();                  
                $return->File = $exp->getFile();
            }
            return $return;
        };

        /*
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         */
        
        $v8->createItems = function ($driverName, $entityName, array $items, $otherOptions = []) use ($v8) {
            // $getItem = $v8->getItems;
            $return = new \stdClass();
            $return->Type = 'exception';
            $return->Value = null;
            try {
                $loader = $this->loader;
                $driver = $loader($driverName);
                
                if ($driver) {
                    $args = func_get_args();
                    $args = json_decode(json_encode(array_splice($args, 1)), true);
                    $args[1] = $args[1];
                    $return->Value = json_encode($driver->createItems(...$args));
                    $return->Type = 'success';
                    return $return;
                }
                
                $return->Exception = 'Unable to connect to remedy';
            } catch (\Exception $exp) {
                $return->Type = 'exception';
                $return->Exception = $exp->getMessage(); 
                $return->StackTrace = $exp->getTraceAsString();                  
                $return->File = $exp->getFile();
            }
            return $return;
        };

        /*
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         */
        
        $v8->createItem = function ($driverName, $entityName, $item, $otherOptions = []) use ($v8) {
            // $getItem = $v8->getItems;
            $otherOptions = is_null($otherOptions) ? [] : $otherOptions;
            $return = new \stdClass();
            $return->Type = 'exception';
            $return->Value = null;
            try {
                $loader = $this->loader;
                $driver = $loader($driverName);
                
                if ($driver) {
                    $args = func_get_args();
                    $args = json_decode(json_encode(array_splice($args, 1)), true);
                    $args[1] = (object)$args[1];
                    $args[2] = is_object($otherOptions)?get_object_vars($otherOptions):$otherOptions;
                    $return->Value = json_encode($driver->createItem(...$args));
                    $return->Type = 'success';
                    return $return;
                }
                $return->Exception = 'Unable to connect to remedy';
            } catch (\Exception $exp) {
                $return->Type = 'exception';
                $return->Exception = $exp->getMessage(); 
                $return->StackTrace = $exp->getTraceAsString();                  
                $return->File = $exp->getFile();
            }
            return $return;
        };

        /*
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         */
        
        $v8->deleteItem = function ($driverName, $entityName, $id, $otherOptions = []) {
            $return = new \stdClass();
            $return->Type = 'exception';
            $return->Value = null;
            try {
                $loader = $this->loader;
                $driver = $loader($driverName);
                if ($driver) {
                    $args = func_get_args();
                    $args = json_decode(json_encode(array_splice($args, 1)), true);
                    $return->Value = json_encode($driver->deleteItem(...$args));
                    $return->Type = 'success';
                    return $return;
                }
                $return->Exception = new \Exception('Unable to retrieve the requested information');
            } catch (\Exception $exp) {
                $return->Exception = $exp->getMessage(); $return->StackTrace = $exp->getTraceAsString();                  $return->File = $exp->getFile();
            }
            return $return;
        };

        /*
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         */
        
        $v8->updateItem = function ($driverName, $entityName, $id, $update, $otherOptions = []) {
            $return = new \stdClass();
            $return->Type = 'exception';
            $return->Value = null;
            try {
                $loader = $this->loader;
                $driver = $loader($driverName);
                if ($driver) {
                    $args = func_get_args();
                    $args = json_decode(json_encode(array_splice($args, 1)), true);
                    $args[2] = (object)$args[2];

                    $return->Value = json_encode($driver->updateItem(...$args));
                    $return->Type = 'success';
                    return $return;
                }
                $return->Exception = new \Exception('Unable to retrieve the requested information');
            } catch (\Exception $exp) {
                $return->Exception = $exp->getMessage(); $return->StackTrace = $exp->getTraceAsString();                  $return->File = $exp->getFile();
            }
            return $return;
        };

        /*
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         */
        
        /**
         * Returns the type of the programming environment.
         * 
         * Returns true if this environment is a production or false otherwise.
         * 
         * @return boolean
         */
        $v8->isProduction = function () {
            $environment_type = variable_get('MWARE_INSTANCE_TYPE', 'sandbox');
            $return = ($environment_type == 'production'?true:false);
            return $return;
        };

        /*
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         */
        
        /**
         * Returns the type of the programming environment.
         * 
         * Returns true if this environment is not a production or false otherwise.
         * 
         * @return boolean
         */
        $v8->isSandbox = function () {
            $environment_type = variable_get('MWARE_INSTANCE_TYPE', 'sandbox');
            $return = ($environment_type == 'sandbox'?true:false);
            return $return;
        };

        /*
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         */
        
        /**
         * Returns the type of the programming environment.
         * 
         * Returns true if this environment is a staging environment or false otherwise.
         * 
         * @return boolean
         */
        $v8->isStaging = function () {
            $environment_type = variable_get('MWARE_INSTANCE_TYPE', 'sandbox');
            $return = ($environment_type == 'staging'?true:false);
            return $return;
        };

        /*
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         */
        
        /**
         * Returns the type of the programming environment.
         * 
         * There are 3 possible values namely: production, sandbox, staging
         * 
         * @return string
         */
        $v8->getInstanceType = function () {
            return variable_get('MWARE_INSTANCE_TYPE', 'sandbox');
        };

        /*
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         */
        
        $v8->markAsOld = function ($driverName, $entityName, $id) use ($v8) {
            $return = new \stdClass();
            $return->Type = 'exception';
            $return->Value = null;
            try {
                $update = new \stdClass();
                $update->Id = $id;
                $update->_IsUpdated = false;

                $updateItem = $v8->updateItem;
                return $updateItem($driverName, $entityName, $id, $update, ['$cacheOnly' => '1']);
            } catch (\Exception $exp) {
                $return->Exception = $exp->getMessage(); 
                $return->StackTrace = $exp->getTraceAsString();                  
                $return->File = $exp->getFile();
            }
            return $return;
        };

        $v8->method = $methodName;
        $arguments = func_get_args();
        $v8->arguments = count($arguments) > 1?array_splice($arguments, 1):[];
        $v8->arguments = count($v8->arguments) > 0?$v8->arguments[0]:[];
        $return = [];

        $formatResponse = function (&$param = null) use ($overall_args) {
            if (isset($overall_args[1])) {
                $return = $overall_args[1];
                return $return($param);
            }
            
            if (isset($param['d'])) {
                $param =  (object)$param['d'];
            }
        };


        /*
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         */

        $v8->return = function ($type = 'success', $response = null, $param3 = [], $encoded = true) use (&$return, $overall_args) {
            $args = func_get_args();
            $argCount = count($args);
            
            switch ($argCount) {
                case 0: {
                    $return['status'] = 'success';
                    $return['d'] = null;
                    break;
                }
                case 1:{
                    $return['status'] = 'success';
                    $return['d'] = json_decode(json_encode($args[0]), true);
                    break;
                }
                case 2:{
                    if (is_string($type)) {
                        switch ($type) {
                            case 'exception': {
                                $return['status'] = 'failure';
                                $return['message'] = is_string($response)?$response:json_decode(json_encode($response), true);
                                break;
                            }
                            default: {
                                $return['d'] = json_decode(json_encode($response), true);
                            }
                        }
                    }
                    break;
                }
                case 3:
                default:{
                    if (is_string($type)) {
                        switch ($type) {
                            case 'exception': {
                                $return['status'] = 'failure';
                                $return['message'] = json_decode(json_encode($response), true);
                                break;
                            }
                            default: {
                                $return['custom_format'] = [
                                    'encoded' => $encoded
                                ];
                                $return['d'] = $type;
                                $return['headers'] = get_object_vars($param3);
                            }
                        }
                    }
                    break;
                }
            }

            throw new FriendlyException();
        };

        /*
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         ********************************************************************************************
         */
        
        $v8->throw = function ($message = 'No message given') use (&$v8) {
            $r = $v8->return;
            $r('exception', $message);
        };

        /**
         * Stores a value for later retrieval.
         *
         * @param $key String The key to identify the value to store.
         * @param $value mixed
         * @return void
         */
        $v8->storeValue = function ($key, $value)
        {
            variable_set("v8__{$key}", $value);
            return $this;
        };

        /**
         * Retrieves a value from that has been previously stored
         *
         * @param String $key The key to identity the value to be retrieved.
         * @param mixed $default
         * @return mixed
         */
        $v8->retrieveValue = function ($key, $default = null)
        {
            return variable_get("v8__{$key}", $default);
        };  

        /**
         * Retrieves a value from that has been previously stored
         *
         * @param String $key The key to identity the value to be retrieved.
         * @param mixed $default
         * @return mixed
         */
        $v8->removeValue = function ($key, $default = null)
        {
            return variable_del("v8__{$key}");
        };    

        $v8->getDoc = function ($method) use($v8) {
            $return = new \stdClass();
            $return->Type = 'exception';
            $return->Value = null;

            try {
                // $method = new ReflectionMethod($this->v8, $method);


                $cal = $v8->{$method};
                if(!is_callable($cal)){ throw new \Exception('Not a string');}
                $method = new ReflectionFunction($cal);
                $str = $method->getDocComment();
                $factory  = DocBlockFactory::createInstance();
                $docblock = $factory->create($method);

                $return->Type = 'success';
                $return->Value = new \stdClass();
                $return->Value->Description = $docblock->getDescription()->render();
                $return->Value->Parameters = [];
                foreach($docblock->getTagsByName('param') as $tagItem){
                    $t = new \stdClass();
                    $t->Name = $tagItem->getVariableName();
                    $t->Description = $tagItem->getDescription()->render();
                    if(!is_null($tagItem->getType())) {
                        $t->Type = $tagItem->getType()->__toString();
                        $type = $tagItem->getType();
                        if(property_exists($type, 'getValueType')){  
                            $getValueType = $tagItem->getType()->getValueType;                      
                            $t->Type = $getValueType();
                        }
                    }
                    $return->Value->Parameters[] = $t;
                }
                
                return $return;
            } catch(\Exception $exp) {
                $return->Exception = "Method: {$method}:: {$exp->getMessage()}"; 
                $return->StackTrace = $exp->getTraceAsString();                  
                $return->File = $exp->getFile();
            }

            return $return;
        };  

        $v8->getAPIFunctions = function() use($v8) {
            $return = new \stdClass();
            $return->Type = 'exception';
            $return->Value = null;

            try {
                // $method = new ReflectionMethod($this->v8, $method);

                $object = new ReflectionObject($v8);

                $return->Type = 'success';
                $return->Value = new \stdClass();
                $return->Value->Functions = [];
                // $return->Value = $object->getProperties();
                foreach($object->getProperties(ReflectionProperty::IS_PUBLIC) as $property){
                    $getDoc = $v8->getDoc;
                    if(property_exists($v8, $property->getName())){
                        $cal = $v8->{$property->getName()};
                        if(property_exists($v8, $property->getName()) && is_callable($cal)){
                            try {
                                $p = $getDoc($property->getName());
                                if($p->Type == 'success'){
                                    $p = $p->Value;
                                    $p->Name = $property->getName();
                                    $return->Value->Functions[] = $p;
                                }
                            } catch(\Exception $exp){

                            }
                        }
                    }
                }

                return $return;
            } catch(\Exception $exp) {
                $return->Exception = $exp->getMessage(); 
                $return->StackTrace = $exp->getTraceAsString();                  
                $return->File = $exp->getFile();
            }

            return $return;
        };

        try {
            $logic = $this->service->Logic;

            // Create a wrapper logic for the PHP functions so that we can use chain style programming.
            $logic = "var l = function(\$){	{$logic} };
            
			var \$ = null;
			var \$\$ = function(){return arguments;};
			var \$\$\$;
			(function(){
				function _(param) {
					this.param = param;
					this.internalValue = null;
					this.internalError = null;
					this.\$ = _\$;
					this._iAm = 'YYYY';
                    var keys = Object.keys(this.\$);
                    this.thens = [];
					var _this = this;

					this.run = function(param){
						if((param != null && _this.param != null) && (param._iAm != undefined && param._iAm == _this._iAm)){
							_this.param = param.param;
							return param;
						} else {
							_this.param = param;
                        }
                        
						return _this;
					};

					this.\$\$ = function(){
                        var method = arguments[0];
                        var ret = null;
						switch(arguments.length){
							case 1:{
								ret = this.\$[method]();
								break;
							}
							case 2:{
								ret = this.\$[method](arguments[1]);
								break;
							}
							case 3:{
								ret = this.\$[method](arguments[1],arguments[2]);
								break;
							}
							case 4:{
								ret = this.\$[method](arguments[1],arguments[2],arguments[3]);
								break;
							}
							case 5:{
								ret = this.\$[method](arguments[1],arguments[2],arguments[3],arguments[4]);
								break;
							}
							case 6:{
								ret = this.\$[method](arguments[1],arguments[2],arguments[3],arguments[4],arguments[5]);
								break;
							}
							case 7:{
								ret = this.\$[method](arguments[1],arguments[2],arguments[3],arguments[4],arguments[5],arguments[6]);
								break;
							}
							case 8:{
								ret = this.\$[method](arguments[1],arguments[2],arguments[3],arguments[4],arguments[5],arguments[6],arguments[7]);
								break;
							}
							case 9:{
								ret = this.\$[method](arguments[1],arguments[2],arguments[3],arguments[4],arguments[5],arguments[6],arguments[7],arguments[8]);
								break;
							}
                        }
                        
                        var aa = ['Type', 'Value', 'Exception', 'Headers', 'StackTrace', 'File', 'DataType', 'Cookies', 'Location'];
                        var matchCount = 0;
                        for(var a in aa){
                            if(ret != undefined && ret['\$' + aa[a]] != undefined){
                                this.param[aa[a]] = ret['\$' + aa[a]];
                                matchCount++;
                            }
                        }

                        if(matchCount == 0){
                            return ret;
                        }

                        return this;
					};

					for(var x in keys){
                        var key = keys[x];
                        
						if (typeof _this.\$[key] == 'function' && key.substring(0,2) != '__'){	
							_this[key] = (function(){
								var args = Array.prototype.slice.call(arguments);
                                if(_this.param == null){
                                    var x = new _({Value:null, Type:null});
                                    var rn = x.\$\$.apply(x, args);
                                    return rn;
                                }
								return _this.run(_this.\$\$.apply(_this, args));
							}).bind(_this, key);
                        } else if(typeof _this.\$[key] == 'function' && key.substring(0,2) == '__'){
                            _this[key.substring(2)] = _this.\$[key];
                        }
                        else {
							_this[key] = _this.\$[key];
						}
					}
	
					this.then = function(func){
						if((_this.param != null || _this.param == undefined) && _this.param.Type == 'success') {
							try {
								if(typeof _this.param.Value == 'string'){	
									_this.param.Value = JSON.parse(_this.param.Value);
								}
							} catch(e){
								_this.param.Value = (_this.param.Value);
							}

							try {
                                _this.param.Value = func.call(null, _this.param.Value, _this.param);
							} catch(e) {
								_this.\$.log('MWAREV8WRAPPER ERROR', (e.message));
							}
						}
						
						return _this;
					} 
					
					this.error = function(func){
						if((_this.param == null || _this.param == undefined) || _this.param.Type != 'success'){
							try {
								_this.internalError = (_this.param != null && _this.param.Exception != undefined) ? [_this.param.Exception, _this.param.StackTrace, _this.param.File]:'null response';
								// _this.internalError = JSON.parse(this.param.Exception);
								func.call(null, _this.internalError);
							} catch(e){
								_this.internalError = func.call(null, _this.internalError);
							}
						}

						return _this;
					}
					
					this.catch = function(func){
						if((_this.param == null || _this.param == undefined) || _this.param.Type != 'success'){
							try {
								_this.internalError = (_this.param != null && _this.param.Exception != undefined) ? _this.param.Exception:'null response';
								_this.internalError = JSON.parse(this.param.Exception);
								func.call(null, _this.internalError);
							} catch(e){
								_this.internalError = func.call(null, _this.internalError);
							}
						}

						return _this;
					}
	
					return this;
				};
				
				var s = new _();
				\$\$\$ = s;
				// \$\$ = (s).run.bind(s);
				\$ = \$\$\$;
			})();
			l(\$);";
            $v8->executeString($logic);
        } catch (FriendlyException $fe) {
        } catch (\Exception $ex) {
            $return['message'] = $ex->getMessage();
        }

        // var_dump($return);
        $formatResponse($return);
        return $return;
    }    
}