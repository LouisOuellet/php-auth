<?php

// Declaring namespace
namespace LaswitchTech\phpAUTH\Objects;

//Import phpConfigurator class into the global namespace
use LaswitchTech\phpConfigurator\phpConfigurator;

// Import phpLogger class into the global namespace
use LaswitchTech\phpLogger\phpLogger;

// Import Database Class into the global namespace
use LaswitchTech\phpDB\Database;

class Relationship {

  // Logger
  private $Logger;
  private $Level = 1;

  // Configurator
  private $Configurator = null;

  // phpDB
  private $Database = null;

  /**
   * Create a new Relationship instance.
   *
   * @param  Object  $Logger
   * @param  Object  $Database
   * @return Object  itself
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
  }

  /**
   * Create a relationship.
   *
   * @param string $sourceTable.
   * @param int $sourceId.
   * @param string $targetTable.
   * @param int $targetId.
   * @return int|void
   */
  public function create($sourceTable, $sourceId, $targetTable, $targetId) {

    // If it is not linking to itself
    if($sourceTable !== $targetTable || $sourceId !== $targetId){

      // Insert a new relationship into the relationships table
      return $this->Database->insert('INSERT INTO relationships (sourceTable, sourceId, targetTable, targetId) VALUES (?,?,?,?)', [strval($sourceTable), intval($sourceId), strval($targetTable), intval($targetId)]);
    }
  }

  /**
   * Delete a relationship.
   *
   * @param string $sourceTable.
   * @param int $sourceId.
   * @param string $targetTable.
   * @param int $targetId.
   * @return int|void
   */
  public function delete($sourceTable, $sourceId, $targetTable, $targetId) {

    // Delete a relationship from the relationships table
    return $this->Database->delete("DELETE FROM relationships WHERE (`sourceTable` = ? AND `sourceId` = ? AND `targetTable` = ? AND `targetId` = ?) OR (`targetTable` = ? AND `targetId` = ? AND `sourceTable` = ? AND `sourceId` = ?)", [$sourceTable, $sourceId, $targetTable, $targetId, $sourceTable, $sourceId, $targetTable, $targetId]);
  }

  /**
   * Get relationships.
   *
   * @param string $sourceTable.
   * @param int $sourceId.
   * @param string|null $targetTable.
   * @return int|void
   */
  public function getRelated($sourceTable, $sourceId, $targetTable = null) {

    // Query the relationships table and return a list of related targetIds

    // Initialize Array
    $Array = [];

    // Retrieve all Relationships
    if($targetTable){
      $Relationships = $this->Database->select("SELECT * FROM relationships WHERE (`sourceTable` = ? AND `sourceId` = ? AND `targetTable` = ?) OR (`targetTable` = ? AND `targetId` = ? AND `sourceTable` = ?)", [$sourceTable, $sourceId, $targetTable, $sourceTable, $sourceId, $targetTable]);
    } else {
      $Relationships = $this->Database->select("SELECT * FROM relationships WHERE (`sourceTable` = ? AND `sourceId` = ?) OR (`targetTable` = ? AND `targetId` = ?)", [$sourceTable, $sourceId, $sourceTable, $sourceId]);
    }

    // Retrieve records
    foreach($Relationships as $Relationship){
      if($sourceTable === $Relationship['sourceTable']){
        if(!isset($Array[$Relationship['targetTable']][$Relationship['targetId']])){
          $Records = $this->Database->select("SELECT * FROM `" . $Relationship['targetTable'] . "` WHERE id = ?", [$Relationship['targetId']]);
          if(count($Records) > 0){
            $Array[$Relationship['targetTable']][$Relationship['targetId']] = $Records[0];
          }
        }
      } else {
        if(!isset($Array[$Relationship['sourceTable']][$Relationship['sourceId']])){
          $Records = $this->Database->select("SELECT * FROM `" . $Relationship['sourceTable'] . "` WHERE id = ?", [$Relationship['sourceId']]);
          if(count($Records) > 0){
            $Array[$Relationship['sourceTable']][$Relationship['sourceId']] = $Records[0];
          }
        }
      }
    }

    // Debug Information
    $this->Logger->debug($Relationships);
    $this->Logger->debug($Array);

    // Return
    return $Array;
  }
}
