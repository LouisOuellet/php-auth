<?php

// Declaring namespace
namespace LaswitchTech\phpAUTH\Objects;

//Import phpConfigurator class into the global namespace
use LaswitchTech\phpConfigurator\phpConfigurator;

// Import phpLogger class into the global namespace
use LaswitchTech\phpLogger\phpLogger;

// Import Database Class into the global namespace
use LaswitchTech\phpDB\Database;

// Import Role Class into the global namespace
use LaswitchTech\phpAUTH\Objects\Relationship;

// Import Exception class into the global namespace
use \Exception;

// Import DateTime class into the global namespace
use \DateTime;

class Group {

  // Constants
  const Type = 'group';
  const Types = 'groups';
  const Name = 'Group';

	// Logger
	private $Logger;
	private $Level = 1;

  // Configurator
  private $Configurator = null;

  // phpDB
  private $Database = null;
  private $Table = self::Types;
  private $Columns = [];
  private $Integers = [];
  private $Strings = [];
  private $Primary = null;
  private $OnUpdate = [];
  private $Defaults = [];
  private $Required = [];
  private $Nullables = [];

  // Object
  private $Object = null;
  private $Classes = [
    'users' => '\\LaswitchTech\\phpAUTH\\Objects\\User',
    'organizations' => '\\LaswitchTech\\phpAUTH\\Objects\\Organization',
    'groups' => '\\LaswitchTech\\phpAUTH\\Objects\\Group',
    'roles' => '\\LaswitchTech\\phpAUTH\\Objects\\Role',
    'permissions' => '\\LaswitchTech\\phpAUTH\\Objects\\Permission',
  ];
  private $Identifiers = [
    'users' => 'username',
    'organizations' => 'id',
    'groups' => 'name',
    'roles' => 'name',
    'permissions' => 'name',
  ];

  // Relationship
  private $Relationship = null;
  private $Relationships = [];

  // Identification
  private $Id = null;
  private $Identifier = null;
  private $Name = null;

  /**
   * Create a new Session instance.
   *
   * @param  string  $Id
   * @param  string  $Identifier
   * @param  Object  $Logger
   * @param  Object  $Database
   * @return void
   * @throws Exception
   */
  public function __construct($Id, $Identifier, $Logger = null, $Database = null, $Object = null){

    // Initialize Configurator
    $this->Configurator = new phpConfigurator('auth');

    // Retrieve Log Level
    $this->Level = $this->Configurator->get('logger', 'level') ?: $this->Level;

    // Initiate Id
    $this->Id = $Id;

    // Initiate Identifier
    $this->Identifier = $Identifier;

    // Initiate Name
    $this->Name = $this->Id;

    // Initiate phpLogger
    $this->Logger = $Logger;
    if(!$this->Logger){
      $this->Logger = new phpLogger('auth');
    }

    // Initiate phpDB
    $this->Database = $Database;
    if(!$this->Database){
      $this->Database = new Database();
    }

    // Initiate Relationship
    $this->Relationship = new Relationship($Logger, $Database);

    // Setup Columns
    $this->Columns = $this->Database->getColumns($this->Table);

    // Setup Integers and Strings
    foreach($this->Columns as $Column => $DataType){
      if(in_array($DataType,['int','bigint','tinyint'])){
        $this->Integers[] = $Column;
      } else {
        $this->Strings[] = $Column;
      }
    }

    // Setup Defaults
    $this->Defaults = $this->Database->getDefaults($this->Table);

    // Setup Primary
    $this->Primary = $this->Database->getPrimary($this->Table);

    // Setup OnUpdate
    $this->OnUpdate = $this->Database->getOnUpdate($this->Table);

    // Setup Required
    $this->Required = $this->Database->getRequired($this->Table);

    // Setup Nullables
    $this->Nullables = $this->Database->getNullables($this->Table);

    // Check if an Object was provided
    if(is_array($Object)){

      // Loop columns to check if Object can be saved
      $Save = true;
      foreach($this->Columns as $Column => $DataType){

        // Check if Key is Present
        if(!array_key_exists($Column,$Object)){
          $Save = false;
          break;
        }

        // Check if Data requires decoding
        if($this->isJson($Object[$Column])){
          $Object[$Column] = json_decode($Object[$Column],true);
        }
      }

      // Save Object
      if($Save){
        $this->Object = $Object;
      }
    }
  }

