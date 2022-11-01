![GitHub repo logo](/dist/img/logo.png)

# phpAUTH
![License](https://img.shields.io/github/license/LouisOuellet/php-auth?style=for-the-badge)
![GitHub repo size](https://img.shields.io/github/repo-size/LouisOuellet/php-auth?style=for-the-badge&logo=github)
![GitHub top language](https://img.shields.io/github/languages/top/LouisOuellet/php-auth?style=for-the-badge)
![Version](https://img.shields.io/github/v/release/LouisOuellet/php-auth?label=Version&style=for-the-badge)

## Features
 - Authentication Support
   - BASIC
   - BEARER
 - Authorization Support

## Why you might need it
If you are looking for an easy way to setup authentication and authorization in your project. This PHP Class is for you.

## Can I use this?
Sure!

## License
This software is distributed under the [GNU General Public License v3.0](https://www.gnu.org/licenses/gpl-3.0.en.html) license. Please read [LICENSE](LICENSE) for information on the software availability and distribution.

## Requirements
PHP >= 5.5.0

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
```php

//Import Auth class into the global namespace
//These must be at the top of your script, not inside a function
use LaswitchTech\phpAUTH\Auth;

//Load Composer's autoloader
require 'vendor/autoload.php';

//Initiate Auth
$Auth = new Auth();
```
