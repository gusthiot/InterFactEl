<?php

require_once("../session.inc");

/**
 * Called to load data period for testing
 */
if(isset($_GET["from"]) && isset($_GET["to"]) && isset($_GET["plate"])) {
    $plateforme = $_GET["plate"];
    checkPlateforme($dataGest, "facturation", $plateforme);
    if($superviseur->isSuperviseur($user) && TEST_MODE == "TEST") {  
        exec(sprintf("rm -rf %s", escapeshellarg(DATA.$plateforme)));
        $date = $_GET["from"];
        while(true) {
            $month = substr($date, 4, 2);
            $year = substr($date, 0, 4);
            $prod = str_replace("data", "../prod/data", DATA.$plateforme)."/".$year."/".$month;
            $dir = DATA.$plateforme."/".$year."/".$month;
            recurseCopy($prod, $dir);

            if($date == $_GET["to"]) {
                break;
            }

            if($month == "12") {
                $date += 89;
            }
            else {
                $date++;
            }
        }
    }
    else {
        $_SESSION['alert-danger'] = "wrong place, wrong user";
    }

    $_SESSION['alert-success'] = "Période correctement chargée";
    header('Location: ../facturation.php?plateforme='.$plateforme);
} 
else {
    $_SESSION['alert-danger'] = "post_data_missing";
    header('Location: ../index.php');
}

/**
 * Copies a directory recursively
 *
 * @param string $src source directory
 * @param string $dst destination directory (should not exist yet)
 * @return void
 */
function recurseCopy(string $src, string $dst): void
{
    $dir = opendir($src);
    mkdir($dst, 0755, true);
    while(false !== ( $file = readdir($dir)) ) {
        if (( $file != '.' ) && ( $file != '..' )) {
            if ( is_dir($src . '/' . $file) ) {
                recurseCopy($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file,$dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}
