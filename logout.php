<?php

require_once("session.inc");

if(!DEV_MODE) {
    $_SESSION = array();
    session_destroy();
}
header('Location: index.php');

