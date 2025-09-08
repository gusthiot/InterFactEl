<?php

require_once("../assets/Lock.php");
require_once("../assets/Info.php");
require_once("../assets/Csv.php");
require_once("../assets/ParamText.php");
require_once("../assets/Message.php");
require_once("../includes/Zip.php");
require_once("../includes/State.php");
require_once("../reports/BSFile.php");
require_once("../concats/ConcatBS.php");
require_once("../concats/ConcatT.php");
require_once("../session.inc");

/**
 * Called to get a zip file from concatenate bilans & stats csv
 */
if(isset($_GET["from"]) && isset($_GET["to"]) && isset($_GET["plate"]) && isset($_GET["type"])) {
    $plateforme = $_GET["plate"];
    checkPlateforme("reporting", $plateforme);

    if($_GET["type"] == "concatenation") {
        ConcatBS::run($_GET["from"], $_GET["to"], $plateforme);
    }
    else {
        ConcatT::run($_GET["from"], $_GET["to"], $plateforme, $_GET["type"]);
    }

    $messages = new Message();
    $_SESSION['alert-success'] = $messages->getMessage('msg2');
    header('Location: ../reporting.php?plateforme='.$plateforme);
} 
else {
    $_SESSION['alert-danger'] = "post_data_missing";
    header('Location: ../index.php');
}

