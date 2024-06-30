<?php

require_once("../includes/Tarifs.php");
require_once("../session.php");

checkGest($dataGest);
if(isset($_GET["plate"]) && isset($_GET["year"]) && isset($_GET["month"])) {
    checkPlateforme($dataGest, $_GET["plate"]);
    $dirTarifs = DATA.$_GET["plate"]."/".$_GET["year"]."/".$_GET["month"]."/";
    Tarifs::suppress($dirTarifs);
    $_SESSION['alert-success'] = "tarifs correctement effacées";
    header('Location: ../tarifs.php?plateforme='.$_GET["plate"]);
}
else {
    $_SESSION['alert-danger'] = "post_data_missing";
    header('Location: ../index.php');
}
