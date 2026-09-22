<?php

require_once("../assets/Message.php");
require_once("../assets/ParamText.php");
require_once("../assets/Parametres.php");
require_once("../session.inc");

/**
 * Called to obtain the parameters for tarifs management
 */
$messages = new Message();
$paramtext = new ParamText();

$json = ["paramtext" => $paramtext->getParams(), "messages" => $messages->getMessages(), "parametres" => json_decode(Parametres::load('../'))];
echo json_encode($json, ENT_QUOTES);
