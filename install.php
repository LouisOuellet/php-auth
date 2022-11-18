<?php

//Import Auth class into the global namespace
//These must be at the top of your script, not inside a function
use LaswitchTech\phpDB\Database;

//Load Composer's autoloader
require 'vendor/autoload.php';

//Define Configuration Information
//Database Information
define("DB_INIT", false);
define("DB_HOST", "localhost");
define("DB_USERNAME", "demo");
define("DB_PASSWORD", "demo");
define("DB_DATABASE_NAME", "demo");
//Initiate Database
$phpDB = new Database();
$phpDB->drop('users');
$phpDB->create('users',[
  'id' => [
    'type' => 'BIGINT(10)',
    'extra' => ['UNSIGNED','AUTO_INCREMENT','PRIMARY KEY']
  ],
  'username' => [
    'type' => 'VARCHAR(60)',
    'extra' => ['NOT NULL','UNIQUE']
  ],
  'password' => [
    'type' => 'VARCHAR(100)',
    'extra' => ['NOT NULL']
  ],
  'token' => [
    'type' => 'VARCHAR(100)',
    'extra' => ['NOT NULL','UNIQUE']
  ]
]);
$phpDB->alter('users',[
  'type' => [
    'action' => 'ADD',
    'type' => 'VARCHAR(10)',
    'extra' => ['NOT NULL','DEFAULT "SQL"']
  ]
]);
$phpDB->alter('users',[
  'created' => [
    'action' => 'ADD',
    'type' => 'DATETIME',
    'extra' => ['DEFAULT CURRENT_TIMESTAMP']
  ],
  'modified' => [
    'action' => 'ADD',
    'type' => 'DATETIME',
    'extra' => ['DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP']
  ]
]);
$UserID = $phpDB->insert("INSERT INTO users (username, password, token) VALUES (?,?,?)", ["user1@domain.com",password_hash("pass1", PASSWORD_DEFAULT),hash("sha256", "pass1", false)]);
$phpDB->drop('roles');
$phpDB->create('roles',[
  'id' => [
    'type' => 'BIGINT(10)',
    'extra' => ['UNSIGNED','AUTO_INCREMENT','PRIMARY KEY']
  ],
  'name' => [
    'type' => 'VARCHAR(60)',
    'extra' => ['NOT NULL','UNIQUE']
  ],
  'permissions' => [
    'type' => 'LONGTEXT',
    'extra' => ['NULL']
  ],
  'members' => [
    'type' => 'LONGTEXT',
    'extra' => ['NULL']
  ]
]);
$phpDB->alter('roles',[
  'created' => [
    'action' => 'ADD',
    'type' => 'DATETIME',
    'extra' => ['DEFAULT CURRENT_TIMESTAMP']
  ],
  'modified' => [
    'action' => 'ADD',
    'type' => 'DATETIME',
    'extra' => ['DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP']
  ]
]);
$phpDB->alter('users',[
  'roles' => [
    'action' => 'ADD',
    'type' => 'LONGTEXT',
    'extra' => ['NULL']
  ]
]);
$RoleID = $phpDB->insert("INSERT INTO roles (name, permissions, members) VALUES (?,?,?)", ["users",json_encode(["users/list" => 1],JSON_UNESCAPED_SLASHES),json_encode([["users" => $UserID]],JSON_UNESCAPED_SLASHES)]);
$phpDB->update("UPDATE users SET roles = ? WHERE id = ?", [json_encode([["roles" => $RoleID]],JSON_UNESCAPED_SLASHES),$UserID]);
$phpDB->drop('sessions');
$phpDB->create('sessions',[
  'id' => [
    'type' => 'BIGINT(10)',
    'extra' => ['UNSIGNED','AUTO_INCREMENT','PRIMARY KEY']
  ],
  'sessionID' => [
    'type' => 'VARCHAR(255)',
    'extra' => ['NOT NULL','UNIQUE']
  ],
  'userID' => [
    'type' => 'BIGINT(10)',
    'extra' => ['NOT NULL']
  ],
  'userAgent' => [
    'type' => 'VARCHAR(255)',
    'extra' => ['NULL']
  ],
  'userBrowser' => [
    'type' => 'VARCHAR(255)',
    'extra' => ['NULL']
  ],
  'userIP' => [
    'type' => 'VARCHAR(255)',
    'extra' => ['NULL']
  ],
  'userData' => [
    'type' => 'LONGTEXT',
    'extra' => ['NULL']
  ],
  'userConsent' => [
    'type' => 'LONGTEXT',
    'extra' => ['NULL']
  ],
  'userActivity' => [
    'action' => 'ADD',
    'type' => 'DATETIME',
    'extra' => ['DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP']
  ]
]);
$phpDB->alter('users',[
  'sessionID' => [
    'action' => 'ADD',
    'type' => 'VARCHAR(255)',
    'extra' => ['NOT NULL','UNIQUE']
  ]
]);
$phpDB->alter('sessions',[
  'created' => [
    'action' => 'ADD',
    'type' => 'DATETIME',
    'extra' => ['DEFAULT CURRENT_TIMESTAMP']
  ],
  'modified' => [
    'action' => 'ADD',
    'type' => 'DATETIME',
    'extra' => ['DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP']
  ]
]);

echo '<a href="/">Done!</a>' . PHP_EOL;
