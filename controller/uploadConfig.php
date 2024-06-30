<?php
require_once("../commons/Zip.php");
require_once("../session.php");

if($superviseur->isSuperviseur($user)) {
    if($_FILES['zip_file']) {
        $fileName = $_FILES["zip_file"]["name"];
        $source = $_FILES["zip_file"]["tmp_name"];
        if(Zip::isAccepted($_FILES["zip_file"]["type"])) {
            $tmpFile = TEMP.time().'_'.$fileName;
            if(copy($source, $tmpFile)) {
                $msg = Zip::unzip($tmpFile, CONFIG);
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
        $_SESSION['alert-danger'] = "post_data_missing";
    }
}
header('Location: ../index.php');



?>
