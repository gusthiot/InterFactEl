<?php
require_once("../includes/Config.php");
require_once("../includes/Zip.php");
require_once("../includes/State.php");
require_once("../assets/Message.php");
require_once("../assets/ParamText.php");
require_once("../session.inc");

/**
 * Called to upload new config files
 */
if(IS_SUPER) {
    if($_FILES['zip_file']) {
        if($_FILES['zip_file']["error"] == 0) {
            $fileName = $_FILES["zip_file"]["name"];
            $source = $_FILES["zip_file"]["tmp_name"];
            if(Zip::isAccepted($_FILES["zip_file"]["type"])) {
                $tmpFile = TEMP.time().'_'.$fileName;
                if(copy($source, $tmpFile)) {
                    $msg = Config::upload($tmpFile);
                    unlink($tmpFile);
                    if(empty($msg)) {
                        $_SESSION['alert-success'] = "Fichiers correctement mis à jour !";
                    }
                    else {
                        $_SESSION['alert-danger'] = $msg;
                    }
            
                }
                else {
                    $_SESSION['alert-danger'] = "copy error";
                }
            }
            else {
                $_SESSION['alert-danger'] = "zip not accepted";
            }
        }
        else {
            $_SESSION['alert-danger'] = Zip::getErrorMessage($_FILES['zip_file']["error"]);
        }
    }
    else {
        $_SESSION['alert-danger'] = "post_data_missing";
    }
}
header('Location: ../index.php');
