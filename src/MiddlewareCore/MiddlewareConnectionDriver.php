<?php

namespace Drupal\middleware_core\MiddlewareCore;

use Drupal\middleware_core\MiddlewareCore\ODataParameters\Filter\FilterProcessor;
use Drupal\middleware_core\MiddlewareCore\EntityDefinitionBrowser;
use Drupal\middleware_core\MiddlewareCore\EncoderDecoder;
use Drupal\middleware_core\MiddlewareCore\InvalidFieldSelectedException;

/**
 * Description of MiddlewareConnectionDriver
 *
 * @author Kolade.Ige
 */
abstract class MiddlewareConnectionDriver
{

    protected $entitiesByInternalName = []; //contains a list of entities, keyed by internal name
    protected $entitiesByDisplayName = []; //contains a list of entities, keyed by display name
    protected $driverLoader = null; //function to be called when there is need to load a driver that has never been loaded.
    protected $drivers = []; //a list of drivers that have been loaded during this session.
    protected $connectionToken = null;
    protected $maxRetries = 50;
    protected $sourceLoader = null;
    protected static $loadedDrivers = [];
    protected $identifier = __CLASS__;    
    protected $preferredDateFormat = 'Y-m-d';
    protected $preferredDateTimeFormat = 'Y-m-d\TH:i:s';
    protected $utilityFunctions = [];
    protected $autoFetch = TRUE;
    
    
    abstract public function getStringer();

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */

