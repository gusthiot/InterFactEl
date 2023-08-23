<?php
require_once("../config.php");

$txt = "";

if(isset($_GET['plate'])) {
    $file = GROUND.$_GET['plate']."/logfile.log";
    if ((file_exists($file)) && (($open = fopen($file, "r")) !== false)) {
        $txt = fread($open, filesize($file));    
        fclose($open);
    }
}

echo '<div>'.$txt.'</div>';