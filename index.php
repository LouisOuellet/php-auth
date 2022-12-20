<?php
//Initiate Session
session_start();

//Import Auth class into the global namespace
//These must be at the top of your script, not inside a function
use LaswitchTech\phpAUTH\Auth;

//Load Composer's autoloader
require 'vendor/autoload.php';

//Define Auth Information
define("AUTH_F_TYPE", "SQL");
define("AUTH_RETURN", "BOOLEAN");
define("AUTH_OUTPUT_TYPE", "STRING");

//Define Database Information
define("DB_HOST", "localhost");
define("DB_USERNAME", "demo");
define("DB_PASSWORD", "demo");
define("DB_DATABASE_NAME", "demo");
define("DB_DEBUG", false);

//Initiate Auth
$phpAUTH = new phpAUTH("SESSION");

//Render
?>
<html>
  <body>
    <p><a href="/">Home</a></p>
    <p><a href="install.php">Install</a></p>
    <?php if($phpAUTH->isConnected()){ ?>
      <p><a href="?logout">Logout</a></p>
      <p>User: <?= json_encode($phpAUTH->getUser(), JSON_PRETTY_PRINT) ?></p>
      <p>BASE64 [pass1]: <?= json_encode(base64_encode("pass1"), JSON_PRETTY_PRINT) ?></p>
      <p>Session ID: <?= json_encode(session_id(), JSON_PRETTY_PRINT) ?></p>
    <?php } else { ?>
      <form method="post">
        <input type="text" name="username">
        <input type="password" name="password">
        <input type="checkbox" name="remember">
        <input type="submit" name="login">
      </form>
    <?php } ?>
    <?php if(isset($_SESSION)){ ?>
      <p>_SESSION: <?= json_encode($_SESSION, JSON_PRETTY_PRINT) ?></p>
    <?php } ?>
    <?php if(isset($_COOKIE)){ ?>
      <p>_COOKIE: <?= json_encode($_COOKIE, JSON_PRETTY_PRINT) ?></p>
    <?php } ?>
    <?php if(isset($_POST)){ ?>
      <p>_POST: <?= json_encode($_POST, JSON_PRETTY_PRINT) ?></p>
    <?php } ?>
    <script src="/vendor/components/jquery/jquery.min.js"></script>
    <script src="/dist/js/cookie.js"></script>
  </body>
</html>
