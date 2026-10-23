<?php

require_once("../assets/Sap.php");
require_once("../assets/Unused.php");
require_once("../assets/Info.php");
require_once("../assets/Facture.php");
require_once("../assets/Label.php");
require_once("../assets/Lock.php");
require_once("../assets/ParamZip.php");
require_once("../assets/Logfile.php");
require_once("../assets/Message.php");
require_once("../assets/NewRates.php");
require_once("../includes/Tarifs.php");
require_once("../includes/State.php");
require_once("../session.inc");

/**
 * Called to send bills to SAP, and manage answers
 */
if(isset($_POST["bills"]) && isset($_POST['type']) && isset($_POST["plate"]) && isset($_POST["year"]) && isset($_POST["month"]) && isset($_POST["version"]) && isset($_POST["run"]) && isset($_POST["mode"])) {
    checkPlateforme("facturation", $_POST["plate"]);
    $plateforme = $_POST["plate"];
    $year = $_POST["year"];
    $month = $_POST["month"];
    $run = $_POST["run"];
    $type = $_POST['type'];
    $version = $_POST["version"];
    $mode = $_POST["mode"];
    $lockProcess = Lock::load("../", "process");
    if(!is_null($lockProcess)) {
        $_SESSION['alert-danger'] = 'Un processus est en cours. Veuillez patientez et rafraîchir la page...';
        header('Location: ../run.php?plateforme='.$plateforme.'&year='.$year.'&month='.$month.'&version='.$version.'&run='.$run);
        exit;
    }
    $bills = $_POST["bills"];

    if(!in_array($mode, ["REAL", "PRES"])) {
        $_SESSION['alert-danger'] = "C'est quoi ce mode...";
        header('Location: ../run.php?plateforme='.$plateforme.'&year='.$year.'&month='.$month.'&version='.$version.'&run='.$run);
        exit;
    }


    $dir = DATA.$plateforme."/".$year."/".$month."/".$version."/".$run;
    $dirTarifs = DATA.$plateforme."/".$year."/".$month;
    $dirPrevMonth = DATA.$plateforme."/".State::getPreviousYear($year, $month)."/".State::getPreviousMonth($year, $month);

    $warn = "";
    $error = "";
    $messages = new Message();
    $sap = new Sap($dir);
    $oldStatus = $sap->status();
    $oldSapState = $sap->state();
    $oks = 0;
    $kos = 0;
    $histo = "";


    Lock::save("../", 'process', "send ".$plateforme." ".$run);
    try {
        $do = true;
        $num = 0;
        while($do) {
            $do = false;
            $redo = [];
            $sap_cont = $sap->getBills();
            $archive = [];
            foreach($bills as $bill) {
                $archive[$bill] = [$sap_cont[$bill][0], $sap_cont[$bill][1], $sap_cont[$bill][2]];
                $result = json_decode(send(Facture::load($dir."/Factures_JSON/facture_".$bill.".json"), $dir, $mode), true);
                if(array_key_exists("status", $result)) {
                    $infos = Info::load($dir);
                    if(!empty($infos)) {
                        if($result["status"] == "SUCCESS") {
                            if(empty($infos["Sent"][2])) {
                                $infos["Sent"][2] = date('Y-m-d H:i:s');
                                $infos["Sent"][3] = USER;
                                Info::save($dir, $infos);
                                Tarifs::finalize($dirTarifs);
                            }
                            if(file_exists($dirPrevMonth) && !Lock::exists($dirPrevMonth, 'month')) {
                                foreach(globReverse($dirPrevMonth) as $dirPrevVersion) {
                                    if(Lock::exists($dirPrevVersion, 'version')) {
                                        $sep = strrpos($dirPrevVersion, "/");
                                        Lock::save($dirPrevMonth, 'month', substr($dirPrevVersion, $sep+1));
                                        break;
                                    }
                                }
                            }
                            if(array_key_exists("salesOrder", $result)) {
                                if($mode == "REAL") {
                                    $sent = "Envoyé en facturation";
                                }
                                elseif($mode == "PRES") {
                                    $sent = "Envoyé en pré-saisie";
                                }
                                $sap_cont[$bill][3] = "SENT";
                                $sap_cont[$bill][4] = $result["salesOrder"];
                                $sap_cont[$bill][5] = $sent;
                                $archive[$bill][3] = "SENT";
                                $archive[$bill][4] = $result["salesOrder"];
                                $archive[$bill][5] = $sent;
                                $oks++;
                            }
                            else {
                                $warn .= $bill.": no salesOrder ? <br />";
                                $kos++;
                            }
                        }
                        else {
                            if(array_key_exists("salesOrderErrors", $result)) {
                                if($type == "send-bills") {
                                    $sap_cont[$bill][3] = "ERROR";
                                    $sap_cont[$bill][4] = "-";
                                    $sap_cont[$bill][5] = $result["salesOrderErrors"]["message"];
                                }
                                $archive[$bill][3] = "ERROR";
                                $archive[$bill][4] = "-";
                                $archive[$bill][5] = $result["salesOrderErrors"]["message"];
                            }
                            else {
                                $warn .= $bill.": no salesOrderErrors ? <br />";
                            }
                            $kos++;
                        }
                    }
                    else {
                        $warn .= $bill.": info vide ? <br />";
                        $kos++;
                    }
                }
                else {
                    $warn .= "result empty, retry <br />";
                    $redo[] = $bill;
                }
            }
            if(count($redo)>0) {
                if($num < 3) {
                    $bills = $redo;
                    $do = true;
                    $num++;
                }
                else {
                    foreach($redo as $rd) {
                        if($type == "send-bills") {
                            $sap_cont[$rd][4] = "-";
                            $sap_cont[$rd][5] = "Problème de connexion au serveur SAP";
                            $archive[$rd][3] = "READY";
                        }
                        else {
                            $archive[$rd][3] = "ERROR";
                        }
                        $archive[$rd][4] = "-";
                        $archive[$rd][5] = "Problème de connexion au serveur SAP";

                    }
                    $error .= count($redo)." factures potentiellement non envoyées. Problème de connexion au serveur SAP. <br />";
                    $histo .= count($redo)." factures potentiellement non envoyées. Problème de connexion au serveur SAP.".PHP_EOL;
                }
            }
            $sap->save($dir, $sap_cont);
            $sap->generateArchive($dir, USER, $archive);
        }
    }
    catch(Exception $e) {
        $error .= $e->getMessage();
    }
    finally {
        unlink("../".Lock::FILES['process']);
    }

    if($sap->status() == 4) {

        Lock::save($dir, 'run', Lock::STATES['finalized']);
        $sep = strrpos($dir, "/");
        Lock::save(substr($dir, 0, $sep), 'version', substr($dir, $sep+1));

        $infos["Closed"][2] = date('Y-m-d H:i:s');
        $infos["Closed"][3] = USER;
        Info::save($dir, $infos);
    }

    $sap = new Sap($dir);
    $status = $sap->status();
    $sapState = $sap->state();
    if($type == "send-bills") {
        $title = "Envoi dans SAP";
    }
    else {
        $title = "Renvoi dans SAP";
    }
    $txt = date('Y-m-d H:i:s')." | ".USER." | ".$year.", ".$month.", ".$version.", ".$run." | ".$run." | ".$title." | ".$oldStatus." | ".$status.PHP_EOL;
    $txt .= $oldSapState." | ".count($bills)." | ".$sapState;
    if($histo != "") {
        $txt .= PHP_EOL.$histo;
    }
    Logfile::write(DATA.$plateforme."/", $txt);
    if(!empty($warn)) {
        $_SESSION['alert-warning'] = $warn;
    }
    if(!empty($error)) {
        $_SESSION['alert-danger'] = $error;
    }
    if($oks > 0) {
        if($oks > 1) {
            $_SESSION['alert-success'] = $messages->getMessage('msg6')."<br/>".$oks." factures envoyées avec succès";
        }
        else {
            $_SESSION['alert-success'] = $messages->getMessage('msg6')."<br/> 1 facture envoyée avec succès";
        }
    }
    if($kos > 0) {
        if($kos > 1) {
            $_SESSION['alert-danger'] = $kos." factures n'ont pu être envoyées";
        }
        else {
            $_SESSION['alert-danger'] = "1 facture n'a pu être envoyée";
        }
    }
}
else {
    $_SESSION['alert-danger'] = "post_data_missing";
    header('Location: ../index.php');
}

