<?php
require_once("../commons/Zip.php");
require_once("../commons/Data.php");
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
                    $results = new Result($tmpDir."result.csv");
                    $params = new Paramedit();
                    $params->load($tmpDir."paramedit.csv");
                    if($plateforme != $params->getParam('Platform')) {
                        $msg = $messages->getMessage('msg3')."<br/>".$messages->getMessage('msg3.1');
                    }
                    elseif($type !== $params->getParam('Type')) {
                        if($type === "SAP") {
                            $msg = $messages->getMessage('msg3')."<br/>".$messages->getMessage('msg3.2');
                        }
                        elseif($type === "PROFORMA") {
                            $msg = $messages->getMessage('msg3')."<br/>".$messages->getMessage('msg3.8');
                        }
                        else {
                            $msg = $messages->getMessage('msg3')."<br/>".$messages->getMessage('msg3.9');
                        }

                    }
                    elseif($plateforme !== $results->getResult('Platform')) {
                        $msg = $messages->getMessage('msg3')."<br/>".$messages->getMessage('msg3.5');
                    }
                    elseif($type !== "SIMU" && $results->getResult('Type') !== "SAP") {
                        $msg = $messages->getMessage('msg3')."<br/>".$messages->getMessage('msg3.6');
                    }
                    else {
                        $pathPlate = "../".$plateforme;
                        if($type !== "SIMU") {
                            $lock = new Lock();
                            if(file_exists($pathPlate)) {
                                $data = Data::availableForFacturation($pathPlate, $messages, $lock, $results);
                                if(!empty($data[$type])) {
                                    if($data[$type][0]['type'] === "error") {
                                        $msg = $data[$type][0]['msg'];
                                    }
                                    else {
                                        $ok = false;
                                        foreach($data[$type] as $option) {
                                            if($option['type'] == "result") {
                                                if($option['exp_y'] === $params->getParam('Year') && (int)($option['exp_m']) === (int)($params->getParam('Month'))) {
                                                    if($option['year'] === $results->getResult('Year') && (int)($option['month']) === (int)($results->getResult('Month')) && $option['version'] === $results->getResult('Version') && $option['run'] === $results->getResult('Folder')) {
                                                        $ok = true;
                                                        break;
                                                    }
                                                }
                                            }
                                        }
                                        if(!$ok) {
                                            $msg = $messages->getMessage('msg3')."<br/>".$messages->getMessage('msg3.4');
                                        }
                                        else {
                                            $msg = runPrefa($tmpDir, $pathPlate, $params->getParam('Year'), $params->getParam('Month'), $sciper, $plateforme, $type);
                                        }
                                    }
                                }
                                else {
                                    $msg = runPrefa($tmpDir, $pathPlate, $params->getParam('Year'), $params->getParam('Month'), $sciper, $plateforme, $type);
                                }
                            }
                            else {
                                $msg = runPrefa($tmpDir, $pathPlate, $params->getParam('Year'), $params->getParam('Month'), $sciper, $plateforme, $type);
                            }
                        }
                        else {
                            $msg = runPrefa($tmpDir, $pathPlate, $params->getParam('Year'), $params->getParam('Month'), $sciper, $plateforme, $type);
                        }
                    }
                }
                Data::delDir($tmpDir);
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

function runPrefa($tmpDir, $path, $year, $month, $sciper, $plateforme, $type) {
    $unique = time();
    $cmd = '/usr/bin/python3.10 ../PyFactEl-V11/main.py -e '.$tmpDir.' -g -d ../ -u'.$unique.' -s '.$sciper.' -l '.$_SESSION['user'];
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
    Data::removeRun($path."/".$year."/".$mstr, $unique);
    if(file_exists($path)) {
        if(file_exists($path."/".$year)) {
            rmdir($path."/".$year);
            rmdir($path);
        }
    }
}




?>
