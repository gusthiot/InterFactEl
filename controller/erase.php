<?php

require_once("../commons/State.php");

session_start();
if(isset($_GET["dir"]) && isset($_GET["run"]) && isset($_GET["plate"])){
    State::removeRun("../".$_GET["dir"], $_GET["run"]);
    $_SESSION['message'] = "run correctement effacé";
    header('Location: ../plateforme.php?plateforme='.$_GET["plate"]);
}
else {
    $_SESSION['message'] = "post_data_missing";
    header('Location: ../index.php');
}