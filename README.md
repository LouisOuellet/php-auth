![GitHub repo logo](/dist/img/logo.png)

# phpAUTH
![License](https://img.shields.io/github/license/LouisOuellet/php-auth?style=for-the-badge)
![GitHub repo size](https://img.shields.io/github/repo-size/LouisOuellet/php-auth?style=for-the-badge&logo=github)
![GitHub top language](https://img.shields.io/github/languages/top/LouisOuellet/php-auth?style=for-the-badge)
![Version](https://img.shields.io/github/v/release/LouisOuellet/php-auth?label=Version&style=for-the-badge)

## Features
  - Authentication Support
    - Front-End
      - BASIC
      - BEARER (Much faster then BASIC)
      - SESSION (Best used for GUI)
    - Back-End
      - SQL
  - Authorization Support
  - GDPR Cookie Compliance
  - CCPA Cookie Compliance
  - JavaScript class available for GDPR & CCPA Cookie Compliance

## Why you might need it
If you are looking for an easy way to setup authentication and authorization in your project. This PHP Class is for you.

## Can I use this?
Sure!

## License
This software is distributed under the [GNU General Public License v3.0](https://www.gnu.org/licenses/gpl-3.0.en.html) license. Please read [LICENSE](LICENSE) for information on the software availability and distribution.

## Requirements
* PHP >= 5.6.0
* MySQL or MariaDB

### SQL Requirements
To support authentication in your application, you will need at least one table called users. Since phpAUTH is packed with phpDB, you can create the table like this:
```php

//Import Database class into the global namespace
//These must be at the top of your script, not inside a function
use LaswitchTech\phpDB\Database;

//Load Composer's autoloader
require 'vendor/autoload.php';

//Initiate Database
$phpDB = new Database("localhost","demo","demo","demo");

//Create the users table
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

//Optionally you may want to add a type column if you want to support multiple Authentication Back-Ends like LDAP, SMTP, IMAP, etc.
$phpDB->alter('users',[
  'type' => [
    'action' => 'ADD',
    'type' => 'VARCHAR(10)',
    'extra' => ['NOT NULL','DEFAULT "SQL"']
  ]
]);

//Other Suggestions
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

//If you enable Roles, you will need a roles table.
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

//Optionally you may want to add a roles column if you want to quickly list roles memberships.

$phpDB->alter('users',[
  'roles' => [
    'action' => 'ADD',
    'type' => 'LONGTEXT',
    'extra' => ['NULL']
  ]
]);

//Other Suggestions
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

//If you use SQL Login, you will need a sessions table.
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
    'type' => 'VARCHAR(255)',
    'extra' => ['NULL']
  ],
  'userActivity' => [
    'action' => 'ADD',
    'type' => 'DATETIME',
    'extra' => ['DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP']
  ]
]);

//Optionally you may want to add a session column if you want to quickly access the user's session.

$phpDB->alter('users',[
  'sessionID' => [
    'action' => 'ADD',
    'type' => 'VARCHAR(255)',
    'extra' => ['NOT NULL','UNIQUE']
  ]
]);

//Other Suggestions
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

//Create user
$UserID = $phpDB->insert("INSERT INTO users (username, password, token) VALUES (?,?,?)", ["user1",password_hash("pass1", PASSWORD_DEFAULT),hash("sha256", "pass1", false)]);

//Create role
$RoleID = $phpDB->insert("INSERT INTO roles (name, permissions, members) VALUES (?,?,?)", ["users",json_encode(["users/list" => 1],JSON_UNESCAPED_SLASHES),json_encode([["users" => $UserID]],JSON_UNESCAPED_SLASHES)]);

//Update user
$phpDB->update("UPDATE users SET roles = ? WHERE id = ?", [json_encode([["roles" => $RoleID]],JSON_UNESCAPED_SLASHES),$UserID]);
```

## Security
Please disclose any vulnerabilities found responsibly – report security issues to the maintainers privately.

## Installation
Using Composer:
```sh
composer require laswitchtech/php-auth
```

## How do I use it?
In this documentations, we will use a table called users for our examples.

### Example
#### Initiate Auth
```php

//Import Auth class into the global namespace
//These must be at the top of your script, not inside a function
use LaswitchTech\phpAUTH\Auth;

//Load Composer's autoloader
require 'vendor/autoload.php';

//Define Configuration Information
//Auth Information
define("AUTH_F_TYPE", "BEARER"); //Default is BEARER
define("AUTH_B_TYPE", "SQL"); //Default is SQL
define("AUTH_ROLES", false); //Default is false
define("AUTH_GROUPS", false); //Default is false
define("AUTH_RETURN", "HEADER"); //Default is HEADER
//Database Information
define("DB_HOST", "localhost");
define("DB_USERNAME", "demo");
define("DB_PASSWORD", "demo");
define("DB_DATABASE_NAME", "demo");

//Initiate Auth
$phpAUTH = new Auth();
```

#### Initiate Auth Without Using Constants
```php

//Import Auth class into the global namespace
//These must be at the top of your script, not inside a function
use LaswitchTech\phpAUTH\Auth;

//Load Composer's autoloader
require 'vendor/autoload.php';

//Initiate Auth
$phpAUTH = new Auth("BASIC", "SQL", true, false, "HEADER");

//Initiate Database
$phpAUTH->connect("localhost","demo","demo","demo");
```

#### Retrieve User Information
```php

//Import Auth class into the global namespace
//These must be at the top of your script, not inside a function
use LaswitchTech\phpAUTH\Auth;

//Load Composer's autoloader
require 'vendor/autoload.php';

//Initiate Auth
$phpAUTH = new Auth("BASIC", "SQL", true, false, "HEADER");

//Initiate Database
$phpAUTH->connect("localhost","demo","demo","demo");

//Retrieve User Information
$user = $phpAUTH->getUser();
```

#### Retrieve Authorization
```php

//Import Auth class into the global namespace
//These must be at the top of your script, not inside a function
use LaswitchTech\phpAUTH\Auth;

//Load Composer's autoloader
require 'vendor/autoload.php';

//Initiate Auth
$phpAUTH = new Auth("BASIC", "SQL", true, false, "BOOLEAN");

//Initiate Database
$phpAUTH->connect("localhost","demo","demo","demo");

//Retrieve Authorization
$Authorization = $phpAUTH->isAuthorized("users/list");
```

#### Handle Authorization
```php

//Import Auth class into the global namespace
//These must be at the top of your script, not inside a function
use LaswitchTech\phpAUTH\Auth;

//Load Composer's autoloader
require 'vendor/autoload.php';

//Initiate Auth
$phpAUTH = new Auth("BASIC", "SQL", true, false, "HEADER");

//Initiate Database
$phpAUTH->connect("localhost","demo","demo","demo");

//Handle Authorization
$phpAUTH->isAuthorized("users/list");
```
