<?php

// Declaring namespace
namespace LaswitchTech\phpAUTH\Types;

// Import User class into the global namespace
use LaswitchTech\phpAUTH\Objects\User;

//Import Exception class into the global namespace
use \Exception;

class Bearer {

  // phpLogger
  private $Logger = null;

  // phpDB
  private $Database = null;

  /**
   * Create a new Bearer instance.
   *
   * @param  Object  $Logger
   * @param  Object  $Database
   * @return void
   * @throws Exception
   */
  public function __construct($Logger, $Database){

    // Initiate phpLogger
    $this->Logger = $Logger;

    // Initiate phpDB
    $this->Database = $Database;

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
   * get Authentication Header.
   *
   * @return string
   * @throws Exception
   */
  private function getAuthenticationHeader(){
    try{

      // Initialize Headers
      $headers = null;

      // Check if Authorization header exists in $_SERVER array and store it in $headers variable
      if(isset($_SERVER['Authorization'])){
        $headers = trim($_SERVER['Authorization']);

      // Check if HTTP_AUTHORIZATION header exists in $_SERVER array and store it in $headers variable
      } else if (isset($_SERVER['HTTP_AUTHORIZATION'])){
        $headers = trim($_SERVER['HTTP_AUTHORIZATION']);

      // If apache_request_headers() function exists, use it to retrieve the Authorization header
      } elseif (function_exists('apache_request_headers')){
        $requestHeaders = apache_request_headers();
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        if (isset($requestHeaders['Authorization'])){
          $headers = trim($requestHeaders['Authorization']);
        }
      }

      // Return the Authorization header, or null if it wasn't found
      return $headers;
    } catch (Exception $e) {

      // If an exception is caught, log an error message and return null
      $this->Logger->error('Error: '.$e->getMessage());
      return null;
    }
  }

  /**
   * get Bearer Token.
   *
   * @return string
   * @throws Exception
   */
  private function getBearerToken() {
    try{

      // Get the Authentication header
      $headers = $this->getAuthenticationHeader();
      if (!empty($headers)) {

        // Check if the header contains a Bearer token
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches) && isset($matches[1]) && !empty($matches[1])) {

          // If the token is found, decode it and return it along with the current timestamp
          return base64_decode($matches[1]);
        }
      }

      // If no Bearer token is found, return null
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

			// Retrieve Bearer Token
      $token = $this->getBearerToken();

      // Validate Bearer Token
      if(!$token){
        return false;
      }

      // Split the token into its component parts
      $parts = explode(':', $token);

      // Validate the number of parts
      if (count($parts) != 2) {
        throw new Exception("Invalid number of parts in Token");
      }

      // Extract the user ID, token string, and token hash
      $Username = $parts[0];
      $TokenString = $parts[1];
      $TokenHash = hash('sha256', $Username . ':' . $TokenString);

      // Create User Object
      $User = new User($Username, 'username', $this->Logger, $this->Database);

      // Retrieve User
      $result = $User->retrieve();

      // Check if user exist
      if(!$result){
        throw new Exception("Could not find User");
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

      // Validate Hash
      // Compare the token hash to the input token hash
      if(!hash_equals($TokenHash, $User->get('bearerToken'))) {
        throw new Exception("Invalid Bearer Token");
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
