<?php
require_once("../commons/Zip.php");
require_once("../commons/State.php");
require_once("../src/Result.php");
require_once("../src/Logfile.php");
require_once("../src/Paramedit.php");
require_once("../src/Paramtext.php");
require_once("../src/Lock.php");
require_once("../session.php");
require_once("../src/Sap.php");

$_SESSION['type'] = "alert-danger";
if(isset($_POST['plate']) && isset($_POST['type']) && isset($_POST['sciper'])) {
    $plateforme = $_POST['plate'];
    $sciper = $_POST['sciper'];
    $type = $_POST['type'];
    if(isset($_FILES[$type])) {
        $zip = $_FILES[$type];
        $fileName = $zip["name"];
        $source = $zip["tmp_name"];
        if(Zip::isAccepted($zip["type"])) {
            $tmpFile = TEMP.$fileName;
            if(copy($source, $tmpFile)) {
                $tmpDir = TEMP.'test/';
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
                            $state->lastState("../".$plateforme, $lockv);
                            $dirOut = "../".$plateforme."/".$state->getLastYear()."/".$state->getLastMonth()."/".$state->getLastVersion()."/".$state->getLastRun()."/OUT/";
                                
                            foreach(State::scanDescSan($dirOut) as $file) {
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

                            $paramFile = "../".$plateforme."/".$year."/".$month."/parametres.zip";
                            if(file_exists($paramFile)) {
                                $msg = Zip::unzip($paramFile, $tmpDir);
                            }
                        }
                        if(empty($msg)) {
                            $pathPlate = "../".$plateforme;
                            $unique = time();
                            $lockp = new Lock();
                            $lockp->save("../", 'process', "prefa ".$plateforme." ".$unique);
                            try {
                                $msg = runPrefa($tmpDir, $pathPlate, $params, $sciper, $plateforme, $unique);
                            }
                            catch(Exception $e) {
                                $msg = $e->getMessage(); 
                            }
                            unlink("../".Lock::FILES['process']);
                        }
                    }
                    State::delDir($tmpDir);
                }
                else {
                    $errors= error_get_last();
                    $msg = $errors['message'];

                }
                $_SESSION['message'] = $msg;
            }
            else {
                $_SESSION['message'] = "copy error";
            }
        }
        else {
            $_SESSION['message'] =  "zip not accepted";
        }
    }
    else {
        $_SESSION['message'] =  "zip missing";
    }
    if($type == "SIMU") {
        header('Location: ../index.php');
    }
    else {
        header('Location: ../plateforme.php?plateforme='.$plateforme);
    }
        
}
else {
    $_SESSION['message'] = "post_data_missing";
    header('Location: ../index.php');
}

function runPrefa($tmpDir, $path, $params, $sciper, $plateforme, $unique) {
    $month = $params->getParam('Month');
    $year = $params->getParam('Year');
    $type = $params->getParam('Type');
    $cmd = '/usr/bin/python3.10 ../PyFactEl/main.py -e '.$tmpDir.' -g -d ../ -u'.$unique.' -s '.$sciper.' -l '.$_SESSION['user'];
    $result = shell_exec($cmd);
    $mstr = State::addToMonth($month, 0);
    if(substr($result, 0, 2) === "OK") {
        $msg = $unique." tout OK ".strstr($result, '(');
        $_SESSION['type'] = "alert-success";
        $tab = explode(" ", $result);
        $version = $tab[1];
        $dir = "../".$plateforme."/".$year."/".$mstr."/".$version."/".$unique;
        if($type === "SAP") {
            $logfile = new Logfile();
            $sap = new Sap();
            $sap->load($dir);
            $status = $sap->status();
            $txt = date('Y-m-d H:i:s')." | ".$_SESSION['user']." | ".$year.", ".$mstr.", ".$version.", ".$unique." | ".$unique." | Création préfacturation | - | ".$status;
            $logfile->write("../".$plateforme, $txt);
            return $msg;
        }
        else {
            Zip::getZipDir(TEMP.$type.'.zip', $dir."/");
            delPrefa($path, $year, $mstr, $unique);
            return $msg;
        }
    }
    else {
        delPrefa($path, $year, $mstr, $unique);
        return $result;
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
