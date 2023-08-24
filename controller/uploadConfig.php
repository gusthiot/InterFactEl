<?php
require_once("../commons/Zip.php");
require_once("../config.php");

if($_FILES['zip_file']) {
    $fileName = $_FILES["zip_file"]["name"];
    $source = $_FILES["zip_file"]["tmp_name"];
    if(Zip::isAccepted($_FILES["zip_file"]["type"])) {
        $tmpFile = TEMP.$fileName;
        if(copy($source, $tmpFile)) {
            $msg = Zip::unzip($tmpFile, "../CONFIG/");
            unlink($tmpFile);
            header('Location: ../index.php?message='.$msg);
    
        }
        else {
            header('Location: ../index.php?message=copy');
        }
    }
    else {
        header('Location: ../index.php?message=zip');
    }
}
else {
    header('Location: ../index.php?message=data');
}



?>
