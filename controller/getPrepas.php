<?php
require_once("../commons/Data.php");
require_once("../src/Message.php");



if(isset($_GET['plate'])) {
    $plateforme = '../'.$_GET['plate'];
    $messages = new Message();
    $return = Data::availableForFacturation($plateforme, $messages);

    header("Content-type: application/json");
    echo json_encode($return);
}
