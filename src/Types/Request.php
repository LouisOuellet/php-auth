<?php

// Declaring namespace
namespace LaswitchTech\phpAUTH\Types;

// Import User class into the global namespace
use LaswitchTech\phpAUTH\Objects\User;

//Import Exception class into the global namespace
use \Exception;

class Request {

  // phpLogger
  private $Logger = null;

  // phpDB
  private $Database = null;

	// phpCSRF
  private $CSRF = null;

  /**
   * Create a new Request instance.
   *
   * @param  Object  $Logger
   * @param  Object  $Database
   * @return void
   * @throws Exception
   */
  public function __construct($Logger, $Database, $CSRF){

    // Initiate phpLogger
    $this->Logger = $Logger;

    // Initiate phpDB
    $this->Database = $Database;

    // Initiate phpCSRF
    $this->CSRF = $CSRF;

    // Initialize Library
    $this->init();
  }

  /**
   * Init Library.
   *
   * @return void
   * @throws Exception
   */
	private function init(){
		try {

      return true;
    } catch (Exception $e) {

			// If an exception is caught, log an error message
      $this->Logger->error('Error: '.$e->getMessage());
      throw new Exception('Error: '.$e->getMessage());
    }
	}

  /**
   * get Request Credentials.
   *
   * @return array|null
   * @throws Exception
   */
  private function getRequestCredentials() {
    try{

      // Check if _REQUEST exist and if the header contains the 'username', 'password' and 'csrf' token
      if(isset($_REQUEST,$_REQUEST['username'],$_REQUEST['password'],$_REQUEST['csrf'])){

        // CSRF Protection Validation
        if($this->CSRF->validate()){

          // Return the username and password as decoded strings
          return [ "username" => $_REQUEST['username'], "password" => $_REQUEST['password'] ];
        }
      }

      // Return null if no credentials are found
      return null;
    } catch (Exception $e) {

      // If an exception is caught, log an error message and return null
      $this->Logger->error('Error: '.$e->getMessage());
      return null;
    }
  }

  /**
   * getAuthentication through Session.
   *
   * @return string
   * @throws Exception
   */
	public function getAuthentication(){
    try {

			// Retrieve Request Credentials
      $credentials = $this->getRequestCredentials();

      // Check if Credentials were retrieved
      if(!$credentials){
        throw new Exception("Could not find the credentials");
      }

      // Create User Object
      $User = new User($credentials['username'], 'username', $this->Logger, $this->Database);

      // Retrieve User
      $result = $User->retrieve();

      // Check if User was found
      if(!$result){
        throw new Exception("Could not find the user");
      }

			// Check if user is isLockedOut
			if($User->isLockedOut()){
				throw new Exception("User is currently locked out");
			}

			// Check if user is isLockedOut
			if($User->isRateLimited()){
				throw new Exception("User has reached the limit of attempts");
			}

      // Record Authentication Attempt
      $User->recordAttempt();

      // Validate Password
      if(!$User->validate($credentials['password'])){
        throw new Exception("Wrong password");
      }

      // Clear Any LogOut request
      if(isset($_REQUEST['logout'])){
        unset($_REQUEST['logout']);
      }
      if(isset($_REQUEST['signout'])){
        unset($_REQUEST['signout']);
      }

      // Return the User Object
      return $User;
    } catch (Exception $e) {

			// If an exception is caught, log an error message
      $this->Logger->error('Error: '.$e->getMessage());
      return false;
    }
  }
}
