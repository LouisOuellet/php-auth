<?php

// Declaring namespace
namespace LaswitchTech\phpAUTH;

//Import phpConfigurator class into the global namespace
use LaswitchTech\phpConfigurator\phpConfigurator;

// Import phpLogger class into the global namespace
use LaswitchTech\phpLogger\phpLogger;

class Authorization {

	// Logger
	private $Logger;
	private $Level = 1;

  // Configurator
  private $Configurator = null;

	// User
  private $User = null;

	// Groups
  private $Groups = [];

	// Roles
  private $Roles = [];

	// Relationships
  private $Relationships = [];

	// Hostnames
  private $Hostnames = [];

  /**
   * Create a new Authentication instance.
   *
   * @param  Object  $User
   * @param  Object  $Logger
   * @return void
   */
  public function __construct($User, $Logger = null){

    // Initialize Configurator
    $this->Configurator = new phpConfigurator('auth');

    // Retrieve Log Level
    $this->Level = $this->Configurator->get('logger', 'level') ?: $this->Level;

    // Retrieve Hostnames
    $this->Hostnames = $this->Configurator->get('auth', 'hostnames') ?: $this->Hostnames;

    // Initialize User
    $this->User = $User;

    // Initialize phpLogger
    $this->Logger = $Logger;
    if(!$this->Logger){
      $this->Logger = new phpLogger('auth');
    }
  }

  /**
   * Verify if the user access through this hostname.
   *
   * @return boolean
   */
	public function isAuthorized(){

		// Initiate Hostname
		$Hostname = 'localhost';

		// Retrieve Hostname
		if(isset($_SERVER['SERVER_NAME'])){
			$Hostname = $_SERVER['SERVER_NAME'];
		}
		if(isset($_SERVER['HTTP_HOST'])){
			$Hostname = $_SERVER['HTTP_HOST'];
		}

		// Log request
		$this->Logger->info("User [" . $this->User->get('username') . "] is requesting access through {$Hostname}");

		// Validate Hostname
		if(in_array($Hostname,$this->Hostnames)){

			// Log request
			$this->Logger->success("User [" . $this->User->get('username') . "] was granted access through {$Hostname}");

			// Return
			return true;
		} else {

			// Log request
			$this->Logger->error("User [" . $this->User->get('username') . "] was denied access through {$Hostname}");

			// Return
			return false;
		}
	}

  /**
   * Verify if the user has a specific permission.
   *
   * @return boolean
   */
	public function hasPermission($permissionName, $requiredLevel = 1){

		// Retrieve the Relationships if not already done
		if(!$this->Relationships){
			$this->Relationships = $this->User->get('relationships', true);
		}

		// Retrieve the Roles if not already done
		if(!$this->Roles && isset($this->Relationships['roles'])){
			$this->Roles = $this->Relationships['roles'];
		}

		// Retrieve the Groups if not already done
		if(!$this->Groups && isset($this->Relationships['groups'])){
			$this->Groups = $this->Relationships['groups'];

			// Retrieve the Roles of each Group
			foreach($this->Groups as $Id => $Group){
				$Relationships = $Group->get('relationships', true);

				// If Role is not already present, add it
				if(isset($Relationships['roles'])){
					foreach($Relationships['roles'] as $Rid => $Role){
						if(!isset($this->Roles['roles'][$Rid])){
							$this->Roles['roles'][$Rid] = $Role;
						}
					}
				}
			}
		}

		// Log request
		$this->Logger->info("User [" . $this->User->get('username') . "] is requesting ({$permissionName})");

    // Check permissions in user roles
    foreach ($this->Roles as $RoleId => $Role) {

			// Select Object
			$Object = current($Role);

			// Retrieve Roles's Permissions
      $permissions = $Object->get('permissions');

			// Debug Information
			$this->Logger->debug($permissions);

			// Validate Permission Level
      if (isset($permissions[$permissionName]) && $permissions[$permissionName] >= $requiredLevel) {

				// Log request
				$this->Logger->success("User [" . $this->User->get('username') . "] is requesting ({$permissionName}) and was granted access");

				// Return
        return true;
      }
    }

		// Log request
		$this->Logger->error("User [" . $this->User->get('username') . "] is requesting ({$permissionName}) and was denied access");

		// Return
    return false;
	}
}
