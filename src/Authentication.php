<?php

// Declaring namespace
namespace LaswitchTech\phpAUTH;

// Import Session class into the global namespace
use LaswitchTech\phpAUTH\Types\Session;

// Import Cookie class into the global namespace
use LaswitchTech\phpAUTH\Types\Cookie;

// Import Bearer class into the global namespace
use LaswitchTech\phpAUTH\Types\Bearer;

// Import Basic class into the global namespace
use LaswitchTech\phpAUTH\Types\Basic;

// Import Request class into the global namespace
use LaswitchTech\phpAUTH\Types\Request;

//Import Exception class into the global namespace
use \Exception;

class Authentication {

	// Logger
	private $Logger;

	// phpDB
  private $Database = null;

	// phpCSRF
  private $CSRF = null;

	// Session
  private $Session = null;

	// Cookie
  private $Cookie = null;

	// Bearer
  private $Bearer = null;

	// Basic
  private $Basic = null;

	// Request
  private $Request = null;

	// User
  public $User = null;

	// Method
  private $Method = null;

  /**
   * Create a new Authentication instance.
   *
   * @param  Object  $Database
   * @param  Object  $Logger
   * @param  Object  $CSRF
   * @return void
   * @throws Exception
   */
  public function __construct($Database, $Logger, $CSRF){

    // Initialize phpLogger
    $this->Logger = $Logger;

    // Initialize phpDB
    $this->Database = $Database;

    // Initialize phpCSRF
    $this->CSRF = $CSRF;

    // Initialize Library
    return $this->init();
  }

  /**
   * Init Library.
   *
   * @return void
   * @throws Exception
   */
	private function init(){
		try {

			// Check if Database is Connected
			if(!$this->Database->isConnected()){
				throw new Exception("Database is not connected.");
			}

			// Initialize Session
			$this->Session = new Session($this->Logger, $this->Database);

			// Initialize Cookie
			$this->Cookie = new Cookie($this->Logger, $this->Database);

			// Initialize Bearer
			$this->Bearer = new Bearer($this->Logger, $this->Database);

			// Initialize Basic
			$this->Basic = new Basic($this->Logger, $this->Database);

			// Initialize Request
			$this->Request = new Request($this->Logger, $this->Database, $this->CSRF);

			// Initialize Authentication
			$this->authenticate();

			// Check if a logout is requested
			if(isset($_REQUEST['logout']) || isset($_REQUEST['signout'])){

				// Check if user is logged in
				if($this->isConnected()){

					// CSRF Protection Validation
					if($this->CSRF->validate()){

						// Logout User
						$this->logout();
					} else {
						throw new Exception("Cross Site Forgery Detected");
					}
				}
			}

			// Return this Object
			return $this;
    } catch (Exception $e) {

			// If an exception is caught, log an error message
      $this->Logger->error('Error: '.$e->getMessage());
    }
	}

  /**
   * Handling Authentication.
   *
   * @return void
   * @throws Exception
   */
	private function authenticate(){
		try {

			// Initialize User
			$User = null;

			// Retrieve User
			// by Session
			if(!$User){
				$User = $this->Session->getAuthentication();
				if($User){
					$this->Method = "Session";
				}
			}

			// by Cookie
			if(!$User){
				$User = $this->Cookie->getAuthentication();
				if($User){
					$this->Method = "Cookie";
				}
			}

			// by Bearer
			if(!$User){
				$User = $this->Bearer->getAuthentication();
				if($User){
					$this->Method = "Bearer";
				}
			}

			// by Basic
			if(!$User){
				$User = $this->Basic->getAuthentication();
				if($User){
					$this->Method = "Basic";
				}
			}

			// by Request
			if(!$User){
				$User = $this->Request->getAuthentication();
				if($User){
					$this->Method = "Request";
				}
			}

			// Check if a User was found
			if(!$User){
				throw new Exception("No user found");
			}

			// Reset Attempts
			$User->resetAttempts();

			// Store User
			$this->User = $User;

			// Save Session
			$this->Session->save($this->User);

			// Save Cookies
			$this->Cookie->save($this->User);
    } catch (Exception $e) {

			// If an exception is caught, log an error message
      $this->Logger->warning('Failed: '.$e->getMessage());
			return false;
    }
	}

  /**
   * Logout User.
   *
   * @return boolean
   * @throws Exception
   */
	public function logout(){
		try{

			// Validate CSRF Protection
			if(!$this->CSRF->validate()){
				throw new Exception("Request forgery detected");
			}

			// Clear Cookies
			$this->Cookie->clear();

			// Clear Session
			$this->Session->clear($this->User);

			// Clear User
			$this->User = null;

			// Return True
			return true;
		} catch (Exception $e) {

			// If an exception is caught, log an error message
      $this->Logger->warning('Failed: '.$e->getMessage());
			return false;
    }
	}

  /**
   * Check if User is connected.
   *
   * @return boolean
   */
	public function isConnected(){
		return ($this->User !== null);
	}
}
