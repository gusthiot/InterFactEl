<?php
require_once("../commons/Zip.php");
require_once("../config.php");
require_once("../assets/Label.php");
require_once("../commons/Params.php");
require_once("../commons/State.php");
require_once("../session.php");
require_once("../assets/Lock.php");

if($_FILES['zip_file'] && isset($_POST['plate']) && isset($_POST['type'])) {
    $plateforme = $_POST['plate'];
    $fileName = $_FILES["zip_file"]["name"];
    $source = $_FILES["zip_file"]["tmp_name"];
    if(Zip::isAccepted($_FILES["zip_file"]["type"])) {
        if($_POST['type'] == "new" && isset($_POST['month-picker'])) {
            $date = explode(" ", $_POST['month-picker']);
            $dirTarifs = DATA.$plateforme."/".$date[1]."/".$date[0]."/";
                $tmpFile = TEMP.$fileName;
                if(copy($source, $tmpFile)) {
                    $msg = Params::importNew($dirTarifs, $tmpFile);
                    if(empty($msg)) {
                        $_SESSION['alert-success'] = "Zip correctement sauvegardé !";
                    }
                    else {
                        $_SESSION['alert-danger'] = $msg;
                    }
                    unlink($tmpFile);
                }
                else {
                    $_SESSION['alert-danger'] = "copy error";
                }
        }
        elseif($_POST['type'] == "correct") {
            $locklast = new Lock();
            $state = new State();
            $state->lastState(DATA.$plateforme, $locklast);
            $dirTarifs = DATA.$plateforme."/".$state->getLastYear()."/".$state->getLastMonth()."/";
            $tmpFile = TEMP.$fileName;
            if(copy($source, $tmpFile)) {
                $msg = Params::correct($dirTarifs, $tmpFile);
                if(empty($msg)) {
                    $_SESSION['alert-success'] = "Zip correctement sauvegardé !";
                }
                else {
                    $_SESSION['alert-danger'] = $msg;
                }

                unlink($tmpFile);
            }
            else {
                $_SESSION['alert-danger'] = "copy error";
            }
        }
        else {
            $_SESSION['alert-danger'] = "post error";
        }
    }
    else {
        $_SESSION['alert-danger'] = "zip not accepted";
    }
    header('Location: ../tarifs.php?plateforme='.$plateforme);
}
else {
    $_SESSION['alert-danger'] = "post_data_missing";
    header('Location: ../index.php');
}


?>
