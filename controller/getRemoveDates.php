<?php

require_once("../assets/Unused.php");
require_once("../assets/Version.php");
require_once("../assets/Label.php");
require_once("../assets/Lock.php");
require_once("../assets/ParamZip.php");
require_once("../assets/Message.php");
require_once("../includes/State.php");
require_once("../includes/Tarifs.php");
require_once("../session.inc");

/**
 * Called to obtain dates from which we could suppress tarifs
 */
if(isset($_POST["plate"]) && isset($_POST["m0"]) && isset($_POST["status"])) {

    $plateforme = $_POST["plate"];
    checkPlateforme("tarifs", $plateforme);

    $dir = DATA.$plateforme;
    $choices = [];
    $m0 = $_POST["m0"];
    $date = Tarifs::maxDate($dir, $m0);

    $version = Version::load('../');
    $messages = new Message();

    while($date > "202406") {

        $month = substr($date, 4, 2);
        $year = substr($date, 0, 4);

        $dirMonth = $dir."/".$year."/".$month;
        if(Lock::exists($dirMonth, 'month')) {
            break;
        }

        $label = Tarifs::label($dirMonth);

        if(Tarifs::v0_exists($dirMonth)) {
            if($m0 == $date) {
                $status = $_POST["status"];
                $status < 4 ? $warning = $messages->getMessage('msg10') : $warning = "";
                Unused::exists($dirMonth) ? $diode = 1 : $diode = 0;
                in_array($status, [5, 7]) ? $clic = 1 : $clic = 0;
                $choices["remove-".$year.$month] = [$month." ".$year, $label, $clic, $diode, 0, $warning];
            }
            else {
                $choices["remove-".$year.$month] = [$month." ".$year, $label, 0, 0, 0, ""];
            }
        }
        else {
            if(Unused::exists($dirMonth)) {
                $warning = Tarifs::warning9($dirMonth, $version);
                $choices["remove-".$year.$month] = [$month." ".$year, $label, 1, 1, 0, $warning];
            }
            else {
                $choices["remove-".$year.$month] = [$month." ".$year, $label, 0, 0, 0, ""];
            }
        }
        $date = State::decreaseDate($date);
    }

    echo json_encode($choices);

}
