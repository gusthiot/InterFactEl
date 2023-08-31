<?php
require_once("../commons/Data.php");
require_once("../session.php");



if(isset($_GET['plate'])) {
    $return = Data::availableForFacturation("../".$_GET['plate'], $messages);

    header("Content-type: application/json");
    echo json_encode($return);
}