  /**
   * Check if a variable contains JSON.
   *
   * @param  string  $String
   * @return boolean
   * @throws Exception
   */
	private function isJson($String){
    if($String !== null && is_string($String)){
      json_decode($String);
      return (json_last_error() == JSON_ERROR_NONE);
    }
    return false;
  }

  /**
   * Retrieve Group.
   *
   * @return object|void
   * @throws Exception
   */
	public function retrieve($force = false){
		try {

      // Check if Database is Connected
      if(!$this->Database->isConnected()){
        throw new Exception("Database is not connected.");
      }

      // Check if Object was already retrieved
      if(!$force && $this->Object !== null){
        return $this;
      }

      // Find the Group
      $Group = $this->Database->select("SELECT * FROM " . $this->Table . " WHERE `" . $this->Identifier . "` = ?", [$this->Id]);

      // Validate Group
      if(count($Group) <= 0){

        // Debug Information
        $this->Logger->debug(count($Group));
        $this->Logger->debug($Group);

        // Throw Exception
        throw new Exception("Unable to find Group.");
      }

      // Identify Group
      $this->Object = $Group[0];

      // Parse Group
      foreach($this->Object as $Key => $Value){
        if($this->Columns[$Key] === "json" && $this->isJson($Value)){
          $this->Object[$Key] = json_decode($Value,true);
        }
        if($Value !== null && $this->Columns[$Key] === "timestamp"){
          $this->Object[$Key] = strtotime($Value);
        }
      }

      // Retrieve Relationships
      $this->Relationships = $this->Relationship->getRelated($this->Table, $this->get('id'));

      return $this;
    } catch (Exception $e) {

			// If an exception is caught, log an error message
      $this->Logger->error('Error: '.$e->getMessage());
      return null;
    }
	}

  /**
   * Get data from this group.
   *
   * @param  string  $Key
   * @param  boolean|null  $asObject
   * @return string|array|void
   * @throws Exception
   */
	public function get($Key, $asObject = false){
		try {

      // Retrieve current object
      $this->retrieve();

      // Check if object was retrieved
      if(!$this->Object){
        throw new Exception("Could not identify the object.");
      }

      // Check if the key requested is relationships
      if($Key === 'relationships'){
        if($asObject){
          foreach($this->Classes as $Table => $Class){
            if(isset($this->Relationships[$Table])){
              foreach($this->Relationships[$Table] as $Id => $Record){
                $this->Relationships[$Table][$Id] = new $Class($Record[$this->Identifiers[$Table]], $this->Identifiers[$Table], $this->Logger, $this->Database);
              }
            }
          }
        }

        // Debug Information
        $this->Logger->debug($this->Relationships);

        // Return
        return $this->Relationships;
      }

      // Check if the key requested exist
      if(!isset($this->Object[$Key]) && $this->Object[$Key] !== null){

        // Debug Information
        $this->Logger->debug($this->Id);
        $this->Logger->debug($this->Identifier);
        $this->Logger->debug($Key);
        $this->Logger->debug($this->User);

        // Throw Exception
        throw new Exception("Could not find the requested key.");
      }

      // If the asObject switch is on, convert records to objects
      if($asObject && array_key_exists($Key,$this->Object) && is_array($this->Object[$Key])){

        // Initialize Array of objects
        $Array = [];

        // Iterate through each objects
        foreach($this->Object[$Key] as $Object){

          // Get Class name
          $Class = $this->Classes[$Key];

          // Create the Objects
          $Array[$Object] = new $Class($Object, $this->Identifiers[$Key], $this->Logger, $this->Database);
        }

        // Return the data point requested as objects
        return $Array;
      } else {

        // Return the data point requested
        return $this->Object[$Key];
      }
    } catch (Exception $e) {

			// If an exception is caught, log an error message
      $this->Logger->error('Error: '.$e->getMessage());
      return null;
    }
	}

