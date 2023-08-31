<?php
require_once("../commons/Zip.php");
require_once("../commons/Data.php");
require_once("../src/Result.php");
require_once("../src/Logfile.php");
require_once("../src/Paramedit.php");
require_once("../session.php");

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
                            if(file_exists($pathPlate)) {
                                $data = Data::availableForFacturation($pathPlate, $messages);
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
                                        $msg = runPrefa($tmpDir, $pathPlate, $params->getParam('Year'), $params->getParam('Month'), $sciper, $plateforme);
                                    }
                                }
                            }
                            else {
                                $msg = runPrefa($tmpDir, $pathPlate, $params->getParam('Year'), $params->getParam('Month'), $sciper, $plateforme);
                            }
                        }
                        else {
                            $msg = "on peut simuler";
                        }
                    }
                }
                delTmpDir($tmpDir);
            }
            else {
                $errors= error_get_last();
                $msg = $errors['message'];

            }
            header('Location: ../plateforme.php?plateforme='.$plateforme.'&message='.$msg);
    
        }
        else {
            header('Location: ../plateforme.php?plateforme='.$plateforme.'&message=copy');
        }


    }
    else {
        header('Location: ../plateforme.php?plateforme='.$plateforme.'&message=zip');
    }
        
}
else {
    header('Location: ../index.php?message=post_data_missing');
}

function delTmpDir($tmpDir) {
    foreach(Data::scanDescSan($tmpDir) as $tmpFile) {
        unlink($tmpDir."/".$tmpFile);
    }
    rmdir($tmpDir);
}

function runPrefa($tmpDir, $path, $year, $month, $sciper, $plateforme) {
    $unique = time();
    $cmd = '/usr/bin/python3.10 ../PyFactEl-V11/main.py -e '.$tmpDir.' -g -d ../ -u'.$unique.' -s '.$sciper;
    $result = shell_exec($cmd);
    $mstr = (int)$month > 9 ? $month : '0'.$month;
    if(substr($result, 0, 2) === "OK") {
        $msg = $unique." tout OK";
        $logfile = new Logfile();
        $txt = date('Y-m-d H:i:s')." | ".$_SESSION['user']." | ".$year.", ".$mstr.", version, ".$unique." | ".$unique." | Création préfacturation | - | statut";
        $logfile->write("../".$plateforme, $txt);
    }
    else {
        $msg = urlencode($result);
        delPrefa($path, $year, $mstr, $unique);
    }
    return $msg;
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
