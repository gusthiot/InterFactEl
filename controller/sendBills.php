<?php

require_once("../src/Sap.php");
require_once("../src/Info.php");
require_once("../src/Facture.php");

if(isset($_POST["bills"]) && isset($_POST['dir'])) {
    $bills = $_POST["bills"];
    $dir = $_POST["dir"];
    $html = "";
    foreach($bills as $bill) {
        $facture = new Facture("../".$dir."/Factures_JSON/facture_".$bill.".json");
        $res = json_decode(send($facture->getFacture()));
        if($res) {
            if(property_exists($res, "E_RESULT") && property_exists($res->E_RESULT, "item") && property_exists($res->E_RESULT->item, "IS_ERROR")) {
                $info = new Info();       
                $content = $info->load("../".$_POST["dir"]);
                if(empty($content["Sent"][2])) {
                    $content["Sent"][2] = date('Y-m-d H:i:s');
                    $info->save("../".$_POST["dir"], $content);
                }
                $sap = new Sap();
                $content = $sap->load("../".$_POST["dir"]);                        
                if(empty($res->E_RESULT->item->IS_ERROR)) {
                    if(property_exists($res->E_RESULT->item, "LOG") && property_exists($res->E_RESULT->item->LOG, "item") && property_exists($res->E_RESULT->item->LOG->item, "MESSAGE")) {
                        $content[$bill][3] = "ERROR";
                        $content[$bill][4] = $res->E_RESULT->item->LOG->item->MESSAGE;
                    }
                }
                else {
                    if(property_exists($res->E_RESULT->item, "DOC_NUMBER")) {
                        $content[$bill][3] = "SENT";
                        $content[$bill][4] = $res->E_RESULT->item->DOC_NUMBER;
                    }
                }
                $sap->save("../".$_POST["dir"], $content);
                $html .= "saved";
            }
        }
    }
    echo $html;
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