<?php

require_once("../assets/Sap.php");
require_once("../assets/Info.php");
require_once("../assets/Facture.php");
require_once("../session.inc.php");
require_once("../assets/Lock.php");
require_once("../assets/Logfile.php");
require_once("../includes/Tarifs.php");
require_once("../includes/State.php");
require_once("../assets/Message.php");

checkGest($dataGest);
if(isset($_POST["bills"]) && isset($_POST['type']) && isset($_POST["plate"]) && isset($_POST["year"]) && isset($_POST["month"]) && isset($_POST["version"]) && isset($_POST["run"])) {
    checkPlateforme($dataGest, $_POST["plate"]);
    $plateforme = $_POST["plate"];
    $year = $_POST["year"];
    $month = $_POST["month"];
    $run = $_POST["run"];
    $type = $_POST['type'];
    $version = $_POST["version"];
    $lockp = new Lock();
    $lockedTxt = $lockp->load("../", "process");
    if(!empty($lockedTxt)) {
        $_SESSION['alert-danger'] = 'Un processus est en cours. Veuillez patientez et rafraîchir la page...</div>';;
        header('Location: ../prefacturation.php?plateforme='.$plateforme.'&year='.$year.'&month='.$month.'&version='.$version.'&run='.$run);
        exit;
    }
    $bills = $_POST["bills"];

    $dir = DATA.$plateforme."/".$year."/".$month."/".$version."/".$run;
    $dirPrevMonth = DATA.$plateforme."/".State::getPreviousYear($year, $month)."/".State::getPreviousMonth($year, $month);

    $warn = "";
    $error = "";
    $logfile = new Logfile();
    $messages = new Message();
    $sap = new Sap();
    $state = new State();
    $sap->load($dir);
    $oldStatus = $sap->status();
    $oldState = $sap->state();
    $oks = 0;
    $kos = 0;

    $lockp = new Lock();
    $lockp->save("../", 'process', "send ".$plateforme." ".$run);
    try {
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
                            $infos["Sent"][3] = $user;
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
                                $sap_cont[$bill][4] = "-";
                                $sap_cont[$bill][5] = $res->E_RESULT->item->LOG->item->MESSAGE;
                                $txt = $bill." | ERROR | ".$res->E_RESULT->item->LOG->item->MESSAGE;
                                $kos++;
                            }
                        }
                        else {
                            if(property_exists($res->E_RESULT->item, "DOC_NUMBER")) {
                                $sap_cont[$bill][3] = "SENT";
                                $sap_cont[$bill][4] = $res->E_RESULT->item->DOC_NUMBER;
                                $sap_cont[$bill][5] = "";
                                $txt = $bill." | SENT | ".$res->E_RESULT->item->DOC_NUMBER;
                                $oks++;
                            }
                        }
                        $sap->save($dir, $sap_cont);
                    }
                    else {
                        $warn .= $bill.": info vide ? <br />";
                    }
                }
            }
            else {
                $kos++;
            }
        }
    }
    catch(Exception $e) {
        $error .= $e->getMesage(); 
    }
    unlink("../".Lock::FILES['process']);

    if($sap->status() == 4) {    
        $locklast = new Lock();
        $state->lastState(DATA.$plateforme, $locklast);
        if(empty($state->getLast())) {
            $dirTarifs = DATA.$plateforme."/".$year."/".$month."/";
            $msg = Tarifs::saveFirst($dir, $dirTarifs);
            if(!empty($msg)) {
                $res .= $msg;
            }  
        }

        $lock = new Lock();
        $lock->save($dir, 'run', $lock::STATES['finalized']);
        $sep = strrpos($dir, "/");
        $lock->save(substr($dir, 0, $sep), 'version', substr($dir, $sep+1));


        $infos["Closed"][2] = date('Y-m-d H:i:s');
        $infos["Closed"][3] = $user;
        $info->save($dir, $infos);
    }

    $sap->load($dir);
    $status = $sap->status();
    $state = $sap->state();
    $txt = date('Y-m-d H:i:s')." | ".$user." | ".$year.", ".$month.", ".$version.", ".$run." | ".$run." | ".$type." | ".$oldStatus." | ".$status.PHP_EOL;
    $txt .= $oldState." | ".count($bills)." | ".$state;
    $logfile->write(DATA.$plateforme."/", $txt);
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
