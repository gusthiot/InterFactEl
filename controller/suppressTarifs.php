<?php

require_once("../commons/Params.php");
require_once("../session.php");

if(isset($_GET["plate"]) && isset($_GET["year"]) && isset($_GET["month"])) {
    $dirTarifs = DATA.$_GET["plate"]."/".$_GET["year"]."/".$_GET["month"]."/";
    Params::suppress($dirTarifs);
    $_SESSION['alert-success'] = "tarifs correctement effacées";
    header('Location: ../tarifs.php?plateforme='.$_GET["plate"]);
}
else {
    $_SESSION['alert-danger'] = "post_data_missing";
    header('Location: ../index.php');
}
