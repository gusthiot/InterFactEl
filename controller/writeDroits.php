<?php

require_once("../assets/Csv.php");
require_once("../session.inc");

if(IS_SUPER) {
    if(isset($_POST["files"])) {
        $files = json_decode($_POST["files"]);
        foreach($files as $file => $content) {
            file_put_contents(CONFIG.$file, base64_decode($content));
        }
    }
}
