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
  protected $FrontType = null;
  protected $FrontTypes = ["BASIC", "BEARER"];
  protected $BackType = null;
  protected $BackTypes = ["SQL"];
  protected $Roles = false;
  protected $Groups = false;
  protected $Return = "HEADER";
  protected $Returns = ["BOOLEAN","HEADER"];
  protected $User = null;

  public function __construct($fronttype = null, $backtype = null, $roles = null, $groups = null, $return = null){

    //Setup Front-End Authentication
    if($fronttype == null && defined('AUTH_F_TYPE')){ $fronttype = AUTH_F_TYPE; }
    if($fronttype == null){ $fronttype = "BEARER"; }
    $fronttype = strtoupper($fronttype);
    if(in_array($fronttype,$this->FrontTypes)){ $this->FrontType = $fronttype; } else {
      $this->sendOutput('Unknown Front-End Type', array('HTTP/1.1 500 Internal Server Error'));
    }
    switch($this->FrontType){
      case"BASIC":
        $this->FrontType = $fronttype;
        $this->Authentication = new Basic();
        break;
      case"BEARER":
        $this->FrontType = $fronttype;
        $this->Authentication = new Bearer();
        break;
    }

    //Setup Back-End Authentication
    if($backtype == null && defined('AUTH_B_TYPE')){ $backtype = AUTH_B_TYPE; }
    if($backtype == null){ $backtype = "SQL"; }
    $backtype = strtoupper($backtype);
    if(in_array($backtype,$this->BackTypes)){ $this->BackType = $backtype; } else {
      $this->sendOutput('Unknown Back-End Type', array('HTTP/1.1 500 Internal Server Error'));
    }

    //Setup Roles
    $this->setRoles($roles);

    //Setup Groups
    $this->setGroups($groups);

    //Setup Return
    $this->setReturn($return);

    //Retrieve User
    $this->getUser();
  }

  public function __call($name, $arguments) {
    $this->sendOutput('Unknown Method', array('HTTP/1.1 500 Internal Server Error'));
  }

  public function setReturn($return = null){
    if($return == null && defined('AUTH_RETURN')){ $return = AUTH_RETURN; }
    if(in_array(strtoupper($return), $this->Returns)){ $this->Return = strtoupper($return); }
    return $this->Return;
  }

  public function setRoles($status = null){
    if($status == null && defined('AUTH_ROLES')){ $status = AUTH_ROLES; }
    if(is_bool($status)){ $this->Roles = $status; }
    return $this->Roles;
  }

  public function setGroups($status = null){
    if($status == null && defined('AUTH_GROUPS')){ $status = AUTH_GROUPS; }
    if(is_bool($status)){ $this->Groups = $status; }
    return $this->Groups;
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
        switch($this->FrontType){
          case"BASIC":
            $user = $this->Database->select("SELECT * FROM users WHERE username = ?", [$this->Authentication->getAuth('username')]);
            if(count($user) > 0){
              $user = $user[0];
              if(isset($user['type']) && in_array(strtoupper($user['type']),$this->BackTypes)){ $backtype = strtoupper($user['type']); }
              else { $backtype = $this->BackType; }
              switch($backtype){
                case"SQL":
                  if(password_verify($this->Authentication->getAuth('password'), $user['password'])){
                    $this->User = $user;
                  }
                  break;
              }
            }
            break;
          case"BEARER":
            $user = $this->Database->select("SELECT * FROM users WHERE token = ?", [hash("sha256", $this->Authentication->getAuth('token'), false)]);
            if(count($user) > 0){
              $this->User = $user[0];
            }
            break;
        }
      }
    } else {
      $this->sendOutput('Unable to Retrieve Authentication', array('HTTP/1.1 403 Permission Denied'));
    }
    if($this->User != null){ return $this->User; }
    else { $this->sendOutput('Unable to Validate Authentication', array('HTTP/1.1 403 Permission Denied')); }
  }

  public function isAuthorized($name, $level = 1){
    $return = false;
    if($this->User != null){
      if($this->Roles){
        $roles = $this->Database->select("SELECT * FROM roles WHERE members LIKE ? AND permissions LIKE ?", ['%'.json_encode(["users" => $this->User['id']],JSON_UNESCAPED_SLASHES).'%','%'.json_encode($name,JSON_UNESCAPED_SLASHES).':%']);
        if(count($roles) > 0){
          foreach($roles as $role){
            $role['permissions'] = json_decode($role['permissions'],true);
            if($role['permissions'][$name] >= $level){ $return = true; }
          }
        }
      } else { $return = true; }
    }
    switch($this->Return){
      case"BOOLEAN": return $return;break;
      case"HEADER":
        if($return){ return $return; }
        else { $this->sendOutput('Permission Denied', array('HTTP/1.1 403 Permission Denied')); }
        break;
    }
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
