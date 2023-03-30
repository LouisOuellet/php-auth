<?php

// Declaring namespace
namespace LaswitchTech\phpAUTH;

// Import Objects classes into the global namespace
use LaswitchTech\phpAUTH\Objects\User;
use LaswitchTech\phpAUTH\Objects\Organization;
use LaswitchTech\phpAUTH\Objects\Group;
use LaswitchTech\phpAUTH\Objects\Role;

//Import Exception class into the global namespace
use \Exception;

class Management {

	// Logger
	private $Logger;

	// phpDB
  private $Database = null;

	// phpCSRF
  private $CSRF = null;

	// Types of Manager
  private $Types = ['users','organizations','groups','roles','permissions'];
  private $Type = null;

	// Tables
  private $Tables = [
		'users' => 'users',
		'organizations' => 'organizations',
		'groups' => 'groups',
		'roles' => 'roles',
		'permissions' => 'permissions',
	];
  private $Table = null;

	// Identifiers
  private $Identifiers = [
		'users' => 'username',
		'organizations' => 'id',
		'groups' => 'name',
		'roles' => 'name',
		'permissions' => 'name',
	];
  private $Identifier = null;

	// Keys
  private $Keys = [
		'users' => 'username',
		'organizations' => 'name',
		'groups' => 'name',
		'roles' => 'name',
		'permissions' => 'name',
	];
  private $Key = null;

	// Names
  private $Names = [
		'users' => 'User',
		'organizations' => 'Organization',
		'groups' => 'Group',
		'roles' => 'Role',
		'permissions' => 'Permission',
	];
  private $Name = null;

	// Namespace
  private $Namespace = "\\LaswitchTech\\phpAUTH\\Objects\\";

  /**
   * Create a new Authentication instance.
   *
   * @param  Object  $Database
   * @param  Object  $Logger
   * @param  Object  $CSRF
   * @return void
   * @throws Exception
   */
  public function __construct($Type, $Database, $Logger, $CSRF){

		// Check available types
		if(!in_array($Type,$this->Types)){
			return null;
		}

		// Set the type of manager
		$this->Type = $Type;

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

			// Configure Manager
			$this->Table = $this->Tables[$this->Type];
			$this->Identifier = $this->Identifiers[$this->Type];
			$this->Name = $this->Names[$this->Type];
			$this->Key = $this->Keys[$this->Type];

			// Return this Object
			return $this;
    } catch (Exception $e) {

			// If an exception is caught, log an error message
      $this->Logger->error('Error: '.$e->getMessage());

			// Return null
			return null;
    }
	}

  /**
   * Create Method for Manager.
   *
   * @param  array  $Data
   * @return void
   * @throws Exception
   */
	public function create($Data = []){
		try {

			// Validate Data Type
			if(!is_array($Data)){
				throw new Exception("Invalid Data.");
			}

			// Validate Data Content
			if(empty($Data)){
				throw new Exception("No Data.");
			}

			// Validate Data Key
			if(!isset($Data[$this->Key])){
				throw new Exception("Main key missing.");
			}

			// Validate CSRF
			if(!$this->CSRF->validate()){
				throw new Exception("Unable to validate CSRF Token.");
			}

			// Retrieve Class name
			$Class = $this->Namespace . $this->Name;

			// Create Object
			$Object = new $Class($Data[$this->Key], $this->Key, $this->Logger, $this->Database);

			// Create Record
			$Result = $Object->new($Data);

			// Return Result
			return $Result;
    } catch (Exception $e) {

			// If an exception is caught, log an error message
      $this->Logger->error('Error: '.$e->getMessage());
    }
	}

  /**
   * Read Method for Manager.
   *
   * @param  null|string|int  $Arg1
   * @param  null|string|int  $Arg2
   * @return void
   * @throws Exception
   */
	public function read($Arg1 = null, $Arg2 = null){
		try {

			// Initialize Variables
			$Limit = '';
			$Id = null;
			$Array = [];

			// Parse Arguments
			foreach([$Arg1,$Arg2] as $Arg){
				if($Arg !== null){
					if(is_int($Arg)){
						$Limit = 'LIMIT ' . $Arg;
					}
					if(is_string($Arg)){
						$Id = $Arg;
					}
					if(!is_string($Arg) && !is_int($Arg)){
						throw new Exception("Invalid Argument.");
					}
				}
			}

			// Retrieve Class name
			$Class = $this->Namespace . $this->Name;

			// Check if an Id was provided
			if($Id){

				// Create Object
				$Object = new $Class($Id, $this->Identifier, $this->Logger, $this->Database);

				// Retrieve Record
				$Object->retrieve();

				// Return Result
				return $Object;
			} else {
				// Build SQL Statement
				$Statement = "SELECT {$this->Identifier} FROM {$this->Table} {$Limit}";

				// Execute Statement
				$Results = $this->Database->select($Statement);

				// Create Objects
				foreach($Results as $Result){

					// Create Object
					$Object = new $Class($Result[$this->Identifier], $this->Identifier, $this->Logger, $this->Database);

					// Retrieve Record
					$Object->retrieve();

					// Save Object
					$Array[$Result[$this->Identifier]] = $Object;
				}

				// Return Result
				return $Array;
			}
    } catch (Exception $e) {

			// If an exception is caught, log an error message
      $this->Logger->error('Error: '.$e->getMessage());
    }
	}

  /**
   * Update Method for Manager.
   *
   * @param  string|int  $Id
   * @param  array  $Data
   * @return void
   * @throws Exception
   */
	public function update($Id, $Data){
		try {

			// Retrieve Class name
			$Class = $this->Namespace . $this->Name;

			// Check if an Id was provided
			if($Id){

				// Create Object
				$Object = new $Class($Id, $this->Identifier, $this->Logger, $this->Database);

				// Retrieve Record
				$Object->retrieve();

				// Update Record
				$Object->save($Data);

				// Return Result
				return $Object;
			}
		} catch (Exception $e) {

			// If an exception is caught, log an error message
      $this->Logger->error('Error: '.$e->getMessage());
    }
	}

  /**
   * Delete Method for Manager.
   *
   * @param  null|string|int  $Arg1
   * @param  null|string|int  $Arg2
   * @return void
   * @throws Exception
   */
	public function delete($Id){
		try {

			// Retrieve Class name
			$Class = $this->Namespace . $this->Name;

			// Check if an Id was provided
			if($Id){

				// Create Object
				$Object = new $Class($Id, $this->Identifier, $this->Logger, $this->Database);

				// Retrieve Record
				$Object->retrieve();

				// Update Record
				$Object->delete();

				// Return Result
				return $Object;
			}
		} catch (Exception $e) {

			// If an exception is caught, log an error message
      $this->Logger->error('Error: '.$e->getMessage());
    }
	}

  /**
   * Link Method for Manager.
   *
   * @param  string|int  $Id
   * @param  array  $Data
   * @return void
   * @throws Exception
   */
	public function link($Id, $Data){
		try {

			// // Retrieve Class name
			// $Class = $this->Namespace . $this->Name;
			//
			// // Check if an Id was provided
			// if($Id){
			//
			// 	// Create Object
			// 	$Object = new $Class($Id, $this->Identifier, $this->Logger, $this->Database);
			//
			// 	// Retrieve Record
			// 	$Object->retrieve();
			//
			// 	// Update Record
			// 	$Object->save($Data);
			//
			// 	// Return Result
			// 	return $Object;
			// }
		} catch (Exception $e) {

			// If an exception is caught, log an error message
      $this->Logger->error('Error: '.$e->getMessage());
    }
	}
}
