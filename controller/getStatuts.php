<?php

require_once("../assets/Sap.php");
require_once("../assets/MessageSap.php");
require_once("../assets/ParamText.php");
require_once("../includes/State.php");
require_once("../session.inc");

/**
 * Called to
 */
$txt = "";
if(isset($_POST['plate']) && isset($_POST['mode']) && isset($_POST['from']) && isset($_POST['to'])) {
    checkPlateforme("facturation", $_POST["plate"]);
    $dir = DATA.$_POST['plate'];
    $mode = $_POST['mode'];
    $paramtext = new ParamText();
    $messageSap = new MessageSap();
    $bills = [];
    $orders = [];
    $date = $_POST['from'];
    if(file_exists($dir)) {

        while($date >= $_POST['to']) {

            $month = substr($date, 4, 2);
            $year = substr($date, 0, 4);

            $dirMonth = $dir."/".$year."/".$month;

            foreach(globReverse($dirMonth) as $dirVersion) {
                $version = basename($dirVersion);
                foreach(globReverse($dirVersion) as $dirRun) {
                    $run = basename($dirRun);
                    foreach(array_reverse(glob($dirRun."/*")) as $file) {
                        if(str_contains($file, "sap_")) {
                            $file = basename($file);
                            $fileTab = explode("_", $file);
                            $login = $fileTab[1];
                            $sap = new Sap($dirRun, basename($file));
                            foreach($sap->getBills() as $fact=>$tab) {
                                if((($tab[4] != "-") && ($mode != "en-erreur")) || (($tab[4] == "-") && in_array($mode, ["toutes", "en-erreur"]))) {
                                    $bills[$fact] = ["tab" => $tab, "login" => $login, "title" => $month . " " . $year . " - " . $version];
                                    if($tab[4] != "-") {
                                        $orders[intval($tab[4])] = ["fact" => $fact, "order" => $tab[4]];
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $date = State::decreaseDate($date);
        }
    }
    if(count($orders) > 0) {
        $cmd = "curl -H 'X-API-KEY: " . NEXUS_KEY . "' " . NEXUS_ORDERS . "?%24filter=";
        $first = true;
        $filter = "";
        foreach($orders as $orderInt=>$data) {
            if($first) {
                $first = false;
            }
            else {
                $filter .= "%20or%20";
            }
            $filter .= "SalesOrder%20eq%20%27" . $data["order"] . "%27";
        }
        if(in_array($mode,["en-cours", "refusees", "non-payees"])) {
            $fin = "%20and%20Fistatu%20neq%20%271%27";
            $app = "SalesDocApprovalStatus%20neq%20%27C%27";
            if($mode == "refusees") {
                $fin = "";
                $app = "SalesDocApprovalStatus%20eq%20%27C%27";
            }
            if($mode == "non-payees") {
                $app = "SalesDocApprovalStatus%20eq%20%27B%27";
            }
            $filter = "%28" . $filter . "%29%20and%20" . $app . $fin;
        }
        var_dump($cmd.$filter);
        $res = shell_exec($cmd.$filter);

        if(!is_null($res)) {
            $result = json_decode($res, true);
            if(is_array($result) && array_key_exists("value", $result)) {
                foreach($result["value"] as $value) {
                    $o = intval($value["SalesOrder"]);
                    $msg = $messageSap->getMessage($value["SalesDocApprovalStatus"], $value["OverallOrdReltdBillgStatus"], $value["Fistatu"]);
                    if(empty($msg)) {
                        $msg = 'Statut problématique : "' . $value["SalesDocApprovalStatus"] . ' : ' . $value["SalesDocApprovalStatusTxt"] . '" :  + "' . $value["OverallOrdReltdBillgStatus"] . ' : '  . $value["OverallOrdReltdBillgStatusTxt"] . '" + "' . $value["Fistatu"] . ' : '  . $value["FistatuTxt"] . '"';
                    }
                    $fact = $orders[$o]["fact"];
                    $bills[$fact]["rep"] = ["msg" => $msg, "date" => $value["CreationDate"], "current" => $value["TransactionCurrency"], "total" => $value["TotalNetAmount"]];
                }
            }
        }
    }
    if(count($bills) > 0) {
        $txt = "<div class='over'><table class='table statuts'><tr>";
        $headers = [];
        foreach(['track-invoice-month2', 'track-sent-login', 'track-client-name', 'track-invoice-id', 'track-total-fact', 'track-err-msg', 'track-doc-nr', 'track-api-date', 'track-api-TTC2', 'track-api-msg'] as $title) {
            $txt .= '<th>'.$paramtext->getParam($title).'</th>';
            $headers[] = $paramtext->getParam($title);
        }
        $totalCsv = implode(";", $headers);
        $txt .= "</tr>";
        foreach($bills as $fact=>$bill) {
            $line = [$bill["title"], $bill["login"], $bill["tab"][0], $bill["tab"][1], $bill["tab"][2], $bill["tab"][5], $bill["tab"][4]];

            if($bill["tab"][4] != "-") {
                if(!empty($bill["rep"])) {
                    array_push($line, $bill["rep"]["date"], $bill["rep"]["current"] . " " . $bill["rep"]["total"], $bill["rep"]["msg"]);
                }
                else {
                    array_push($line, "liste", "non", "accessible");
                }
            }
            else {
                array_push($line, "-", "-", "-");
            }
            $txt .= '<tr>';
            foreach($line as $cell) {
                $txt .= '<td>' . $cell . '</td>';
            }
            $txt .= '</tr>';
            $totalCsv .= "\n".implode(";", $line);
        }

        $txt .= "</table></div>";
        $txt .= '<div class="center-tile"><a href="data:text/plain;base64,'.base64_encode($totalCsv).'" download="statuts.csv"><div id="export-statuts" class="tile tight-tile">Exporter</div></a></div>';
    }
    else {
        $txt = "Aucun résultat disponible";
    }
}
echo $txt;


function getStatut($order)
{
    $cmd = "curl -H 'X-API-KEY: " . NEXUS_KEY . "' " . NEXUS_ORDERS . "?%24filter=SalesOrder%20eq%20%27" . $order . "%27";
    $res = shell_exec($cmd);

    if(!is_null($res)) {
        $result = json_decode($res, true);
        if(array_key_exists("value", $result)) {
            return $result["value"];
        }
    }
    return "";
}
