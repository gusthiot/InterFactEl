<?php

require_once("../assets/Scroll.php");
require_once("../session.inc");

if(IS_SUPER) {
    if(isset($_POST["content"])) {
        Csv::write(DATA.Scroll::NAME, $_POST["content"]);
    }
    else {
        Csv::write(DATA.Scroll::NAME, []);
    }
    $_SESSION['alert-success'] = "Messages bien enregistrés";
}
else {
    $_SESSION['alert-danger'] = "error";
}
