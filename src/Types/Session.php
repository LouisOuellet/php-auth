<?php

// Declaring namespace
namespace LaswitchTech\phpAUTH\Types;

//Import phpConfigurator class into the global namespace
use LaswitchTech\phpConfigurator\phpConfigurator;

// Import phpLogger class into the global namespace
use LaswitchTech\phpLogger\phpLogger;

// Import Database Class into the global namespace
use LaswitchTech\phpDB\Database;

// Import User class into the global namespace
use LaswitchTech\phpAUTH\Objects\User;

//Import Exception class into the global namespace
use \Exception;

class Session {

  // phpLogger
  private $Logger = null;
	private $Level = 1;

  // Configurator
  private $Configurator = null;

  // phpDB
  private $Database = null;

  /**
   * Create a new Session instance.
   *
   * @param  Object  $Logger
   * @param  Object  $Database
   * @return void
   * @throws Exception
   */
  public function __construct($Logger = null, $Database = null) {

    // Initialize Configurator
    $this->Configurator = new phpConfigurator('auth');

    // Retrieve Log Level
    $this->Level = $this->Configurator->get('logger', 'level') ?: $this->Level;

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

      // Check if a Session was started
      if(session_status() === PHP_SESSION_NONE) {
        throw new Exception("Session is was not started.");
      }

      return true;
    } catch (Exception $e) {

			// If an exception is caught, log an error message
      $this->Logger->error('Error: '.$e->getMessage());
      throw new Exception('Error: '.$e->getMessage());
    }
	}

  /**
   * get Session Id.
   *
   * @return string
   * @throws Exception
   */
	private function getId(){
    try {

			// Initialize Id
      $Id = null;

      // Retrieve Session ID
      if(isset($_SESSION,$_SESSION['sessionId'])){
        $Id = $_SESSION['sessionId'];
      } elseif(session_id()){
        $Id = session_id();
      }

      return $Id;
    } catch (Exception $e) {

			// If an exception is caught, log an error message
      $this->Logger->error('Error: '.$e->getMessage());
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

      // Debug Information
      $this->Logger->debug("Attempting connection using SESSION");

      // Check if Session Authentication is enabled
      if(!$this->Configurator->get('auth','session')){
        throw new Exception("Session Authentication is Disabled");
      }

			// Retrieve sessionId
      $sessionId = $this->getId();

      // Validate Session Id
      if(!$sessionId){
        return false;
      }

      // Find an Active Session
      $Session = $this->Database->select("SELECT * FROM sessions WHERE sessionId = ?", [$sessionId]);

      // Validate Session
      if(!isset($Session[0])){
        return false;
      }

      // Identify Session
      $Session = $Session[0];

      // Create User Object
      $User = new User($Session['username'], 'username', $this->Logger, $this->Database);

      // Retrieve User
      $User->retrieve();

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

      // Return the User Object
      return $User;
    } catch (Exception $e) {

			// If an exception is caught, log an error message
      $this->Logger->error('Error: '.$e->getMessage());
      return false;
    }
  }

  /**
   * Get User Consent.
   *
   * @return string JSON
   * @throws Exception
   */
  private function getUserConsent(){
    try{

      // Retrieve User Consent
      $userConsent = [];
      // Essentials
      if(isset($_COOKIE,$_COOKIE['cookiesAcceptEssentials'])){

        // Set Cookie `cookiesAcceptEssentials`
        $userConsent[] = 'cookiesAcceptEssentials';
      }
      // Performance
      if(isset($_COOKIE,$_COOKIE['cookiesAcceptPerformance'])){

        // Set Cookie `cookiesAcceptPerformance`
        $userConsent[] = 'cookiesAcceptPerformance';
      }
      // Quality
      if(isset($_COOKIE,$_COOKIE['cookiesAcceptQuality'])){

        // Set Cookie `cookiesAcceptQuality`
        $userConsent[] = 'cookiesAcceptQuality';
      }
      // Personalisations
      if(isset($_COOKIE,$_COOKIE['cookiesAcceptPersonalisations'])){

        // Set Cookie `cookiesAcceptPersonalisations`
        $userConsent[] = 'cookiesAcceptPersonalisations';
      }

      // Convert to JSON
      $userConsent = json_encode($userConsent, JSON_UNESCAPED_SLASHES);

      // Return
      return $userConsent;
    } catch (Exception $e) {

			// If an exception is caught, log an error message
      $this->Logger->error('Error: '.$e->getMessage());
    }
  }

  /**
   * Get User Agent.
   *
   * @return string JSON
   * @throws Exception
   */
  private function getUserAgent(){
    try{

      // Retrieve User Agent
      $userAgent = 'Unknown';
      if(isset($_SERVER['HTTP_USER_AGENT'])){
        $userAgent = json_encode($_SERVER['HTTP_USER_AGENT']);
      }

      // Return
      return $userAgent;
    } catch (Exception $e) {

			// If an exception is caught, log an error message
      $this->Logger->error('Error: '.$e->getMessage());
    }
  }

  /**
   * Get Client Browser.
   *
   * This function attempts to determine the user's web browser based on the 'HTTP_USER_AGENT'
   * header in the request. If the header cannot be found or is not recognizable, the function
   * returns 'Unknown'.
   *
   * @return string The name of the user's web browser
   * @throws Exception
   */
  private function getClientBrowser(){

    try{

      if(!isset($_SERVER['HTTP_USER_AGENT'])){
        // If no recognizable browser was found, return 'Unknown'
        return 'Unknown';
      }

      // Retrieve the HTTP_USER_AGENT header and convert it to lowercase for easier comparison
      $t = strtolower($_SERVER['HTTP_USER_AGENT']);

      // Append a space to the beginning of the header value to make the code below easier to write
      $t = " " . $t;

      // Check the header value for each browser in turn. If a match is found, return the browser name
      if     (strpos($t, 'opera'     ) || strpos($t, 'opr/')     ) return 'Opera'            ;
      elseif (strpos($t, 'edge'      )                           ) return 'Edge'             ;
      elseif (strpos($t, 'chrome'    )                           ) return 'Chrome'           ;
      elseif (strpos($t, 'safari'    )                           ) return 'Safari'           ;
      elseif (strpos($t, 'firefox'   )                           ) return 'Firefox'          ;
      elseif (strpos($t, 'msie'      ) || strpos($t, 'trident/7')) return 'Internet Explorer';

      // If no recognizable browser was found, return 'Unknown'
      return 'Unknown';

    } catch (Exception $e) {

      // If an exception is caught, log an error message and return 'Unknown'
      $this->Logger->error('Error: '.$e->getMessage());
      return 'Unknown';
    }
  }

  /**
   * Get Client Ip.
   *
   * @return string
   * @throws Exception
   */
  private function getClientIp(){
    try{

      // Initialize Ip Address
      $ipaddress = null;

      // Check for the IP address in several possible HTTP headers
      if(getenv('HTTP_CLIENT_IP')){
        $ipaddress = getenv('HTTP_CLIENT_IP');
      } elseif(getenv('HTTP_X_FORWARDED_FOR')){
        $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
      } elseif(getenv('HTTP_X_FORWARDED')){
        $ipaddress = getenv('HTTP_X_FORWARDED');
      } elseif(getenv('HTTP_FORWARDED_FOR')){
        $ipaddress = getenv('HTTP_FORWARDED_FOR');
      } elseif(getenv('HTTP_FORWARDED')){
        $ipaddress = getenv('HTTP_FORWARDED');
      } elseif(getenv('REMOTE_ADDR')){
        $ipaddress = getenv('REMOTE_ADDR');
      } elseif(defined('STDIN')){
        $ipaddress = 'LOCALHOST';
      } else {
        $ipaddress = 'UNKNOWN';
      }

      // Check if the IP address is one of the known values for localhost and if so, change it to 'LOCALHOST'
      if(in_array($ipaddress,['127.0.0.1','127.0.1.1','::1'])){ $ipaddress = 'LOCALHOST'; }

      // Return Ip Address
      return $ipaddress;

    } catch (Exception $e) {

      // If an exception is caught, log an error message and return 'UNKNOWN'
      $this->Logger->error('Error: '.$e->getMessage());
      return 'UNKNOWN';

    }
  }

  /**
   * Save Session.
   *
   * @param  Object  $User
   * @return boolean
   * @throws Exception
   */
  Public function save($User){
    try{

      // Check if User contains an object
      if(!$User){
        throw new Exception("This User Object does not contain anything");
      }

      // Find all user Sessions to be purged
      $Sessions = $this->Database->select("SELECT * FROM sessions WHERE username = ? AND sessionId != ?", [$User->get('username'),session_id()]);

      // Delete any existing sessions that do not match with the session id
      foreach($Sessions as $session){
        $this->Database->delete("DELETE FROM sessions WHERE id = ?", [$session['id']]);
      }

      // Find an active session
      $Sessions = $this->Database->select("SELECT * FROM sessions WHERE username = ? AND sessionId = ?", [$User->get('username'),session_id()]);

      // Check if an active session was found
      if(count($Sessions) > 0){
        $Session = $Sessions[0];

        // Update the session
        $this->Database->update("UPDATE sessions SET userAgent = ?, userBrowser = ?, userIP = ?, userConsent = ? WHERE sessionId = ?", [$this->getUserAgent(),$this->getClientBrowser(),$this->getClientIp(),$this->getUserConsent(),session_id()]);
      } else {

        // Create the session
        $this->Database->insert("INSERT INTO sessions (sessionId,username,userAgent,userBrowser,userIP,userConsent) VALUES (?,?,?,?,?,?)", [session_id(),$User->get('username'),$this->getUserAgent(),$this->getClientBrowser(),$this->getClientIp(),$this->getUserConsent()]);
      }

      // Update the session id
      $User->save('sessionId', session_id());

      // Build Session
      // Save Session Id
      $_SESSION['sessionId'] = session_id();

      // Save Timestamp
      if(isset($_REQUEST['remember'])){
        $_SESSION['timestamp'] = time() + (86400 * 30);
      } else {
        $_SESSION['timestamp'] = time();
      }

      // Return true if completed
      return true;
    } catch (Exception $e) {

			// If an exception is caught, log an error message
      $this->Logger->error('Error: '.$e->getMessage());
      return false;
    }
  }

  /**
   * Clear Session.
   *
   * @param  Object  $User
   * @return boolean
   * @throws Exception
   */
  Public function clear($User){
    try{

      // Delete stored session
      $this->Database->delete("DELETE FROM sessions WHERE username = ?", [$User->get('username')]);

      // clear session variables
      if(isset($_SESSION) && !empty($_SESSION)){
        foreach($_SESSION as $key => $value){ unset($_SESSION[$key]); }
      }

      // remove all session variables
      session_unset();

      // destroy the session
      session_destroy();

      // start a new session
      session_start();

      // return true if all was successfull
      return true;
    } catch (Exception $e) {

      // If an exception is caught, log an error message
      $this->Logger->error('Error: '.$e->getMessage());
      return false;
    }
  }
}
