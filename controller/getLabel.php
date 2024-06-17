<?php

require_once("../assets/Label.php");
require_once("../session.php");

$txt = "";

if(isset($_POST["plate"]) && isset($_POST["year"]) && isset($_POST["month"])) {
    $dir = DATA.$_POST['plate']."/".$_POST['year']."/".$_POST['month'];
    if(isset($_POST["version"]) && isset($_POST["run"])) {
        $dir .= "/".$_POST['version']."/".$_POST['run'];
    }
    $label = new Label();
    $txt = $label->load($dir);
}

echo '<div id="labelLine"><textarea name="label" id="labelArea">'.$txt.'</textarea><button type="button" id="saveLabel" class="btn but-line">Save</button></div>';
