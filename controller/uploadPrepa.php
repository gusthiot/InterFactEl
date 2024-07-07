<?php
require_once("../includes/Zip.php");
require_once("../assets/Parametres.php");
require_once("../includes/State.php");
require_once("../assets/Result.php");
require_once("../assets/Logfile.php");
require_once("../assets/Paramedit.php");
require_once("../assets/Paramtext.php");
require_once("../assets/Lock.php");
require_once("../assets/Message.php");
require_once("../assets/Sap.php");
require_once("../session.inc");

checkGest($dataGest);
if(isset($_POST['plate']) && isset($_POST['type'])) {
    checkPlateforme($dataGest, $_POST["plate"]);
    $plateforme = $_POST['plate'];
    $lockp = new Lock();
    $lockedTxt = $lockp->load("../", "process");
    if(!empty($lockedTxt)) {
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
                            $results = new Result();
                            $params = new Paramedit();
                            if($params->load($tmpDir."paramedit.csv") && $results->load($tmpDir."result.csv")) {
                                if($plateforme !== $params->getParam('Platform')) {
                                    $msg = $messages->getMessage('msg3')."<br/>".$messages->getMessage('msg3.1');
                                }
                                elseif($plateforme !== $results->getResult('Platform')) {
                                    $msg = $messages->getMessage('msg3')."<br/>".$messages->getMessage('msg3.4');
                                }
                                elseif($type === "SIMU" && $params->getParam('Type') !== "SIMU") {
                                    $msg = $messages->getMessage('msg3')."<br/>".$messages->getMessage('msg3.7');
                                }
                                elseif(!State::isNextOrSame($results->getResult('Month'), $results->getResult('Year'), $params->getParam('Month'), $params->getParam('Year'))) {
                                    $msg = $messages->getMessage('msg3')."<br/>".$messages->getMessage('msg3.8');
                                }
                            }
                            else {
                                $msg = "Fichier(s) paramedit.csv et/ou result.csv vide(s)";
                            }
                        }
                        else {
                            $lockv = new Lock();
                            $state = new State();
                            $state->lastState(DATA.$plateforme, $lockv);
                            $dirOut = DATA.$plateforme."/".$state->getLastYear()."/".$state->getLastMonth()."/".$state->getLastVersion()."/".$state->getLastRun()."/OUT/";
                                
                            foreach(array_diff(scandir($dirOut), ['.', '..']) as $file) {
                                if(!copy($dirOut.$file, $tmpDir.$file)) {
                                    $msg = "erreur de copie ".$dirOut.$file." vers ".$tmpDir.$file;
                                    break;
                                }
                            }

                            $tmpPe = $tmpDir.'paramedit.csv';
                            $wm = "";
                            $tyfact = "SAP";
                            if($type == "PROFORMA") {
                                $paramtext = new Paramtext();
                                if($paramtext->load($dirOut."paramtext.csv")) {
                                    $wm = $paramtext->getParam('filigr-prof');
                                }
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
                            $params = new Paramedit();
                            $params->write($tmpPe, $array);
                            $params->load($tmpPe);

                            $paramFile = DATA.$plateforme."/".$year."/".$month."/".Parametres::NAME;
                            if(file_exists($paramFile)) {
                                $msg = Zip::unzip($paramFile, $tmpDir);
                            }
                        }
                        if(empty($msg)) {
                            $pathPlate = DATA.$plateforme;
                            $unique = time();
                            $lockp = new Lock();
                            $lockp->save("../", 'process', "prefa ".$plateforme." ".$unique);
                            try {
                                runPrefa($tmpDir, $pathPlate, $params, $sciper, $plateforme, $unique, $messages);
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

function runPrefa($tmpDir, $path, $params, $sciper, $plateforme, $unique, $messages) {
    $month = $params->getParam('Month');
    $year = $params->getParam('Year');
    $type = $params->getParam('Type');
    $dev = "";
    if(DEV_MODE) {
        $dev = " -n";
    }
    $cmd = '/usr/bin/python3.10 ../PyFactEl/main.py -e '.$tmpDir.$dev.' -g -d '.DATA.' -u'.$unique.' -s '.$sciper.' -l '.$user;
    $result = shell_exec($cmd);
    $mstr = State::addToMonth($month, 0);
    if(substr($result, 0, 2) === "OK") {
        $tab = explode(" ", $result);
        $version = $tab[1];
        $dir = DATA.$plateforme."/".$year."/".$mstr."/".$version."/".$unique;
        if($type === "SAP") {
            $logfile = new Logfile();
            $sap = new Sap();
            $sap->load($dir);
            $status = $sap->status();
            $txt = date('Y-m-d H:i:s')." | ".$user." | ".$year.", ".$mstr.", ".$version.", ".$unique." | ".$unique." | Création préfacturation | - | ".$status;
            $logfile->write(DATA.$plateforme, $txt);
            $_SESSION['alert-success'] = $messages->getMessage('msg1');
        }
        else {
            $lock = new Lock();
            $name = $sciper."_".$type.'.zip';
            $lock->saveByName("../".$sciper.".lock", TEMP.$name);
            Zip::setZipDir(TEMP.$name, $dir."/", Lock::FILES['run']);
            delPrefa($path, $year, $mstr, $unique);
            $_SESSION['alert-success'] = $messages->getMessage('msg2');
        }
    }
    else {
        delPrefa($path, $year, $mstr, $unique);
        $_SESSION['alert-danger'] = $messages->getMessage('msg4')."<br/>".nl2br($result);
    }
}

function delPrefa($path, $year, $mstr, $unique) {
    State::removeRun($path."/".$year."/".$mstr, $unique);
    if(file_exists($path)) {
        if(file_exists($path."/".$year)) {
            rmdir($path."/".$year);
            rmdir($path);
        }
    }
}




?>
