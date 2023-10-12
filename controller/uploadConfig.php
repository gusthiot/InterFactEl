<?php
require_once("../commons/Zip.php");
require_once("../config.php");

session_start();
if($_FILES['zip_file']) {
    $fileName = $_FILES["zip_file"]["name"];
    $source = $_FILES["zip_file"]["tmp_name"];
    if(Zip::isAccepted($_FILES["zip_file"]["type"])) {
        $tmpFile = TEMP.$fileName;
        if(copy($source, $tmpFile)) {
            $msg = Zip::unzip($tmpFile, "../CONFIG/");
            unlink($tmpFile);
            if(empty($msg)) {
                $msg = "Fichiers correctement mis à jour !";
            }
            $_SESSION['message'] = $msg;
    
        }
        else {
            $_SESSION['message'] = "copy";
        }
    }
    else {
        $_SESSION['message'] = "zip";
    }
}
else {
    $_SESSION['message'] = "data";
}
header('Location: ../index.php');



?>