  /**
   * Create a new group.
   *
   * @param array $Data Associative array of group data.
   * @return object|void
   * @throws Exception
   */
	public function new($Data, $isAPI = false){
		try {

      // Check if Database is Connected
      if(!$this->Database->isConnected()){
        throw new Exception("Database is not connected.");
      }

      // Check Identification
      if(!in_array($this->Identifier,['name'])){
        throw new Exception("Object must identified by the name.");
      }

      // Find the Group
      $Group = $this->Database->select("SELECT * FROM " . $this->Table . " WHERE `" . $this->Identifier . "` = ?", [$this->Id]);

      // Validate Group does not exist
      if(count($Group) > 0){
        throw new Exception("Group already exist.");
      }

      // Add/Overwrite name into Data
      $Data['name'] = $this->Name;

      // Initialize JSON Arrays
      foreach($this->Columns as $Column => $DataType){
        if($DataType === "json"){
          if(!isset($Data[$Column])){
            $Data[$Column] = [];
          } else {
            if($Data[$Column] === null || $Data[$Column] === ''){
              $Data[$Column] = [];
            }
            if(is_string($Data[$Column])){
              $Data[$Column] = json_decode($Data[$Column],true);
            }
          }
        }
      }

      // Create Group Array
      $Group = [];
      foreach($Data as $Key => $Value){

        // Debut Information
        $this->Logger->debug("Does {$Key} exist? " . !array_key_exists($Key,$this->Columns));

        // Unset Value if it does not exist
        if(!array_key_exists($Key,$this->Columns)){

          // Debut Information
          $this->Logger->debug("Unset: {$Key}");

          // Unset
          unset($Data[$Key]);
          continue;
        }

        // Debut Information
        $this->Logger->debug("Is {$Key} an array? " . is_array($Value));

        // Convert Arrays to Json
        if(is_array($Value)){
          $Value = json_encode($Value, JSON_UNESCAPED_SLASHES);
        }

        // Debut Information
        $this->Logger->debug($this->Columns);

        // Convert DataTypes
        switch($this->Columns[$Key]){
          case"datetime":
            if($Value !== null && $Value !== ""){
              $DateTime = new DateTime($Value);
              $Value = $DateTime->format('Y-m-d H:i:s');
              $Array[$Key] = $Value;
            }
            break;
          case"timestamp":
            if($Value !== null && $Value !== ""){
              $DateTime = new DateTime();
              $DateTime->setTimestamp($Value);
              $Value = $DateTime->format('Y-m-d H:i:s');
              $Array[$Key] = $Value;
            }
            break;
          case"int":
          case"bigint":
          case"tinyint":
            $Value = intval($Value);
            $Data[$Key] = $Value;
            break;
          default:
            $Value = strval($Value);
            $Data[$Key] = $Value;
            break;
        }

        // Debut Information
        $this->Logger->debug("Is {$Key} empty? " . ((empty($Value) || $Value === '' || $Value === null) && !is_int($Value)));

        // Unset Value if it's empty
        if((empty($Value) || $Value === '' || $Value === null) && !is_int($Value)){

          // Debut Information
          $this->Logger->debug("Unset: {$Key}");

          // Unset
          unset($Data[$Key]);
          continue;
        }

        // Debut Information
        $this->Logger->debug("Should {$Key} be updated? " . (isset($this->OnUpdate[$Key])));

        // Should it be updated?
        if(isset($this->OnUpdate[$Key])){

          // Debut Information
          $this->Logger->debug("Unset: {$Key}");

          // Unset
          unset($Data[$Key]);
          continue;
        }

        // Debut Information
        $this->Logger->debug("Keeping: {$Key}");

        // Data Validated
        $Data[$Key] = $Value;
        $Group[$Key] = $Value;
      }

      // Build insert statement
      $Statement = 'INSERT INTO ' . $this->Table . ' (' . implode(',',array_keys($Group)) . ') VALUES (' . implode(',', array_fill(0, count($Group), '?')) . ')';

      // Sanitize Values
      foreach($Group as $Key => $Value){
        if(is_array($Value)){
          $Group[$Key] = json_encode($Value, JSON_UNESCAPED_SLASHES);
        }
      }

      // Concatenate Values
      $Values = array_values($Group);

      // Debug Information
      $this->Logger->debug($Statement);
      $this->Logger->debug($Values);

      // Execute Statement
      $Group['id'] = $this->Database->insert($Statement, $Values);

      // Check if Group was created
      if(!$Group['id']){
        throw new Exception("An error occured during the creation of the group.");
      }

      // Retrieve Object
      $this->retrieve();

      // Look for some defaults roles
      $Roles = $this->Database->select("SELECT * FROM roles WHERE `isDefault` = ?", [1]);

      // If any are found add them
      if(count($Roles) > 0){
        foreach($Roles as $Role){
          $this->link('roles',$Role['id']);
        }
      }

      // Return
      return $this;
    } catch (Exception $e) {

			// If an exception is caught, log an error message
      $this->Logger->error('Error: '.$e->getMessage());
      return false;
    }
	}

