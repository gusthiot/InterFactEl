<?php

require_once("../assets/Sap.php");
require_once("../assets/MessageSap.php");
require_once("../assets/ParamText.php");
require_once("../session.inc");

/**
 * Called to
 */
$txt = "";
if(isset($_POST['plate'])) {
    checkPlateforme("facturation", $_POST["plate"]);
    $dir = DATA.$_POST['plate'];
    $paramtext = new ParamText();
    $messageSap = new MessageSap();
    $txt = "<div class='over'><table class='table table-boxed'>";
    $txt .= "<tr><th>" . $paramtext->getParam('track-invoice-month2') . "</th><th>" . $paramtext->getParam('track-sent-login') . "</th><th>" . $paramtext->getParam('track-client-name') . "</th><th>" . $paramtext->getParam('track-invoice-id') . "</th><th>" . $paramtext->getParam('track-total-fact') . "</th>";
    $txt .= "<th>" . $paramtext->getParam('track-err-msg') . "</th><th>" . $paramtext->getParam('track-doc-nr') . "</th><th>" . $paramtext->getParam('track-api-date') . "</th><th>" . $paramtext->getParam('track-api-TTC2') . "</th><th>" . $paramtext->getParam('track-api-msg') . "</th></tr>";
    if(file_exists($dir)) {
        foreach(globReverse($dir) as $dirYear) {
            $year = basename($dirYear);
            foreach(globReverse($dirYear) as $dirMonth) {
                $month = basename($dirMonth);
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
                                    $txt .= '<tr><td>' . $month . " " . $year . " - " . $version . "</td>";
                                    $txt .= '<td>' . $login . '</td><td>' . $tab[0] . '</td><td>' . $tab[1] . '</td><td>'. $tab[2] . '</td>';
                                    $txt .= '<td>' . $tab[5] . '</td><td>' . $tab[4] . '</td>';
                                    if($tab[4] != "-") {
                                        $sapTab = getStatut($tab[4]);
                                        if(!empty($sapTab)) {
                                            $doc = $sapTab[0];
                                            $msg = $messageSap->getMessage($doc["SalesDocApprovalStatus"], $doc["OverallOrdReltdBillgStatus"], $doc["Fistatu"]);
                                            if(empty($msg)) {
                                                $msg = 'Statut problématique : "' . $doc["SalesDocApprovalStatusTxt"] . '" + "' . $doc["OverallOrdReltdBillgStatusTxt"] . '" + "' . $doc["FistatuTxt"] . '"';
                                            }
                                            $txt .= '<td>' . $doc["CreationDate"] . '</td><td>' . $doc["TransactionCurrency"] . " " . $doc["TotalNetAmount"] . '</td><td>' . $msg . '</td></tr>';
                                        }
                                        else {
                                            $txt .= '<td>liste</td><td>non</td><td>accessible</td></tr>';
                                        }
                                    }
                                    else {
                                        $txt .= '<td>-</td><td>-</td><td>-</td></tr>';
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    $txt .= "</table></div>";
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
