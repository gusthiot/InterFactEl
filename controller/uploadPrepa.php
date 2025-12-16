<?php

require_once("../assets/ParamZip.php");
require_once("../assets/Logfile.php");
require_once("../assets/Result.php");
require_once("../assets/ParamEdit.php");
require_once("../assets/ParamText.php");
require_once("../assets/Lock.php");
require_once("../assets/Message.php");
require_once("../assets/Sap.php");
require_once("../includes/Zip.php");
require_once("../includes/State.php");
require_once("../session.inc");

/**
 * Called while uploading a preparation zip to run a prefacturation
 */
if(isset($_POST['type'])) {
    $type = $_POST['type'];

    if($type != "SIMU") {
        if(!isset($_POST['plate'])) {
            $_SESSION['alert-danger'] = 'plateforme manquante';
            header('Location: ../index.php');
            exit;
        }
        checkPlateforme("facturation", $_POST["plate"]);
        $plateforme = $_POST['plate'];
    }
/*
    if($type == "ARCHIVE") {
        if(!IS_SUPER || TEST_MODE != "TEST") {
            $_SESSION['alert-danger'] = "wrong place, wrong user";
            header('Location: ../facturation.php?plateforme='.$plateforme);
            exit;
        }
    }
*/
//    if($type != "ARCHIVE") {
        $lockProcess = Lock::load("../", "process");
        if(!is_null($lockProcess)) {
            $_SESSION['alert-danger'] = 'Un processus est en cours. Veuillez patientez et rafraîchir la page...</div>';
            if($type == "SIMU") {
                header('Location: ../index.php');
                exit;
            }
            else {
                header('Location: ../facturation.php?plateforme='.$plateforme);
                exit;
            }
        }
//    }

    if(isset($_FILES[$type])) {
        if($_FILES[$type]["error"] == 0) {
            $zip = $_FILES[$type];
            $fileName = $zip["name"];
            $source = $zip["tmp_name"];
            if(Zip::isAccepted($zip["type"])) {
                $tmpFile = TEMP.time().'_'.$fileName;
                if(copy($source, $tmpFile)) {
                    $tmpDir = TEMP.'prepa_'.time().'/';
                    if (file_exists($tmpDir) || mkdir($tmpDir, 0777, true)) {
                        $msg = Zip::unzip($tmpFile, $tmpDir);
                        unlink($tmpFile);
                        if(empty($msg)) {
                            /*if($type == "ARCHIVE") {
                                State::recurseCopy($tmpDir, DATA.$plateforme);
                                $_SESSION['alert-success'] = "Archive correctement chargée";
                            }*/
                            //else {
                                $messages = new Message();
                                if(!copy(CONFIG.ParamText::NAME, $tmpDir.ParamText::NAME)) {
                                    $msg .= "erreur de copie de ".ParamText::NAME;
                                }
                                if($type == "FIRST") {
                                    // if you need to upload all data, we need ton check consistancy
                                    $result = new Result($tmpDir);
                                    $paramedit = new ParamEdit($tmpDir);
                                    if($plateforme !== $paramedit->getParam('Platform')) {
                                        $msg = $messages->getMessage('msg3')."<br/>".$messages->getMessage('msg3.4');
                                    }
                                    elseif($plateforme !== $result->getParam('Platform')) {
                                        $msg = $messages->getMessage('msg3')."<br/>".$messages->getMessage('msg3.5');
                                    }
                                    elseif(!State::isNextToOrSameAs($result->getParam('Month'), $result->getParam('Year'), $paramedit->getParam('Month'), $paramedit->getParam('Year'))) {
                                        $msg = $messages->getMessage('msg3')."<br/>".$messages->getMessage('msg3.6');
                                    }
                                }
                                elseif($type == "SIMU") {
                                    $result = new Result($tmpDir);
                                    $paramedit = new ParamEdit($tmpDir);
                                    if($paramedit->getParam('Type') !== "SIMU") {
                                        $msg = $messages->getMessage('msg3')."<br/>".$messages->getMessage('msg3.1');
                                    }
                                    elseif($paramedit->getParam('Platform') !== $result->getParam('Platform')) {
                                        $msg = $messages->getMessage('msg3')."<br/>".$messages->getMessage('msg3.2');
                                    }
                                    elseif(!State::isNextToOrSameAs($result->getParam('Month'), $result->getParam('Year'), $paramedit->getParam('Month'), $paramedit->getParam('Year'))) {
                                        $msg = $messages->getMessage('msg3')."<br/>".$messages->getMessage('msg3.3');
                                    }
                                    $plateforme = $paramedit->getParam('Platform');
                                }
                                else {
                                    // with previous internal data, consistancy should be guaranteed
                                    $state = new State(DATA.$plateforme);
                                    /*
                                    $dirOut = $state->getLastPath()."/OUT/";
                                    foreach(array_diff(scandir($dirOut), ['.', '..']) as $file) {
                                        if($file == "paramtext.csv") {
                                            continue;
                                        }
                                        if(!copy($dirOut.$file, $tmpDir.$file)) {
                                            $msg .= "erreur de copie de ".$file;
                                            break;
                                        }
                                    }
                                    */
                                    $wm = "";
                                    $tyfact = "SAP";
                                    if($type == "PROFORMA") {
                                        $paramtext = new ParamText();
                                        $wm = $paramtext->getParam('filigr-prof');
                                        $tyfact = "PROFORMA";
                                    }
                                    if($type == "REDO") {
                                        $year = $state->getLastYear();
                                        $month = $state->getLastMonth();
                                    }
                                    else {
                                        $year = $state->getNextYear();
                                        $month = $state->getNextMonth();
                                    }
                                    $array = [["Platform", $plateforme], ["Year", $year], ["Month", $month], ["Type", $tyfact], ["Watermark", $wm]];
                                    Csv::write($tmpDir."/".ParamEdit::NAME, $array);
                                    $paramedit = new ParamEdit($tmpDir);
                                    $paramFile = DATA.$plateforme."/".$year."/".$month."/".ParamZip::NAME;
                                    if(file_exists($paramFile)) {
                                        $msg .= Zip::unzip($paramFile, $tmpDir);
                                    }
                                }
                                if(empty($msg)) {
                                    // if files are ok, we can run
                                    $pathPlate = DATA.$plateforme;
                                    $unique = time();
                                    Lock::save("../", 'process', "prefa ".$plateforme." ".$unique);
                                    try {
                                        runPrefa($tmpDir, $pathPlate, $paramedit, $plateforme, $unique, $messages);
                                    }
                                    catch(Exception $e) {
                                        $msg = $e->getMessage();
                                    }
                                    unlink("../".Lock::FILES['process']);
                                }
                                else {
                                    $_SESSION['alert-danger'] = $msg;
                                }
                            //}
                        }
                        else {
                            $_SESSION['alert-danger'] = $msg;
                        }
                        State::delDir($tmpDir);
                    }
                    else {
                        $errors= error_get_last();
                        $_SESSION['alert-danger'] = $errors['message'];

                    }
                }
                else {
                    $_SESSION['alert-danger'] = "copy error";
                }
            }
            else {
                $_SESSION['alert-danger'] = "zip not accepted";
            }
        }
        else {
            $_SESSION['alert-danger'] = Zip::getErrorMessage($_FILES[$type]["error"]);
        }
    }
    else {
        $_SESSION['alert-danger'] =  "zip missing";
    }
    if($type == "SIMU") {
        header('Location: ../index.php');
    }
    else {
        header('Location: ../facturation.php?plateforme='.$plateforme);
    }

}
else {
    $_SESSION['alert-danger'] = "post_data_missing";
    header('Location: ../index.php');
}

