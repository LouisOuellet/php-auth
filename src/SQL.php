<?php

//Declaring namespace
namespace LaswitchTech\phpAUTH;

class SQL {

  protected $Authentication = null;
  protected $Base64 = true;

  public function __construct(){
    $this->Authentication = $this->getSQLAuth();
  }

  public function __call($name, $arguments) {
    $this->sendOutput('Unknown Method: '.$name, array('HTTP/1.1 500 Internal Server Error'));
  }

  public function getAuth($field = null){
    if($field != null && $this->isSet() && isset($this->Authentication[$field])){
      return $this->Authentication[$field];
    }
    return $this->Authentication;
  }

  public function isSet(){
    return is_array($this->Authentication);
  }

  protected function getSQLAuth(){
    if(isset($_SESSION,$_SESSION['sessionID'])){
      return [ "sessionID" => $_SESSION['sessionID'] ];
    } elseif(isset($_POST,$_POST['username'],$_POST['password'])){
      return [ "username" => $_POST['username'], "password" => $_POST['password'] ];
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
