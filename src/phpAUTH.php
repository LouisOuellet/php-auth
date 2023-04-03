<?php

// Declaring namespace
namespace LaswitchTech\phpAUTH;

//Import phpConfigurator class into the global namespace
use LaswitchTech\phpConfigurator\phpConfigurator;

// Import phpLogger class into the global namespace
use LaswitchTech\phpLogger\phpLogger;

// Import Database Class into the global namespace
use LaswitchTech\phpDB\Database;

// Import phpCSRF Class into the global namespace
use LaswitchTech\phpCSRF\phpCSRF;

// Import Installer class into the global namespace
use LaswitchTech\phpAUTH\Installer;

// Import Authentication class into the global namespace
use LaswitchTech\phpAUTH\Authentication;

// Import Authorization class into the global namespace
use LaswitchTech\phpAUTH\Authorization;

// Import Management class into the global namespace
use LaswitchTech\phpAUTH\Management;

// Import Exception class into the global namespace
use \Exception;

class phpAUTH {

	// Logger
	private $Logger;
	private $Level = 1;

  // Configurator
  private $Configurator = null;

	// phpDB
  private $Database = null;

	// phpCSRF
  private $CSRF = null;

	// Installer
  public $Installer = null;

	// Authentication
  public $Authentication = null;

	// Authorization
  public $Authorization = null;

	// Management
  public $Management = null;

  /**
   * Create a new phpAUTH instance.
   *
   * @return void
   * @throws Exception
   */
  public function __construct(){

    // Initialize Configurator
    $this->Configurator = new phpConfigurator('auth');

    // Retrieve Log Level
    $this->Level = $this->Configurator->get('logger', 'level') ?: $this->Level;

    // Initiate phpLogger
    $this->Logger = new phpLogger('auth');

    //Initiate phpCSRF
    $this->CSRF = new phpCSRF();

    //Initiate phpDB
    $this->Database = new Database();

		// Initialize
		$this->init();
  }

  /**
   * Configure Library.
   *
   * @param  string  $option
   * @param  bool|int  $value
   * @return void
   * @throws Exception
   */
  public function config($option, $value){
		try {
			if(is_string($option)){
	      switch($option){
	        default:
	          throw new Exception("unable to configure $option.");
	          break;
	      }
	    } else{
	      throw new Exception("1st argument must be as string.");
	    }
		} catch (Exception $e) {

			// If an exception is caught, log an error message
			$this->Logger->error('Error: '.$e->getMessage());
		}

    return $this;
  }

  /**
   * Init Library.
   *
   * @return void
   * @throws Exception
   */
  public function init(){
    try {

      // Debug Information
      $this->Logger->debug("Initializing");

			// Check if Database is Connected
			if(!$this->Database->isConnected()){
				throw new Exception("Database is not connected.");
			}

      // Initialize Authentication
      $this->Authentication = new Authentication($this->Logger, $this->Database, $this->CSRF);

			// If a User is authenticated, Initialize Authorization
			if($this->Authentication && $this->Authentication->isConnected()){
				$this->Authorization = new Authorization($this->Authentication->User, $this->Logger);
			}
    } catch (Exception $e) {

			// If an exception is caught, log an error message
      $this->Logger->error('Error: '.$e->getMessage());
    }

    return $this;
  }

  /**
   * Install phpAuth and create the database tables required.
   *
   * @return void
   * @throws Exception
   */
	public function install(){

		// Initialize Installer
		$this->Installer = new Installer($this->Database, $this->Logger);

		// Return Installer
		return $this->Installer;
	}

  /**
   * Manage phpAuth components.
   *
   * @return void
   * @throws Exception
   */
	public function manage($Type){

		// Check available types
		if(!in_array($Type,['users','organizations','groups','roles','permissions'])){
			return null;
		}

		// Initialize Manager
		$this->Management = new Management($Type, $this->Database, $this->Logger, $this->CSRF);

		// Return Manager
		return $this->Management;
	}
}
