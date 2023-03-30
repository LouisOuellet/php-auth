<?php

// Declaring namespace
namespace LaswitchTech\phpAUTH\Objects;

// Import phpSMTP Class into the global namespace
use LaswitchTech\phpSMTP\phpSMTP;

// Import phpIMAP Class into the global namespace
use LaswitchTech\phpIMAP\phpIMAP;

// Import Role Class into the global namespace
use LaswitchTech\phpAUTH\Objects\Relationship;

// Import Exception class into the global namespace
use \Exception;

// Import DateTime class into the global namespace
use \DateTime;

class User {

  // Constants
  const Type = 'user';
  const Types = 'users';
  const Name = 'User';
  const minPasswordLength = 8;
  const disallowedPasswords = ['password', '123456', 'qwerty'];

  // phpLogger
  private $Logger = null;

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

  // Relationship
  private $Relationship = null;
  private $Relationships = [];

  // phpSMTP
  private $SMTP = null;

  // phpIMAP
  private $IMAP = null;

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
  private $ContactInfo = [
    'address',
    'city',
    'state',
    'country',
    'zipcode',
    'phone',
    'domain',
    'database',
    'server',
  ];

  // Identification
  private $Id = null;
  private $Identifier = null;

  // Security
  private $Token = null;
  private $Password = null;
  private $maxAttempts = 5;
  private $window = 300;
  private $lockoutDuration = 1800;

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
  public function __construct($Id, $Identifier, $Logger, $Database, $Object = null){

    // Initiate Id
    $this->Id = $Id;

    // Initiate Identifier
    $this->Identifier = $Identifier;

    // Initiate phpLogger
    $this->Logger = $Logger;

    // Initiate phpDB
    $this->Database = $Database;

    // Initiate Relationship
    $this->Relationship = new Relationship($Logger, $Database);

    // Setup Columns
    $this->Columns = $this->Database->getColumns($this->Table);

    // Setup Integers and Strings
    foreach($this->Columns as $Column => $DataType){
      if(in_array($DataType,['int','bigint'])){
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
   * Generate a strong password.
   *
   * @param  int|null $length
   * @return string
   */
	private function generatePassword($length = 16) {
    // Define possible characters
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789~!@#$%^&*()_+';

    // Get the length of the character list
    $charLength = strlen($chars);

    // Generate random password
    $this->Password = '';
    for ($i = 0; $i < $length; $i++) {
      $this->Password .= $chars[rand(0, $charLength - 1)];
    }

    return $this->Password;
  }

  /**
   * Generate a Bearer Token.
   *
   * @param  int|null $length
   * @return string
   */
	private function generateToken($length = 32) {

    // Generate a random string for the token
    $this->Token = bin2hex(random_bytes($length));

    // Combine the user ID and the token string
    $TokenData = $this->Id . ':' . $this->Token;

    // Hash the token data using a secure hashing algorithm
    return hash('sha256', $TokenData);
  }

  /**
   * Get saved generated password.
   *
   * @param  int|null $length
   * @return string
   */
	public function getPassword() {

    // Return the saved generated password
    return $this->Password;
  }

  /**
   * Get saved generated token.
   *
   * @param  int|null $length
   * @return string
   */
	public function getToken() {

    // Return the saved generated token
    return $this->Token;
  }

  /**
   * Retrieve User.
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

      // Find the User
      $User = $this->Database->select("SELECT * FROM " . $this->Table . " WHERE `" . $this->Identifier . "` = ?", [$this->Id]);

      // Validate User
      if(count($User) <= 0){

        // Debug Information
        $this->Logger->debug(count($User));
        $this->Logger->debug($User);

        // Throw Exception
        throw new Exception("Unable to find User.");
      }

      // Identify User
      $this->Object = $User[0];

      // Parse User
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
   * Get data from this user.
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

      // Debug Information
      $this->Logger->debug($Key);

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
        $this->Logger->debug($this->Object);

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
   * Create a new user.
   *
   * @param array $Data Associative array of user data.
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
      if($this->Identifier !== "username"){
        throw new Exception("User must identified by the username.");
      }

      // Validate Username
      if(!filter_var($this->Id, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Username must a valid email address.");
      }

      // Find the User
      $User = $this->Database->select("SELECT * FROM " . $this->Table . " WHERE `" . $this->Identifier . "` = ?", [$this->Id]);

      // Validate User does not exist
      if(count($User) > 0){
        throw new Exception("User already exist.");
      }

      // Add/Overwrite username into Data
      $Data[$this->Identifier] = $this->Id;

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

      // Save isAPI Switch
      $Data['isAPI'] = $isAPI;

      // Check for additionnal validations
      if($Data['isAPI']){

        // Hash the token data using a secure hashing algorithm
        $Data['bearerToken'] = $this->generateToken();
      } else {

        // Generate a password if none were provided
        if(!isset($Data['password'])){
          $Data['password'] = $this->generatePassword();
        }

        // Check password length
        if (strlen($Data['password']) < self::minPasswordLength) {
          throw new Exception("Password is not long enough.");
        }

        // Check disallowed passwords
        if (in_array(strtolower($Data['password']), self::disallowedPasswords)) {
          throw new Exception("Password is too easy.");
        }

        // Check for mix of character types
        if (!preg_match('/[A-Z]/', $Data['password']) || !preg_match('/[a-z]/', $Data['password']) || !preg_match('/[0-9]/', $Data['password']) || !preg_match('/[\W]/', $Data['password'])) {
          throw new Exception("Password must contain at least 1 uppercase, 1 lowercase, 1 number and 1 symbol.");
        }

        // Create Salt
        $Salt = bin2hex(random_bytes(16));

        // Hash the password
        $Hash = password_hash($Data['password'] . $Salt, PASSWORD_DEFAULT);

        // Save password
        $Data['passwordSalt'] = $Salt;
        $Data['passwordHash'] = $Hash;
      }

      // Create User Array
      $User = [];
      foreach($Data as $Key => $Value){

        // Debut Information
        $this->Logger->debug("Does {$Key} exist? " . !isset($this->Columns[$Key]));

        // Unset Value if it does not exist
        if(!isset($this->Columns[$Key])){

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
        $this->Logger->debug("Is {$Key} empty? " . (empty($Value) || $Value === '' || $Value === null));

        // Unset Value if it's empty
        if(empty($Value) || $Value === '' || $Value === null){

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
        $User[$Key] = $Value;
      }

      // Identify the Domain of the user
      $Parts = explode('@',$Data['username']);
      $Data['domain'] = end($Parts);
      $User['domain'] = $Data['domain'];

      // Build insert statement
      $Statement = 'INSERT INTO ' . $this->Table . ' (' . implode(',',array_keys($User)) . ') VALUES (' . implode(',', array_fill(0, count($User), '?')) . ')';

      // Concatenate Values
      foreach($User as $Key => $Value){
        if(is_array($Value)){
          $User[$Key] = json_encode($Value, JSON_UNESCAPED_SLASHES);
        }
      }
      $Values = array_values($User);

      // Debug Information
      $this->Logger->debug($Statement);
      $this->Logger->debug($Values);

      // Execute Statement
      $Id = $this->Database->insert($Statement, $Values);

      // Check if User was created
      if(!$Id){
        throw new Exception("An error occured during the creation of the user.");
      }

      // Retrieve new Object
      $this->retrieve();

      // Debug Information
      $this->Logger->debug([$User['domain'],1]);

      // Look for Organizations
      $Organizations = $this->Database->select("SELECT * FROM organizations WHERE `domain` = ? AND `isDefault` = ?", [$User['domain'],1]);

      // Debug Information
      $this->Logger->debug($Organizations);

      // Link Organizations
      if(count($Organizations) > 0){
        foreach($Organizations as $Organization){
          $this->link('organizations',$Organization['id']);
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
   * Save/Update data of this user.
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
        $this->Logger->debug("Does {$Key} exist? " . !isset($this->Columns[$Key]));

        // Unset Value if it does not exist
        if(!isset($this->Columns[$Key])){

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
        $this->Logger->debug("Is {$Key} empty? " . (empty($Value) || $Value === '' || $Value === null));

        // Unset Value if it's empty
        if(empty($Value) || $Value == '' || $Value == null){

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
        if($Value == ''){
          $Value = NULL;
        }
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

      // Update User's contact information from organization
      if($this->get('isContactInfoDynamic')){
        if(isset($this->Relationships['organizations'])){
          foreach($this->Relationships['organizations'] as $Id => $Organization){

            // Setup Fields to update
            $Fields = [];

            // Check for fields to update
            foreach($this->ContactInfo as $Key){
              if($this->get($Key) !== $Organization[$Key]){

                // Save key
                $Fields[$Key] = $Organization[$Key];
              }
            }

            // Save if some record needs modification
            if(count($Fields) > 0){

              // Save Object
              $this->save($Fields);
            }
          }
        }
      }

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
        case"organizations":
          // Check if Contact Info is dynamic
          if($this->get('isContactInfoDynamic')){

            // Fields to Update
            $Fields = [];

            // Check for fields to update
            foreach($this->ContactInfo as $Key){
              if($this->get($Key) !== $Record[$Key]){

                // Save key
                $Fields[$Key] = $Record[$Key];
              }
            }

            // Save if some record needs modification
            if(count($Fields) > 0){

              // Save Object
              $this->save($Fields);
            }
          }
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
        case"organizations":
          // Check if Contact Info is dynamic
          if($this->get('isContactInfoDynamic')){

            // Fields to Update
            $Fields = [];

            // Check for fields to update
            foreach($this->ContactInfo as $Key){
              if($this->get($Key) === $Record[$Key]){

                // Save key
                $Fields[$Key] = null;
              }
            }

            // Save if some record needs modification
            if(count($Fields) > 0){

              // Save Object
              $this->save($Fields);
            }
          }
          break;
        default:
          break;
      }
    }

    // Return
    return $this;
  }

  /**
   * Verify if user is rate limited.
   *
   * @return boolean
   */
  public function isRateLimited() {
    $currentTime = time();
    $timeDifference = $currentTime - $this->get('lastAttempt');

    // Debug Information
    $this->Logger->debug("Time Difference is currently at : {$timeDifference}");

    if ($this->get('attempts') >= $this->maxAttempts && $timeDifference <= $this->window) {
      return true;
    }

    return false;
  }

  /**
   * Verify if user is locked out.
   *
   * @return boolean
   */
  public function isLockedOut() {
    $currentTime = time();
    $timeDifference = $currentTime - $this->get('lastAttempt');

    // Debug Information
    $this->Logger->debug("Time Difference is currently at : {$timeDifference}");

    if ($this->get('attempts') >= $this->maxAttempts && $timeDifference <= $this->lockoutDuration) {
      return true;
    }

    return false;
  }

  /**
   * record an attempt.
   *
   * @return void
   */
  public function recordAttempt() {
    $currentTime = time();
    $timeDifference = $currentTime - $this->get('lastAttempt');

    $Array = [
      "attempts" => $this->get('attempts'),
      "lastAttempt" => $this->get('lastAttempt'),
    ];

    // Reset attempts if outside the rate-limiting window
    if ($timeDifference > $this->window) {
      $Array['attempts'] = 0;
    }

    // Increment attempts and update last_attempt
    $Array['attempts'] += 1;
    $Array['lastAttempt'] = $currentTime;

    // Log Attempt
    $this->Logger->info("User [" . $this->get('username') . "] attempted to authenticate");

    // Save the updated attempts and last_attempt values
    $this->save($Array);
  }

  /**
   * reset attempts.
   *
   * @return void
   */
  public function resetAttempts() {
    $Array = [
      "attempts" => 0,
      "lastAttempt" => NULL,
    ];

    // Log Attempt
    $this->Logger->success("User [" . $this->get('username') . "] was authenticated");

    // Save the updated attempts and last_attempt values
    $this->save($Array);
  }

  /**
   * Validate Password of this user.
   *
   * @param  string $Password
   * @return void
   * @throws Exception
   */
	public function validate($Password){
    try{

      // Check if User was retrieved
      if(!$this->Object){
        throw new Exception("Could not identify the user.");
      }

      // Sanitize Password
      if(!is_string($Password)){
        throw new Exception("Invalid password.");
      }

      // Get User's Database
      $Database = $this->get('database');
      if($Database === null){
        $Database = '';
      }
      $Database = strtoupper($Database);

      // Validate Password
      switch($Database){
        case"SQL":

          // Validate against the password store in the SQL Database
          return password_verify($Password . $this->Object['passwordSalt'], $this->Object['passwordHash']);
          break;
        case"IMAP":

          // Check if phpIMAP was Initialized and Initialize it if it's not
          if(!$this->IMAP){
            $this->IMAP = new phpIMAP();
          }

          // Check if Database Server Information is available
          if($this->get('server')){

            // Retrieve Database Server Information
            $Server = $this->get('server');

            // Validate Server Information
            if(isset($Server['host'], $Server['port'], $Server['encryption'])){

              // Attempt to login and return the result
              return $this->IMAP->login($this->get('username'), $Password, $Server['host'], $Server['port'], $Server['encryption']);
            } else {
              throw new Exception("Invalid Database Server Information.");
            }
          } else {
            throw new Exception("Unable to validate password using :" . $Database . ".");
          }
          break;
        case"SMTP":

          // Check if phpSMTP was Initialized and Initialize it if it's not
          if(!$this->SMTP){
            $this->SMTP = new phpSMTP();
          }

          // Check if Database Server Information is available
          if($this->get('server')){

            // Retrieve Database Server Information
            $Server = $this->get('server');

            // Validate Server Information
            if(isset($Server['host'], $Server['port'], $Server['encryption'])){

              // Attempt to login and return the result
              return $this->SMTP->login($this->get('username'), $Password, $Server['host'], $Server['port'], $Server['encryption']);
            } else {
              throw new Exception("Invalid Database Server Information.");
            }
          } else {
            throw new Exception("Unable to validate password using :" . $this->get('database') . ".");
          }
          break;
        default:
          throw new Exception("Unknown database.");
          break;
      }

      // Return null
      return null;
    } catch (Exception $e) {

      // If an exception is caught, log an error message
      $this->Logger->error('Error: '.$e->getMessage());
      return null;
    }
  }

  /**
   * Delete this user.
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
