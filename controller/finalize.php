<?php

require_once("../src/Lock.php");

if(isset($_GET["dir"])){
    $dir = "../".$_GET["dir"];
    $lock = new Lock();
    $lock->save($dir, 'run', "finalized");
    $sep = strrpos($dir, "/");
    $lock->save(substr($dir, 0, $sep), 'version', substr($dir, $sep+1));
    echo "finalisé";
}