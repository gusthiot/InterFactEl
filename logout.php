<?php

require_once("session.inc");

if(DEV_MODE) {
    header('Location: index.php');
}
else {
    $oClient->logout();
}

