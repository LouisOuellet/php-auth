![GitHub repo logo](/dist/img/logo.png)

# phpAUTH
![License](https://img.shields.io/github/license/LouisOuellet/php-auth?style=for-the-badge)
![GitHub repo size](https://img.shields.io/github/repo-size/LouisOuellet/php-auth?style=for-the-badge&logo=github)
![GitHub top language](https://img.shields.io/github/languages/top/LouisOuellet/php-auth?style=for-the-badge)
![Version](https://img.shields.io/github/v/release/LouisOuellet/php-auth?label=Version&style=for-the-badge)

## Features
  - Authentication Support BASIC, BEARER and SESSION
  - 3rd-party Authentication Support through SMTP or IMAP
  - Authorization Support
  - Cross-site Request Forgery Protection ([phpCSRF](https://github.com/LouisOuellet/php-csrf))
  <!-- - GDPR Cookie Compliance -->
  <!-- - CCPA Cookie Compliance -->
  <!-- - JavaScript class available for GDPR & CCPA Cookie Compliance -->
  <!-- - Host Validation -->

## Why you might need it
If you are looking for an easy way to setup authentication and authorization in your project. This PHP Class is for you.

## Can I use this?
Sure!

## License
This software is distributed under the [GNU General Public License v3.0](https://www.gnu.org/licenses/gpl-3.0.en.html) license. Please read [LICENSE](LICENSE) for information on the software availability and distribution.

## Requirements
* PHP >= 7.3.0
* MySQL or MariaDB

## Security
Please disclose any vulnerabilities found responsibly – report security issues to the maintainers privately.

## Objects
* User
* Organization
* Group
* Role
* Permission

## Relationships
This library also includes support for relationships. Here are the ones already used in `phpAUTH`:

* User - Organization : User is a member of the Organization.
* User - Group : User is a member of the Group.
* User - Role : User is a member of the Role.
* Organization - Organization : Organization is a member of the Organization. Also known as a Subsidiary.
* Organization - Group : Organization can use the Group to manager it's members.
* Organization - Role : Organization can use the Role to manager it's members.
* Group - Role : Group is a member of the Role.

## Understanding Roles and Groups
When using this library, permissions are assigned on roles. Roles can be assigned directly to a user or through a group of users. The highest permission level provided is used for validation. For example, if a user is member of role `Administrator` and `User`, both possess the permission `Dashboard`, `Administrator`'s level is set to `4` and `User`'s level is set to `1`, then the effective permission level is `4`.

### Permission Levels
* __0__: No access allowed
* __1__: Read access allowed
* __2__: Create access allowed
* __3__: Edit access allowed
* __4__: Delete access allowed

## Installation
Using Composer:
```sh
composer require laswitchtech/php-auth
```

## How do I use it?

### Examples
There are many examples for you to check out in the [example](example) folder.

### Initiate
```php

// Initiate Session
session_start();

// These must be at the top of your script, not inside a function
use LaswitchTech\phpAUTH\phpAUTH;

// Load Composer's autoloader
require 'vendor/autoload.php';

// Initiate phpAUTH
$phpAUTH = new phpAUTH();
```

### Check if a User was authenticated
```php
// Check if a User was authenticated
$phpAUTH->Authentication->isConnected()
```

### Check if a User has a specific permission
```php
// Check if a User has a specific permission
$phpAUTH->Authorization->hasPermission($Name, $Level)
```

### Using Managers
First managers allow you to manage objects such as Users, Organizations, Groups, Roles and Permissions
```php
// Create a Manager
$Manager = $phpAUTH->manage("users");

// Retrieve all Objects
$Objects = $Manager->read();

// Retrieve single Object
$Objects = $Manager->read($Identifier);
```

### Using Objects
```php
// Create
$Manager->create($Fields);

// Read
$Object->get($Field);

// Update
$Object->save($Fields);
// Or
$Manager->update($Identifier, $Fields);

// Delete
$Object->delete();
// Or
$Manager->delete($Identifier);

// Link
$Object->link($Table, $Id);

// Unlink
$Object->unlink($Table, $Id);
```

### Installer
```php
// These must be at the top of your script, not inside a function
use LaswitchTech\phpAUTH\phpAUTH;
use LaswitchTech\phpDB\Database;
use LaswitchTech\phpLogger\phpLogger;
use LaswitchTech\phpConfigurator\phpConfigurator;

// Load Composer's autoloader
require 'vendor/autoload.php';

// Initialize Database
$phpLogger = new phpLogger();

// Configure phpLogger
$phpLogger->config('level',5);

// Initialize Database
$phpDB = new Database();

// Configure Database
$phpDB->config("host","localhost")->config("username","demo")->config("password","demo")->config("database","demo2");

// Initiate phpAUTH
$phpAUTH = new phpAUTH();

// Install phpAUTH
$Installer = $phpAUTH->install();

// Create a User
$User = $Installer->create("user",["username" => "username@domain.com"]);

// Create an API
$API = $Installer->create("api",["username" => "api@domain.com"]);

// Initiate phpConfigurator
$Configurator = new phpConfigurator('auth');

// Configure phpConfigurator
$Configurator->set('auth','basic',false)->set('auth','bearer',false)->set('auth','request',true)->set('auth','cookie',true)->set('auth','session',true);
$Configurator->set('auth','maxAttempts',5)->set('auth','maxRequests',1000)->set('auth','lockoutDuration',1800)->set('auth','windowAttempts',100)->set('auth','windowRequests',60);
```

### Lorem
```php
```

### Lorem
```php
```
