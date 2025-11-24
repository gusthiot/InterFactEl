<?php
require_once("../assets/Ticket.php");
require_once("../includes/Zip.php");
require_once("../includes/State.php");
require_once("../session.inc");

/**
 * Called by ticket viewer tool, to display tickets from external data
 */
if($_FILES['zip_file']) {
    if($_FILES['zip_file']["error"] == 0) {
        $fileName = $_FILES["zip_file"]["name"];
        $source = $_FILES["zip_file"]["tmp_name"];
        if(Zip::isAccepted($_FILES["zip_file"]["type"])) {
            $tmpFile = TEMP.time().'_'.$fileName;
            if(copy($source, $tmpFile)) {
                $unique = 'tickets_'.time();
                $tmpDir = TEMP.$unique.'/';
                if (file_exists($tmpDir) || mkdir($tmpDir, 0777, true)) {
                    $msg = Zip::unzip($tmpFile, $tmpDir);
                    if(empty($msg)) {
                        if(!file_exists($tmpDir.Ticket::NAME)) {
                            $msg = "Le fichier de ticket est absent !";
                        }
                    }
                }
                unlink($tmpFile);
                if(empty($msg)) {
                    header('Location: ../ticket.php?unique='.$unique);
                    exit;
                }
                else {
                    State::delDir($tmpDir);
                    $_SESSION['alert-danger'] = $msg;
                }

            }
            else {
                $_SESSION['alert-danger'] = "copy error";
            }
        }
        else {
            $_SESSION['alert-danger'] = "zip not accepted : ".$_FILES["zip_file"]["type"];
        }
    }
    else {
        $_SESSION['alert-danger'] = Zip::getErrorMessage($_FILES['zip_file']["error"]);
    }
}
else {
    $_SESSION['alert-danger'] = "post_data_missing";
}
header('Location: ../index.php');
