<?php

class Data 
{

    static $escaped = ['..', '.','logfile.log', 'lock.csv'];
    static function scanDescSan($dir) 
    {
        return array_diff(scandir($dir, SCANDIR_SORT_DESCENDING), self::$escaped);
    }

    static function removeRun($path, $todel) {
        if(file_exists($path)) {
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
    }

    static function litMonth($m) {
        return $m < 10 ? "0".$m : $m;
    }

    static function availableForFacturation($pathPlate, $messages) {
        $return = array('SAP'=>array(), 'PROFORMA'=>array());   
        $last_y = 0;
        $last_m = 0;
        $last_v = 0;
        $last_r = 0;
        $tree = [];
        foreach(self::scanDescSan($pathPlate) as $year) {
            if($last_y == 0) {
                $last_y = $year;
            }
            foreach(self::scanDescSan($pathPlate."/".$year) as $month) {
                if($last_m == 0) {
                    $last_m = $month;
                }
                $tree[$year][$month] = ['lock'=>FALSE, 'version'=>[]];
                foreach(self::scanDescSan($pathPlate."/".$year."/".$month) as $version) {
                    if($last_v == 0) {
                        $last_v = $version;
                    }
                    if (file_exists($pathPlate."/".$year."/".$month."/lock.csv")) {
                        $tree[$year][$month]["lock"] = TRUE;
                    }
                    $tree[$year][$month]['versions'][$version] = ['lock'=>FALSE, 'lockruns'=>TRUE];
                    if (file_exists($pathPlate."/".$year."/".$month."/".$version."/lock.csv")) {
                        $tree[$year][$month]['versions'][$version]['lock'] = TRUE;
                    }
                    foreach(self::scanDescSan($pathPlate."/".$year."/".$month."/".$version) as $run) {
                        if($last_r == 0) {
                            $last_r = $run;
                            $tree[$year][$month]['versions'][$version]['last_run'] = $run;
                        }
                        /* si un run n'est pas fermé */
                        if (!file_exists($pathPlate."/".$year."/".$month."/".$version."/".$run."/lock.csv")) {
                            $tree[$year][$month]['versions'][$version]['lockruns'] = FALSE;
                        }
                    }

                }
            }
        }
        if($last_m == "12") {
            $next_y = ((int)$last_y)+1;
            $next_m = 01;
        }
        else {
            $next_y = $last_y;
            $next_m = self::litMonth(((int)$last_m)+1);
        }
        if($last_m == "01") {
            $prev_y = ((int)$last_y)-1;
            $prev_m = 12;
        }
        else {
            $prev_y = $last_y;
            $prev_m = self::litMonth(((int)$last_m)-1);
        }

        /* si tous les runs fermés */ 
        if($tree[$last_y][$last_m]['versions'][$last_v]['lockruns']) {     
            /* si dernière version fermée */ 
            if($tree[$last_y][$last_m]['versions'][$last_v]['lock']) { 
                // retourne m,v pour m+1,0
                $return['PROFORMA'][] = [
                    'type'=>"result",
                    'msg'=>$last_m." ".$last_y." v".$last_v." (pour facturation ".$next_m." ".$next_y.")",
                    'run'=>$last_r,
                    'version'=>$last_v,
                    'month'=>$last_m,
                    'year'=>$last_y,
                    'exp_m'=>$next_m,
                    'exp_y'=>$next_y];
                $return['SAP'][] = [
                    'type'=>"info",
                    'msg'=>$last_m." ".$last_y.": ".$messages->getMessage('msg1.1')];
                // retourne m,v pour m+1,0 ou m,v+1
                $return['SAP'][] = [
                    'type'=>"result",
                    'msg'=>$last_m." ".$last_y." v".$last_v." (pour facturation ".$last_m." ".$last_y.")",
                    'run'=>$last_r,
                    'version'=>$last_v,
                    'month'=>$last_m,
                    'year'=>$last_y,
                    'exp_m'=>$last_m,
                    'exp_y'=>$last_y];
                $return['SAP'][] = [
                    'type'=>"result",
                    'msg'=>$last_m." ".$last_y." v".$last_v." (pour facturation ".$next_m." ".$next_y.")",
                    'run'=>$last_r,
                    'version'=>$last_v,
                    'month'=>$last_m,
                    'year'=>$last_y,
                    'exp_m'=>$next_m,
                    'exp_y'=>$next_y];
            }
            else { 
                $return['PROFORMA'][] = [
                    'type'=>'error',
                    'msg'=>"facturation ".$last_m." ".$last_y.": ".$messages->getMessage('msg2')];
                // si dernière version = 0 et ouverte
                if($last_v == 0) {
                    if(in_array($prev_y,$tree) && in_array($prev_m, $tree[$prev_y])) {
                        $version = arsort(array_keys($tree[$prev_y][$prev_m]['versions']))[0];
                        $run = $tree[$prev_y][$prev_m]['versions'][$version]['last_run'];
                        // retourne m-1,vmax pour m,0
                        $return['SAP'][] = [
                            'type'=>"result",
                            'msg'=>$prev_m." ".$prev_y." v".$version." (pour facturation ".$last_m." ".$last_y.")",
                            'run'=>$run,
                            'version'=>$version,
                            'month'=>$prev_m,
                            'year'=>$prev_y,
                            'exp_m'=>$last_m,
                            'exp_y'=>$last_y];
                    }
                    else {
                        $return['SAP'][] = [
                            'type'=>"error",
                            'msg'=>"facturation ".$last_m." ".$last_y.": ".$messages->getMessage('msg1.3')];
                    }

                }
                // si dernière version > 0 et ouverte
                else {
                    $version = $last_v-1;
                    if(in_array($version,$tree[$last_y][$last_m]['versions'])) {
                        $run = $tree[$last_y][$last_m]['versions'][$version]['last_run'];
                        // retourne m,v-1 pour m,v 
                        $return['SAP'][] = [
                            'type'=>"result",
                            'msg'=>$last_m." ".$last_y." v".$version." (pour facturation ".$last_m." ".$last_y.")",
                            'run'=>$run,
                            'version'=>$version,
                            'month'=>$last_m,
                            'year'=>$last_y,
                            'exp_m'=>$last_m,
                            'exp_y'=>$last_y];
                        $return['SAP'][] = [
                            'type'=>"error",
                            'msg'=>"facturation ".$last_m." ".$last_y.": ".$messages->getMessage('msg1.2')];
                    }
                    else {
                        $return['SAP'][] = [
                            'type'=>"error",
                            'msg'=>"facturation ".$last_m." ".$last_y.": la version précédente n'existe pas, ce n'est pas normal"];
                    }

                }

            }

        }
        else {
            $return['SAP'][] = [
                'type'=>'error',
                'msg'=>"facturation ".$last_m." ".$last_y.": ".$messages->getMessage('msg1')];
            $return['PROFORMA'][] = [
                'type'=>'error',
                'msg'=>"facturation ".$last_m." ".$last_y.": ".$messages->getMessage('msg2')];
        }

        return $return;
    }

}