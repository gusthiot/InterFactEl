<?php

if(($_FILES['zip_file']) && isset($_POST['type'])) {
    $filename = $_FILES["zip_file"]["name"];
    $source = $_FILES["zip_file"]["tmp_name"];
    $accepted_types = array('application/zip', 'application/x-zip-compressed', 'multipart/x-zip', 'application/x-compressed');
    if(in_array($_FILES["zip_file"]["type"], $accepted_types)) {
        $tmp_file = "../tmp/".$filename;
        if(copy($source, $tmp_file)) {
            $msg = "error";
            if($_POST['type']=="config") {
                $msg = unzip($tmp_file);
            }
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


function unzip($file) {
    $zip = new ZipArchive;
    if ($zip->open($file)) {
        $zip->extractTo('../CONFIG/');
        $zip->close();
        return "success";
    }
    return "error";
}






?>
