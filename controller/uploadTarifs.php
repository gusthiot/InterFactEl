<?php
require_once("../commons/Zip.php");
require_once("../config.php");
require_once("../src/Label.php");
require_once("../commons/Parametres.php");

session_start();
$_SESSION['type'] = "alert-danger";
if($_FILES['zip_file'] && isset($_POST['plate']) && isset($_POST['month-picker'])) {
    $plateforme = $_POST['plate'];
    $date = explode(" ", $_POST['month-picker']);
    $fileName = $_FILES["zip_file"]["name"];
    $source = $_FILES["zip_file"]["tmp_name"];
    $dirTarifs = "../".$plateforme."/".$date[1]."/".$date[0]."/";
    if(Zip::isAccepted($_FILES["zip_file"]["type"])) {
        $tmpFile = TEMP.$fileName;
        if(copy($source, $tmpFile)) {
            $msg = Parametres::importNew($dirTarifs, $tmpFile, $plateforme);
            if(empty($msg)) {
                $_SESSION['type'] = "alert-success";
                $msg = "Zip correctement sauvegardé !";
            }
            $_SESSION['message'] = $msg;
            unlink($tmpFile);
        }
        else {
            $_SESSION['message'] = "copy error";
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
