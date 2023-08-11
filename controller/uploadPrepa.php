<?php
require_once("../commons/Zip.php");
require_once("../commons/Data.php");
require_once("../src/Result.php");
require_once("../src/Paramedit.php");
require_once("../src/Message.php");

if(($_FILES['zip_file']) && isset($_POST['plate']) && isset($_POST['type'])) {
    $plateforme = $_POST['plate'];
    $messages = new Message();
    $type = $_POST['type'];
    $filename = $_FILES["zip_file"]["name"];
    $source = $_FILES["zip_file"]["tmp_name"];
    if(Zip::isAccepted($_FILES["zip_file"]["type"])) {
        $tmp_file = "../tmp/".$filename;
        if(copy($source, $tmp_file)) {
            $tmp_dir = '../tmp/test/';
            if (file_exists($tmp_dir) || mkdir($tmp_dir, 0777, true)) {
                $msg = Zip::unzip($tmp_file, $tmp_dir);
                unlink($tmp_file);
                $results = new Result($tmp_dir."result.csv");
                $params = new Paramedit();
                $params->load($tmp_dir."paramedit.csv");
                if($plateforme != $params->getParam('Platform')) {
                    $msg = $messages->getMessage('msg3')."<br/>".$messages->getMessage('msg3.1');
                }
                elseif($type != $params->getParam('Type')) {
                    $msg = $messages->getMessage('msg3')."<br/>".$messages->getMessage('msg3.2');
                }
                if($plateforme != $results->getResult('Platform')) {
                    $msg = $messages->getMessage('msg3')."<br/>".$messages->getMessage('msg3.1');
                }
                elseif($type != "SIMU" && $results->getResult('Type') != "SAP") {
                    $msg = $messages->getMessage('msg3')."<br/>".$messages->getMessage('msg3.3');
                }
                else {
                    $path = "../".$plateforme;
                    if($type == "SAP") {
                        if(file_exists($path)) {
                            $data = Data::availableForFacturation($path, $messages);
                            if($data['sap']['error']) {
                                $msg = $data['sap']['error'];
                            }
                            elseif(!$data['sap']['result']) {
                                $msg = "no data"; 
                            }
                            else {
                                $ok = FALSE;
                                foreach($data['sap']['result'] as $option) {
                                    if($option['year'] == $results->getResult('Year') && (int)($option['month']) == (int)($results->getResult('Month')) && $option['version'] == $results->getResult('Version') && $option['run'] == $results->getResult('Folder')) {
                                        $ok = TRUE;
                                        break;
                                    }
                                }
                                if(!$ok) {
                                    $msg = $messages->getMessage('msg3')."<br/>".$messages->getMessage('msg3.4');
                                }
                                else {
                                    $msg = runPrefa($tmp_dir, $path, $params->getParam('Year'), $params->getParam('Month'));
                                    //$msg = "on peut préfacturer";
                                }
                            }
                        }
                        else {
                            $msg = runPrefa($tmp_dir, $path, $params->getParam('Year'), $params->getParam('Month'));
                            //$msg = "on peut préfacturer sans historique";
                        }
                    }
                    elseif($type == "PROFORMA") {
                        if(file_exists($path)) {
                            $data = Data::availableForFacturation($path, $messages);
                            if($data['proforma']['error']) {
                                $msg = $data['proforma']['error'];
                            }
                            elseif(!$data['proforma']['result']) {
                                $msg = "no data"; 
                            }
                            else {
                                $ok = FALSE;
                                foreach($data['proforma']['result'] as $option) {
                                    if($option['year'] == $results->getResult('Year') && (int)($option['month']) == (int)($results->getResult('Month')) && $option['version'] == $results->getResult('Version') && $option['run'] == $results->getResult('Folder')) {
                                        $ok = TRUE;
                                        break;
                                    }
                                }
                                if(!$ok) {
                                    $msg = $messages->getMessage('msg3')."<br/>".$messages->getMessage('msg3.4');
                                }
                                else {
                                    $msg = "on peut générer";
                                }
                            }
                        }
                        else {
                            $msg = "on peut générer sans historique";
                        }
                    }
                    else {
                        $msg = "on peut simuler";
                    }

                }
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


function runPrefa($tmp_dir, $path, $year, $month) {
    $unique = time();
    $cmd = '/usr/bin/python3.10 ../PyFactEl-V11/main.py -e '.$tmp_dir.' -s -d ../ -u'.$unique;
    $result = shell_exec($cmd);
    if(substr($result, 0, 2) == "OK") {
        $msg = $unique." tout OK";
    }
    else {
        $msg = urlencode($result);
        delPrefa($path, $year, $month, $unique);
    }
    return $msg;
}

function delPrefa($path, $year, $month, $unique) {
    $mstr = (int)$month > 9 ? $month : '0'.$month;
    Data::removeRun($path."/".$year."/".$mstr, $unique);
    rmdir($path."/".$year);
    rmdir($path);
}




?>
