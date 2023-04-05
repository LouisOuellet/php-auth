<?php
// Initiate Session
session_start();

// Import Auth class into the global namespace
// These must be at the top of your script, not inside a function
use LaswitchTech\phpAUTH\phpAUTH;
use LaswitchTech\phpCSRF\phpCSRF;
use LaswitchTech\phpDB\Database;

// Load Composer's autoloader
require 'vendor/autoload.php';

// Initiate phpAUTH
$phpAUTH = new phpAUTH();

// Initiate phpDB
$phpDB = new Database();

// Initiate phpCSRF
$phpCSRF = new phpCSRF();

// Initialize Types
$Types = ['user','organization','group','role','permission'];

// Initialize Identifiers
$Identifiers = [
  'user' => 'username',
  'organization' => 'id',
  'group' => 'name',
  'role' => 'name',
  'permission' => 'name',
];

// Validate Type
if(!isset($_GET['type']) || !in_array($_GET['type'],$Types)){
  exit;
}

// Store Type
$Page = $_GET['type'];

// Validate Id
if(!isset($_GET['id'])){
  exit;
}

// Store Type
$Id = $_GET['id'];

// Create Manager
$Manager = $phpAUTH->manage("{$Page}s");

// Retrieve Objects
$Object = $Manager->read($Id);

// Retrieve Columns
$Columns = $phpDB->getColumns("{$Page}s");

// Retrieve Required Columns
$Required = $phpDB->getRequired("{$Page}s");

// Retrieve Defaults
$Defaults = $phpDB->getDefaults("{$Page}s");

// Retrieve OnUpdate
$OnUpdate = $phpDB->getOnUpdate("{$Page}s");

// Retrieve Primary
$Primary = $phpDB->getPrimary("{$Page}s");

if(isset($_POST) && !empty($_POST)){
  if($phpCSRF->validate()){
    // $Object->save($_POST);
    if($Manager->update($Id, $_POST)){
      $Object->retrieve(true);
      if(strval($Id) !== strval($_POST[$Identifiers[$Page]])){
        header('Location: edit.php?type=' . $Page . '&id=' . $_POST[$Identifiers[$Page]]);
        exit();
      }
    }
  }
}

// Header
$Header = ucfirst($Page);

