<?php

//Declaring namespace
namespace LaswitchTech\phpAUTH;

//Import Classes into the global namespace
use LaswitchTech\phpDB\Database;
use LaswitchTech\phpAUTH\Basic;
use LaswitchTech\phpAUTH\Bearer;

class Auth {

  protected $Database = null;
  protected $Authentication = null;
  protected $Authorization = null;
  protected $Type = null;
  protected $User = null;

  public function __construct($type = null){
    if($type == null && defined('AUTH_TYPE')){ $type = AUTH_TYPE; }
    switch(strtoupper($type)){
      case"BASIC":
        $this->Type = strtoupper($type);
        $this->Authentication = new Basic();
        break;
      case"BEARER":
        $this->Type = strtoupper($type);
        $this->Authentication = new Bearer();
        break;
      default:
        $this->sendOutput('Unknown Authentication Type', array('HTTP/1.1 500 Internal Server Error'));
        break;
    }
    $this->getUser();
  }

  public function __call($name, $arguments) {
    $this->sendOutput('Unknown Authentication Method', array('HTTP/1.1 500 Internal Server Error'));
  }

  public function connect($host = null, $username = null, $password = null, $database = null){
    if($host == null && defined('DB_HOST')){ $host = DB_HOST; }
    if($username == null && defined('DB_USERNAME')){ $username = DB_USERNAME; }
    if($password == null && defined('DB_PASSWORD')){ $password = DB_PASSWORD; }
    if($database == null && defined('DB_DATABASE_NAME')){ $database = DB_DATABASE_NAME; }
    $this->Database = new Database($host, $username, $password, $database);
  }

  public function getUser(){
    if($this->Authentication->isSet()){
      if($this->Database == null){ $this->connect(); }
      if($this->User == null){
        switch($this->Type){
          case"BASIC":
            $user = $this->Database->select("SELECT * FROM users WHERE username = ?", [$this->Authentication->getAuth('username')]);
            if(count($user) > 0){
              $user = $user[0];
              if(password_verify($this->Authentication->getAuth('password'), $user['password'])){
                $this->User = $user;
              }
            }
            break;
          case"BEARER":
            $user = $this->Database->select("SELECT * FROM users WHERE token = ?", [hash("sha256", $this->Authentication->getAuth('token'), false)]);
            if(count($user) > 0){ $this->User = $user[0]; }
            break;
        }
      }
    } else {
      $this->sendOutput('Unable to Retrieve Authentication', array('HTTP/1.1 403 Permission Denied'));
    }
    if($this->User != null){ return $this->User; }
    else { $this->sendOutput('Unable to Validate Authentication', array('HTTP/1.1 403 Permission Denied')); }
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