  /**
   * Save/Update data of this group.
   *
   * @param  string|array $Key
   * @param  string|null  $Value
   * @return void
   * @throws Exception
   */
	public function save($Key, $Value = null){
		try {

      // Retrieve Object
      $this->retrieve();

      // Check if Object was retrieved
      if(!$this->Object){
        throw new Exception("Could not identify the object.");
      }

      // Validate $Value
      if(is_string($Key) && !$Value){
        throw new Exception("No value provided.");
      }

      // Initialize Array
      $Array = [];

      // Check if an array of data was provided
      if(is_array($Key)){
        $Array = $Key;
      } else {
        $Array[$Key] = $Value;
      }

      // Validate all fields
      foreach($Array as $Key => $Value){

        // Debut Information
        $this->Logger->debug("Does {$Key} exist? " . !array_key_exists($Key,$this->Columns));

        // Unset Value if it does not exist
        if(!array_key_exists($Key,$this->Columns)){

          // Debut Information
          $this->Logger->debug("Unset: {$Key}");

          // Unset
          unset($Array[$Key]);
          continue;
        }

        // Debut Information
        $this->Logger->debug($this->Columns);

        // Convert Arrays to JSON
        if(is_array($Value)){
          $Value = json_encode($Value, JSON_UNESCAPED_SLASHES);
          $Array[$Key] = $Value;
        }

        // Convert DataTypes
        switch($this->Columns[$Key]){
          case"datetime":
            if($Value !== null && $Value !== ""){
              $DateTime = new DateTime($Value);
              $Value = $DateTime->format('Y-m-d H:i:s');
              $Array[$Key] = $Value;
            }
            break;
          case"timestamp":
            if($Value !== null && $Value !== ""){
              $DateTime = new DateTime();
              $DateTime->setTimestamp($Value);
              $Value = $DateTime->format('Y-m-d H:i:s');
              $Array[$Key] = $Value;
            }
            break;
          case"int":
          case"bigint":
          case"tinyint":
            $Value = intval($Value);
            $Array[$Key] = $Value;
            break;
          default:
            $Value = strval($Value);
            $Array[$Key] = $Value;
            break;
        }

        // Initialize Validate
        $Validate = $this->Object[$Key];

        // Sanitize Validate
        if(is_array($Validate)){
          $Validate = json_encode($Validate, JSON_UNESCAPED_SLASHES);
        }

        // Debut Information
        $this->Logger->debug("Is {$Key} empty? " . ((empty($Value) || $Value === '' || $Value === null) && !is_int($Value)));

        // Unset Value if it's empty
        if((empty($Value) || $Value === '' || $Value === null) && !is_int($Value)){

          $Value = NULL;

          // Should still be updated if the current data is not empty.
          if($Validate == $Value || !in_array($Key,$this->Nullables)){

            // Debut Information
            $this->Logger->debug("Unset: {$Key}");

            // Unset
            unset($Array[$Key]);
            continue;
          }
        }

        // Debut Information
        $this->Logger->debug("Should {$Key} be updated? " . (isset($this->OnUpdate[$Key])));

        // Should it be updated?
        if(isset($this->OnUpdate[$Key])){

          // Debut Information
          $this->Logger->debug("Unset: {$Key}");

          // Unset
          unset($Array[$Key]);
          continue;
        }

        // Debut Information
        $this->Logger->debug("Is {$Key} an array? " . is_array($Value));

        // Convert Arrays to Json
        if(is_array($Value)){
          $Value = json_encode($Value, JSON_UNESCAPED_SLASHES);
        }

        // Debut Information
        $this->Logger->debug("Compare these values:");
        $this->Logger->debug($Value);
        $this->Logger->debug($Validate);

        // Debut Information
        $this->Logger->debug("Is {$Key} equal? " . ($Validate == $Value));
        $this->Logger->debug("Value Datatype: " . gettype($Value));
        $this->Logger->debug("Validate Datatype: " . gettype($Value));

        // Unset Value if no changes were made
        if($Validate == $Value){

          // Debut Information
          $this->Logger->debug("Unset: {$Key}");

          // Unset
          unset($Array[$Key]);
          continue;
        }

        // Debut Information
        $this->Logger->debug("Keeping: {$Key}");
      }

      // Check if we still proceed in updating something
      if(count($Array) <= 0){
        return $this;
      }

      // Build update statement
      $Statement = 'UPDATE ' . $this->Table . ' SET ';
      $Values = [];
      foreach($Array as $Key => $Value){
        if(count($Values) > 0){
          $Statement .= ', ';
        }
        $Statement .= "`{$Key}`" . ' = ?';
        $Values[] = $Value;
      }
      $Statement .= ' WHERE id = ?';
      $Values[] = $this->get('id');

      // Debut Information
      $this->Logger->debug($Statement);
      $this->Logger->debug($Values);

      // Execute Statement
      $this->Database->update($Statement,$Values);

      // Retrieve Object
      $this->retrieve(true);

      // Return Object
      return $this;
    } catch (Exception $e) {

			// If an exception is caught, log an error message
      $this->Logger->error('Error: '.$e->getMessage());
      return null;
    }
	}