/**
 * Sends a bill to SAP
 *
 * @param string $data bill data
 * @param string $dir directory where to find attachments
 * @param string $mode REAL/PRES, if SAP generates bills or not
 * @return string SAP answer
 */
function send(string $data, string $dir, string $mode): string
{
    $decoded = json_decode($data, true);
    if($mode == "REAL") {
        $decoded["SDDocumentReason"] = "900";
        $decoded["HeaderBillingBlockReason"] = "";
    }
    else {
        $decoded["SDDocumentReason"] = "Z1";
        $decoded["HeaderBillingBlockReason"] = "Z1";
    }

    /*
    if(!DEV_MODE) {
        foreach($decoded["attachment"] as $i=>$attachment) {
            $filename = $decoded["attachment"][$i]["filename"];
            if($filename == "grille.pdf") {
                $data = file_get_contents($dir."/OUT/".$filename);
            }
            else {
                $data = file_get_contents($dir."/Annexes_PDF/".$filename);
            }
            $data64 = base64_encode($data);
            $decoded["attachment"][$i]["filecontent"] = $data64;
        }
    }
    */
    $encoded = json_encode($decoded);

    /*
    $curl = curl_init();

    $data = file_get_contents($dir."/OUT/grille.pdf");
    $data64 = base64_encode($data);
    $_SESSION['alert-info'] = $data64;

    $headers = array("X-API-KEY: " . NEXUS_KEY, "--form 'salesOrder=". $encoded . "'");
    curl_setopt($curl, CURLOPT_HTTPHEADER,$headers);
    curl_setopt($curl, CURLOPT_POST, 1);
    //curl_setopt($curl, CURLOPT_GET, 1);
    //curl_setopt($curl, CURLOPT_POSTFIELDS, $encoded);

    curl_setopt($curl, CURLOPT_URL, NEXUS_CUSTOMERS);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($curl);

    curl_close($curl);
*/

//$encoded = '{"SalesOrderType":"ZPLT","SalesOrganization":1028,"DistributionChannel":"20","OrganizationDivision":10,"TransactionCurrency":"CHF","SoldToParty":"1000000420","PurchaseOrderByCustomer":"EXT_2026_09_0_1198","SDDocumentReason":"900","HeaderBillingBlockReason":"","to_Partner":[{"PartnerFunction":"ZM","SCIPER":"999"},{"PartnerFunction":"RE","Customer":"1000000420","to_Address":[{"EmailAddress":"accounts.payable@cern.ch","OrganizationName3":""}]}],"to_Text":[{"LongTextID":"TX06","LongText":"Your ref. : no de commande PC1221165"}],"to_Item":[{"Material":"9010144","SalesOrderItemText":"Services","RequestedQuantity":"1.0","WBSElement":"90-0265","ZZ1_CODEOP_SDI":"UA202609M","to_PricingElement":[{"ConditionType":"PPR0","ConditionRateValue":"904.85"}],"to_Text":[{"LongTextID":"TX02","LongText":""}]},{"Material":"9010055","SalesOrderItemText":"Consumables","RequestedQuantity":"1.0","WBSElement":"90-0265","ZZ1_CODEOP_SDI":"UA202609C","to_PricingElement":[{"ConditionType":"PPR0","ConditionRateValue":"49.25"}],"to_Text":[{"LongTextID":"TX02","LongText":""}]},{"Material":"9010148","SalesOrderItemText":"No-show penalties","RequestedQuantity":"1.0","WBSElement":"90-0265","ZZ1_CODEOP_SDI":"UA202609R","to_PricingElement":[{"ConditionType":"PPR0","ConditionRateValue":"96.5"}],"to_Text":[{"LongTextID":"TX02","LongText":""}]},{"Material":"9010340","SalesOrderItemText":"Overheads 20%","RequestedQuantity":"1.0","WBSElement":"90-0265","ZZ1_CODEOP_SDI":"UA202609OH","to_PricingElement":[{"ConditionType":"PPR0","ConditionRateValue":"210.1"}],"to_Text":[{"LongTextID":"TX02","LongText":""}]}]}';

    $cmd = "curl -H 'X-API-KEY: " . NEXUS_KEY . "' " . NEXUS_URL . " --form 'salesOrder=" . $encoded . "'";
    $result = shell_exec($cmd);
    $_SESSION['alert-info'] = $result;

    return $result;
}
