<?php

class Data 
{

    const ESCAPED = ['..', '.','logfile.log', 'lockm.csv', 'lockv.csv'];

    static function scanDescSan(string $dir): array 
    {
        return array_diff(scandir($dir, SCANDIR_SORT_DESCENDING), self::ESCAPED);
    }

    static function removeRun(string $path, string $todel): void
    {
        if(file_exists($path)) {
            foreach(self::scanDescSan($path) as $version) {
                foreach(self::scanDescSan($path."/".$version) as $run) {
                    if($run === $todel) {
                        exec(sprintf("rm -rf %s", escapeshellarg($path."/".$version."/".$run)));
                    }
                }
                rmdir($path."/".$version);
            }
            rmdir($path);
        }
    }

    static function delDir(string $dir): void 
    {
        foreach(Data::scanDescSan($dir) as $file) {
            unlink($dir."/".$file);
        }
        rmdir($dir);
    }

    static function addToString(string $txt, int $num): string 
    {
        return (string)((int)($txt) + $num);
    }

    static function addToMonth(string $month, int $num): string 
    {
        $m = (int)($month) + $num;
        return $m < 10 ? "0".(string)($m) : (string)($m);
    }

    static function availableForFacturation(string $pathPlate, Message $messages, Lock $lock, Result $results = null): array
    {
        $last_y = "";
        $last_m = "";
        $last_v = "";
        $last_r = "";
        $tree = [];
        foreach(self::scanDescSan($pathPlate) as $year) {
            foreach(self::scanDescSan($pathPlate."/".$year) as $month) {
                $tree[$year][$month] = ['lock'=>false, 'version'=>[]];
                foreach(self::scanDescSan($pathPlate."/".$year."/".$month) as $version) {
                    if (file_exists($pathPlate."/".$year."/".$month."/lockm.csv")) {
                        $tree[$year][$month]["lock"] = true;
                    }
                    $tree[$year][$month]['versions'][$version] = ['lock'=>false, 'lockruns'=>true];
                    if (file_exists($pathPlate."/".$year."/".$month."/".$version."/lockv.csv")) {
                        $tree[$year][$month]['versions'][$version]['lock'] = true;
                    }
                    foreach(self::scanDescSan($pathPlate."/".$year."/".$month."/".$version) as $run) {

                        /* si un run n'est pas fermé */
                        if (!file_exists($pathPlate."/".$year."/".$month."/".$version."/".$run."/lock.csv")) {
                            $tree[$year][$month]['versions'][$version]['lockruns'] = false;
                        }
                        else {
                            $loctxt = $lock->load($pathPlate."/".$year."/".$month."/".$version."/".$run, "run");
                            if($loctxt == $lock::STATES['invalidate']) {
                                continue;
                            }
                        }
                        if(empty($last_r) || $run > $last_r) {
                            $last_r = $run;
                            $last_v = $version;
                            $last_m = $month;
                            $last_y = $year;
                            $tree[$year][$month]['versions'][$version]['last_run'] = $run;
                        }
                    }
                }
            }
        }
        $return = ['SAP'=>[], 'PROFORMA'=>[]];
        if(!empty($last_r)) {
            if($last_m == "12") {
                $next_y = self::addToString($last_y, 1);
                $next_m = 01;
            }
            else {
                $next_y = $last_y;
                $next_m = self::addToMonth($last_m, 1);
            }
            if($last_m == "01") {
                $prev_y = self::addToString($last_y, -1);
                $prev_m = 12;
            }
            else {
                $prev_y = $last_y;
                $prev_m = self::addToMonth($last_m, -1);
            }

            if($results && ((int)($results->getResult('Month')) !== (int)($last_m) || $results->getResult('Year') !== $last_y || $results->getResult('Version') !== $last_v || $results->getResult('Folder') !== $last_r)) {    
                $return['SAP'][] = [
                    'type'=>'error',
                    'msg'=>"facturation ".$last_m." ".$last_y.": ".$messages->getMessage('msg3.7')];
                $return['PROFORMA'][] = [
                    'type'=>'error',
                    'msg'=>"facturation ".$last_m." ".$last_y.": ".$messages->getMessage('msg3.7')];
            }
            else {
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
                        $msg = str_replace("<v>", $last_v, $messages->getMessage('msg1.1'));
                        $msg = str_replace("<mm>", $last_m, $msg);
                        $msg = str_replace("<aaaa>", $last_y, $msg);
                        $return['SAP'][] = [
                            'type'=>"info",
                            'msg'=>$msg];
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
                        if($last_v === "0") {
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
                                $msg = str_replace("<v>", $last_v, $messages->getMessage('msg1.3'));
                                $msg = str_replace("<mm>", $last_m, $msg);
                                $msg = str_replace("<aaaa>", $last_y, $msg);
                                $return['SAP'][] = [
                                    'type'=>"error",
                                    'msg'=>"facturation ".$last_m." ".$last_y.": ".$msg];
                            }

                        }
                        // si dernière version > 0 et ouverte
                        else {
                            $version = self::addToString($last_v, -1);
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
                                $msg = str_replace("<v>", $last_v, $messages->getMessage('msg1.2'));
                                $msg = str_replace("<mm>", $last_m, $msg);
                                $msg = str_replace("<aaaa>", $last_y, $msg);
                                $return['SAP'][] = [
                                    'type'=>"error",
                                    'msg'=>"facturation ".$last_m." ".$last_y.": ".$msg];
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
            }
        }
        return $return;
    }

}