<?php

require_once("../commons/Data.php");

session_start();
if(isset($_GET["dir"]) && isset($_GET["run"]) && isset($_GET["plate"])){
    Data::removeRun("../".$_GET["dir"], $_GET["run"]);
    $_SESSION['message'] = "ok";
    header('Location: ../plateforme.php?plateforme='.$_GET["plate"]);
}
else {
    $_SESSION['message'] = "post_data_missing";
    header('Location: ../index.php');
}