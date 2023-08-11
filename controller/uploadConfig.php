<?php
require_once("../commons/Zip.php");

if($_FILES['zip_file']) {
    $filename = $_FILES["zip_file"]["name"];
    $source = $_FILES["zip_file"]["tmp_name"];
    if(Zip::isAccepted($_FILES["zip_file"]["type"])) {
        $tmp_file = "../tmp/".$filename;
        if(copy($source, $tmp_file)) {
            $msg = Zip::unzip($tmp_file, '../CONFIG/');
            unlink($tmp_file);
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
