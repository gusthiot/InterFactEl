<?php

require_once("../src/Lock.php");

if(isset($_GET["dir"])){
    $lock = new Lock();
    $lock->save("../".$_GET["dir"], 'run', "invalidate");
    echo "invalidé";
}