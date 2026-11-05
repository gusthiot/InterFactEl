<?php

require_once("../assets/Sap.php");
require_once("../session.inc");

/**
 * Called to
 */
$txt = "";
if(isset($_POST['plate'])) {
    checkPlateforme("facturation", $_POST["plate"]);
    $dir = DATA.$_POST['plate'];
    //$order = "0010000236";
    //$txt = getStatut($order);
    $txt = "<div class='over'><div id='statut'>";
    if(file_exists($dir)) {
        foreach(globReverse($dir) as $dirYear) {
            $txt .= basename($dirYear)."<br />";
            foreach(globReverse($dirYear) as $dirMonth) {
                $txt .= basename($dirMonth)."<br />";
                foreach(globReverse($dirMonth) as $dirVersion) {
                    $txt .= basename($dirVersion)."<br />";
                    foreach(globReverse($dirVersion) as $dirRun) {
                        $txt .= basename($dirRun)."<br />";
                        foreach(array_reverse(glob($dirRun."/*")) as $file) {
                            if(str_contains($file, "sap_")) {
                                $txt .= basename($file)."<br />";
                                $sap = new Sap($dirRun, basename($file));
                                foreach($sap->getBills() as $fact=>$tab) {
                                    $txt .= json_encode($tab);
                                    $txt .= "<br />";
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    $txt .= "</div></div>";
}

echo $txt;


function getStatut($order)
{
    $cmd = "curl -H 'X-API-KEY: " . NEXUS_KEY . "' " . NEXUS_ORDERS . "?%24filter=SalesOrder%20eq%20%27" . $order . "%27";
    $res = shell_exec($cmd);

    if(!is_null($res)) {
        $result = json_decode($res, true);
        if(array_key_exists("value", $result)) {
            return json_encode($result["value"]);
        }
    }
    return "";
}
