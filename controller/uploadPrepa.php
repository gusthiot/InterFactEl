<?php
require_once("../commons/Zip.php");
require_once("../commons/State.php");
require_once("../src/Result.php");
require_once("../src/Logfile.php");
require_once("../src/Paramedit.php");
require_once("../src/Lock.php");
require_once("../session.php");
require_once("../src/Sap.php");

if(($_FILES['zip_file']) && isset($_POST['plate']) && isset($_POST['type']) && isset($_POST['sciper'])) {
    $plateforme = $_POST['plate'];
    $sciper = $_POST['sciper'];
    $type = $_POST['type'];
    $fileName = $_FILES["zip_file"]["name"];
    $source = $_FILES["zip_file"]["tmp_name"];
    if(Zip::isAccepted($_FILES["zip_file"]["type"])) {
        $tmpFile = TEMP.$fileName;
        if(copy($source, $tmpFile)) {
            $tmpDir = TEMP.'test/';
            if (file_exists($tmpDir) || mkdir($tmpDir, 0777, true)) {
                $msg = Zip::unzip($tmpFile, $tmpDir);
                unlink($tmpFile);
                if(empty($msg)) {
                    $results = new Result();
                    $params = new Paramedit();
                    $lockv = new Lock();
                    $state->lastState("../".$plateforme, $lockv);
                    if($params->load($tmpDir."paramedit.csv") && $results->load($tmpDir."result.csv")) {
                        if($plateforme != $params->getParam('Platform')) {
                            $msg = $messages->getMessage('msg3')."<br/>".$messages->getMessage('msg3.1');
                        }
                        elseif($type === "SIMU" && $params->getParam('Type') !== "SIMU") {
                            $msg = $messages->getMessage('msg3')."<br/>".$messages->getMessage('msg3.7');
                        }
                        elseif($type !== "FIRST" && $type !== "SIMU" && (($type === "PROFORMA" && $params->getParam('Type') !== "PROFORMA") || ($type !== "PROFORMA" && $params->getParam('Type') !== "SAP"))) {
                            $msg = $messages->getMessage('msg3')."<br/>".$messages->getMessage('msg3.2');
                        }
                        elseif($type === "REDO" && State::isSame($state->getLastMonth(), $state->getLastYear(), $params->getParam('Month'), $params->getParam('Year'))) {
                            $msg = $messages->getMessage('msg3')."<br/>".$messages->getMessage('msg3.3');
                        }
                        elseif(($type === "MONTH" || $type === "PROFORMA") && !State::isNext($state->getLastMonth(), $state->getLastYear(), $params->getParam('Month'), $params->getParam('Year'))) {
                            $msg = $messages->getMessage('msg3')."<br/>".$messages->getMessage('msg3.3');
                        }
                        elseif($plateforme !== $results->getResult('Platform')) {
                            $msg = $messages->getMessage('msg3')."<br/>".$messages->getMessage('msg3.4');
                        }
                        elseif(($type == "FIRST" || $type == "SIMU") && !State::isNextOrSame($results->getResult('Month'), $results->getResult('Year'), $params->getParam('Month'), $params->getParam('Year'))) {
                            $msg = $messages->getMessage('msg3')."<br/>".$messages->getMessage('msg3.8');
                        }
                        elseif($type !== "SIMU" && $results->getResult('Type') !== "SAP") {
                            $msg = $messages->getMessage('msg3')."<br/>".$messages->getMessage('msg3.5');
                        }
                        else {
                            $pathPlate = "../".$plateforme;
                            if($type !== "SIMU" && $type !== "FIRST") {
                                if((int)$state->getLastMonth() === (int)$results->getResult('Month') && $state->getLastYear() === $results->getResult('Year') && $state->getLastVersion() === $results->getResult('Version') && $state->getLastRun() === $results->getResult('Folder')) {
                                    $msg = runPrefa($tmpDir, $pathPlate, $params, $sciper, $plateforme);
                                }
                                else {
                                    $msg = $messages->getMessage('msg3')."<br/>".$messages->getMessage('msg3.6');
                                }
                            }
                            else {
                                $msg = runPrefa($tmpDir, $pathPlate, $params, $sciper, $plateforme);
                            }
                        }
                    }
                    else {
                        $msg = "Fichier(s) paramedit.csv et/ou result.csv vide(s)";
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
            $_SESSION['message'] = "copy";
        }
    }
    else {
        $_SESSION['message'] = "zip";
    }
    header('Location: ../plateforme.php?plateforme='.$plateforme);
        
}
else {
    $_SESSION['message'] = "post_data_missing";
    header('Location: ../index.php');
}

function runPrefa($tmpDir, $path, $params, $sciper, $plateforme) {
    $unique = time();
    $month = $params->getParam('Month');
    $year = $params->getParam('Year');
    $type = $params->getParam('Type');
    $cmd = '/usr/bin/python3.10 ../PyFactEl-V11/main.py -n -e '.$tmpDir.' -g -d ../ -u'.$unique.' -s '.$sciper.' -l '.$_SESSION['user'];
    $result = shell_exec($cmd);
    $mstr = (int)$month > 9 ? $month : '0'.$month;
    if(substr($result, 0, 2) === "OK") {
        $msg = $unique." tout OK ".strstr($result, '(');
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
