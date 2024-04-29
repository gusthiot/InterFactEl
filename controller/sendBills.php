<?php

require_once("../src/Sap.php");
require_once("../src/Info.php");
require_once("../src/Facture.php");
require_once("../session.php");
require_once("../src/Lock.php");
require_once("../src/Logfile.php");
require_once("../commons/Parametres.php");

if(isset($_POST["bills"]) && isset($_POST['dir']) && isset($_POST['dirPrevMonth']) && isset($_POST['type']) && isset($_POST["dirTarifs"])) {
    $bills = $_POST["bills"];
    $dir = "../".$_POST["dir"];
    $dirPrevMonth = "../".$_POST["dirPrevMonth"];
    $html = "";
    $logfile = new Logfile();
    $sap = new Sap();
    $sap->load($dir);
    $oldStatus = $sap->status();
    $oldState = $sap->state();
    $oks = "";
    foreach($bills as $bill) {
        $facture = new Facture($dir."/Factures_JSON/facture_".$bill.".json");
        $resArray = send($facture->getFacture());
        if($resArray[0] && !$resArray[1]) {
            $res = json_decode($resArray[0]);
            if(property_exists($res, "E_RESULT") && property_exists($res->E_RESULT, "item") && property_exists($res->E_RESULT->item, "IS_ERROR")) {
                $info = new Info();
                $infos = $info->load($dir);
                if(!empty($infos)) {
                    if(empty($infos["Sent"][2])) {
                        $infos["Sent"][2] = date('Y-m-d H:i:s');
                        $infos["Sent"][3] = $_SESSION['user'];
                        $info->save($dir, $infos);
                    }
                    if (file_exists($dirPrevMonth) && !file_exists($dirPrevMonth."/lockm.csv")) {
                        $lock = new Lock();
                        $lock->save($dirPrevMonth, 'month', "");
                    }
                    $sap_cont = $sap->load($dir);
                    if(!empty($res->E_RESULT->item->IS_ERROR)) {
                        if(property_exists($res->E_RESULT->item, "LOG") && property_exists($res->E_RESULT->item->LOG, "item") && property_exists($res->E_RESULT->item->LOG->item, "MESSAGE")) {
                            $sap_cont[$bill][3] = "ERROR";
                            $sap_cont[$bill][4] = $res->E_RESULT->item->LOG->item->MESSAGE;
                            $txt = $bill." | ERROR | ".$res->E_RESULT->item->LOG->item->MESSAGE;
                        }
                    }
                    else {
                        if(property_exists($res->E_RESULT->item, "DOC_NUMBER")) {
                            $sap_cont[$bill][3] = "SENT";
                            $sap_cont[$bill][4] = $res->E_RESULT->item->DOC_NUMBER;
                            $txt = $bill." | SENT | ".$res->E_RESULT->item->DOC_NUMBER;
                        }
                    }
                    $sap->save($dir, $sap_cont);
                    if(!empty($oks)) {
                        $oks .= ", ";
                    }
                    $oks .= $bill;
                }
                else {
                    $html .= $bill.": info vide ? <br />";
                }
            }
        }
        else {
                $html .= $bill.": ".json_encode($resArray[1])."<br />";
        }
    }
    $html .= $oks." : ok <br />";

    if($sap->status() == 4) {
        $lock = new Lock();
        $lock->save($dir, 'run', $lock::STATES['finalized']);
        $sep = strrpos($dir, "/");
        $lock->save(substr($dir, 0, $sep), 'version', substr($dir, $sep+1));
        if(!empty($_POST["dirTarifs"])) {
            $dirTarifs = "../".$_POST["dirTarifs"];
            if(!Parametres::saveFirst($dir, $dirTarifs)) {
                $res .= "erreur sauvegarde paramètres ";
            }   
        }
        $infos["Closed"][2] = date('Y-m-d H:i:s');
        $infos["Closed"][3] = $_SESSION['user'];
        $info->save($dir, $infos);

    }

    logAction($_POST["dir"], $sap, $_POST['type'], $oldStatus, $oldState, $logfile, count($bills));
    $_SESSION['message'] = $html;
}


function logAction($dir, $sap, $type, $oldStatus, $oldState, $logfile, $number) {
    $sap->load("../".$dir);
    $status = $sap->status();
    $state = $sap->state();
    $tab = explode("/", $dir);
    $txt = date('Y-m-d H:i:s')." | ".$_SESSION['user']." | ".$tab[1].", ".$tab[2].", ".$tab[3].", ".$tab[4]." | ".$tab[4]." | ".$type." | ".$oldStatus." | ".$status.PHP_EOL;
    $txt .= $oldState." | ".$number." | ".$state;
    $logfile->write("../".$tab[0], $txt);
}

function send(string $data): array
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($curl, CURLOPT_USERPWD, SAP_USER.":".SAP_PWD);

    curl_setopt($curl, CURLOPT_URL, SAP_URL);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    $result[] = curl_exec($curl);
    if($result[0]) {
        $result[] = null;
    }
    else {
        $result[] = curl_error($curl);
    }

    curl_close($curl);

    return $result;
}
