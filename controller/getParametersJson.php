<?php

require_once("../assets/Message.php");
require_once("../assets/ParamText.php");
require_once("../assets/Parameters.php");
require_once("../session.inc");

$messages = new Message();
$paramtext = new ParamText();

$json = ["paramtext" => $paramtext->getParams(), "messages" => $messages->getMessages(), "parameters" => json_decode(Parameters::load('../'))];
echo json_encode($json, ENT_QUOTES);
