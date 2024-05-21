<?php

require_once("../assets/Label.php");

$txt = "";

if(isset($_POST["plate"]) && isset($_POST["year"]) && isset($_POST["month"]) && isset($_POST["version"]) && isset($_POST["run"])) {
    $dir = "../".$_POST['plate']."/".$_POST['year']."/".$_POST['month']."/".$_POST['version']."/".$_POST['run'];
    $label = new Label();
    $txt = $label->load($dir);
}

echo '<div><textarea name="label" id="labelArea">'.$txt.'</textarea><button type="button" id="saveLabel" class="btn but-line">Save</button></div>';
