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

function maxDate($mp, $dir)
{
    foreach(globReverse($dir) as $dirYear) {
        foreach(globReverse($dirYear) as $dirMonth) {
            if(Unused::exists($dirMonth)) {
                return basename($dirYear).basename($dirMonth);
            }
        }
    }
    return $mp['year'].$mp['month'];
}

if(isset($_POST["plate"])) {

    $plateforme = $_POST["plate"];
    checkPlateforme("tarifs", $plateforme);

    $dir = DATA.$plateforme;
    $mp = State::firstOpenMonth($dir);
    $choices = [];
    $version = Version::load('../');

    if(!empty($mp['month'])) {
        $messages = new Message();
        $date = maxDate($mp, $dir);

        while(true) {

            $month = substr($date, 4, 2);
            $year = substr($date, 0, 4);

            $dirMonth = $dir."/".$year."/".$month;
            if(Lock::exists($dirMonth, 'month') || ($date == "202407")) {
                break;
            }

            if(State::isSameAs($month, $year, $mp['month'], $mp['year'])) {
                $status = Tarifs::status($dirMonth);
                in_array($status, [8, 9, 10, 11]) ? $warning = $messages->getMessage('msg10') :
                    ($status == 1 ? $warning = Tarif::warning9($dirMonth, $version) : $warning = "");
                in_array($status, [1, 13, 15]) ? $clic = 1 : $clic = 0;
                Unused::exists($dirMonth) ? $diode = 1 : $diode = 0;

                $choices["remove-".$year.$month] = [$month." ".$year, Tarifs::label($dirMonth), $clic, $diode, 0, $warning];
            }
            else {
                if(Unused::exists($dirMonth)) {
                    $warning = Tarifs::warning9($dirMonth, $version);
                    $choices["remove-".$year.$month] = [$month." ".$year, Tarifs::label($dirMonth), 1, 1, 0, $warning];
                }
                else {
                    $choices["remove-".$year.$month] = [$month." ".$year, "", 0, 0, 0, ""];
                }

            }

            if($month == "01") {
                $date -= 89;
            }
            else {
                $date--;
            }
        }
    }
    echo json_encode($choices);

}
