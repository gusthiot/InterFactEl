<?php

require_once("../assets/Sap.php");
require_once("../assets/Info.php");
require_once("../assets/Facture.php");
require_once("../assets/Lock.php");
require_once("../assets/Logfile.php");
require_once("../assets/Message.php");
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

    if(TEST_MODE && (!IS_SUPER || DEV_MODE) && ($mode == "REAL" || $mode == "PRES")) {
        $_SESSION['alert-danger'] = 'Seule la simulation est disponible';
        header('Location: ../run.php?plateforme='.$plateforme.'&year='.$year.'&month='.$month.'&version='.$version.'&run='.$run);
        exit;

    }
    if(!TEST_MODE && $mode == "SIMU") {
        $_SESSION['alert-danger'] = 'Pas de simulation en production';
        header('Location: ../run.php?plateforme='.$plateforme.'&year='.$year.'&month='.$month.'&version='.$version.'&run='.$run);
        exit;
    }
    if(!in_array($mode, ["REAL", "PRES", "SIMU"])) {
        $_SESSION['alert-danger'] = "C'est quoi ce mode...";
        header('Location: ../run.php?plateforme='.$plateforme.'&year='.$year.'&month='.$month.'&version='.$version.'&run='.$run);
        exit;
    }


    $dir = DATA.$plateforme."/".$year."/".$month."/".$version."/".$run;
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
                $resArray = send(Facture::load($dir."/Factures_JSON/facture_".$bill.".json"), $dir, $mode);
                if($resArray[0]) {
                    $res = json_decode($resArray[0]);
                    if($res && property_exists($res, "E_RESULT") && property_exists($res->E_RESULT, "item") && property_exists($res->E_RESULT->item, "IS_ERROR")) {
                        $infos = Info::load($dir);
                        if(!empty($infos)) {
                            if(!empty($res->E_RESULT->item->IS_ERROR)) {
                                if(property_exists($res->E_RESULT->item, "LOG") && property_exists($res->E_RESULT->item->LOG, "item") && property_exists($res->E_RESULT->item->LOG->item, "MESSAGE")) {
                                    if($type == "send-bills") { 
                                        $sap_cont[$bill][3] = "ERROR";
                                        $sap_cont[$bill][4] = "-";
                                        $sap_cont[$bill][5] = $res->E_RESULT->item->LOG->item->MESSAGE;
                                    }
                                    $archive[$bill][3] = "ERROR";
                                    $archive[$bill][4] = "-";
                                    $archive[$bill][5] = $res->E_RESULT->item->LOG->item->MESSAGE;
                                }
                                else {
                                    $warn .= $bill.": no error message ? <br />";
                                }
                                $kos++;
                            }
                            else {
                                if(empty($infos["Sent"][2])) {
                                    $infos["Sent"][2] = date('Y-m-d H:i:s');
                                    $infos["Sent"][3] = USER;

                                    Info::save($dir, $infos);
                                }
                                if (file_exists($dirPrevMonth) && !file_exists($dirPrevMonth."/".Lock::FILES['month'])) {
                                    foreach(globReverse($dirPrevMonth) as $dirPrevVersion) {
                                        if (file_exists($dirPrevVersion."/".Lock::FILES['version'])) {
                                            $sep = strrpos($dirPrevVersion, "/");
                                            Lock::save($dirPrevMonth, 'month', substr($dirPrevVersion, $sep+1));
                                            break;
                                        }
                                    }
                                }
                                if(property_exists($res->E_RESULT->item, "DOC_NUMBER")) {
                                    if($mode == "REAL") {
                                        $sent = "Envoyé en facturation";
                                    }
                                    elseif($mode == "PRES") {
                                        $sent = "Envoyé en pré-saisie";
                                    }
                                    else {
                                        $sent = "Envoyé en simulation";
                                    }
                                    $sap_cont[$bill][3] = "SENT";
                                    $sap_cont[$bill][4] = $res->E_RESULT->item->DOC_NUMBER;
                                    $sap_cont[$bill][5] = $sent;
                                    $archive[$bill][3] = "SENT";
                                    $archive[$bill][4] = $res->E_RESULT->item->DOC_NUMBER;
                                    $archive[$bill][5] = $sent;
                                    $oks++;
                                }
                                else {
                                    $warn .= $bill.": no doc_number ? <br />";
                                    $kos++;
                                }
                            }
                        }
                        else {
                            $warn .= $bill.": info vide ? <br />";
                            $kos++;
                        }
                    }
                    else {
                        $redo[] = $bill;
                    }
                }
                else {
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
    unlink("../".Lock::FILES['process']);

    if($sap->status() == 4) {
        $state = new State(DATA.$plateforme);
        if(empty($state->getLast())) {
            $dirTarifs = DATA.$plateforme."/".$year."/".$month."/";
            $msg = Tarifs::saveFirst($dir, $dirTarifs);
            if(!empty($msg)) {
                $res .= $msg;
            }  
        }

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
 * @param string $mode REAL/PRES/SIMU, if SAP generates bills or not
 * @return array SAP answer, or error
 */
function send(string $data, string $dir, string $mode): array
{
    $decoded = json_decode($data, true);
    $decoded["execmode"] = $mode;
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

    $encoded = json_encode($decoded);
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $encoded);

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