/**
 * Runs Python prefacturation
 *
 * @param string $tmpDir directory were to temporary find the files needed for the prefacturation
 * @param string $path path to plateform directory
 * @param ParamEdit $paramedit edition parameters data
 * @param string $plateforme plateform number
 * @param string $unique run unique name
 * @param Message $messages config messages data
 * @return void
 */
function runPrefa(string $tmpDir, string $path, ParamEdit $paramedit, string $plateforme, string $unique, Message $messages): void
{
    $month = $paramedit->getParam('Month');
    $year = $paramedit->getParam('Year');
    $type = $paramedit->getParam('Type');
    $dev = "";
    if(DEV_MODE) {
        $dev = " -n";
    }
    $cmd = '/usr/bin/python3 ../PyFactEl/main.py -e '.$tmpDir.$dev.' -g -s -d '.TEMP.' -u'.$unique.' -l '.USER;
    $res = shell_exec($cmd);
    $mstr = State::addToMonth($month, 0);
    if(substr($res, 0, 2) === "OK") {
        $tab = explode(" ", $res);
        $version = $tab[1];
        if($type === "SAP") {
            $dir = DATA.$plateforme."/".$year."/".$mstr."/".$version;
            if (file_exists($dir) || mkdir($dir, 0755, true)) {
                rename(TEMP.$unique, $dir."/".$unique);
                $sap = new Sap($dir."/".$unique);
                $txt = date('Y-m-d H:i:s')." | ".USER." | ".$year.", ".$mstr.", ".$version.", ".$unique." | ".$unique." | Création préfacturation | - | ".$sap->status();
                Logfile::write(DATA.$plateforme, $txt);
                $_SESSION['alert-success'] = $messages->getMessage('msg1');
            }
            else {
                State::delDir(TEMP.$unique);
                $_SESSION['alert-danger'] = "Problème pour créer le dossier !";
            }
        }
        else {
            $name = USER."_".$type.'.zip';
            Lock::saveByName("../".USER.".lock", TEMP.$name);
            Zip::setZipDir(TEMP.$name, TEMP.$unique."/", Lock::FILES['run']);
            State::delDir(TEMP.$unique);
            $_SESSION['alert-success'] = $messages->getMessage('msg2');
        }
    }
    else {
        State::delDir(TEMP.$unique);
        $_SESSION['alert-danger'] = $messages->getMessage('msg4')."<br/>".nl2br($res);
    }
}
