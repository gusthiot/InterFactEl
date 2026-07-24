<?php

require_once("../assets/Csv.php");
require_once("../session.inc");

if(IS_SUPER) {
    if(isset($_POST["name"]) && isset($_POST["content"])) {
        Csv::write(CONFIG.$_POST["name"].".csv", $_POST["content"]);
    }
    else {
        echo "no post";
    }
}
