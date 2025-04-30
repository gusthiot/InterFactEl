<?php

class ReportRuns extends Report
{

    public function __construct($plateforme, $to, $from) 
    { 
        parent::__construct($plateforme, $to, $from);
        $this->reportKey = 'statmach';
        $this->totalKeys = ["transac-runtime", "runtime-N", "runtime-avg", "runtime-stddev"];
        $this->reportColumns = ["mach-id", "transac-runtime", "runtime-N", "runtime-avg", "runtime-stddev"];
        $this->master = ["par-machine"=>[], "par-categorie"=>[]];
        $this->onglets = ["par-machine" => "Stats par Machine", "par-categorie" => "Stats par Catégorie"];
        $this->columns = ["par-machine" => ["mach-name"], "par-categorie" => ["item-name"]];
        $this->columnsCsv = ["par-machine" => array_merge($this::MACHINE_DIM, $this::GROUPE_DIM, $this::CATEGORIE_DIM), "par-categorie" => array_merge($this::GROUPE_DIM, $this::CATEGORIE_DIM)];
    }

    function prepare($suffix) 
    {
        $this->prepareMachines();
        $this->prepareGroupes();
        $this->prepareCategories();

        $this->processReportFile($suffix);
    }

    function generate($suffix)
    {
        $runsArray = [];
        if($this->factel < 8) {
            $columns = $this->bilansStats[$this->factel]['cae']['columns'];
            $lines = Csv::extract($this->getFileNameInBS('cae'));
            $stats = [];
            for($i=1;$i<count($lines);$i++) {
                $tab = explode(";", $lines[$i]);
                $machId = $tab[$columns["mach-id"]];
                $itemGrp = $this->machines[$machId]["item-grp"];
                $itemId = $this->groupes[$itemGrp]["item-id-K1"];
                $plateId = $this->categories[$itemId]["platf-code"];
                if($plateId == $this->plateforme) {
                    $mu = ($tab[$columns["Tmach-HP"]] + $tab[$columns["Tmach-HC"]]) / 60;
                    $specials = ["mach022", "mach023", "mach036", "mach145"];
                    if(in_array($machId, $specials)) {
                        $mr = $mu;
                    }
                    else {
                        if(!empty($tab[$columns["staff-note"]]) && str_contains($tab[$columns["staff-note"]], "Before cap")) {
                            $from = strpos($tab[$columns["staff-note"]], "cap:") + 5;
                            $to = strpos($tab[$columns["staff-note"]], "min)") - 1;
                            $mr = substr($tab[$columns["staff-note"]], $from, $to-$from);
                        }
                        else {
                            $mr = $mu;
                        }
                    }
                    if($mr > 0) {
                        if(!in_array($machId, $stats)) {
                            $stats[$machId] = ["nb"=>0, "sum"=>0, "rts"=>[]];
                        }
                        $stats[$machId]["nb"] += 1;
                        $stats[$machId]["sum"] += floatval($mr);
                        $stats[$machId]["rts"][] = $mr;
                    }
                }
            }
            foreach($stats as $id=>$value) {
                $avg = $this->monthAverage($value["sum"], $value["nb"]);
                $stddev = $this->monthStdDev($value["rts"], $avg, $value["nb"]);
                $runsArray[] = [$id, $value["sum"], $value["nb"], $avg, $stddev];
            }
        }
        else {
            $columns = $this->bilansStats[$this->factel]['Stat-m']['columns'];
            $lines = Csv::extract($this->getFileNameInBS('Stat-m'));
            for($i=1;$i<count($lines);$i++) {
                $tab = explode(";", $lines[$i]);
                if($tab[$columns["flow-type"]] == "cae" && $tab[$columns["transac-runtime"]] > 0 && $tab[$columns["item-codeK"]] == "K1") {
                    $runsArray[] = [$tab[$columns["mach-id"]], $tab[$columns["transac-runtime"]], $tab[$columns["runtime-N"]], $tab[$columns["runtime-avg"]], $tab[$columns["runtime-stddev"]]]; 
                }
            }
        }
        for($i=0;$i<count($runsArray);$i++) {
            $runsArray[$i][1] = round($runsArray[$i][1],3);
            $runsArray[$i][3] = round($runsArray[$i][3],3);
            $runsArray[$i][4] = round($runsArray[$i][4],3);
        }
        return $runsArray;
    }

    function masterise($runsArray)
    {
        foreach($runsArray as $line) {
            $machine = $this->machines[$line[0]];
            $groupe = $this->groupes[$machine["item-grp"]];
            $categorie = $this->categories[$groupe["item-id-K1"]];
            $operations = ["transac-runtime"=>$line[1], "runtime-N"=>$line[2], "runtime-avg"=>$line[3], "runtime-stddev"=>$line[4]];
            $keys = ["par-machine"=>$line[0], "par-categorie"=>$groupe["item-id-K1"]];
            $extends = ["par-machine"=>[$machine, $groupe, $categorie], "par-categorie"=>[$groupe, $categorie]];
            $dimensions = ["par-machine"=>[$this::MACHINE_DIM, $this::GROUPE_DIM, $this::CATEGORIE_DIM], "par-categorie"=>[$this::GROUPE_DIM, $this::CATEGORIE_DIM]];

            foreach($keys as $id=>$key) {
                $this->mapping($key, $id, $extends, $dimensions, $operations);
            }
        }
    }

    function mapping($key, $id, $extends, $dimensions, $operations) 
    {
        if(!array_key_exists($key, $this->master[$id])) {
            $this->master[$id][$key] = [];            
            foreach($dimensions[$id] as $pos=>$dimension) {
                foreach($dimension as $d) {
                    $this->master[$id][$key][$d] = $extends[$id][$pos][$d];
                }
            }
            foreach($operations as $op=>$val) {
                $this->master[$id][$key][$op] = [];
            }
        }
        foreach($operations as $op=>$val) {
            $this->master[$id][$key][$op][] = $val;
        }
        return $this->master;
    }


    function display() 
    {
        foreach($this->master as $id=>$keys) {
            foreach($keys as $key=>$cells) {
                $avg = $this->periodAverage($cells["runtime-N"], $cells["runtime-avg"]);
                $stddev = $this->periodStdDev($cells["runtime-N"], $cells["runtime-avg"], $cells["runtime-stddev"], $avg);
                $sum = 0;
                $numTot = 0;
                for($i=0; $i< count($cells["runtime-N"]); $i++) {
                    $sum += $cells["transac-runtime"][$i];
                    $numTot += $cells["runtime-N"][$i];
                }
                $this->master[$id][$key]["transac-runtime"] = $sum;
                $this->master[$id][$key]["runtime-N"] = $numTot;
                $this->master[$id][$key]["runtime-avg"] = $avg;
                $this->master[$id][$key]["runtime-stddev"] = $stddev;
            }
        }
        echo $this->templateDisplay("Statistiques machines sur la période", "", false, ["par-machine" => 'sortMachine', "par-categorie" => 'sortCategorie']);
    }

    static function sortMachine($a, $b) 
    {
        return strcmp($a["mach-name"], $b["mach-name"]);
    }

    static function sortCategorie($a, $b) 
    {
        return strcmp($a["item-name"], $b["item-name"]);
    }
}
