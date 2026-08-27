<?php

require_once("../session.inc");


if(DATA_GEST) {
    if(isset($_POST["from"]) && isset($_POST["to"])) {
        $plate = $_POST["from"];
        $plateformes = $gestionnaire->getPlateformes(USER);
        $order = $plateformes[$plate];
        $newOrder = $plateformes[$_POST["to"]];
        $content = Csv::extract(CONFIG.Gestionnaire::NAME);
        for($num = 1; $num < count($content); $num++) {
            $line = $content[$num];
            if($line[0] == USER) {
                if($line[1] == $plate) {
                    $content[$num][3] = $newOrder;
                }
                else {
                    if(intval($line[3]) > intval($order)) {
                        if(intval($line[3]) <= intval($newOrder)) {
                            $content[$num][3] = strval(intval($line[3]) - 1);
                        }
                    }
                    else {
                        if(intval($line[3]) >= intval($newOrder)) {
                            $content[$num][3] = strval(intval($line[3]) + 1);
                        }
                    }
                }
            }
        }
        Csv::write(CONFIG.Gestionnaire::NAME, $content);
        echo "ok";
    }
    else {
        echo "il manque des données";
    }
}
else {
    echo "Vous n'avez aucun droit de gestion";
}
