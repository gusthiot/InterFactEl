<?php
require_once("../commons/Data.php");
require_once("../from_csv/Message.php");



if(isset($_GET['plate'])) {
    $plateforme = '../'.$_GET['plate'];
    $messages = new Message();
    $return = Data::availableForFacturation($plateforme);

    header("Content-type: application/json");
    echo json_encode($return);
}
