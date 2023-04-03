#!/usr/bin/env php
<?php

// These must be at the top of your script, not inside a function
use LaswitchTech\phpConfigurator\phpConfigurator;

// Load Composer's autoloader
require 'vendor/autoload.php';

// Initiate phpConfigurator
$Configurator = new phpConfigurator('auth');

// Configure Auth to only use Basic and Bearer Authentication
$Configurator->set('auth','basic',true)->set('auth','bearer',true)->set('auth','request',false)->set('auth','cookie',false)->set('auth','session',false);

// cURL Options
$url = "https://phpauth.local/api.php";
$username = "username@domain.com";
$password = "f+&J3%^JLFc6Y9^T";
$token = "api@domain.com:72c902533ffc40415e93d73d350e96978fb07d52cb06b5f5034577e0d34281c8";

// Encode
$username = base64_encode($username);
$password = base64_encode($password);
$token = base64_encode($token);

// Setup a Basic cURL
$Basic = curl_init();
curl_setopt($Basic, CURLOPT_URL, $url);
curl_setopt($Basic, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($Basic, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($Basic, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($Basic, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($Basic, CURLOPT_USERPWD, "$username:$password");

// Setup a Bearer cURL
$Bearer = curl_init();
curl_setopt($Bearer, CURLOPT_URL, $url);
curl_setopt($Bearer, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($Bearer, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($Bearer, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($Bearer, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);

// Loop each cURL
foreach([$Basic,$Bearer] as $cURL){

  // Execute cURL
  $response = curl_exec($cURL);

  // Output Response
  if (curl_errno($cURL)) {
    echo 'Error: ' . curl_error($cURL) . PHP_EOL;
  } else {
    echo 'Response: ' . $response . PHP_EOL;
  }

  // Close cURL
  curl_close($cURL);
}

// ReConfigure Auth to only use Request, Session and Cookie Authentication
$Configurator->set('auth','basic',false)->set('auth','bearer',false)->set('auth','request',true)->set('auth','cookie',true)->set('auth','session',true);
