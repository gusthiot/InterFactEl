<?php

require_once("../src/Label.php");

$txt = "";

if(isset($_POST['dir'])) {
    $label = new Label();
    $txt = $label->load("../".$_POST['dir']);
}

echo '<div><textarea name="label" id="labelArea">'.$txt.'</textarea><button type="button" id="saveLabel" class="btn btn-outline-dark">Save</button></div>';
