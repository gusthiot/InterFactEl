<?php
require_once("../session.inc");

/**
 * 
 */
if(isset($_POST["plate"])) {
    $plateforme = $_POST["plate"];
    checkPlateforme($dataGest, "reporting", $plateforme);

    $in = "";
    $name = "../structure_in.json";
    if ((file_exists($name)) && (($open = fopen($name, "r")) !== false)) {
        $in = fread($open, filesize($name));
        fclose($open);
    }
    $ins = json_decode($in, true);
    $ret = [];
    foreach($ins as $version=>$content) {
        $vArray = [];
        foreach($content as $file=>$list) {
            $fArray = ["prefix"=>"", "columns"=>[]];
            foreach($list as $num=>$code) {
                if($num == "0") {
                    $fArray["prefix"] = $code;
                }
                else {
                    if(!empty($code)) {
                        $fArray["columns"][$code] = $num-1;
                    }
                }
            }
            $vArray[$file] = $fArray;
        }

        $ret[$version] = $vArray;
    }
    var_dump($ret);
    file_put_contents('../test.json', json_encode($ret, JSON_PRETTY_PRINT));



    $_SESSION['alert-success'] = "ok for now...";
}
