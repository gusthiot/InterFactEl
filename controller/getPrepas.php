<?php
require_once("../commons/Data.php");
require_once("../src/Lock.php");
require_once("../session.php");



if(isset($_GET['plate'])) {
    $lock = new Lock();
    $return = Data::availableForFacturation("../".$_GET['plate'], $messages, $lock);

    header("Content-type: application/json");
    echo json_encode($return);
}
