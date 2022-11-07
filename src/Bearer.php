<?php

//Declaring namespace
namespace LaswitchTech\phpAUTH;

class Bearer {

  protected $Authentication = null;

  public function __construct(){
    $this->Authentication = $this->getBearerToken();
  }

  public function __call($name, $arguments) {
    $this->sendOutput('Unknown Method', array('HTTP/1.1 500 Internal Server Error'));
  }

  public function getAuth($field = null){
    if($field != null && $this->isSet() && isset($this->Authentication[$field])){
      return $this->Authentication[$field];
    } else { return $this->Authentication; }
  }

  public function isSet(){
    return is_array($this->Authentication);
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
      if (preg_match('/Bearer\s(\S+)/', $headers, $matches) && isset($matches[1]) && !empty($matches[1])) {
        return [ "token" => base64_decode($matches[1]) ];
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
