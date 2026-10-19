<?php

require_once("session.inc");

/**
 * Complete logout from Entra
 */

if(ENV_MODE == "DEV") {
    header('Location: index.php');
}
else {
    $_SESSION['logout'] = true;
    header('Location: https://cmifact.epfl.ch/entra/entra.php');
}
