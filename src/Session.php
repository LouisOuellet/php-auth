<?php

//Declaring namespace
namespace LaswitchTech\phpAUTH;

//Import phpCSRF's phpCSRF Class into the global namespace
use LaswitchTech\phpCSRF\phpCSRF;

class Session {

  protected $CSRF = null;
  protected $Authentication = null;
  protected $Base64 = true;
  protected $Timestamp = null;

  public function __construct(){

    //Initiate CSRF Protection
    $this->CSRF = new phpCSRF();

    //Construct
    $this->Authentication = $this->getSQLAuth();
    $this->setCookieSettings();
  }

  public function __call($name, $arguments) {
    $this->sendOutput($name, array('HTTP/1.1 501 Not Implemented'));
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

  protected function setCookieSettings(){
    if(isset($_SESSION,$_SESSION['sessionID']) || isset($_COOKIE,$_COOKIE['sessionID'])){
      if(isset($_REQUEST,$_REQUEST['cookiesAccept']) && !isset($_COOKIE['cookiesAccept'])){
        if(isset($_REQUEST['cookiesAccept'])){
          setcookie( "cookiesAccept", true, $this->Timestamp + 86400 );
          setcookie( "cookiesAcceptEssentials", true, $this->Timestamp + 86400 );
          $_SESSION['cookiesAccept'] = true;
          $_SESSION['cookiesAcceptEssentials'] = true;
        }
        if(isset($_REQUEST['cookiesAcceptPerformance'])){
          setcookie( "cookiesAcceptPerformance", true, $this->Timestamp + 86400 );
          $_SESSION['cookiesAcceptPerformance'] = true;
        }
        if(isset($_REQUEST['cookiesAcceptQuality'])){
          setcookie( "cookiesAcceptQuality", true, $this->Timestamp + 86400 );
          $_SESSION['cookiesAcceptQuality'] = true;
        }
        if(isset($_REQUEST['cookiesAcceptPersonalisations'])){
          setcookie( "cookiesAcceptPersonalisations", true, $this->Timestamp + 86400 );
          $_SESSION['cookiesAcceptPersonalisations'] = true;
        }
      }
    }
  }

  protected function getSQLAuth(){
    $return = null;
    if(isset($_SESSION,$_SESSION['sessionID'])){
      $return = [ "sessionID" => $_SESSION['sessionID'], "timestamp" => $_SESSION['timestamp'] ];
    } elseif(isset($_COOKIE,$_COOKIE['sessionID'],$_COOKIE['timestamp'])){
      $return = [ "sessionID" => $_COOKIE['sessionID'], "timestamp" => $_COOKIE['timestamp'] ];
    } elseif(isset($_REQUEST,$_REQUEST['username'],$_REQUEST['password'],$_REQUEST['csrf'])){
      // CSRF Protection
      if($this->CSRF->validate()){
        $return = [ "username" => $_REQUEST['username'], "password" => $_REQUEST['password'], "timestamp" => time() ];
        if(isset($_REQUEST['remember'])){ $return['timestamp'] = time() + (86400 * 30); }
      }
    }
    if($return != null){ $this->Timestamp = $return['timestamp']; } else { $this->Timestamp = time(); }
    return $return;
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
