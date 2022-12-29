<?php

//Declaring namespace
namespace LaswitchTech\phpAUTH;

//Import phpDB's Database Class into the global namespace
use LaswitchTech\phpDB\Database;

//Import phpAUTH's Classes into the global namespace
use LaswitchTech\phpAUTH\Basic;
use LaswitchTech\phpAUTH\Bearer;
use LaswitchTech\phpAUTH\Session;

//Import phpCSRF's phpCSRF Class into the global namespace
use LaswitchTech\phpCSRF\phpCSRF;

class phpAUTH {

  protected $Database = null;
  protected $Authentication = null;
  protected $Authorization = null;
  protected $FrontEndDBType = "SESSION";
  protected $FrontEndDBTypes = ["SESSION", "BASIC", "BEARER"];
  protected $BackEndDBType = null;
  protected $BackEndDBTypes = ["SQL"];
  protected $OutputType = null;
  protected $OutputTypes = ["HEADER","STRING"];
  protected $Roles = false;
  protected $Groups = false;
  protected $Return = "HEADER";
  protected $Returns = ["BOOLEAN","HEADER"];
  protected $User = null;
  protected $Status = null;
  protected $URI = null;
  public $CSRF = null;
  protected $CookieOptions = [];
  protected $Domains = [];
  protected $Domain = null;

  public function __construct($fronttype = null, $backtype = null, $roles = null, $groups = null, $output = null, $return = null){

    // Configure Cookie Scope
    if(session_status() < 2){
      ini_set('session.cookie_samesite', 'Strict');
      ini_set('session.cookie_secure', 'On');
    }

    //Initiate CSRF Protection
    $this->CSRF = new phpCSRF();

    //Setup Back-End Authentication
    if($backtype == null && defined('AUTH_B_TYPE')){ $backtype = AUTH_B_TYPE; }
    if($backtype == null){ $backtype = "SQL"; }
    $backtype = strtoupper($backtype);
    if(in_array($backtype,$this->BackEndDBTypes)){ $this->BackEndDBType = $backtype; } else {
      $this->sendOutput('Unknown Back-End Type', array('HTTP/1.1 500 Internal Server Error'));
    }

    //Setup Front-End Authentication
    $this->Authentication = new Session();
    if(!$this->Authentication->isSet()){
      $this->Authentication = null;
      $this->FrontEndDBType = "BEARER";
      if($fronttype != null && in_array(strtoupper($fronttype),$this->FrontEndDBTypes)){ $this->FrontEndDBType = strtoupper($fronttype); }
      if(defined('AUTH_F_TYPE') && in_array(strtoupper(AUTH_F_TYPE),$this->FrontEndDBTypes)){ $this->FrontEndDBType = strtoupper(AUTH_F_TYPE); }
      switch($this->FrontEndDBType){
        case"BASIC":
          $this->Authentication = new Basic();
          break;
        case"BEARER":
          $this->Authentication = new Bearer();
          break;
        default:
          $this->Authentication = new Session();
          break;
      }
    }

    //Setup Domains
    $this->setDomains();
    $this->setDomain();

    //Setup Cookie Options
    $this->setCookieOptions();

    //GDPR & CCPA Cookie Compliance
    $this->certification();

    //Setup Roles
    $this->setRoles($roles);

    //Setup Groups
    $this->setGroups($groups);

    //Setup Output
    $this->setOutputType($output);

    //Setup Return
    $this->setReturn($return);

    //Parse URI
    $this->parseURI();

    //Logout User
    $this->logout();

    //Retrieve User
    $this->getUser();
  }

  public function __call($name, $arguments) {
    $this->sendOutput($name, array('HTTP/1.1 501 Not Implemented'));
  }

  public function setDomains($domains = null){
    if($domains == null && defined('AUTH_DOMAINS')){ $domains = AUTH_DOMAINS; }
    if($domains == null){ $domains = []; }
    $this->Domains = $domains;
  }

  public function setDomain(){
    if(isset($_SERVER['HTTP_HOST']) && $this->validateDomain($_SERVER['HTTP_HOST'])){
      $this->Domain = $_SERVER['HTTP_HOST'];
    }
    if(!isset($_SERVER['HTTP_HOST'])){
      $this->Domain = $this->Domains[array_key_first($this->Domains)];
    }
  }

  public function validateDomain($domain = null){
    if($domain == null){ $domain = $this->Domain; }
    if($domain == null || !in_array($domain, $this->Domains) || count($this->Domains) <= 0){
      if(!in_array('*', $this->Domains)){
        $this->sendOutput('This host is not authorized', array('HTTP/1.1 400 Bad Request'), true);
        return false;
      }
    }
    return true;
  }

