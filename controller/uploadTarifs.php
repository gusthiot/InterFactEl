<?php

require_once("../assets/Lock.php");
require_once("../assets/ParamZip.php");
require_once("../assets/Message.php");
require_once("../includes/Zip.php");
require_once("../includes/Tarifs.php");
require_once("../includes/State.php");
require_once("../session.inc");

/**
 * Called while uploading new tarifs files
 */
if($_FILES['zip_file'] && isset($_POST['plate']) && isset($_POST['type'])) {
    checkPlateforme("tarifs", $_POST["plate"]);
    if($_FILES['zip_file']["error"] == 0) {
        $plateforme = $_POST['plate'];
        $fileName = $_FILES["zip_file"]["name"];
        $source = $_FILES["zip_file"]["tmp_name"];
        $messages = new Message();
        $state = new State(DATA.$plateforme);
        if(Zip::isAccepted($_FILES["zip_file"]["type"])) {
            if($_POST['type'] == "new" && isset($_POST['month-picker'])) {
                $date = explode(" ", $_POST['month-picker']);
                $dirTarifs = DATA.$plateforme."/".$date[1]."/".$date[0]."/";
                if (!file_exists($dirTarifs."/".ParamZip::NAME)) {
                    $tmpFile = TEMP.time().'_'.$fileName;
                    if(copy($source, $tmpFile)) {
                        $msg = Tarifs::importNew($dirTarifs, $tmpFile);
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
                else{
                    if($state->isSame($date[0], $date[1])) {
                        $_SESSION['alert-danger'] = $messages->getMessage('msg8');
                    }
                    else {
                        $_SESSION['alert-danger'] = $messages->getMessage('msg7');
                    }
                }
            }
            elseif($_POST['type'] == "correct") {
                $dirTarifs = DATA.$plateforme."/".$state->getLastYear()."/".$state->getLastMonth()."/";
                $tmpFile = TEMP.time().'_'.$fileName;
                if(copy($source, $tmpFile)) {
                    $msg = Tarifs::correct($dirTarifs, $tmpFile);
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
    }
    else {
        $_SESSION['alert-danger'] = Zip::getErrorMessage($_FILES['zip_file']["error"]);
    }
    header('Location: ../tarifs.php?plateforme='.$plateforme);
}
else {
    $_SESSION['alert-danger'] = "post_data_missing";
    header('Location: ../index.php');
}
