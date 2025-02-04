<?php

$month = substr($date, 4, 2);
$year = substr($date, 0, 4);
$dir = DATA.$plateforme."/".$year."/".$month;
$dirVersion = array_reverse(glob($dir."/*", GLOB_ONLYDIR))[0];
$run = Lock::load($dirVersion, "version");
$dirRun = $dirVersion."/".$run;

$infos = Info::load($dirRun);
$factel = $infos["FactEl"][2];

$versionTab = explode("/", $dirVersion);
$version = $versionTab[count($versionTab)-1];
if($factel > 8) {
    $suffix = "_".$plateName."_".$year."_".$month."_".$version;
}
else {
    $suffix = "_".$year."_".$month;
}
$monthly = $year."-".$month;
$monthList[] = $monthly;
$clients = getDirectoryCsv($dirRun."/IN/", $in[$factel]['client'], $clients);
$classes = getDirectoryCsv($dirRun."/IN/", $in[$factel]['classeclient'], $classes);
if($factel < 7) {
    $clientsClasses = getDirectoryCsv($dirRun."/IN/", $in[$factel]['clientclasse'], $clientsClasses);
}
if($factel == 8) {
    $articlesTemp = [];
    $ordersTemp = [];
    $articlesTemp = getDirectoryCsv($dirRun."/IN/", $in[$factel]['articlesap'], $articlesTemp);
    $ordersTemp = getDirectoryCsv($dirRun."/IN/", $in[$factel]['ordersap'], $ordersTemp);
    foreach($articlesTemp as $code=>$line) {
        if(!array_key_exists($code, $articles)) {
            $data = $line;
            $data["item-order"] = $ordersTemp[$code]["item-order"];
            $articles[$code] = $data;
        }
    }
}
else {
    $articles = getDirectoryCsv($dirRun."/IN/", $in[$factel]['articlesap'], $articles);
}