  public function setCookieOptions($options = null){
    $defaults = [
      'secure' => true,
      'httponly' => false,
      'domain' => $this->Domain,
      'samesite' => 'Strict',
      'expires' => time() + 60*60*24*30,
    ];
    if($options == null && defined('AUTH_COOKIE_OPTIONS')){ $options = AUTH_COOKIE_OPTIONS; }
    if($options == null){ $options = []; }
    foreach ($options as $key => $value) {
      if(isset($defaults[$key])){
        $defaults[$key] = $value;
      }
    }
    $this->CookieOptions = $defaults;
  }

  protected function certification(){
    if(isset($_SESSION,$_SESSION['sessionID']) || isset($_COOKIE,$_COOKIE['sessionID'])){
      if(isset($_REQUEST,$_REQUEST['cookiesAccept']) && !isset($_COOKIE['cookiesAccept'])){
        $options = $this->CookieOptions;
        $options['httponly'] = false;
        if(isset($_REQUEST['cookiesAccept'])){
          setcookie( "cookiesAccept", true, $options );
          setcookie( "cookiesAcceptEssentials", true, $options );
          $_SESSION['cookiesAccept'] = true;
          $_SESSION['cookiesAcceptEssentials'] = true;
        }
        if(isset($_REQUEST['cookiesAcceptPerformance'])){
          setcookie( "cookiesAcceptPerformance", true, $options );
          $_SESSION['cookiesAcceptPerformance'] = true;
        }
        if(isset($_REQUEST['cookiesAcceptQuality'])){
          setcookie( "cookiesAcceptQuality", true, $options );
          $_SESSION['cookiesAcceptQuality'] = true;
        }
        if(isset($_REQUEST['cookiesAcceptPersonalisations'])){
          setcookie( "cookiesAcceptPersonalisations", true, $options );
          $_SESSION['cookiesAcceptPersonalisations'] = true;
        }
      }
    }
  }

  public function setOutputType($output = null){
    if($output == null && defined('AUTH_OUTPUT_TYPE')){ $output = AUTH_OUTPUT_TYPE; }
    if($output == null){ $output = "HEADER"; }
    if(in_array(strtoupper($output), $this->OutputTypes)){ $this->OutputType = strtoupper($output); }
    return $this->OutputType;
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
    if($host != null && $username != null && $password != null && $database != null){
      $this->Database = new Database($host, $username, $password, $database);
      if(!defined('DB_HOST')){ define('DB_HOST',$host); }
      if(!defined('DB_USERNAME')){ define('DB_USERNAME',$username); }
      if(!defined('DB_PASSWORD')){ define('DB_PASSWORD',$password); }
      if(!defined('DB_DATABASE_NAME')){ define('DB_DATABASE_NAME',$database); }
    }
  }

  protected function parseURI(){
    if($this->URI == null){
      if(count(explode('?',$_SERVER['REQUEST_URI'])) > 1){
        $vars = explode('?',$_SERVER['REQUEST_URI'])[1];
        $this->URI = [];
        foreach(explode('&',$vars) as $var){
          $params = explode('=',$var);
          if(count($params) > 1){ $this->URI[$params[0]] = $params[1]; }
          else { $this->URI[$params[0]] = true; }
        }
      }
    }
    return $this->URI;
  }

  protected function logout($force = false){
    if(isset($this->URI['logout']) || isset($this->URI['signout']) || $force){
      // CSRF Protection
      if($this->CSRF->validate() || $force){

        // clear session variables
        if(isset($_SESSION) && !empty($_SESSION)){
          foreach($_SESSION as $key => $value){ unset($_SESSION[$key]); }
        }

        // clear cookie variables
        $options = $this->CookieOptions;
        $options['expires'] = -1;
        if(isset($_SERVER['HTTP_COOKIE'])){
          $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
          foreach($cookies as $cookie){
            $parts = explode('=', $cookie);
            $name = trim($parts[0]);
            unset($_COOKIE[$name]);
            setcookie($name, null, $options);
            setcookie($name, null, $options);
          }
        }

        // remove all session variables
        session_unset();

        // destroy the session
        session_destroy();

        // redirect header
        header('Location: /');
      }
    }
  }

