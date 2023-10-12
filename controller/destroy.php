<?php
session_start();
if(isset($_GET["plate"])) {
    exec(sprintf("rm -rf %s", escapeshellarg("../".$_GET["plate"])));
    $_SESSION['message'] = "ok";
    header('Location: ../plateforme.php?plateforme='.$_GET["plate"]);
}
else {
    $_SESSION['message'] = "post_data_missing";
    header('Location: ../index.php');
}