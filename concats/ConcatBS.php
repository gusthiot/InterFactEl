<?php

class ConcatBS
{
    static function run($from, $to, $plateforme)
    {
        $fact_from = self::getVersion($from, $plateforme);
        $fact_to = self::getVersion($to, $plateforme);
        if($fact_from != $fact_to) {
            $_SESSION['alert-danger'] = "Sélectionner la période pour une même version logicielle";
            header('Location: ../reporting.php?plateforme='.$plateforme);
            exit;
        }

        $abrev = DATA_GEST['reporting'][$plateforme];
        $noms = ["Bilan-annulé", "Bilan-conso-propre", "Bilan-factures", "Bilan-subsides", "Bilan-usage", "Stat-client", "Stat-machine", "Stat-nbre-user", "Stat-user", "Transaction1", "Transaction2", "Transaction3"];
        $suf_fin = "_".$abrev."_".substr($from, 0, 4)."_".substr($from, 4, 2)."_".substr($to, 0, 4)."_".substr($to, 4, 2).".csv";    
        $tmpDir = TEMP.'reporting_'.time().'/';

        foreach($noms as $nom) {
            $date = $from;
            $first = true;
            while(true) {
                $content = [];
                $month = substr($date, 4, 2);
                $year = substr($date, 0, 4);
                $dir = DATA.$plateforme."/".$year."/".$month;

                if (file_exists($dir."/".Lock::FILES['month'])) {
                    $version = Lock::load($dir, "month");
                    $dirVersion = $dir."/".$version;
                    $run = Lock::load($dirVersion, "version");
                    $dirRun = $dirVersion."/".$run;
                }
                else {
                    foreach(globReverse($dir) as $dirVersion) {
                        $run = Lock::load($dirVersion, "version");
                        if (!is_null($run)) {
                            $dirRun = $dirVersion."/".$run;
                            break;
                        }
                    }
                }

                $suf = "_".$abrev."_".$year."_".$month."_".basename($dirVersion).".csv";
                
                $path = $dirRun."/Bilans_Stats/".$nom.$suf;
                $csv = Csv::extract($path);
                if(!empty($csv)) {
                    if($first) {
                        $content[] = explode(";", $csv[0]);
                        $first = false;
                    }
                    for($i=1;$i<count($csv);$i++) {
                        $content[] = explode(";", $csv[$i]);
                    }
                }

                if (file_exists($tmpDir) || mkdir($tmpDir, 0777, true)) {
                    Csv::append($tmpDir.$nom.$suf_fin, $content);
                }

                if($date == $to) {
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

        $zip = 'concatenation.zip';
        Lock::saveByName("../".USER.".lock", TEMP.$zip);
        Zip::setZipDir(TEMP.$zip, $tmpDir);
        State::delDir($tmpDir);
    }

    static function getVersion($date, $plateforme)
    {
        $month = substr($date, 4, 2);
        $year = substr($date, 0, 4);
        $dir = DATA.$plateforme."/".$year."/".$month;
        $dirVersion = globReverse($dir)[0];
        $run = Lock::load($dirVersion, "version");
        $infos = Info::load($dirVersion."/".$run);
        return $infos["FactEl"][2];
    }
}
