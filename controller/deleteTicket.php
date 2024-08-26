<?php
require_once("../includes/State.php");
require_once("../session.inc");

/**
 * Called to delete temporary ticket data
 */
if(isset($_POST["unique"])) {
    delete($_POST["unique"]);
}
elseif(isset($_GET["unique"])) {
    delete($_GET["unique"]);
}
else {
    $_SESSION['alert-danger'] = "data_missing";
}
header('Location: ../index.php');

function delete($unique) {
    State::delDir(TEMP.$unique.'/');
    $_SESSION['alert-info'] = "Les données ont été effacées";
}
