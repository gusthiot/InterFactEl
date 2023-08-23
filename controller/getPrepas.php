<?php
require_once("../commons/Data.php");
require_once("../src/Message.php");
require_once("../config.php");



if(isset($_GET['plate'])) {
    $messages = new Message();
    $return = Data::availableForFacturation(GROUND.$_GET['plate'], $messages);

    header("Content-type: application/json");
    echo json_encode($return);
}
