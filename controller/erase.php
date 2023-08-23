<?php

require_once("../commons/Data.php");

if(isset($_GET["dir"]) && isset($_GET["run"]) && isset($_GET["plate"])){
    Data::removeRun("../".$_GET["dir"], $_GET["run"]);
    header('Location: ../plateforme.php?plateforme='.$_GET["plate"].'&message=ok');
}
else {
    header('Location: ../index.php?message=post_data_missing');
}