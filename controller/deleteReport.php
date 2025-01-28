<?php
require_once("../includes/State.php");
require_once("../session.inc");

/**
 * Called to delete temporary csv reports
 */
if(isset($_POST["unique"])) {
    delete($_POST["unique"]);
}
elseif(isset($_GET["unique"])) {
    delete($_GET["unique"]);
    if(isset($_GET["plate"])) {
        header('Location: ../reporting.php?plateforme='.$_GET["plate"]);
    }
    else {
        header('Location: ../index.php');
    }
}

function delete($unique) 
{
    State::delDir(TEMP.$unique.'/');
}
