<?php

if(isset($_GET["plate"])) {
    exec(sprintf("rm -rf %s", escapeshellarg("../".$_GET["plate"])));
    header('Location: ../plateforme.php?plateforme='.$_GET["plate"].'&message=ok');
}
else {
    header('Location: ../index.php?message=post_data_missing');
}