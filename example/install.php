<?php
// Initiate Session
session_start();

// Import Auth class into the global namespace
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
$Configurator->set('auth','basic',false)->set('auth','bearer',false)->set('auth','request',true)->set('auth','cookie',true)->set('auth','session',true);

//Render
?>
<!doctype html>
<html lang="en" class="h-100 w-100">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    <title>Install</title>
  </head>
  <body class="h-100 w-100">
    <div class="row h-100 w-100 m-0 p-0">
      <div class="col h-100 m-0 p-0">
        <div class="container h-100">
          <div class="d-flex h-100 row align-items-center justify-content-center">
            <div class="col">
              <h3>Installation <strong>Completed</strong></h3>
              <p class="mb-4">
                <span class="me-4">
                  <strong>Username:</strong> <?= $API->get('username'); ?>
                </span>
                <span>
                  <strong>Token:</strong> <?= $API->getToken(); ?>
                </span>
              </p>
              <p class="mb-4">
                <span class="me-4">
                  <strong>Username:</strong> <?= $User->get('username'); ?>
                </span>
                <span>
                  <strong>Password:</strong> <?= $User->getPassword(); ?>
                </span>
              </p>
              <div class="btn-group w-100 border shadow">
                <a href="install.php" class="btn btn-block btn-light">Re-Install</a>
                <a href="/" class="btn btn-block btn-primary">Log In</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <script src="/vendor/components/jquery/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/dist/js/cookie.js"></script>
  </body>
</html>
