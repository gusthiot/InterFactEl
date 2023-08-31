<?php

require_once("../src/Sap.php");
require_once("../src/Info.php");
require_once("../src/Facture.php");
require_once("../session.php");
require_once("../src/Lock.php");
require_once("../src/Logfile.php");

if(isset($_POST["bills"]) && isset($_POST['dir']) && isset($_POST['type'])) {
    $bills = $_POST["bills"];
    $dir = "../".$_POST["dir"];
    $html = "";
    $logfile = new Logfile();
    $sap = new Sap();
    $sap->load($dir);
    $oldStatus = $sap->status();
    foreach($bills as $bill) {
        $facture = new Facture($dir."/Factures_JSON/facture_".$bill.".json");
        $res = json_decode(send($facture->getFacture()));
        if($res) {
            if(property_exists($res, "E_RESULT") && property_exists($res->E_RESULT, "item") && property_exists($res->E_RESULT->item, "IS_ERROR")) {
                $info = new Info();       
                $content = $info->load($dir);
                if(empty($content["Sent"][2])) {
                    $content["Sent"][2] = date('Y-m-d H:i:s');
                    $info->save($dir, $content);
                }
                $content = $sap->load($dir);                        
                if(!empty($res->E_RESULT->item->IS_ERROR)) {
                    if(property_exists($res->E_RESULT->item, "LOG") && property_exists($res->E_RESULT->item->LOG, "item") && property_exists($res->E_RESULT->item->LOG->item, "MESSAGE")) {
                        $content[$bill][3] = "ERROR";
                        $content[$bill][4] = $res->E_RESULT->item->LOG->item->MESSAGE;
                        $txt = $bill." | ERROR | ".$res->E_RESULT->item->LOG->item->MESSAGE;
                    }
                }
                else {
                    if(property_exists($res->E_RESULT->item, "DOC_NUMBER")) {
                        $content[$bill][3] = "SENT";
                        $content[$bill][4] = $res->E_RESULT->item->DOC_NUMBER;
                        $txt = $bill." | SENT | ".$res->E_RESULT->item->DOC_NUMBER;
                    }
                }
                $sap->save($dir, $content);
                logSap($_POST["dir"], $bill, $content, $logfile);
                if($sap->status() == 4) {
                    $lock = new Lock();
                    $lock->save($dir, 'run', "finalized");
                    $sep = strrpos($dir, "/");
                    $lock->save(substr($dir, 0, $sep), 'version', substr($dir, $sep+1));

                }
                $html .= json_encode($res);
            }
        }
    }
    logAction($_POST["dir"], $sap, $_POST['type'], $oldStatus, $logfile);
    echo $html;//$messages->getMessage('msg7');
}


function logSap($dir, $bill, $content, $logfile) {
    $tab = explode("/", $dir);
    $txt = $bill." | ".$content[$bill][3]." | ".$content[$bill][4];
    $logfile->write("../".$tab[0], $txt);
}

function logAction($dir, $sap, $type, $oldStatus, $logfile) {
    $sap->load("../".$dir);
    $status = $sap->status();
    $tab = explode("/", $dir);
    $txt = date('Y-m-d H:i:s')." | ".$_SESSION['user']." | ".$tab[1].", ".$tab[2].", ".$tab[3].", ".$tab[4]." | ".$tab[4]." | ".$type." | ".$oldStatus." | ".$status;
    $logfile->write("../".$tab[0], $txt);
}

function send(string $data): string
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($curl, CURLOPT_USERPWD, SAP_SIMU_USER.":".SAP_SIMU_PWD);  

    curl_setopt($curl, CURLOPT_URL, "https://testsapservices.epfl.ch/poq/RESTAdapter/api/sd/facture");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($curl);

    curl_close($curl);

    return $result;
}