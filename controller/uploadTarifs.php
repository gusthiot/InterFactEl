<?php
require_once("../includes/Zip.php");
require_once("../assets/Label.php");
require_once("../includes/Params.php");
require_once("../includes/State.php");
require_once("../session.php");
require_once("../assets/Lock.php");
require_once("../assets/Parametres.php");
require_once("../assets/Message.php");

checkGest($dataGest);
if($_FILES['zip_file'] && isset($_POST['plate']) && isset($_POST['type'])) {
    checkPlateforme($dataGest, $_POST["plate"]);
    $plateforme = $_POST['plate'];
    $fileName = $_FILES["zip_file"]["name"];
    $source = $_FILES["zip_file"]["tmp_name"];
    $locklast = new Lock();
    $messages = new Message();
    $state = new State();
    $state->lastState(DATA.$plateforme, $locklast);
    if(Zip::isAccepted($_FILES["zip_file"]["type"])) {
        if($_POST['type'] == "new" && isset($_POST['month-picker'])) {
            $date = explode(" ", $_POST['month-picker']);
            $dirTarifs = DATA.$plateforme."/".$date[1]."/".$date[0]."/";
            if (!file_exists($dirTarifs."/".Parametres::NAME)) {
                $tmpFile = TEMP.time().'_'.$fileName;
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
            else{
                if(State::isSame($state->getLastMonth(), $state->getLastYear(), $date[0], $date[1])) {
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