	protected function getClientIP(){
	  $ipaddress = '';
	  if(getenv('HTTP_CLIENT_IP')){
	    $ipaddress = getenv('HTTP_CLIENT_IP');
	  } elseif(getenv('HTTP_X_FORWARDED_FOR')){
	    $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
	  } elseif(getenv('HTTP_X_FORWARDED')){
	    $ipaddress = getenv('HTTP_X_FORWARDED');
	  } elseif(getenv('HTTP_FORWARDED_FOR')){
	    $ipaddress = getenv('HTTP_FORWARDED_FOR');
	  } elseif(getenv('HTTP_FORWARDED')){
	    $ipaddress = getenv('HTTP_FORWARDED');
	  } elseif(getenv('REMOTE_ADDR')){
	    $ipaddress = getenv('REMOTE_ADDR');
    } elseif(defined('STDIN')){
      $ipaddress = 'LOCALHOST';
	  } else {
	    $ipaddress = 'UNKNOWN';
		}
    if(in_array($ipaddress,['127.0.0.1','127.0.1.1','::1'])){ $ipaddress = 'LOCALHOST'; }
	  return $ipaddress;
	}

  protected function getClientBrowser(){
    $t = strtolower($_SERVER['HTTP_USER_AGENT']);
    $t = " " . $t;
    if     (strpos($t, 'opera'     ) || strpos($t, 'opr/')     ) return 'Opera'            ;
    elseif (strpos($t, 'edge'      )                           ) return 'Edge'             ;
    elseif (strpos($t, 'chrome'    )                           ) return 'Chrome'           ;
    elseif (strpos($t, 'safari'    )                           ) return 'Safari'           ;
    elseif (strpos($t, 'firefox'   )                           ) return 'Firefox'          ;
    elseif (strpos($t, 'msie'      ) || strpos($t, 'trident/7')) return 'Internet Explorer';
    return 'Unkown';
  }

  public function getDiag(){
    return [
      "DB_STATUS" => ($this->Database != null),
      "DB_HOST" => DB_HOST,
      "DB_USERNAME" => DB_USERNAME,
      "DB_PASSWORD" => DB_PASSWORD,
      "DB_DATABASE_NAME" => DB_DATABASE_NAME,
      "session_id" => session_id(),
      "isSet" => $this->Authentication->isSet(),
      "getAuth" => $this->Authentication->getAuth(),
      "isUsername" => !is_array($this->Authentication->getAuth('username')),
      "isSession" => !is_array($this->Authentication->getAuth('sessionID')),
      "sessionID" => $this->Authentication->getAuth('sessionID'),
      "User" => $this->User,
      "FrontEndDBType" => $this->FrontEndDBType,
      "getUser" => $this->getUser(),
    ];
  }

  public function getStatus(){
    if($this->Authentication->isSet()){
      if($this->Database == null){ $this->connect(); }
      if($this->Database->isConnected()){
        if($this->Status == null){
          switch($this->FrontEndDBType){
            case"BASIC":
              $user = $this->Database->select("SELECT * FROM users WHERE username = ?", [$this->Authentication->getAuth('username')]);
              if(count($user) > 0){
                $user = $user[0];
                if(isset($user['type']) && in_array(strtoupper($user['type']),$this->BackEndDBTypes)){ $backtype = strtoupper($user['type']); }
                else { $backtype = $this->BackEndDBType; }
                switch($backtype){
                  case"SQL":
                    if(password_verify($this->Authentication->getAuth('password'), $user['password'])){
                      $this->Status = $user['status'];
                    }
                    break;
                }
              }
              break;
            case"BEARER":
              $user = $this->Database->select("SELECT * FROM users WHERE token = ?", [$this->Authentication->getAuth('token')]);
              if(count($user) > 0){ $this->Status = $user[0]['status']; }
              break;
            case"SESSION":
              if(!is_array($this->Authentication->getAuth('username'))){
                $user = $this->Database->select("SELECT * FROM users WHERE username = ?", [$this->Authentication->getAuth('username')]);
                if(count($user) > 0){
                  $user = $user[0];
                  if(isset($user['type']) && in_array(strtoupper($user['type']),$this->BackEndDBTypes)){ $backtype = strtoupper($user['type']); }
                  else { $backtype = $this->BackEndDBType; }
                  switch($backtype){
                    case"SQL":
                      if(password_verify($this->Authentication->getAuth('password'), $user['password'])){
                        $this->Status = $user['status'];
                      }
                      break;
                  }
                }
              } elseif(!is_array($this->Authentication->getAuth('sessionID'))){
                $user = $this->Database->select("SELECT * FROM users WHERE sessionID = ?", [$this->Authentication->getAuth('sessionID')]);
                if(count($user) > 0){ $this->Status = $user[0]['status']; }
              }
              break;
          }
        }
      }
    }
    return $this->Status;
  }

