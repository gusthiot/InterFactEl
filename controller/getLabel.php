<?php

require_once("../assets/Label.php");
require_once("../session.inc");

/**
 * Called to display a textarea to add/modify a label, with the current label if exists
 */
checkGest($dataGest);
$txt = "";
if(isset($_POST["plate"]) && isset($_POST["year"]) && isset($_POST["month"])) {
    checkPlateforme($dataGest, $_POST["plate"]);
    $dir = DATA.$_POST['plate']."/".$_POST['year']."/".$_POST['month'];
    if(isset($_POST["version"]) && isset($_POST["run"])) {
        $dir .= "/".$_POST['version']."/".$_POST['run'];
    }
    $label = Label::load($dir);
}

echo '<div id="label-line"><textarea name="label" id="label-area">'.$label.'</textarea><button type="button" id="save-label" class="btn but-line">Save</button></div>';
