<?php

require_once("session.inc");

if(DEV_MODE) {
    header('Location: index.php');
}
else {
    $_SESSION['logout'] = true;
    header('Location: https://cmifact.epfl.ch/entra/entra.php');
}