  /**
   * Link object to this one.
   *
   * @param string $Table of object to link.
   * @param string $Id of the object to link.
   * @return object $this
   */
	public function link($Table, $Id){

    // Get the record
    $Records = $this->Database->select("SELECT * FROM `" . $Table . "` WHERE `id` = ?", [$Id]);

    // Validate record
    if(count($Records) > 0){

      // Get first record
      $Record = $Records[0];

      // Create Relationship
      if($this->Relationship->create($this->Table, $this->get('id'), $Table, $Record['id'])){

        // Save new Relationship
        if(!isset($this->Relationships[$Table][$Record['id']])){
          $this->Relationships[$Table][$Record['id']] = $Record;
        }
      }

      // Additionnal Actions
      switch($Table){
        case"users":
        case"organizations":
        case"groups":
        case"roles":
        case"permissions":
          break;
        default:
          break;
      }
    }

    // Return
    return $this;
  }

  /**
   * Unlink object to this one.
   *
   * @param string $Table of object to unlink.
   * @param string $Id of the object to unlink.
   * @return object $this
   */
	public function unlink($Table, $Id){

    // Get the record
    $Records = $this->Database->select("SELECT * FROM `" . $Table . "` WHERE `id` = ?", [$Id]);

    // Validate record
    if(count($Records) > 0){

      // Get first record
      $Record = $Records[0];

      // Create Relationship
      if($this->Relationship->delete($this->Table, $this->get('id'), $Table, $Record['id'])){

        // Save new Relationship
        if(isset($this->Relationships[$Table][$Record['id']])){

          // Unset this object
          unset($this->Relationships[$Table][$Record['id']]);

          // If the table is empty, unset it
          if(count($this->Relationships[$Table]) <= 0){
            unset($this->Relationships[$Table]);
          }
        }
      }

      // Additionnal Actions
      switch($Table){
        case"users":
        case"organizations":
        case"groups":
        case"roles":
        case"permissions":
          break;
        default:
          break;
      }
    }

    // Return
    return $this;
  }

  /**
   * Delete this group.
   *
   * @return object|void
   * @throws Exception
   */
	public function delete(){

    // Retrieve Record
    $this->retrieve();

    // Delete Relationships
    foreach($this->Relationships as $Table => $Records){
      foreach($Records as $Id => $Record){
        $this->Relationship->delete($this->Table, $this->get('id'), $Table, $Record['id']);
      }
    }

    // Delete this Object
    $result = $this->Database->delete("DELETE FROM " . $this->Table . " WHERE `id` = ?", [$this->get('id')]);

    // Nullify Object
    $this->Object = null;

    // Return Result
    return $this;
  }
}
