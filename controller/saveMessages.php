<?php

require_once("../assets/Scroll.php");
require_once("../session.inc");

/**
 * Called to save a new label, or delete it
 */
if(IS_SUPER && isset($_POST["content"])) {

    Csv::write(DATA.Scroll::NAME, $_POST["content"]);
    $_SESSION['alert-success'] = "Messages bien enregistrés";
}
else {
    $_SESSION['alert-danger'] = "error";
}