  public function getUser($field = null){
    if($this->Authentication->isSet()){
      if($this->Database == null){ $this->connect(); }
      if($this->Database->isConnected()){
        if($this->User == null){
          switch($this->FrontEndDBType){
            case"BASIC":
              $user = $this->Database->select("SELECT * FROM users WHERE username = ?", [$this->Authentication->getAuth('username')]);
              if(count($user) > 0){
                $user = $user[0];
                if(isset($user['type']) && in_array(strtoupper($user['type']),$this->BackEndDBTypes)){ $backtype = strtoupper($user['type']); }
                else { $backtype = $this->BackEndDBType; }
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
              $user = $this->Database->select("SELECT * FROM users WHERE token = ?", [$this->Authentication->getAuth('token')]);
              if(count($user) > 0){ $this->User = $user[0]; }
              break;
            case"SESSION":
              if(!is_array($this->Authentication->getAuth('username'))){
                $user = $this->Database->select("SELECT * FROM users WHERE username = ?", [$this->Authentication->getAuth('username')]);
                if(count($user) > 0){
                  $user = $user[0];
                  if(isset($user['type']) && in_array(strtoupper($user['type']),$this->BackEndDBTypes)){ $backtype = strtoupper($user['type']); }
                  else { $backtype = $this->BackEndDBType; }
                  switch($backtype){
                    case"SQL":
                      if(password_verify($this->Authentication->getAuth('password'), $user['password'])){
                        $this->User = $user;
                      }
                      break;
                  }
                }
              } elseif(!is_array($this->Authentication->getAuth('sessionID'))){
                $user = $this->Database->select("SELECT * FROM users WHERE sessionID = ?", [$this->Authentication->getAuth('sessionID')]);
                if(count($user) > 0){ $this->User = $user[0]; }
              }
              if($this->User != null){
                if($this->User['isActive'] == 0 && $this->User['token'] != null && isset($_GET['token'])){
                  if(base64_decode($_GET['token']) == $this->User['token']){
                    $this->User['isActive'] = 1;
                    $this->Database->update("UPDATE users SET token = ?, isActive = ? WHERE id = ?", [null,1,$this->User['id']]);
                  }
                }
                if($this->User['isActive'] == 0){ $this->User = null; }
              }
              if($this->User != null){
                if(!isset($_SESSION['sessionID']) || $this->User['sessionID'] != session_id()){
                  $this->User['sessionID'] = session_id();
                  $this->Database->update("UPDATE users SET sessionID = ? WHERE id = ?", [$this->User['sessionID'],$this->User['id']]);
                  if($this->User['sessionID'] != ''){
                    $this->Database->insert("INSERT INTO sessions (sessionID,userID,userAgent,userBrowser,userIP,userData) VALUES (?,?,?,?,?,?)", [$this->User['sessionID'],$this->User['id'],$_SERVER['HTTP_USER_AGENT'],$this->getClientBrowser(),$this->getClientIP(),json_encode($this->User)]);
                    $options = $this->CookieOptions;
                    $options['expires'] = $this->Authentication->getAuth('timestamp');
                    if(!isset($_COOKIE['sessionID'])){ setcookie( "sessionID", $this->User['sessionID'], $options ); }
                    if(!isset($_COOKIE['timestamp'])){ setcookie( "timestamp", $this->Authentication->getAuth('timestamp'), $options ); }
                    $_SESSION['sessionID'] = $this->User['sessionID'];
                    $_SESSION['timestamp'] = $this->Authentication->getAuth('timestamp');
                  }
                }
                if(isset($_SESSION['cookiesAccept'])){
                  $this->Database->update("UPDATE sessions SET userConsent = ? WHERE sessionID = ?", [json_encode($_SESSION),$this->User['sessionID']]);
                }
              }
              break;
          }
        }
      }
    } else {
      $this->sendOutput('Unable to Retrieve Authentication', array('HTTP/1.1 511 Network Authentication Required'));
    }
    if($this->User != null){
      if($field != null && is_string($field) && (isset($this->User[$field]) || $this->User[$field] == null)){ return $this->User[$field]; }
      return $this->User;
    } else {
      $this->sendOutput('Unable to Validate Authentication', array('HTTP/1.1 511 Network Authentication Required'));
    }
  }

  protected function hex($length = 16){
    return bin2hex(openssl_random_pseudo_bytes($length));
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

  public function isConnected(){ return ($this->User != null); }

  protected function sendHeader($data, $httpHeaders=array()){
    header_remove('Set-Cookie');
    if (is_array($httpHeaders) && count($httpHeaders)) {
      foreach ($httpHeaders as $httpHeader) {
        header($httpHeader);
      }
    }
    echo $data;
    exit;
  }

  protected function sendOutput($data, $httpHeaders=array(), $force = false){
    switch($this->OutputType){
      case NULL:
      case"STRING":
        if($force){ $this->sendHeader($data, $httpHeaders); }
        else { return $data; }
        break;
      case"HEADER":
        $this->sendHeader($data, $httpHeaders);
        break;
    }
  }
}
