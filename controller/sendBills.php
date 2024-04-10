<?php

require_once("../src/Sap.php");
require_once("../src/Info.php");
require_once("../src/Facture.php");
require_once("../session.php");
require_once("../src/Lock.php");
require_once("../src/Logfile.php");

if(isset($_POST["bills"]) && isset($_POST['dir']) && isset($_POST['dirPrevMonth']) && isset($_POST['type'])) {
    $bills = $_POST["bills"];
    $dir = "../".$_POST["dir"];
    $dirPrevMonth = "../".$_POST["dirPrevMonth"];
    $html = "";
    $logfile = new Logfile();
    $sap = new Sap();
    $sap->load($dir);
    $oldStatus = $sap->status();
    $oldState = $sap->state();
    foreach($bills as $bill) {
        $facture = new Facture($dir."/Factures_JSON/facture_".$bill.".json");
        $res = json_decode(send($facture->getFacture()));
        if($res) {
            if(property_exists($res, "E_RESULT") && property_exists($res->E_RESULT, "item") && property_exists($res->E_RESULT->item, "IS_ERROR")) {
                $info = new Info();
                $infos = $info->load($dir);
                if(!empty($infos)) {
                    if(empty($infos["Sent"][2])) {
                        $infos["Sent"][2] = date('Y-m-d H:i:s');
                        $infos["Sent"][3] = $_SESSION['user'];
                        $info->save($dir, $infos);
                    }
                    if (!file_exists($dirPrevMonth."/lockm.csv")) {
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
                }
                else {
                    $res .= " info vide ? ";
                }
                $html .= json_encode($res);
            }
        }
    }
    if($sap->status() == 4) {
        $lock = new Lock();
        $lock->save($dir, 'run', $lock::STATES['finalized']);
        $sep = strrpos($dir, "/");
        $lock->save(substr($dir, 0, $sep), 'version', substr($dir, $sep+1));
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

function send(string $data): string
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($curl, CURLOPT_USERPWD, SAP_USER.":".SAP_PWD);  

    curl_setopt($curl, CURLOPT_URL, SAP_URL);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($curl);

    curl_close($curl);

    return $result;
}