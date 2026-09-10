<?php

require_once("../assets/Message.php");
require_once("../assets/Plateforme.php");
require_once("../assets/ParamText.php");
require_once("../assets/Personnel.php");
require_once("../assets/Droit.php");
require_once("../session.inc");

if(IS_SUPER) {
    $plateformes = new Plateforme();
    $personnel = new Personnel();
    $messages = new Message();
    $paramtext = new ParamText();

    $contents = [];
    $contents["listeplateforme"] = $plateformes->getContent();
    $contents["personnel"] = $personnel->getContent();
    $contents["gestionnaire"] = $gestionnaire->getContent();
    $contents["superviseur"] = $superviseur->getContent();

    $json = ["supervisor" => USER, "contents" => $contents, "paramtext" => $paramtext->getParams(), "messages" => $messages->getMessages(), "droits" => json_decode(Droit::load('../'))];
    echo json_encode($json, ENT_QUOTES);
}
else {
    $_SESSION['alert-info'] = "Vous n'avez aucun droit de supervision";
}
