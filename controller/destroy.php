<?php
require_once("../session.php");
if(isset($_GET["plate"])) {
    exec(sprintf("rm -rf %s", escapeshellarg(DATA.$_GET["plate"])));
    $_SESSION['alert-success'] = "données de plateforme correctement effacées";
    header('Location: ../plateforme.php?plateforme='.$_GET["plate"]);
}
else {
    $_SESSION['alert-danger'] = "post_data_missing";
    header('Location: ../index.php');
}
