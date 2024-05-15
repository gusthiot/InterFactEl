<?php
require_once("../commons/Zip.php");
require_once("../config.php");

session_start();
$_SESSION['type'] = "alert-danger";
if($_FILES['zip_file']) {
    $fileName = $_FILES["zip_file"]["name"];
    $source = $_FILES["zip_file"]["tmp_name"];
    if(Zip::isAccepted($_FILES["zip_file"]["type"])) {
        $tmpFile = TEMP.$fileName;
        if(copy($source, $tmpFile)) {
            $msg = Zip::unzip($tmpFile, "../CONFIG/");
            unlink($tmpFile);
            if(empty($msg)) {
                $_SESSION['type'] = "alert-success";
                $_SESSION['message'] = "Fichiers correctement mis à jour !";
            }
            else {
                $_SESSION['message'] = $msg;
            }
    
        }
        else {
            $_SESSION['message'] = "copy error";
        }
    }
    else {
        $_SESSION['message'] = "zip not accepted";
    }
}
else {
    $_SESSION['message'] = "post_data_missing";
}
header('Location: ../index.php');



?>
