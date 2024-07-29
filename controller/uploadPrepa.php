<?php

require_once("../assets/ParamZip.php");
require_once("../assets/Logfile.php");
require_once("../assets/ParamRun.php");
require_once("../assets/Lock.php");
require_once("../assets/Message.php");
require_once("../assets/Sap.php");
require_once("../includes/Zip.php");
require_once("../includes/State.php");
require_once("../session.inc");

/**
 * Called while uploading a preparation zip to run a prefacturation
 */
checkGest($dataGest);
if(isset($_POST['plate']) && isset($_POST['type'])) {
    checkPlateforme($dataGest, $_POST["plate"]);
    $plateforme = $_POST['plate'];
    $lockProcess = Lock::load("../", "process");
    if(!empty($lockProcess)) {
        $_SESSION['alert-danger'] = 'Un processus est en cours. Veuillez patientez et rafraîchir la page...</div>';;
        header('Location: ../plateforme.php?plateforme='.$plateforme);
        exit;
    }
 
    $type = $_POST['type'];
    $messages = new Message();
    if(isset($_FILES[$type])) {
        $zip = $_FILES[$type];
        $fileName = $zip["name"];
        $source = $zip["tmp_name"];
        if(Zip::isAccepted($zip["type"])) {
            $tmpFile = TEMP.time().'_'.$fileName;
            if(copy($source, $tmpFile)) {
                $tmpDir = TEMP.'test_'.time().'/';
                if (file_exists($tmpDir) || mkdir($tmpDir, 0777, true)) {
                    $msg = Zip::unzip($tmpFile, $tmpDir);
                    unlink($tmpFile);
                    if(empty($msg)) {                    
                        if($type == "FIRST" || $type == "SIMU") {
                            // if you need tu upload all data, we need ton check consistancy
                            $result = new ParamRun($tmpDir, 'result');
                            $paramedit = new ParamRun($tmpDir, 'edit');
                            if($plateforme !== $paramedit->getParam('Platform')) {
                                $msg = $messages->getMessage('msg3')."<br/>".$messages->getMessage('msg3.1');
                            }
                            elseif($plateforme !== $result->getParam('Platform')) {
                                $msg = $messages->getMessage('msg3')."<br/>".$messages->getMessage('msg3.4');
                            }
                            elseif($type === "SIMU" && $paramedit->getParam('Type') !== "SIMU") {
                                $msg = $messages->getMessage('msg3')."<br/>".$messages->getMessage('msg3.7');
                            }
                            elseif(!State::isNextOrSame($result->getParam('Month'), $result->getParam('Year'), $paramedit->getParam('Month'), $paramedit->getParam('Year'))) {
                                $msg = $messages->getMessage('msg3')."<br/>".$messages->getMessage('msg3.8');
                            }
                        }
                        else {
                            // with previous internal data, consitancy should be guaranteed
                            $state = new State(DATA.$plateforme);
                            $dirOut = DATA.$plateforme."/".$state->getLastYear()."/".$state->getLastMonth()."/".$state->getLastVersion()."/".$state->getLastRun()."/OUT/";
                                
                            foreach(array_diff(scandir($dirOut), ['.', '..']) as $file) {
                                if(!copy($dirOut.$file, $tmpDir.$file)) {
                                    $msg = "erreur de copie ".$dirOut.$file." vers ".$tmpDir.$file;
                                    break;
                                }
                            }

                            $wm = "";
                            $tyfact = "SAP";
                            if($type == "PROFORMA") {
                                $paramtext = new ParamRun($dirOut, 'text');
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
                            ParamRun::write($tmpDir."/".ParamRun::NAMES['edit'], $array);
                            $paramedit = new ParamRun($tmpDir, 'edit');

                            $paramFile = DATA.$plateforme."/".$year."/".$month."/".ParamZip::NAME;
                            if(file_exists($paramFile)) {
                                $msg = Zip::unzip($paramFile, $tmpDir);
                            }
                        }
                        if(empty($msg)) {
                            // if files are ok, we can run
                            $pathPlate = DATA.$plateforme;
                            $unique = time();
                            Lock::save("../", 'process', "prefa ".$plateforme." ".$unique);
                            try {
                                runPrefa($tmpDir, $pathPlate, $paramedit, $sciper, $plateforme, $unique, $messages, $user);
                            }
                            catch(Exception $e) {
                                $msg = $e->getMessage(); 
                            }
                            unlink("../".Lock::FILES['process']);
                        }
                        else {
                            $_SESSION['alert-danger'] = $msg;
                        }
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
        $_SESSION['alert-danger'] =  "zip missing";
    }
    if($type == "SIMU") {
        header('Location: ../index.php');
    }
    else {
        header('Location: ../plateforme.php?plateforme='.$plateforme);
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
 * @param Paramrun $paramedit edition parameters data
 * @param string $sciper user sciper
 * @param string $plateforme plateform number
 * @param string $unique run unique name
 * @param Message $messages config messages data
 * @param string $user user login surname
 * @return void
 */
function runPrefa(string $tmpDir, string $path, Paramrun $paramedit, string $sciper, string $plateforme, string $unique, Message $messages, string $user): void 
{
    $month = $paramedit->getParam('Month');
    $year = $paramedit->getParam('Year');
    $type = $paramedit->getParam('Type');
    $dev = "";
    if(DEV_MODE) {
        $dev = " -n";
    }
    $cmd = '/usr/bin/python3.10 ../PyFactEl/main.py -e '.$tmpDir.$dev.' -g -d '.DATA.' -u'.$unique.' -s '.$sciper.' -l '.$user;
    $res = shell_exec($cmd);
    $mstr = State::addToMonth($month, 0);
    if(substr($res, 0, 2) === "OK") {
        $tab = explode(" ", $res);
        $version = $tab[1];
        $dir = DATA.$plateforme."/".$year."/".$mstr."/".$version."/".$unique;
        if($type === "SAP") {
            $sap = new Sap($dir);
            $txt = date('Y-m-d H:i:s')." | ".$user." | ".$year.", ".$mstr.", ".$version.", ".$unique." | ".$unique." | Création préfacturation | - | ".$sap->status();
            Logfile::write(DATA.$plateforme, $txt);
            $_SESSION['alert-success'] = $messages->getMessage('msg1');
        }
        else {
            $name = $sciper."_".$type.'.zip';
            Lock::saveByName("../".$sciper.".lock", TEMP.$name);
            Zip::setZipDir(TEMP.$name, $dir."/", Lock::FILES['run']);
            delPrefa($path, $year, $mstr, $unique);
            $_SESSION['alert-success'] = $messages->getMessage('msg2');
        }
    }
    else {
        delPrefa($path, $year, $mstr, $unique);
        $_SESSION['alert-danger'] = $messages->getMessage('msg4')."<br/>".nl2br($res);
    }
}

/**
 * Deletes the result of a prefacturation
 *
 * @param string $path path to plateform directory
 * @param string $year concerned year
 * @param string $mstr concerned month, in string format
 * @param string $unique concerned run
 * @return void
 */
function delPrefa(string $path, string $year, string $mstr, string $unique): void
{
    State::removeRun($path."/".$year."/".$mstr, $unique);
    if(file_exists($path)) {
        if(file_exists($path."/".$year)) {
            rmdir($path."/".$year);
            rmdir($path);
        }
    }
}
