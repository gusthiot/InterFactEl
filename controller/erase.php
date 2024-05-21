<?php

require_once("../commons/State.php");

session_start();
if(isset($_GET["year"]) && isset($_GET["month"]) && isset($_GET["run"]) && isset($_GET["plate"])){
    $dir = "../".$_GET["plate"]."/".$_GET["year"]."/".$_GET["month"];
    State::removeRun($dir, $_GET["run"]);
    $_SESSION['alert-success'] = "run correctement effacé";
    header('Location: ../plateforme.php?plateforme='.$_GET["plate"]);
}
else {
    $_SESSION['alert-danger'] = "post_data_missing";
    header('Location: ../index.php');
}
