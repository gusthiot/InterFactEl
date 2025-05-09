<?php

class ReportRuns extends Report
{
    private $totalM;
    private $totalN;

    public function __construct($plateforme, $to, $from) 
    { 
        parent::__construct($plateforme, $to, $from);
        $this->totalM = 0;
        $this->totalN = 0;
        $this->reportKey = 'statmach';
        $this->reportColumns = ["mach-id", "transac-runtime", "runtime-N", "runtime-avg", "runtime-stddev"];
        $this->tabs = [
            "par-machine" => [
                "title" => "Stats par Machine",
                "columns" => ["mach-name"],
                "dimensions" => array_merge($this::MACHINE_DIM, $this::GROUPE_DIM, $this::CATEGORIE_DIM),
                "operations" => ["transac-runtime", "runtime-N", "runtime-avg", "runtime-stddev"],
                "results" => []

            ], 
            "par-categorie"=>[
                "title" => "Stats par Catégorie",
                "columns" => ["item-name"],
                "dimensions" => array_merge($this::GROUPE_DIM, $this::CATEGORIE_DIM),
                "operations" => ["transac-runtime", "runtime-N", "runtime-avg", "runtime-stddev"],
                "results" => []
            ]
        ];
    }

    function prepare() 
    {
        $this->prepareMachines();
        $this->prepareGroupes();
        $this->prepareCategories();

        $this->processReportFile();
    }

    function generate()
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

    function mapping($runsArray)
    {
        foreach($runsArray as $line) {
            $machine = $this->machines[$line[0]];
            $groupe = $this->groupes[$machine["item-grp"]];
            $categorie = $this->categories[$groupe["item-id-K1"]];
            $values = [
                "transac-runtime"=>$line[1], 
                "runtime-N"=>$line[2], 
                "runtime-avg"=>$line[3], 
                "runtime-stddev"=>$line[4]
            ];
            $ids = [
                "par-machine"=>$line[0], 
                "par-categorie"=>$groupe["item-id-K1"]
            ];
            $extends = [
                "par-machine"=>[$machine, $groupe, $categorie],
                "par-categorie"=>[$groupe, $categorie]
            ];
            $dimensions = [
                "par-machine"=>[$this::MACHINE_DIM, $this::GROUPE_DIM, $this::CATEGORIE_DIM], 
                "par-categorie"=>[$this::GROUPE_DIM, $this::CATEGORIE_DIM]
            ];

            foreach($this->tabs as $tab=>$data) {
                if(!array_key_exists($ids[$tab], $this->tabs[$tab]["results"])) {
                    $this->tabs[$tab]["results"][$ids[$tab]] = [];            
                    foreach($dimensions[$tab] as $pos=>$dimension) {
                        foreach($dimension as $d) {
                            $this->tabs[$tab]["results"][$ids[$tab]][$d] = $extends[$tab][$pos][$d];
                        }
                    }
                    foreach($this->tabs[$tab]["operations"] as $operation) {
                        $this->tabs[$tab]["results"][$ids[$tab]][$operation] = [];
                    }
                }
                foreach($values as $operation=>$value) {
                    $this->tabs[$tab]["results"][$ids[$tab]][$operation][] = $value;
                }
            }
        }
    }

    function display() 
    {
        foreach($this->tabs as $tab=>$data) {
            foreach($data["results"] as $key=>$cells) {
                $avg = $this->periodAverage($cells["runtime-N"], $cells["runtime-avg"]);
                $stddev = $this->periodStdDev($cells["runtime-N"], $cells["runtime-avg"], $cells["runtime-stddev"], $avg);
                $sum = 0;
                $numTot = 0;
                for($i=0; $i< count($cells["runtime-N"]); $i++) {
                    $sum += $cells["transac-runtime"][$i];
                    $numTot += $cells["runtime-N"][$i];
                }
                $this->tabs[$tab]["results"][$key]["transac-runtime"] = $sum;
                $this->tabs[$tab]["results"][$key]["runtime-N"] = $numTot;
                $this->tabs[$tab]["results"][$key]["runtime-avg"] = $avg;
                $this->tabs[$tab]["results"][$key]["runtime-stddev"] = $stddev;
                $this->totalM += $sum;
                $this->totalN += $numTot;
            }
        }

        $title = '<div class="total">Statistiques machines : '.$this->period().' </div>';
        $title .= '<div class="subtotal">Nombre d’heures productives = '.$this->totalM.'</div>';
        $title .= '<div class="subtotal">Nombre de runs productifs (temps machine > 0) = '.$this->totalN.'</div>';
        echo $this->templateDisplay($title, false);
    }
}
