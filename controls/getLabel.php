<?php

require_once("../from_txt/Label.php");

$txt = "";

if(isset($_POST['dir'])) {
    $label = new Label($_POST['dir']);
    $txt = $label->getLabel();
}

echo '<div><textarea name="label" id="labelArea">'.$txt.'</textarea><button type="button" id="saveLabel" class="btn btn-outline-dark">Save</button></div>';
