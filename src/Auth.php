<?php

//Declaring namespace
namespace LaswitchTech\phpAUTH;

//Import Database class into the global namespace
use LaswitchTech\phpDB\Database;

class Auth {

  public function __construct($type = null){
    if($type == null && defined('AUTH_TYPE')){ $type = AUTH_TYPE; }
    switch($type){
      case"BASIC":
        $this->getBasicAuth();
        break;
      case"BEARER":
        $this->getBearerToken();
        break;
      default:
        $this->sendOutput('Unknown Authentication Type', array('HTTP/1.1 500 Internal Server Error'));
        break;
    }
  }

  public function __call($name, $arguments) {
    $this->sendOutput('Unknown Authentication Method', array('HTTP/1.1 500 Internal Server Error'));
  }

  private function getAuthorizationHeader(){
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
      $headers = trim($_SERVER['Authorization']);
    } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
      $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
    } elseif (function_exists('apache_request_headers')) {
      $requestHeaders = apache_request_headers();
      $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
      if (isset($requestHeaders['Authorization'])) {
        $headers = trim($requestHeaders['Authorization']);
      }
    }
    return $headers;
  }

  protected function getBearerToken() {
    $headers = $this->getAuthorizationHeader();
    if (!empty($headers)) {
      if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
        return [ "token" => $matches[1] ];
      }
    }
    return null;
  }

  protected function getBasicAuth() {
    $headers = $this->getAuthorizationHeader();
    if (!empty($headers)) {
      if (str_contains($headers, 'Basic') && isset($_SERVER['PHP_AUTH_USER'])) {
        return [ "username" => $_SERVER['PHP_AUTH_USER'], "password" => $_SERVER['PHP_AUTH_PW'] ];
      }
    }
    return null;
  }

  protected function sendOutput($data, $httpHeaders=array()) {
    header_remove('Set-Cookie');
    if (is_array($httpHeaders) && count($httpHeaders)) {
      foreach ($httpHeaders as $httpHeader) {
        header($httpHeader);
      }
    }
    echo $data;
    exit;
  }
}