    public function __construct(callable $driverLoader, callable $sourceLoader = null, $identifier = __CLASS__)
    {
        $this->driverLoader = $driverLoader;
        $this->sourceLoader = $sourceLoader;
        self::$loadedDrivers[$identifier] = &$this;
        $this->identifier = $identifier;
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */

    public function getItemsInternal($entityBrowser, &$connection_token = null, array $select, $filter, $expands = [], $otherOptions = []){
        throw new \Exception('Not yet implemented');
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */

    public function updateItemInternal($entityBrowser, &$connectionToken = null, $id, \stdClass $object, array $otherOptions = []){
        throw new \Exception('Not yet implemented');
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */

    public function createItemInternal($entityBrowser, &$connectionToken = null, \stdClass $object, array $otherOptions = []){
        throw new \Exception('Not yet implemented');
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */

    public function deleteItemInternal($entityBrowser, &$connectionToken = null, $id, array $otherOptions = []){
        throw new \Exception('Not yet implemented');
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */
        
    public function executeFunctionInternal($functionName, array $objects = [], &$connectionToken = null, array $otherOptions = [])
    {
        throw new \Exception('Not yet implemented');
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */
     
    public function executeEntityFunctionInternal($entityBrowser, $functionName, array $objects = [], &$connectionToken = null, array $otherOptions = [])
    {
        throw new \Exception('Not yet implemented');
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */
        
    public function executeEntityItemFunctionInternal($entityBrowser, $id, $functionName, array $data = [], &$connectionToken = null, array $otherOptions = [])
    {
        throw new \Exception('Not yet implemented');
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */
    
    public function ensureDataStructureInternal($entityBrowser, &$connectionToken = null, array $otherOptions = []){
        // throw new \Exception('Not yet implemented');
        return NULL;
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */
    
    /**
     * Invokes the synch function of the specified driver
     *
     * @param mixed $entityBrowser
     * @param string $date
     * @return void
     */
    public function syncByRecordIds($entityBrowser, $ids = []){      
        $entityBrowser = $this->getEntityBrowser($entityBrowser);
        
        if (is_null($entityBrowser)) {
            throw new \Exception('Invalid entity could not be found.');
        }

        if($entityBrowser->shouldCacheData()){
            $sync = $entityBrowser->getParent()->getUtilityFunction('date_sync_util');
            if(!is_null($sync)) {
                $sourceDestination = implode('|', [$this->getIdentifier(), $entityBrowser->getCacheDriverName()]);
                $sync($sourceDestination, $entityBrowser->getDisplayName(), NULL, $ids);
            }
        }
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */

    /**
     * Invokes the synch function of the specified driver
     *
     * @param mixed $entityBrowser
     * @param string $date
     * @return void
     */
    public function syncFromDate($entityBrowser, $date = '1900-01-01'){        
        $entityBrowser = $this->getEntityBrowser($entityBrowser);
        if (is_null($entityBrowser)) {
            throw new \Exception('Invalid entity could not be found.');
        }

        if($entityBrowser->shouldCacheData()){
            // Check for date constants
            $now = new \DateTime();
            switch($date){
                case '$today$':{
                    $date = $now->format('Y-m-d');
                    break;
                }
                case '$1HR$':{
                    $interval = new \DateInterval("PT1H");
                    $date = $now->sub($interval)->format('Y-m-d');
                    break;
                }
                case '$6HR$':{
                    $interval = new \DateInterval("PT6H");
                    $date = $now->sub($interval)->format('Y-m-d');
                    break;
                }
                case '$24HR$':{
                    $interval = new \DateInterval("PT24H");
                    $date = $now->sub($interval)->format('Y-m-d');
                    break;
                }
                case '$month$':{
                    $interval = new \DateInterval("P1M");
                    $date = $now->sub($interval)->format('Y-m-d');
                    break;
                }
                case '$year$':{
                    $interval = new \DateInterval("P1Y");
                    $date = $now->sub($interval)->format('Y-m-d');
                    break;
                }
            }

            if(isset($this->utilityFunctions['date_sync_util'])){
                $sync = $this->utilityFunctions['date_sync_util'];
                $sourceDestination = implode('|', [$this->getIdentifier(), $entityBrowser->getCacheDriverName()]);
                $sync($sourceDestination, $entityBrowser->getDisplayName(), $date);
                // echo $date.' '.$sourceDestination.' '.$entityBrowser->getDisplayName().' '.is_null($sync);
            }
        }
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */    
    
    public function addUtilityFunction($name, callable $function){
        $this->utilityFunctions[$name] = $function;
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */
    

    public function getUtilityFunction($name){
        return isset($this->utilityFunctions[$name])? $this->utilityFunctions[$name]: NULL;
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */
    

    public function isDriverLoaded($driverName)
    {
        return in_array($driverName, array_keys(self::$loadedDrivers));
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */
    

    public function getLoadedDrivers()
    {
        return self::$loadedDrivers;
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */
    

    public function getDriver($driverName)
    {
        return self::$loadedDrivers[$driverName];
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */
    

    public function getIdentifier()
    {
        return $this->identifier;
    }
    
    /**
     * Loads the entity definition in the DataDictionary into memory.
     *
     * @param array $entities
     * @return MiddlewareConnectonDriver
     */
    public function setEntities(array $entities)
    {
        foreach ($entities as $entity_name => $entity) {
            $entityDef = new EntityDefinitionBrowser($entity_name, $entity, $this);
            $this->entitiesByInternalName[$entity['internal_name']] = $entityDef;
            $this->entitiesByDisplayName[$entityDef->getDisplayName()] = &$this->entitiesByInternalName[$entity['internal_name']]; //$entityDef;//&$this->entitiesByInternalName[$entity['internal_name']];
        }

        return $this;
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */
    
    /**
     * Returns the <class>EntityDefinitionBrowser</class> identified by $entity.
     *
     * @param String $entity
     * @return EntityDefinitionBrowser
     */
    public function getEntityBrowser($entityBrowser)
    {
        $entityBrowser2 = ($entityBrowser instanceof EntityDefinitionBrowser) ? $entityBrowser : (isset($this->entitiesByDisplayName[$entityBrowser])?$this->entitiesByDisplayName[$entityBrowser]:$entityBrowser);
        
        if($entityBrowser2 instanceof EntityDefinitionBrowser){
            $this->setStrategies($entityBrowser2);
            return $entityBrowser2;
        }

        return $entityBrowser;
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */
    
    public function loadDriver($driverName)
    {
        if (!in_array($driverName, array_keys(self::$loadedDrivers))) {
            $loader = $this->driverLoader;
            $driver = $loader($driverName);
            self::$loadedDrivers[$driverName] = &$driver;
            return self::$loadedDrivers[$driverName];
        }
        return self::$loadedDrivers[$driverName];
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */
    
    /**
     * Parse Date / DateTime values returned by this connection driver
     * Sub-classes should override this method.
     *
     * @param DateTime $value
     * @return void
     */
    protected function parseDateValue($value)
    {
        $type_1 = '/^(([\d]{4})\-([\d]{2})\-([\d]{2})(T([\d]{2})\:([\d]{2})(\:([\d]{2}))?)?)$/';
        $type_2 = '/^(([\d]{4})\-([\d]{2})\-([\d]{2})(T([\d]{2})\:([\d]{2})))$/';
        $type_3 = '/^([\d]{4})\\-([\d]{2})\-([\d]{2})$/';

        if (preg_match($type_3, $value) == 1) {
            return \DateTime::createFromFormat('!Y-m-d', $value);
        } elseif (preg_match($type_2, $value) == 1) {
            return \DateTime::createFromFormat('!Y-m-d\\TH:i', $value);
        } elseif (preg_match($type_1, $value) == 1) {
            return \DateTime::createFromFormat('!Y-m-d\\TH:i:s', $value);
        }

        throw new \Exception("The time format is not known. Class MiddlewareConnectionDriver {$value}");
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */
    
    /**
     * Returns a number that represents the maximum allowed OR statements to use when converting from IN to OR.
     *
     * This is necessary for systems that do not have an OOB implementation of the IN operator.
     *
     * @return void
     */
    public function getMaxInToOrConversionChunkSize()
    {
        return 100;
    }

    //TODO: Make this function less nostic of the Drupal function.

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */

    /**
     * Stores a value for later retrieval.
     *
     * @param String $key The key to identify the value to store.
     * @param mixed $value
     * @return void
     */
    public function storeValue($key, $value)
    {
        variable_set("mw__{$key}", $value);
        return $this;
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */
    
    /**
     * Retrieves a value from that has been previously stored
     *
     * @param String $key The key to identity the value to be retrieved.
     * @param mixed $default
     * @return mixed
     */
    public function retrieveValue($key, $default = null)
    {
        return variable_get("mw__{$key}", $default);
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */
    
    /**
     * Invokes a function call on the underlying system, passing the specified objects as parameters.
     *
     * @param String $functionName
     * @param array $objects
     * @param array $otherOptions
     * @return \stdClass representing the result of the function call.
     */
    public function executeFunction($functionName, array $data = [], array $otherOptions = [])
    {        
        $retryCount = isset($otherOptions['retryCount'])?$otherOptions['retryCount']:-1;
        $otherOptions['retryCount'] = $retryCount + 1;

        try {
            $result = $this->executeFunctionInternal($functionName, $data, $this->connectionToken, $otherOptions);
            return $result;
        } catch (\Exception $exc) {
            if ($retryCount < $this->maxRetries) {
                return $this->executeFunction($functionName, $data, $otherOptions);
            } else {
                throw $exc;
            }
        }
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */
        
    public function executeEntityFunction($entityBrowser, $functionName, array $data = [], array $otherOptions = [])
    {
        $entityBrowser = $this->getEntityBrowser($entityBrowser);
        if (is_null($entityBrowser)) {
            throw new \Exception('Invalid entity could not be found.');
        }

        $retryCount = isset($otherOptions['retryCount'])?$otherOptions['retryCount']:-1;
        $otherOptions['retryCount'] = $retryCount + 1;

        try {
            $result = $this->executeEntityFunctionInternal($entityBrowser, $functionName, $data, $this->connectionToken, $otherOptions);
            return $result;
        } catch (\Exception $exc) {
            if ($retryCount < $this->maxRetries) {
                return $this->executeEntityFunctionInternal($entityBrowser, $functionName, $data, $otherOptions);
            } else {
                throw $exc;
            }
        }
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */
        
    public function executeEntityItemFunction($entityBrowser, $id, $functionName, array $data = [], array $otherOptions = [])
    {
        $entityBrowser = $this->getEntityBrowser($entityBrowser);
        if (is_null($entityBrowser)) {
            throw new \Exception('Invalid entity could not be found.');
        }

        $retryCount = isset($otherOptions['retryCount'])?$otherOptions['retryCount']:-1;
        $otherOptions['retryCount'] = $retryCount + 1;

        try {
            $result = $this->executeEntityItemFunctionInternal($entityBrowser, $id, $functionName, $data, $this->connectionToken, $otherOptions);
            return $result;
        } catch (\Exception $exc) {
            if ($retryCount < $this->maxRetries) {
                return $this->executeEntityItemFunctionInternal($entityBrowser, $id, $functionName, $data, $otherOptions);
            } else {
                throw $exc;
            }
        }
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */
        
    /**
     * Creates the data table structure equivalent of the object schema in this connection driver.
     *
     * @param mixed $entityBrowser
     * @param array $otherOptions
     * @return void
     */
    public function ensureDataStructure($entityBrowser, array $otherOptions = [])
    {
        $entityBrowser = $this->getEntityBrowser($entityBrowser);
        if (is_string($entityBrowser) || is_null($entityBrowser)) {
            throw new \Exception("Invalid entity '{$entityBrowser}' could not be found in {$this->getIdentifier()}");
        }

        // If this entity is cached to another driver
        $skipCache = isset($otherOptions['$skipCache'])?''.$otherOptions['$skipCache']:'0';
        $skipCache = $skipCache == '1'?TRUE:FALSE;
        if ($entityBrowser->shouldCacheData() && ($this->getIdentifier() != $entityBrowser->getCachingDriverName()) && $skipCache == FALSE) {
            // Load the driver instead
            $cacheDriver = $this->loadDriver($entityBrowser->getCachingDriverName());
            
            // Return the results from the cache driver.
            $args = func_get_args();
            $args[0] = strtolower("{$this->getIdentifier()}__{$entityBrowser->getInternalName()}");
            $return = $cacheDriver->ensureDataStructure(...$args);
            return $return;
        }
        
        // If this entity's storage is delegated to another driver.
        if ($entityBrowser->delegatesStorage() && ($this->getIdentifier() != $entityBrowser->getDelegateDriverName())) {
            // Load the driver instead
            $delegateDriver = $this->loadDriver($entityBrowser->getDelegateDriverName());
            
            // Return the results from the cache driver.
            $args = func_get_args();
            $args[0] = strtolower("{$this->getIdentifier()}__{$entityBrowser->getInternalName()}");
            $return = $delegateDriver->ensureDataStructure(...$args);
            return $return;
        }

        return $this->ensureDataStructureInternal($entityBrowser, $this->connectionToken, $otherOptions);
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */
        
    public function getCacheReference($entityBrowser, array $otherOptions = [])
    {
        $entityBrowser = $this->getEntityBrowser($entityBrowser);
        if (is_string($entityBrowser) || is_null($entityBrowser)) {
            throw new \Exception("Invalid entity '{$entityBrowser}' could not be found in {$this->getIdentifier()}");
        }

        // Get the driver
        $driverName = $entityBrowser->getCacheDriverName();

        return $this->ensureDataStructureInternal($entityBrowser, $this->connectionToken, $otherOptions);
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */
    
    /**
     * Returns the resource identified by <code>$id</code>
     *
     * @param EntityDefinitionBrowser $entityBrowser A reference to the entity datasource.
     * @param String $id The unique identifier of the resource to be retrieved.
     * @param mixed $select Comma-separated string or array of the resource fields to return.
     * @param string $expands Comma-separated string or array of the sub-resource fields to return.
     * @param array $otherOptions Key-Value array of other query parameters.
     * @return \stdClass
     */
    public function getItemById($entityBrowser, $id, $select, $expands = '', $otherOptions = [])
    {
        $entityBrowser = $this->getEntityBrowser($entityBrowser);
        
        if (is_null($entityBrowser)) {
            throw new \Exception('Invalid entity could not be found.');
        }

        if($entityBrowser->getIdField()->isInteger() && !is_numeric ($id)){
            throw new \Exception("Non-numberic value passed as filter criteria for '{$entityBrowser->getIdField()->getDataType()}' field '{$entityBrowser->getIdField()->getDisplayName()}'");
        }
        
        $result = $this->getItemsByIds($entityBrowser, [$id], $select, $expands, $otherOptions);

        reset($result);
        $first_key = key($result);

        return count($result) > 0 ? $result[$first_key] : null;
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */
    
    /**
     * Returns the resources identified by the array ids in $ids.
     *
     * @param EntityDefinitionBrowser $entityBrowser A reference to the entity datasource.
     * @param String $id The unique identifier of the resource to be retrieved.
     * @param mixed $select Comma-separated string or array of the resource fields to return.
     * @param string $expands Comma-separated string or array of the sub-resource fields to return.
     * @param array $otherOptions Key-Value array of other query parameters.
     * @return \stdClass
     */
    public function getItemsByIds($entityBrowser, $ids, $select, $expands = '', $otherOptions = [])
    {
        $entityBrowser = $this->getEntityBrowser($entityBrowser);
        if (is_null($entityBrowser) || is_string($entityBrowser)) {
            throw new \Exception("Invalid entity '{$entityBrowser}' could not be found.");
        }

        $result = $this->getItemsByFieldValues($entityBrowser, $entityBrowser->getIdField(), $ids, $select, $expands, $otherOptions);
        return $result;
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */
    

    /**
     * Undocumented function
     *
     * @param EntityDefinitionBrowser | String $entityBrowser  A reference to the entity datasource.
     * @param EntityFieldDefinition $entityField A reference to the resource field definition to be used as match criteria.
     * @param array $values An array of values to be used as search criteria.
     * @param array | String $select Comma-separated string or array of the resource fields to return.
     * @param string $expands Comma-separated string or array of the sub-resource fields to return.
     * @param array $otherOptions Key-Value array of other query parameters.
     * @return \stdClass
     */
    public function _getItemsByFieldValues($entityBrowser, EntityFieldDefinition $entityField, array $values, $select, $expands = '', &$otherOptions = [])
    {
        $entityBrowser = $this->getEntityBrowser($entityBrowser);
        if (is_string($entityBrowser) || is_null($entityBrowser)) {
            throw new \Exception("Invalid entity '{$entityBrowser}' could not be found in {$this->getIdentifier()}");
        }

        // implode the values based on the type of the field
        $implosion = '';
        $type = $entityField->getDataType();
        switch ($type) {
            case 'bigint':
            case 'int': {
                    $implosion = implode(',', $values);
                    break;
            }
            default: {
                    $implosion = implode("_x0027_,_x0027_", $values);
                    $implosion = EncoderDecoder::escape($implosion);
                    $implosion = str_replace("_x0027_", "'", $implosion);
                    $implosion = "'{$implosion}'";
            }
        }

        $this->connectionToken = isset($otherOptions['$connectionToken'])?$otherOptions['$connectionToken']: $this->connectionToken;
        $otherOptions['$connectionToken'] = &$this->connectionToken;

        $additionalFilter = isset($otherOptions['more_filter']) ? "({$otherOptions['more_filter']}) and " : '';
        $result = $this->getItems($entityBrowser, $select, "{$additionalFilter}{$entityField->getDisplayName()} IN({$implosion})", $expands, $otherOptions);
        return $result;
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */
    

    public function getItemsByFieldValues2($entityBrowser, EntityFieldDefinition $entityField, array $values, $select, $expands = '', &$otherOptions = []){
        $max_chunk_size = $this->getMaxInToOrConversionChunkSize();
        $expand_chunks = array_chunk($values, $max_chunk_size);
        $data = [];

        foreach ($expand_chunks as $chunk) {
            $chunkResult = $this->_getItemsByFieldValues($entityBrowser, $entityField, $chunk, $select, $expands, $otherOptions);
            $data = array_merge($data, $chunkResult);
        }

        return $data;
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */
    

    public function getItemsByFieldValues($entityBrowser, $fieldName, array $values, $select, $expands = '', &$otherOptions = []){
        $entityBrowser = $this->getEntityBrowser($entityBrowser);
        if (is_null($entityBrowser)) {
            throw new \Exception('Invalid entity could not be found.');
        }

        
        $entityField = ($fieldName instanceof EntityFieldDefinition)? $fieldName: $entityBrowser->getFieldByDisplayName($fieldName);

        return $this->getItemsByFieldValues2($entityBrowser, $entityField, $values, $select, $expands, $otherOptions);
    }
    
    /**
     * Returns an array of entity items.
     *
     * @param [type] $entityBrowser
     * @param [type] $fields
     * @param [type] $filter
     * @param string $expandeds
     * @param array $otherOptions
     * @param array $performance
     * @return void
     */
    public function getItems($entityBrowser, $fields = 'Id', $filter = '', $expandeds = '', $otherOptions = [], &$performance = [])
    {
        $getItemArgs = func_get_args();
        $fieldsBeforeManipulation = $fields;
        $entityBrowser = $this->getEntityBrowser($entityBrowser);
        if (is_string($entityBrowser) || is_null($entityBrowser)) {
            throw new \Exception("Invalid entity '{$entityBrowser}' could not be found in {$this->getIdentifier()}");
        }       

        $scope = $this;
        $entityBrowser = $this->setStrategies($entityBrowser);
        
        $retryCount = isset($otherOptions['retryCount'])?$otherOptions['retryCount']:0;
        $retryCount = $retryCount + 1;
        $otherOptions['retryCount'] = $retryCount;
        $getItemArgs[4] = &$otherOptions;

        // Handle method redirection
        if($entityBrowser->shouldRedirectRead()){
            $providerInfo = $entityBrowser->getReadProviderInfo();
            $driver = $this;
            if($providerInfo->driver != $this->getIdentifier()){
                $driver = $this->loadDriver($providerInfo->driver);
            }
            
            // Execute the update provider's update method.
            // $args = func_get_args();
            $getItemArgs[0] = $providerInfo->entity;
            // $getItemArgs[4]['retryCount'] = $retryCount;
            $return = NULL;
            
            try {
                $return = $driver->getItems(...$getItemArgs);
            } 
            // May be the datastructure is faulty
            catch(\Exception $exc){
                $driver->ensureDataStructure($getItemArgs[0]);
                $return = $driver->getItems(...$getItemArgs);
            }
            
            return $return;
        }

        // If this entity is cached to another driver
        $skipCache = isset($otherOptions['$skipCache'])?''.$otherOptions['$skipCache']:'0';
        $skipCache = $skipCache == '1'?TRUE:FALSE;
        if ($entityBrowser->shouldCacheData() && ($this->getIdentifier() != $entityBrowser->getCachingDriverName()) && $skipCache == FALSE) {
            // Load the driver instead
            $cacheDriver = $this->loadDriver($entityBrowser->getCachingDriverName());
            
            // Return the results from the cache driver.
            // $args = func_get_args();
            $getItemArgs[0] = strtolower("{$this->getIdentifier()}__{$entityBrowser->getInternalName()}");
            // $getItemArgs[4]['retryCount'] = $retryCount;
            $return = NULL;
            
            try {
                // Try to fetch the _IsUpdate field in the cache.
                $f = is_string($getItemArgs[1])?\str_replace(' ', '', $getItemArgs[1]):implode(',', $getItemArgs[1]);
                $f = strlen($f) > 0 ? "{$f},_IsUpdated":'_IsUpdated';
                $getItemArgs[1] = $f;
                $return = $cacheDriver->getItems(...$getItemArgs);
            } 

            // An invalid field has been selected.
            catch(InvalidFieldSelectedException $exc){
                throw $exc;
            }
            // May be the data structure is faulty
            catch(\Exception $exc){
                $cacheDriver->ensureDataStructure($getItemArgs[0]);
                $this->syncFromDate($entityBrowser);

                if($retryCount < $this->maxRetries){
                    $return = $cacheDriver->getItems(...$getItemArgs);
                } else{
                    throw $exc;
                }
            }
            return $return;
        }
        
        // If this entity's storage is delegated to another driver.
        if ($entityBrowser->delegatesStorage() && ($this->getIdentifier() != $entityBrowser->getDelegateDriverName())) {
            // Load the driver instead
            $delegateDriver = $this->loadDriver($entityBrowser->getDelegateDriverName());
            $return = NULL;
            
            // Return the results from the cache driver.
            $getItemArgs[0] = strtolower("{$this->getIdentifier()}__{$entityBrowser->getInternalName()}");
           
            try {
                $return = $delegateDriver->getItems(...$getItemArgs);
            } 
            // May be the datastructure is faulty
            catch(\Exception $exc){
                $delegateDriver->ensureDataStructure($getItemArgs[0]);
                $this->syncFromDate($entityBrowser);
                if($retryCount < $this->maxRetries) {
                    $return = $delegateDriver->getItems(...$getItemArgs);
                } else {
                    throw $exc;
                }
            }
            return $return;
        }

        // Take note of the preferred date format
        if(isset($otherOptions['$dateFormat'])){
            $this->preferredDateFormat = $otherOptions['$dateFormat'];
        }

        if(isset($otherOptions['$dateTimeFormat'])){
            $this->preferredDateTimeFormat = $otherOptions['$dateTimeFormat'];
        }
        
        // Set the default limit
        if (!isset($otherOptions['$top'])) {
            $otherOptions['$top'] = 100;
        } else {
            $top = intval($otherOptions['$top']);
            $otherOptions['$top'] = $top < 1 ? 100 : $top;
        }

        if (!isset($otherOptions['$pageNumber'])) {
            $otherOptions['$pageNumber'] = 1;
        } else {
            $pageNumber = intval($otherOptions['$pageNumber']);
            $otherOptions['$pageNumber'] = $pageNumber < 1 ? 1 : $pageNumber;
        }
        
        if (!isset($otherOptions['$skip'])) {
            $otherOptions['$skip'] = 0;
        } else {
            $skip = intval($otherOptions['$skip']);
            $otherOptions['$skip'] = $skip < 0 ? 0 : $skip;
        }

        if (!isset($otherOptions['$pageSize'])) {
            $otherOptions['$pageSize'] = $otherOptions['$top'];
        } else {
            $pageSize = intval($otherOptions['$pageSize']);
            $otherOptions['$pageSize'] = $pageSize < 1 ? $otherOptions['$top'] : $pageSize;
        }

        if (!isset($otherOptions['$distinct'])) {
            $otherOptions['$distinct'] = [];
        } else {
            $distinct = is_string($otherOptions['$distinct'])?str_replace(' ', '', $otherOptions['$distinct']):[];
            $distinctRe = [];
            $distinctEx = is_string($otherOptions['$distinct'])?explode(',', $distinct):$otherOptions['$distinct'];
            foreach ($distinctEx as $dis) {
                $distinctRe[] = $entityBrowser->getFieldByDisplayName($dis)->getInternalName();
            }
            $otherOptions['$distinct'] = array_unique($distinctRe);
        }

        //TODO: Stop overriding $orderBy and implement code to rename fields.
        $otherOptions['$orderBy'] = (isset($otherOptions['$orderBy']) && strlen(trim($otherOptions['$orderBy'])) > 0)? $otherOptions['$orderBy']: "{$entityBrowser->getIdField()->getDisplayName()} asc";
        
        // Set the default filter
        $filter = trim(strlen(trim($filter)) < 1 ? $this->getDefaultFilter() : $filter);
        $includeDeleted = isset($otherOptions['$includeDeleted']) && ($otherOptions['$includeDeleted'] == '1')?TRUE:FALSE;

        // If not stated otherwise, try to exclude already deleted items.
        if(!$includeDeleted) {
            $isDeletedField = $entityBrowser->hasField('IsDeleted');
            if($isDeletedField != FALSE) {
                $filter = $filter.(strlen($filter) > 0 ? ' and IsDeleted eq $FALSE$':'IsDeleted eq $FALSE$');
            }
        }

        // Convert the select parameter into an array.
        $fields = is_array($fields) ? $fields : preg_split('@(?:\s*,\s*|^\s*|\s*$)@', $fields, null, PREG_SPLIT_NO_EMPTY);
        $driverScope = $this;

        // Cleanse the $select parameter
        $select = static::getCurrentSelections($entityBrowser, $fields);

        // Process the $filter statement
        $filterExpression = FilterProcessor::convert($entityBrowser, $filter, null, $this->getStringer());
        
        // Convert the $expand parameter into an array
        $expands = [];
        $expandeds = is_array($expandeds) ? $expandeds : preg_split('@(?:\s*,\s*|^\s*|\s*$)@', $expandeds, null, PREG_SPLIT_NO_EMPTY);

        $yyy = [];
        foreach ($expandeds as &$expand) {
            if (($pos = strpos($expand, '/')) > 0) {
                $key = substr($expand, 0, $pos);
                $val = substr($expand, $pos + 1);
                $expand = $key;

                if (isset($expands[$expand])) {
                    if (!isset($expands[$expand]['expand'])) {
                        $expands[$expand]['expand'] = [];
                    }

                    $yyy = &$expands[$expand]['expand'];
                }

                if (!isset($yyy[$key])) {
                    $yyy[$key] = [$val];
                } else {
                    $yyy[$key][] = $val;
                }

                $expands[$expand]['expand'] = $yyy;
            }
            $fieldInfo = $entityBrowser->getFieldByDisplayName($expand);

            //check if this field can be expaanded
            if ($fieldInfo->isExpandable()) {
                // Get a reference to the remote driver
                $remoteDriver = $fieldInfo->getRemoteDriver();

                // $remoteEntityBrowser = $remoteDriver->entitiesByDisplayName[$fieldInfo->getRemoteEntityName()];
                $remoteEntityBrowser = isset($remoteDriver->entitiesByDisplayName[$fieldInfo->getRemoteEntityName()])? $remoteDriver->entitiesByDisplayName[$fieldInfo->getRemoteEntityName()]:null;

                // TODO: Review this later. Problem is because of cached entities
                if (!is_null($remoteEntityBrowser)) {
                    $remoteField = $remoteEntityBrowser->getFieldByDisplayName($fieldInfo->getRelatedForeignFieldName());

                    // Get the selected subfields of this expanded field
                    $expandX = self::getCurrentExpansions($entityBrowser, $expand, $fields);

                    // Ensure that the lookup field of the remote entity is included in the remote entity's selection
                    if (!in_array($remoteField->getDisplayName(), $expandX)) {
                        $expandX[] = $remoteField->getDisplayName();
                    }

                    $ex0 = isset($expands[$expand]) ? $expands[$expand] : [];
                    $ex1 = array_merge(['select' => $expandX, 'ids' => [], 'info' => $fieldInfo, 'remoteFieldInfo' => $remoteField, 'data' => []], $ex0);
                    $expands[$expand] = $ex1;

                    // Ensure the field this expansion depends on is selected.
                    $localFieldName = $fieldInfo->getRelatedLocalFieldName();
                    if (!in_array($localFieldName, $select)) {
                        $select[] = $localFieldName;
                    }
                } else {
                    throw new \Exception("Referenced entity '{$fieldInfo->getRemoteEntityName()}' in field '{$fieldInfo->getDisplayName()}' of '{$fieldInfo->getParent()->getDisplayName()}' could not be found in {$fieldInfo->getRemoteDriver()->getIdentifier()}.");
                    // var_dump('ULL REMOTE', $fieldInfo->getRemoteEntityName(), $fieldInfo->getDisplayName(), $fieldInfo->getParent()->getParent()->getIdentifier(), $fieldInfo->getRemoteDriver()->getIdentifier(), $fieldInfo->getParent()->getDisplayName());
                }
            } else {
                throw new \Exception("Field {$expand} can not be expanded.");
            }
        }

        // Fetch the data of matching records
        $select = array_unique($entityBrowser->getFieldInternalNames($select));
        $dateFields = $entityBrowser->getFieldsOfTypeByInternalName(['date', 'datetime'], $select);

        $result = NULL;
        try {
            $result = $this->getItemsInternal($entityBrowser, $this->connectionToken, $select, EncoderDecoder::unescapeall("{$filterExpression}"), $expands, $otherOptions);
        } catch(\Exception $giiExc) {
            // watchdog("OUTDATED AAAA", "abc: {$giiExc->getMessage()}");
            throw $giiExc;
        }
        
        if (!is_null($result)) {
            $select_map = $entityBrowser->getFieldsByInternalNames($select);
            $dataIsOld = FALSE;
            $oldRecords = [];

            // DeterminE if this is the cache
            // echo $entityBrowser->getDisplayName();
            $hasField = $entityBrowser->hasField('_IsUpdated');
            $isACache = !is_null($hasField);
            
            foreach($result as $key => &$record) { //use ($entityBrowser, $select_map, &$expands, $dateFields, &$dataIsOld, $isACache) {
                // Try to update the cache before fetching the data if it is old.
                // $record = &$result[$key];
                if($isACache && $retryCount < $this->maxRetries){
                    if($retryCount < ($this->maxRetries / 2)){
                        if(is_object($record) && property_exists($record, '_IsUpdated') && $record->_IsUpdated != TRUE){
                            $oldRecords[] = $record->{$entityBrowser->getIdField()->getInternalName()};
                            $dataIsOld = TRUE;
                            continue;
                        } else if(array_key_exists('_IsUpdated', $record) && $record['_IsUpdated'] != TRUE){
                            $oldRecords[] = $record[$entityBrowser->getIdField()->getInternalName()];
                            $dataIsOld = TRUE;
                            continue;
                        } 
                        // Be quiet about it.
                        else {}
                    } else {
                        // TODO: find an environment agnostic way to log slow queries
                        watchdog("SLOW & OUTDATED QUERIES", "{$retryCount} {$entityBrowser->getDisplayName()}");
                    }
                }

                // Avoid displaying the _IsUpdated field
                if(array_key_exists('_IsUpdated', $select_map)){
                    unset($select_map['_IsUpdated']);
                }

                $record = $entityBrowser->renameFields($record, $select_map);
                foreach ($dateFields as $dateField) {
                    $dateFieldName = $dateField->getDisplayName();

                    if (is_object($record) && !is_null($record) && !is_null($record->{$dateFieldName})) {
                        $dateVal =  $this->parseDateValue($record->{$dateFieldName});
                        $record->{$dateFieldName} = ( $dateField->isDateTime() ? $dateVal->format($this->preferredDateTimeFormat) : $dateVal->format($this->preferredDateFormat) );
                    } elseif (is_array($record)) {
                        foreach ($record as &$innerRecord) {
                            if (is_object($innerRecord) && !is_null($innerRecord) && !is_null($innerRecord->{$dateFieldName})) {
                                $dateVal =  $this->parseDateValue($innerRecord->{$dateFieldName});
                                $innerRecord->{$dateFieldName} = ( $dateField->isDateTime() ? $dateVal->format($this->preferredDateTimeFormat) : $dateVal->format($this->preferredDateFormat) );
                            } else {
                                throw new \Exception('Error on date field');
                            }
                        }
                    }
                }

                // Prepare to fetch expanded data
                foreach ($expands as $expand_key => &$expand_val) {
                    $fieldInfo = $expand_val['info'];
                    $relatedKey = $fieldInfo->getRelatedLocalFieldName();
                    $ids = $entityBrowser->fetchFieldValues($record, $relatedKey);
                    if (is_array($ids)) {
                        $expand_val['ids'] = array_merge($expand_val['ids'], $ids);
                    } else {
                        $expand_val['ids'][] = $ids;
                    }
                }
            };

            // Attempt to update the cache and then retry.
            if($dataIsOld == TRUE){
                $this->syncByRecordIds($entityBrowser->getCachedObject(), $oldRecords);
                return $this->getItems(...$getItemArgs);
            }

            // Fetch the related field values in one sweep
            array_walk($expands, function (&$expand_val, $expand_key) use ($driverScope, $skipCache, $includeDeleted) {
                $localField = $expand_val['info'];
                $remoteField = $expand_val['remoteFieldInfo'];
                $remoteEntityBrowser = $remoteField->getParent();
                $remoteDriver = $remoteEntityBrowser->getParent();
                $otherOptions = ['$top' => 1000000000];

                // Propagate $skipCache parameter
                if($skipCache){
                    $otherOptions['$skipCache'] = '1';
                }

                if($includeDeleted){
                    $otherOptions['$includeDeleted'] = '1';
                }

                if (!is_null($localField->getRemoteEntityFilter())) {
                    $otherOptions['more_filter'] = $localField->getRemoteEntityFilter();
                }

                // Remove duplicates
                $expand_val['ids'] = array_unique($expand_val['ids']);

                // Divide the keys into manageable chunks
                $max_chunk_size = $this->getMaxInToOrConversionChunkSize();
                $expand_chunks = array_chunk($expand_val['ids'], $max_chunk_size);
                $data = NULL;

                $ex = isset($expand_val['expand'][$localField->getDisplayName()]) ? $expand_val['expand'][$localField->getDisplayName()] : [];

                foreach ($expand_chunks as $chunk) {
                    $chunkResult = $remoteDriver->getItemsByFieldValues($remoteEntityBrowser, $remoteField, $chunk, $expand_val['select'], implode(',', $ex), $otherOptions);

                    // Combine this chunk result with previous chunk results
                    $data = $remoteEntityBrowser->mergeExpansionChunks($data, $chunkResult, $localField, $remoteField);
                }
                
                $expand_val['data'] = $data;
            });

            // Attach the fetched values to their corresponding parents
            array_walk($result, function (&$record, $recordIndex) use ($entityBrowser, $expands) {
                // Prepare to fetch expanded data
                foreach ($expands as $expand_key => &$expand_val) {
                    $fieldInfo = &$expand_val['info'];
                    $record = $entityBrowser->joinExpansionToParent($recordIndex, $record, $fieldInfo, $expand_val['data']);
                }
            });
        } else {
            if ($retryCount < $this->maxRetries) {
                watchdog("RETRY COUNT: ", "{$retryCount} OF {$this->maxRetries}");
                $otherOptions['retryCount'] = $retryCount;
                return $this->getItems(...$getItemArgs);
                // return $this->getItems($entityBrowser, $fields, $filter, $expandeds, $otherOptions) ;
            } else {
                throw new \Exception("Failed to get data from Entity {$entityBrowser->getDisplayName()}");
            }
        }
        
        return $result;
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */
    

    public function updateItem($entityBrowser, $id, \stdClass $object, array $otherOptions = [])
    {
        $updateItemArgs = func_get_args();
        $entityBrowser = $this->getEntityBrowser($entityBrowser);        
        if (is_string($entityBrowser) || is_null($entityBrowser)) {
            throw new \Exception("Invalid entity '{$entityBrowser}' could not be found in {$this->getIdentifier()}");
        }

        // Handle method redirection
        if($entityBrowser->shouldRedirectUpdate()){
            $providerInfo = $entityBrowser->getUpdateProviderInfo();
            $driver = $this;
            if($providerInfo->driver != $this->getIdentifier()){
                $driver = $this->loadDriver($providerInfo->driver);
            }
            
            // Execute the update provider's update method.
            // $args = func_get_args();
            $updateItemArgs[0] = $providerInfo->entity;
            $return = $driver->updateItem(...$updateItemArgs);
            return $return;
        }
        
        // Handle storage delegation
        if ($entityBrowser->delegatesStorage() && ($this->getIdentifier() != $entityBrowser->getDelegateDriverName())) {
            // Load the driver instead
            $delegateDriver = $this->loadDriver($entityBrowser->getDelegateDriverName());
            
            // Return the results from the cache driver.
            // $args = func_get_args();
            $updateItemArgs[0] = strtolower("{$this->getIdentifier()}__{$entityBrowser->getInternalName()}");
            $return = $delegateDriver->updateItem(...$updateItemArgs);
            return $return;
        }

        $entityBrowser = $this->setStrategies($entityBrowser);

        $retryCount = isset($otherOptions['retryCount'])?$otherOptions['retryCount']:0;
        $otherOptions['retryCount'] = $retryCount + 1;
        if(!isset($updateItemArgs[3])){
            $updateItemArgs[3] = [];
        }
        $updateItemArgs[3]['retryCount'] = $otherOptions['retryCount'];

        // Strip-out invalid fields
        $setFields = $entityBrowser->getValidFieldsByDisplayName(array_keys(get_object_vars($object)));

        $obj = new \stdClass();
        foreach ($setFields as $setField) {
            // avoid objects
            if (!is_object($object->{$setField->getDisplayName()}) && !is_array($object->{$setField->getDisplayName()})) {
                $obj->{$setField->getDisplayName()} = $object->{$setField->getDisplayName()};
            }
        }
        
        if($entityBrowser->shouldManageTimestamps()){
            $now = new \DateTime();
            if(property_exists($obj, 'Created')){
                unset($obj->Created);
            }
            $obj->Modified = $now->format('Y-m-d\TH:i:s');
        }

        if (!isset($otherOptions['$select'])) {
            $otherOptions['$select'] = EntityFieldDefinition::getDisplayNames($setFields);
        } else {
            $abccd = is_string($otherOptions['$select']) ? explode(',', $otherOptions['$select']) : (is_array($otherOptions['$select']) ? $otherOptions['$select'] : []);
            $abccc = array_merge($abccd, EntityFieldDefinition::getDisplayNames($setFields));
            $otherOptions['$select'] = array_unique($abccc);
        }
        
        $updateItemArgs[3]['$select'] = $otherOptions['$select'];
        
        if (!isset($otherOptions['$expand'])) {
            $otherOptions['$expand'] = '';
        }
        $updateItemArgs[3]['$expand'] = $otherOptions['$expand'];

        // If there is need to specifically update the cache copy alone
        $cacheOnly = (isset($otherOptions['$cacheOnly']) && $otherOptions['$cacheOnly'] == '1')? TRUE:FALSE;
        if (
            $entityBrowser->shouldCacheData() && 
            ($this->getIdentifier() != $entityBrowser->getCachingDriverName()) && 
            $cacheOnly
        ) {
            // Load the driver instead
            $cacheDriver = $this->loadDriver($entityBrowser->getCachingDriverName());
            
            // Refactor the arguments to target the cache.
            // $updateItemArgs = func_get_args();
            $updateItemArgs[0] = strtolower("{$this->getIdentifier()}__{$entityBrowser->getInternalName()}");

            // Mark for pending update so that irrespective of the created or modified time, this record will be updated.
            $updateItemArgs[2]->_IsUpdated = FALSE;
            try {
                return $cacheDriver->updateItem(...$updateItemArgs);
            } 
            // May be the datastructure is faulty
            catch(\Exception $exc){
                $cacheDriver->ensureDataStructure($updateItemArgs[0]);
                return $cacheDriver->updateItem(...$updateItemArgs);
            }
        }

        try {
            if ($this->updateItemInternal($entityBrowser, $this->connectionToken, $id, $obj, $otherOptions)) {
                
                // Try to write the update to the cache also
                try {
                    if ($entityBrowser->shouldCacheData() && ($this->getIdentifier() != $entityBrowser->getCachingDriverName())) {
                        // Load the driver instead
                        $cacheDriver = $this->loadDriver($entityBrowser->getCachingDriverName());
                        
                        // Refactor the arguments to target the cache.
                        // $updateItemArgs = func_get_args();
                        $updateItemArgs[0] = strtolower("{$this->getIdentifier()}__{$entityBrowser->getInternalName()}");

                        // Try to sync the cache
                        $now = (new \DateTime())->format('Y-m-d');

                        // Mark for pending update so that irrespective of the created or modified time, this record will be updated.
                        $updateItemArgs[2]->{'_IsUpdated'} = FALSE;
                        $updateItemArgs[3]['$cacheOnly'] = '1';
                        try {
                            $cacheDriver->updateItem(...$updateItemArgs);
                            $this->syncByRecordIds($entityBrowser, [$id]);
                        } 
                        // May be the datastructure is faulty
                        catch(\Exception $exc){
                            $cacheDriver->ensureDataStructure($updateItemArgs[0]);
                            $cacheDriver->updateItem(...$updateItemArgs);
                            $this->syncByRecordIds($entityBrowser, [$id]);
                        }
                    }
                } 
                // Fail silently
                catch(\Exception $exp){}

                // Return the item that was just updated.
                // $d = [$entityBrowser->getDisplayName(), $id, $updateItemArgs[3]['$select'], $updateItemArgs[3]['$expand'], $updateItemArgs[3]];
                // return $this->getIdentifier();
                // $updateItemArgs[0] = $entityBrowser->getDisplayName();
                // $getItemById = $this->getItemById;
                // return $otherOptions['$select'];
                return $this->getItemById($entityBrowser, $id, $otherOptions['$select'], $otherOptions['$expand'], $otherOptions);//$updateItemArgs[3]['$select'], $updateItemArgs[3]['$expand'], []);
            }
        } catch (\Exception $exc) {
            if ($retryCount < $this->maxRetries) {
                $updateItemArgs[0] = $entityBrowser;
                return $this->updateItem($entityBrowser, $id, $object, $otherOptions);
            } else {
                throw $exc;
            }
        }
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */
    

    public function createItems($entityBrowser, array $objects, array $otherOptions = []){
        // Don't bother if the array is empty
        if(count($objects) < 1){ 
            throw new \Exception('List of items to create cannot be empty.');
        }

        $entityBrowser = $this->getEntityBrowser($entityBrowser);
        if (is_string($entityBrowser) || is_null($entityBrowser)) {
            throw new \Exception("Invalid entity '{$entityBrowser}' could not be found in {$this->getIdentifier()}");
        }        

        // Handle method redirection
        if($entityBrowser->shouldRedirectCreate()){
            $providerInfo = $entityBrowser->getCreateProviderInfo();
            $driver = $this;
            if($providerInfo->driver != $this->getIdentifier()) {
                $driver = $this->loadDriver($providerInfo->driver);
            }

            // Execute the provider's create method.
            $args = func_get_args();
            $args[0] = $providerInfo->entity;
            $return = $driver->createItems(...$args);
            return $return;
        }

        // Handle storage delegation
        if ($entityBrowser->delegatesStorage() && ($this->getIdentifier() != $entityBrowser->getDelegateDriverName())) {

            // Load the driver instead
            $delegateDriver = $this->loadDriver($entityBrowser->getDelegateDriverName());
            
            // Return the results from the cache driver.
            $args = func_get_args();
            $args[0] = strtolower("{$this->getIdentifier()}__{$entityBrowser->getInternalName()}");
            $args[2]['$setId'] = '1';
            $return = $delegateDriver->createItems(...$args);
            return $return;
        }
                
        $entityBrowser = $this->setStrategies($entityBrowser);
        $retryCount = isset($otherOptions['retryCount'])?$otherOptions['retryCount']:0;
        $otherOptions['retryCount'] = $retryCount + 1;

        // Strip-out invalid fields
        $otherOptions['processedItems'] = isset($otherOptions['processedItems'])?$otherOptions['processedItems']:[];
        $reses = &$otherOptions['processedItems'];
        foreach($objects as $objId => &$object){    
            if(property_exists($object,'__Created_Internal') && $object->__Created_Internal = true) {
                continue;
            }

            $object = (object)$object;    
            set_time_limit(20);   
            $setFields = $entityBrowser->getValidFieldsByDisplayName(array_keys(get_object_vars($object)));

            $obj = new \stdClass();
            foreach ($setFields as $setField) {
                // avoid objects
                if (!is_object($object->{$setField->getDisplayName()}) && !is_array($object->{$setField->getDisplayName()})) {
                    $obj->{$setField->getDisplayName()} = $object->{$setField->getDisplayName()};
                }
            }

            // If timestamp management is enabled on the entity, set it.
            if($entityBrowser->shouldManageTimestamps()){
                $now = new \DateTime();
                $obj->Created = $now->format('Y-m-d\TH:i:s');
                $obj->Modified = $now->format('Y-m-d\TH:i:s');
            }

            // Prepare the selected fields for the return
            if (!isset($otherOptions['$select'])) {
                $otherOptions['$select'] = EntityFieldDefinition::getDisplayNames($setFields);
            } else {
                $abccd = is_string($otherOptions['$select']) ? explode(',', $otherOptions['$select']) : (is_array($otherOptions['$select']) ? $otherOptions['$select'] : []);
                $abccc = array_merge($abccd, EntityFieldDefinition::getDisplayNames($setFields));
                $otherOptions['$select'] = array_unique($abccc);
            }

            // Prepare the expanded fields for the returned value
            if (!isset($otherOptions['$expand'])) {
                $otherOptions['$expand'] = '';
            }

            // Check for duplicates
            $res = new \stdClass();     
            $duplicates = [];
            $duplicateFilter = isset($otherOptions['$duplicateFilter'])?$otherOptions['$duplicateFilter']:'';
            if(strlen($duplicateFilter) > 0){
                // Try getting the item first
                $duplicateFilter = preg_replace_callback(
                    '|(\{\}\->)([\w]+[\w\d]*)|',
                    function ($matches) use($object) {
                        if(is_object($object) && property_exists($object, $matches[2])) {
                            return $object->{$matches[2]};
                        } 
                        else if(is_array($object) && isset($object[$matches[2]])){
                            return $object[$matches[2]];
                        } else {

                            throw new \Exception("The requested arrow property '{$matches[2]}' could not be found");
                        }
                    },
                    $duplicateFilter
                );

                $duplicates = $this->getItems($entityBrowser, "{$entityBrowser->getIdField()->getDisplayName()}", $duplicateFilter);
            }

            // If a duplicate exists, pretend the record was just created.
            if(count($duplicates) > 0){
                $res->d = $duplicates[0]->Id;

                // Silently do an update if requested
                $updateMatches = isset($otherOptions['$updateMatches'])?$otherOptions['$updateMatches']: FALSE;
                if($updateMatches){ 
                    $this->updateItemInternal($entityBrowser, $this->connectionToken, $res->d, $obj, $otherOptions);
                }
                $res->success = true;
            } 

            // Invoke the internal create method.
            else {
                $res = $this->createItemInternal($entityBrowser, $this->connectionToken, $obj, $otherOptions);
            }
            
            // Requery and return the created object.
            if (property_exists($res, 'd') && $res->success == true) {
                $args = func_get_args();
                $object->__Created_Internal = true;

                // Try to write the update to the cache also
                try {
                    if ($entityBrowser->shouldCacheData() && ($this->getIdentifier() != $entityBrowser->getCachingDriverName()) && count($duplicates) < 1) {
                        // Load the driver instead
                        $cacheDriver = $this->loadDriver($entityBrowser->getCachingDriverName());
                        
                        // Refactor the arguments to target the cache.
                        $args[0] = strtolower("{$this->getIdentifier()}__{$entityBrowser->getInternalName()}");
                        $args[1] = $args[1][$objId];
                        $args[1]->Id = $res->d;

                        // Since we ommit items that are deleted, set this one as not deleted
                        if($entityBrowser->hasField('IsDeleted')){
                            $args[1]->IsDeleted = FALSE;
                        }

                        // Set record as pending update. It is possible that some fields will be calculated by the remote source
                        $args[1]->_IsUpdated = FALSE;

                        $args[2]['$setId'] = '1';
                        $now = (new \DateTime())->format('Y-m-d');
                        try {
                            $cacheDriver->createItem(...$args);
                            $this->syncFromDate($entityBrowser, $now);
                        } 

                        // May be the datastructure is faulty
                        catch(\Exception $exc){
                            $cacheDriver->ensureDataStructure($args[0]);
                            $cacheDriver->createItem(...$args);
                            $this->syncFromDate($entityBrowser, $now);
                        }
                    }
                } 
                // Fail silently
                catch(\Exception $exp){}

                // The $autoFetch parameter determines whether to fetch the just inserted record or its Id alone.
                // The default is to fetch the inserted record but may be overridden by an implementing cl
                if(isset($otherOptions['$autoFetch']) && (''.$otherOptions['$autoFetch']) == '1'){
                    $this->autoFetch = TRUE;
                }

                if($this->autoFetch){
                    $reses[] = $this->getItemById($entityBrowser, $res->d, $otherOptions['$select'], $otherOptions['$expand'], $otherOptions);
                } else {
                    $reses[] = $res->d;
                }
            }
            
            // Otherwise, if something is wrong, retry
            else {
                if ($retryCount < $this->maxRetries) {
                    $otherOptions['processedItems'] = $reses;
                    $reses[] = $this->createItems($entityBrowser, $objects, $otherOptions);
                } else {
                    throw new \Exception("Unable to create a new record in {$entityBrowser->getDisplayName()} of ".__CLASS__);
                }
            }
        }

        return $reses;
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */
    

    public function createItem2($entityBrowser, \stdClass $object, array $otherOptions = [])
    {
        $resp = $this->createItems($entityBrowser, [$object], $otherOptions);
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */
    

    public function createItem($entityBrowser, \stdClass $object, array $otherOptions = [])
    {
        $entityBrowser = $this->getEntityBrowser($entityBrowser);
        if (is_string($entityBrowser) || is_null($entityBrowser)) {
            throw new \Exception("Invalid entity '{$entityBrowser}' could not be found in {$this->getIdentifier()}");
        }        

        // Handle method redirection
        if($entityBrowser->shouldRedirectCreate()){
            $providerInfo = $entityBrowser->getCreateProviderInfo();
            $driver = $this;
            if($providerInfo->driver != $this->getIdentifier()) {
                $driver = $this->loadDriver($providerInfo->driver);
            }
            // Execute the provider's create method.
            $args = func_get_args();
            $args[0] = $providerInfo->entity;
            $return = $driver->createItem(...$args);
            return $return;
        }

        // Handle storage delegation
        if ($entityBrowser->delegatesStorage() && ($this->getIdentifier() != $entityBrowser->getDelegateDriverName())) {

            // Load the driver instead
            $delegateDriver = $this->loadDriver($entityBrowser->getDelegateDriverName());
            
            // Return the results from the cache driver.
            $args = func_get_args();
            $args[0] = strtolower("{$this->getIdentifier()}__{$entityBrowser->getInternalName()}");
            $args[2]['$setId'] = '1';
            $return = $delegateDriver->createItem(...$args);
            return $return;
        }

        // If there is need to specifically update the cache copy alone
        $cacheOnly = (isset($otherOptions['$cacheOnly']) && $otherOptions['$cacheOnly'] == '1')? TRUE:FALSE;
        if (
            $entityBrowser->shouldCacheData() && 
            ($this->getIdentifier() != $entityBrowser->getCachingDriverName()) && 
            $cacheOnly
        ) {
            // Load the driver instead
            $cacheDriver = $this->loadDriver($entityBrowser->getCachingDriverName());

            // Refactor the arguments to target the cache.
            $args = func_get_args();
            $args[0] = strtolower("{$this->getIdentifier()}__{$entityBrowser->getInternalName()}");

            // Since we ommit items that are deleted, set this one as not deleted
            if($entityBrowser->hasField('IsDeleted')){
                $args[1]->IsDeleted = FALSE;
            }

            // Set record as pending update. It is possible that some fields will be calculated by the remote source
            $args[1]->_IsUpdated = FALSE;

            $args[2]['$setId'] = '1';
            $now = (new \DateTime())->format('Y-m-d');
            try {
                return $cacheDriver->createItem(...$args);
                // $this->syncFromDate($entityBrowser, $now);
            } 

            // May be the datastructure is faulty
            catch(\Exception $exc){
                $cacheDriver->ensureDataStructure($args[0]);
                $cacheDriver->createItem(...$args);
                $this->syncFromDate($entityBrowser, $now);
            }
        }
                
        $entityBrowser = $this->setStrategies($entityBrowser);
        $retryCount = isset($otherOptions['retryCount'])?$otherOptions['retryCount']:0;
        $otherOptions['retryCount'] = $retryCount + 1;

        // Strip-out invalid fields
        $setFields = $entityBrowser->getValidFieldsByDisplayName(array_keys(get_object_vars($object)));

        $obj = new \stdClass();
        foreach ($setFields as $setField) {
            // avoid objects
            if (!is_object($object->{$setField->getDisplayName()}) && !is_array($object->{$setField->getDisplayName()})) {
                $obj->{$setField->getDisplayName()} = $object->{$setField->getDisplayName()};
            }
        }

        if($entityBrowser->shouldManageTimestamps()){
            $now = new \DateTime();
            $obj->Created = $now->format('Y-m-d\TH:i:s');
            $obj->Modified = $now->format('Y-m-d\TH:i:s');
        }

        // Prepare the selected fields for the return
        if (!isset($otherOptions['$select'])) {
            $otherOptions['$select'] = EntityFieldDefinition::getDisplayNames($setFields);
        } else {
            $abccd = is_string($otherOptions['$select']) ? explode(',', $otherOptions['$select']) : (is_array($otherOptions['$select']) ? $otherOptions['$select'] : []);
            $abccc = array_merge($abccd, EntityFieldDefinition::getDisplayNames($setFields));
            $otherOptions['$select'] = array_unique($abccc);
        }

        // Prepare the expanded fields for the returned value
        if (!isset($otherOptions['$expand'])) {
            $otherOptions['$expand'] = '';
        }

        // Check for duplicates
        $res = new \stdClass();
        $updateMatches = isset($otherOptions['$updateMatches'])?$otherOptions['$updateMatches']: FALSE;
        $duplicateFilter = isset($otherOptions['$duplicateFilter'])?$otherOptions['$duplicateFilter']:'';
        $duplicates = [];

        // Check for conflicts.
        if(strlen($duplicateFilter) > 0){
            $duplicates = $this->getItems($entityBrowser, "{$entityBrowser->getIdField()->getDisplayName()}", $duplicateFilter);
        }

        // If a duplicate exists.
        if(count($duplicates) > 0){
            // pretend the record was just created.
            $res->d = $duplicates[0]->Id;

            // Silently do an update if requested
            if($updateMatches){ 
                $this->updateItemInternal($entityBrowser, $this->connectionToken, $res->d, $obj, $otherOptions);
            }
            $res->success = true;
        } 

        // Invoke the internal create method.
        else {
            $res = $this->createItemInternal($entityBrowser, $this->connectionToken, $obj, $otherOptions);
        }
        
        // Requery and return the created object.
        if (property_exists($res, 'd') && $res->success == true) {

            // Try to write the creation data to the cache also
            try {
                if ($entityBrowser->shouldCacheData() && ($this->getIdentifier() != $entityBrowser->getCachingDriverName()) && count($duplicates) < 1) {
                    // Load the driver instead
                    $cacheDriver = $this->loadDriver($entityBrowser->getCachingDriverName());
                    
                    // Refactor the arguments to target the cache.
                    $args = func_get_args();
                    $args[0] = strtolower("{$this->getIdentifier()}__{$entityBrowser->getInternalName()}");
                    $args[1]->Id = $res->d;

                    // Since we ommit items that are deleted, set this one as not deleted
                    if($entityBrowser->hasField('IsDeleted')){
                        $args[1]->IsDeleted = FALSE;
                    }

                    // Set record as pending update. It is possible that some fields will be calculated by the remote source
                    $args[1]->_IsUpdated = FALSE;

                    $args[2]['$setId'] = '1';
                    $now = (new \DateTime())->format('Y-m-d');
                    try {
                        $cacheDriver->createItem(...$args);
                        $this->syncFromDate($entityBrowser, $now);
                    } 
                    // May be the datastructure is faulty
                    catch(\Exception $exc){
                        $cacheDriver->ensureDataStructure($args[0]);
                        $cacheDriver->createItem(...$args);
                        $this->syncFromDate($entityBrowser, $now);
                    }
                }
            } 
            // Fail silently
            catch(\Exception $exp){}

            // The $autoFetch parameter determines whether to fetch the just inserted record or its Id alone.
            // The default is to fetch the inserted record but may be overridden by an implementing cl
            if(isset($otherOptions['$autoFetch']) && (''.$otherOptions['$autoFetch']) == '1'){
                $this->autoFetch = TRUE;
            }

            if($this->autoFetch){
                $return = $this->getItemById($entityBrowser, $res->d, $otherOptions['$select'], $otherOptions['$expand'], $otherOptions);
            } else {
                return $res->d;
            }

            return $return;
        }
        
        // Otherwise, if something is wrong, retry
        else {
            if ($retryCount < $this->maxRetries) {
                return $this->createItem($entityBrowser, $objects, $otherOptions);
            } else {
                throw new \Exception("Unable to create a new record in {$entityBrowser->getDisplayName()} of ".__CLASS__);
            }
        }
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */
    

    public function deleteItem($entityBrowser, $id, array $otherOptions = [], &$deleteCount = 0)
    {        
        $entityBrowser = $this->getEntityBrowser($entityBrowser);
        $deleteItemArgs = func_get_args();
        if (is_string($entityBrowser) || is_null($entityBrowser)) {
            throw new \Exception("Invalid entity '{$entityBrowser}' could not be found in {$this->getIdentifier()}");
        }
        
        // Handle method redirection
        if($entityBrowser->shouldRedirectDelete()){
            $providerInfo = $entityBrowser->getDeleteProviderInfo();
            $driver = $this;
            if($providerInfo->driver != $this->getIdentifier()){
                $driver = $this->loadDriver($providerInfo->driver);
            }
            // Execute the update provider's update method.
            $args = func_get_args();
            $args[0] = $providerInfo->entity;
            $return = $driver->deleteItem(...$args);            
            return $return;
        }

        // Handle storage delegation
        if ($entityBrowser->delegatesStorage() && ($this->getIdentifier() != $entityBrowser->getDelegateDriverName())) {
            // Load the driver instead
            $delegateDriver = $this->loadDriver($entityBrowser->getDelegateDriverName());
            
            // Return the results from the cache driver.
            $args = func_get_args();
            $args[0] = strtolower("{$this->getIdentifier()}__{$entityBrowser->getInternalName()}");
            $return = $delegateDriver->deleteItem(...$args);
            return $return;
        }
        
        $entityBrowser = $this->setStrategies($entityBrowser);
        
        $retryCount = isset($otherOptions['retryCount'])?$otherOptions['retryCount']: 0;
        $otherOptions['retryCount'] = $retryCount + 1;

        // If there is need to specifically update the cache copy alone
        $cacheOnly = (isset($otherOptions['$cacheOnly']) && $otherOptions['$cacheOnly'] == '1')?TRUE:FALSE;
        if (
            $entityBrowser->shouldCacheData() && 
            ($this->getIdentifier() != $entityBrowser->getCachingDriverName()) && 
            $cacheOnly
        ) {
            // Load the driver instead
            $cacheDriver = $this->loadDriver($entityBrowser->getCachingDriverName());
            
            // Refactor the arguments to target the cache.
            $deleteItemArgs[0] = strtolower("{$this->getIdentifier()}__{$entityBrowser->getInternalName()}");

            try {
                return $cacheDriver->deleteItem(...$deleteItemArgs);
            } 
            // May be the datastructure is faulty
            catch(\Exception $exc){
                $cacheDriver->ensureDataStructure($deleteItemArgs[0]);
                return $cacheDriver->deleteItem(...$deleteItemArgs);
            }
        }

        try {
            $deleteResult = $this->deleteItemInternal($entityBrowser, $this->connectionToken, $id, $otherOptions);
            $select = isset($otherOptions['$select'])?$otherOptions['$select']:['Id','Created','Modified'];
            $filter = isset($otherOptions['$filter'])?$otherOptions['$filter']:'';
            $expand = isset($otherOptions['$expand'])?$otherOptions['$expand']:'';

            $deleteCount = $deleteResult->d;
            if($deleteCount > 0){
                try {
                    // $return = $this->getItems($entityBrowser, $select, $filter, $expand);
                    $deleteResult = ['status' => 'success'];//$return;
                    $deleteResult['deleteCount'] = $deleteCount;

                    // Try marking it as deleted or deleting it in the cache also
                    if ($entityBrowser->shouldCacheData() && ($this->getIdentifier() != $entityBrowser->getCachingDriverName())) {
                        // Load the driver instead
                        $cacheDriver = $this->loadDriver($entityBrowser->getCachingDriverName());
                        
                        // Refactor the arguments to target the cache.
                        $args = func_get_args();
                        $args[0] = strtolower("{$this->getIdentifier()}__{$entityBrowser->getInternalName()}");
                        $today = (new \DateTime())->format('Y-m-d');

                        // Mark as deleted
                        if($entityBrowser->hasField('IsDeleted')){    
                            $update = new \stdClass();
                            $update->Id = $id;
                            $update->IsDeleted = TRUE;
                            try {
                                $cacheDriver->updateItem($args[0], $id, $update, $otherOptions);
                                $this->syncFromDate($entityBrowser, $today);
                            } 
                            // May be the datastructure is faulty
                            catch(\Exception $exc){
                                $cacheDriver->ensureDataStructure($args[0]);
                                $cacheDriver->updateItem($args[0], $id, $update, $otherOptions);
                                $this->syncFromDate($entityBrowser, $today);
                            }
                        } 
                        else 
                        // Otherwise, delete from the cache
                        {
                            try {
                                $cacheDriver->deleteItem(...$args);
                                $this->syncFromDate($entityBrowser, $today);
                            } 
                            // May be the datastructure is faulty
                            catch(\Exception $exc){
                                $cacheDriver->ensureDataStructure($args[0]);
                                $cacheDriver->deleteItem(...$args);
                                $this->syncFromDate($entityBrowser, $today);
                            }
                        }
                    }
                } catch (\Exception $ex) {
                    $deleteResult = [
                        'status' => 'failure'
                    ];
                }                
                
                return $deleteResult;
            }            
        } catch (\Exception $exc) {
            if ($retryCount < $this->maxRetries) {
                return $this->deleteItem($entityBrowser, $id, $otherOptions);
            } else {
                throw $exc;
            }
        }

        // Requery and return the created object.
        // if (property_exists($res, 'd') && $res->success == true) {
        //     // Try to write the update to the cache also
        //     try {
        //         if ($entityBrowser->shouldCacheData() && ($this->getIdentifier() != $entityBrowser->getCachingDriverName())) {
        //             // Load the driver instead
        //             $cacheDriver = $this->loadDriver($entityBrowser->getCachingDriverName());
                    
        //             // Refactor the arguments to target the cache.
        //             $args = func_get_args();
        //             $args[0] = strtolower("{$this->getIdentifier()}__{$entityBrowser->getInternalName()}");
        //             $args[1]->Id = $res->d;
        //             $args[2]['$setId'] = '1';
        //             $now = (new \DateTime())->format('Y-m-d');
        //             try {
        //                 $cacheDriver->createItem(...$args);
        //                 $this->syncFromDate($entityBrowser, $now);
        //             } 
        //             // May be the datastructure is faulty
        //             catch(\Exception $exc){
        //                 $cacheDriver->ensureDataStructure($args[0]);
        //                 $cacheDriver->createItem(...$args);
        //                 $this->syncFromDate($entityBrowser, $now);
        //             }
        //         }
        //     } 
        //     // Fail silently
        //     catch(\Exception $exp){}

        //     $return = $this->getItemById($entityBrowser, $res->d, $otherOptions['$select'], $otherOptions['$expand'], $otherOptions);
        //     return $return;
        // } 
        
        // // Otherwise, if something is wrong, retry
        // else {
        //     if ($retryCount < $this->maxRetries) {
        //         return $this->createItem($entityBrowser, $object, $otherOptions);
        //     } else {
        //         throw new \Exception("Unable to create a new record in {$entityBrowser->getDisplayName()} of ".__CLASS__);
        //     }
        // }
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */
    

    public function fetchFieldValues($record, $selected_field)
    {
        $value = EncoderDecoder::escapeinner($record->{$selected_field});
        return [$value];
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */
    

    public function addExpansionToRecord($entity, &$record, EntityFieldDefinition $fieldInfo, $vals)
    {
        $keyVal = $record->{$fieldInfo->getRelatedLocalFieldName()};

        if (is_array($vals)) {

            $results = isset($vals["{$keyVal}"]) ? $vals["{$keyVal}"] : ($fieldInfo->isMany() ? [] : NULL);
        } elseif ($vals instanceof MiddlewareComplexEntity) {
            $results = $vals->getByKey("{$keyVal}", $fieldInfo->isMany());
        } else {
            $results = null;
        }

        $record->{$fieldInfo->getDisplayName()} = $fieldInfo->isMany() ? ['results' => $results] : $results;

        return $record;
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */
    

    public function renameRecordFields($record, $selected_fields)
    {

        $r = new \stdClass();

        foreach ($selected_fields as $key => $field) {
            $displayName = $field->getDisplayName();
            if (is_object($record) && property_exists($record, $key)) {
                if (is_array($record->{$key})) {
                    if ($field->isArray()) {
                        $r->{$displayName} = $record->{$key};
                    } elseif (count($record->{$key}) > 0) {
                        $r->{$displayName} = $record->{$key}[0];
                    }
                } else {
                    $r->{$displayName} = $field->isInteger()?intval($record->{$key}):($field->isBoolean()?boolVal($record->{$key}):$record->{$key});
                }
            } elseif (is_array($record) && isset($record[$key])) {
                if (is_array($record[$key])) {
                    if ($field->isArray()) {
                        $r->{$displayName} = $record[$key];
                    } elseif (count($record[$key]) > 0) {
                        $r->{$displayName} = $record[$key][0];
                    }
                } else {
                    $r->{$displayName} = $field->isInteger()?intval($record[$key]):($field->isBoolean()?boolVal($record[$key]):$record[$key]);
                }
            } else {
                $r->{$displayName} = $field->isArray() ? [] : null;
            }
        }

        $record = $r;

        return $record;
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */
    

    public function reverseRenameRecordFields(EntityDefinitionBrowser $brower, \stdClass $record, $type = 'normal')
    {
        $r = new \stdClass();
        $keys = array_keys(get_object_vars($record));

        foreach ($keys as $key) {
            $internalName = $brower->getFieldByDisplayName($key)->getInternalName(TRUE, $type);
            
            if (property_exists($r, $internalName)) {
                if (!is_null($record->{$key})) {
                    $r->{$internalName} = $record->{$key};
                }
            } else {
                $r->{$internalName} = $record->{$key};
            }
        }

        return $r;
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */
    

    public function mergeRecordArray($data, $chunkResult, EntityFieldDefinition $localField, EntityFieldDefinition $remoteField = null)
    {
        $r = is_array($data) ? $data : [];

        if (!is_null($chunkResult)) {
            foreach ($chunkResult as $val) {
                
                if (is_null($remoteField)) {
                    $r[] = $val;
                } else {
                    $remoteFieldName = ($remoteField->isExpandable()) ? $remoteField->getRelatedLocalField()->getDisplayName() : $remoteField->getDisplayName();

                    $remoteFieldValue = $val->{$remoteFieldName};

                    // If there is no key matching the remote field value in the array, add it.
                    if (!isset($r["{$remoteFieldValue}"])) {
                        $r["{$remoteFieldValue}"] = null;
                        if ($localField->isMany()) {
                            $r["{$remoteFieldValue}"] = [];
                        }
                    }

                    // Put a value in the remote field key
                    if ($localField->isMany()) {
                        $r["{$remoteFieldValue}"][] = $val;
                    } else {
                        $r["{$remoteFieldValue}"] = $val;
                    }
                }
            }
        }

        return $r;
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */
    

    public static function getCurrentSelections(EntityDefinitionBrowser $entityBrowser, array $fields)
    {

        // set the compulsary fields of the entity
        $required_fields = $entityBrowser->getMandatoryFieldNames();
        foreach ($required_fields as $required_field) {
            if (!in_array($required_field, $fields)) {
                $fields[] = $required_field;
            }
        }

        // remove complex or invalid fields
        $shorts = [];
        $fields = array_values(array_filter($fields, function (&$item) use (&$shorts) {
                    $shorthand = '/[\[]([\w\|\d]+)[\]]/i';
                    $matchs = [];
                    preg_match_all($shorthand, $item, $matchs, PREG_SET_ORDER);

            if (strpos($item, '/') > -1) {
                return false;
            } elseif (count($matchs) > 0) {
                foreach ($matchs as $mat) {
                    $ss = preg_split('@(?:\s*\|\s*|^\s*|\s*$)@', $mat[1], null, PREG_SPLIT_NO_EMPTY);
                    foreach ($ss as $s) {
                        if (!in_array($s, $shorts)) {
                            $shorts[] = $s;
                        }
                    }
                }
                return false;
            } else {
                return $item;
            }
        }));

        return array_merge($fields, $shorts);
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */
    

    public static function getCurrentExpansions(EntityDefinitionBrowser $entityBrowser, $field, $fields)
    {
        
        $regex = "/({$field})\/([^\s\,]+)/";
        $fieldsR = [];

        foreach ($fields as $item) {
            $a = [];
            preg_match($regex, $item, $a);
            if (count($a) > 0 && !in_array($a[2], $fieldsR)) {
                $fieldsR[] = $a[2];
            }
        }

        return $fieldsR;
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */
    

    protected function getDefaultFilter()
    {
        return '';
    }

    /*
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     ********************************************************************************************
     */
    
    private function setStrategies(EntityDefinitionBrowser $entityBrowser)
    {
        $scope = &$this;

        $entityBrowser->setRenameStrategy(function () use ($scope) {
            return $scope->renameRecordFields(...func_get_args());
        });

        $entityBrowser->setReverseRenameStrategy(function () use ($scope) {
            return $scope->reverseRenameRecordFields(...func_get_args());
        });

        $entityBrowser->setExpansionJoinStrategy(function () use ($scope) {            
            return $scope->addExpansionToRecord(...func_get_args());
        });

        $entityBrowser->setMergeExpansionChunksStrategy(function () use ($scope) {
                return $scope->mergeRecordArray(...func_get_args());
        });

        $entityBrowser->setFieldValueFetchStrategy(function () use ($scope) {
            return $scope->fetchFieldValues(...func_get_args());
        });

        return $entityBrowser;
    }
}
