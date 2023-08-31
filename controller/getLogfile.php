<?php

require_once("../src/Logfile.php");

$txt = "";

if(isset($_POST['plate'])) {
    $logfile = new Logfile();
    $txt = $logfile->load("../".$_POST['plate']);
}

echo '<div>'.str_replace(PHP_EOL, "<br />", $txt).'</div>';