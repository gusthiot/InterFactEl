<?php

class Data {
    static function scanDescSan($dir) {
        return array_diff(scandir($dir, SCANDIR_SORT_DESCENDING), array('..', '.','logfile.log'));
    }

    static function removeRun($path, $todel) {
        foreach(self::scanDescSan($path) as $version) {
            foreach(self::scanDescSan($path."/".$version) as $run) {
                if($run == $todel) {
                    exec(sprintf("rm -rf %s", escapeshellarg($path."/".$version."/".$run)));
                }
            }
            rmdir($path."/".$version);
        }
        rmdir($path);
    }

    static function availableForFacturation($plateforme, $messages) {
        $return = array('sap'=>array('error'=>null, 'result'=>null), 'proforma'=>array('error'=>null, 'result=>null'));   
        $last_y = 0;
        $last_m = 0;
        $last_v = 0;
        $last_r = 0;
        $tree = array();
        foreach(self::scanDescSan($plateforme) as $year) {
            if($last_y == 0) {
                $last_y = $year;
            }
            foreach(self::scanDescSan($plateforme."/".$year) as $month) {
                if($last_m == 0) {
                    $last_m = $month;
                }
                $tree[$year][$month] = array('lock'=>FALSE, 'version'=>array());
                foreach(self::scanDescSan($plateforme."/".$year."/".$month) as $version) {
                    if($last_v == 0) {
                        $last_v = $version;
                    }
                    if (file_exists($plateforme."/".$year."/".$month."/lock.csv")) {
                        $tree[$year][$month]["lock"] = TRUE;
                    }
                    $tree[$year][$month]['versions'][$version] = array('lock'=>FALSE, 'lockruns'=>TRUE);
                    if (file_exists($plateforme."/".$year."/".$month."/".$version."/lock.csv")) {
                        $tree[$year][$month]['versions'][$version]['lock'] = TRUE;
                    }
                    foreach(self::scanDescSan($plateforme."/".$year."/".$month."/".$version) as $run) {
                        if($last_r == 0) {
                            $last_r = $run;
                            $tree[$year][$month]['versions'][$version]['last_run'] = $run;
                        }
                        if (!file_exists($plateforme."/".$year."/".$month."/".$version."/".$run."/lock.csv")) {
                            $tree[$year][$month]['versions'][$version]['lockruns'] = FALSE;
                        }
                    }

                }
            }
        }

        if($tree[$last_y][$last_m]['versions'][$last_v]['lock']) {
            if($last_m = "12") {
                $year = ((int)$last_y)+1;
                $month = 01;
            }
            else {
                $year = $last_y;
                $month = ((int)$last_m)+1;
            }
            $return['proforma']['result'][] = array(
                'msg'=>$last_m." ".$last_y." v".$last_v." (pour facturation ".$month." ".$year.")",
                'run'=>$last_r,
                'version'=>$last_v,
                'month'=>$last_m,
                'year'=>$last_y);
            $return['sap']['result'][] = array(
                'msg'=>$last_m." ".$last_y." v".$last_v." (pour facturation ".$month." ".$year.")",
                'run'=>$last_r,
                'version'=>$last_v,
                'month'=>$last_m,
                'year'=>$last_y);
            if($tree[$last_y][$last_m]['versions'][$last_v]['lockruns']) {
                $return['sap']['result'][] = array(
                    'msg'=>$last_m." ".$last_y." v".$last_v." (pour facturation ".$last_m." ".$last_y.")",
                    'run'=>$last_r,
                    'version'=>$last_v,
                    'month'=>$last_m,
                    'year'=>$last_y);
            }
        }
        else {
            $return['proforma']['error'] = $messages->getMessage('msg2.3');
            if($tree[$last_y][$last_m]['versions'][$last_v]['lockruns']) {
                if((int)$last_v == 0) {
                    if($last_m = "01") {
                        $year = ((int)$last_y)-1;
                        $month = 12;
                    }
                    else {
                        $year = $last_y;
                        $month = ((int)$last_m)-1;
                    }
                    if(in_array($year,$tree) && in_array($month, $tree[$year])) {
                        $version = arsort(array_keys($tree[$year][$month]['versions']))[0];
                        $run = $tree[$year][$month]['versions'][$version]['last_run'];
                        $return['sap']['result'][] = array(
                            'msg'=>$month." ".$year." v".$version." (pour facturation ".$last_m." ".$last_y.")",
                            'run'=>$run,
                            'version'=>$version,
                            'month'=>$month,
                            'year'=>$year);
                    }
                }
                else {
                    $version = ((int)$last_v)-1;
                    $run = $tree[$year][$month]['versions'][$version]['last_run'];
                    $return['sap']['result'][] = array(
                        'msg'=>$last_m." ".$last_y." v".$version." (pour facturation ".$last_m." ".$last_y.")",
                        'run'=>$run,
                        'version'=>$version,
                        'month'=>$last_m,
                        'year'=>$last_y);
                }

            }
        }

        return $return;
        // proforma : - pour le mois suivant : le dernier mois, version max si elle est fermée, sinon rien
        // sap : - pour le mois suivant : le dernier mois, version max si elle est fermée
        //       - pour le mois : si dernier mois pas fermé, si reps version max fermés
        //          -- dernier mois, version max si version max fermée
        //          -- dernier mois, version max-1 si version max pas fermée mais >0
        //          -- avant-dernier mois, version max si version max pas fermée mais =0
    }

}