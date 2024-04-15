<?php
require_once("../commons/Zip.php");
require_once("../config.php");
require_once("../src/Label.php");

session_start();
if($_FILES['zip_file'] && isset($_POST['plate']) && isset($_POST['month-picker'])) {
    $plateforme = $_POST['plate'];
    $date = explode(" ", $_POST['month-picker']);
    //$fileName = $_FILES["zip_file"]["name"];
    $source = $_FILES["zip_file"]["tmp_name"];
    $destDir = "../".$plateforme."/".$date[1]."/".$date[0]."/";
    if(Zip::isAccepted($_FILES["zip_file"]["type"])) {
        if (file_exists($destDir) || mkdir($destDir, 0777, true)) {
            if(copy($source, $destDir."parametres.zip")) {
                $msg = "Zip correctement sauvegardé !";
                $label = new Label();
                if(!$label->save($destDir, "New")) {
                    $msg .= " problème avec le label";
                }
                unlink($tmpFile);
            }
            else {
                $_SESSION['message'] = "copy error ";
            }
        }
        else {
            $errors= error_get_last();
            $_SESSION['message'] = $errors['message'];
        }
    }
    else {
        $_SESSION['message'] = "zip not accepted";
    }
    header('Location: ../tarifs.php?plateforme='.$plateforme);
}
else {
    $_SESSION['message'] = "post_data_missing";
    header('Location: ../index.php');
}


?>