//Render
?>
<!doctype html>
<html lang="en" class="h-100 w-100">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    <title><?= $Header ?> Management</title>
    <script src="/vendor/components/jquery/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  </head>
  <body class="h-100 w-100">
    <div class="row h-100 w-100 m-0 p-0">
      <div class="col h-100 m-0 p-0">
        <div class="container h-100">
          <div class="d-flex h-100 row align-items-center justify-content-center">
            <div class="col">
              <h3 class="mt-5 mb-3">Edit this <strong><?= $Header ?></strong> <small>(<?= $Object->get($Identifiers[$Page]); ?>)</small></h3>
              <?php if($phpAUTH->Authentication->isConnected()){ ?>
                <div class="btn-group w-100 border shadow mb-4">
                  <a href="manage.php?type=<?= $Page ?>" class="btn btn-block btn-light">Return</a>
                  <a href="create.php?type=<?= $Page ?>" class="btn btn-block btn-success">Create</a>
                  <a href="detail.php?type=<?= $Page ?>&id=<?= $Id ?>" class="btn btn-block btn-primary">Details</a>
                  <a href="relationship.php?type=<?= $Page ?>&id=<?= $Id ?>" class="btn btn-block btn-light">Relationships</a>
                  <?php if($Page === "role"){ ?>
                    <a href="permission.php?type=<?= $Page ?>&id=<?= $Id ?>" class="btn btn-block btn-info">Permissions</a>
                  <?php } ?>
                  <a href="edit.php?type=<?= $Page ?>&id=<?= $Id ?>" class="btn btn-block btn-warning">Edit</a>
                  <a href="delete.php?type=<?= $Page ?>&id=<?= $Id ?>" class="btn btn-block btn-danger">Delete</a>
                </div>
                <p class="mb-5">
                  <div class="overflow-auto">
                    <form method="post">
                      <?php foreach($Columns as $Column => $DataType){ ?>
                        <?php if(in_array($Column,['passwordSalt','passwordHash','bearerToken','permissions'])){ continue; } ?>
                        <?php if(isset($Defaults[$Column]) || isset($OnUpdate[$Column]) || $Column == $Primary){ continue; } ?>
                        <?php $Value = $Object->get($Column); ?>
                        <?php if(is_array($Value)){ $Value = json_encode($Value, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); } ?>
                        <div class="row mb-2 mx-0">
                          <div class="col-12">
                            <div class="input-group">
                              <span class="input-group-text <?php if(in_array($Column,$Required)){ echo "text-bg-primary"; } ?>" id="label<?= $Column ?>"><?= $Column ?></span>
                              <?php
                                switch($DataType){
                                  case"longtext":
                                  case"text":
                                  case"json":
                                    ?><textarea class="form-control" name="<?= $Column ?>" placeholder="<?= ucfirst($Column) ?>" aria-label="<?= ucfirst($Column) ?>" aria-describedby="label<?= $Column ?>" <?php if(in_array($Column,$Required)){ echo "required"; } ?>><?= $Value ?></textarea><?php
                                    break;
                                  case"date":
                                    ?><input type="date" name="<?= $Column ?>" value="<?= $Value ?>" class="form-control" placeholder="<?= ucfirst($Column) ?>" aria-label="<?= ucfirst($Column) ?>" aria-describedby="label<?= $Column ?>" <?php if(in_array($Column,$Required)){ echo "required"; } ?>><?php
                                    break;
                                  case"time":
                                    ?><input type="time" name="<?= $Column ?>" value="<?= $Value ?>" class="form-control" placeholder="<?= ucfirst($Column) ?>" aria-label="<?= ucfirst($Column) ?>" aria-describedby="label<?= $Column ?>" <?php if(in_array($Column,$Required)){ echo "required"; } ?>><?php
                                    break;
                                  case"datetime":
                                    ?><input type="datetime-local" name="<?= $Column ?>" value="<?= $Value ?>" class="form-control" placeholder="<?= ucfirst($Column) ?>" aria-label="<?= ucfirst($Column) ?>" aria-describedby="label<?= $Column ?>" <?php if(in_array($Column,$Required)){ echo "required"; } ?>><?php
                                    break;
                                  case"int":
                                  case"bigint":
                                    ?><input type="number" name="<?= $Column ?>" value="<?= $Value ?>" class="form-control" placeholder="<?= ucfirst($Column) ?>" aria-label="<?= ucfirst($Column) ?>" aria-describedby="label<?= $Column ?>" <?php if(in_array($Column,$Required)){ echo "required"; } ?>><?php
                                    break;
                                  default:
                                    ?><input type="text" name="<?= $Column ?>" value="<?= $Value ?>" class="form-control" placeholder="<?= ucfirst($Column) ?>" aria-label="<?= ucfirst($Column) ?>" aria-describedby="label<?= $Column ?>" <?php if(in_array($Column,$Required)){ echo "required"; } ?>><?php
                                    break;
                                }
                              ?>
                            </div>
                          </div>
                        </div>
                      <?php } ?>
                      <input type="hidden" class="d-none" name="csrf" value="<?= $phpCSRF->token() ?>">
                      <div class="btn-group w-100 border shadow mb-4">
                        <input type="hidden" class="d-none" name="id" value="<?= $Object->get('id') ?>">
                        <button type="submit" class="btn btn-block btn-success">Save</button>
                      </div>
                    </form>
                  </div>
                </p>
              <?php } else { ?>
                <div class="btn-group w-100 border shadow">
                  <a href="install.php" class="btn btn-block btn-light">Install</a>
                  <a href="/" class="btn btn-block btn-primary">Log In</a>
                </div>
              <?php } ?>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?= $phpAUTH->Compliance->form() ?>
  </body>
</html>
