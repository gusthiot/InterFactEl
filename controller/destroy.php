<?php
session_start();
if(isset($_GET["plate"])) {
    exec(sprintf("rm -rf %s", escapeshellarg("../".$_GET["plate"])));
    $_SESSION['type'] = "alert-success";
    $_SESSION['message'] = "données de plateforme correctement effacées";
    header('Location: ../plateforme.php?plateforme='.$_GET["plate"]);
}
else {
    $_SESSION['type'] = "alert-danger";
    $_SESSION['message'] = "post_data_missing";
    header('Location: ../index.php');
}
