<?php

/**
 * ReportRuns class allows to generate reports about machines and categories stats for runs and hours
 */
class ReportRuns extends Report
{
    /**
     * Total of hours
     *
     * @var float
     */
    private float $totalM;

    /**
     * Total of runs
     *
     * @var integer
     */
    private int $totalN;

    /**
     * To load dimensions only from the last month
     *
     * @var boolean
     */
    private bool $first;

    /**
     * Dimensions only from the last month
     */
    private array $firstGroupes;
    private array $firstMachinesGroupes;
    private array $firstCategories;

    /**
     * Class constructor
     *
     * @param string $plateforme reports for this given plateform
     * @param string $to last month of the period
     * @param string $from first month of the period
     */
    function __construct(string $plateforme, string $to, string $from)
    { 
        parent::__construct($plateforme, $to, $from);
        $this->totalM = 0.0;
        $this->totalN = 0;
        $this->firstMachinesGroupes = [];
        $this->firstGroupes = [];
        $this->firstCategories = [];
        $this->first = true;
        $this->reportKey = 'statmach';
        $this->reportColumns = ["mach-id", "transac-runtime", "runtime-N", "runtime-avg", "runtime-stddev"];
        $this->tabs = [
            "par-machine" => [
                "title" => "Stats par Machine",
                "columns" => ["mach-name", "item-nbr", "item-name", "item-unit"],
                "dimensions" => array_merge($this::MACHINE_DIM, $this::GROUPE_DIM, $this::CATEGORIE_DIM),
                "operations" => ["transac-runtime", "runtime-N", "runtime-avg", "runtime-stddev"],
                "formats" => ["float", "int", "float", "float"],
                "results" => []

            ], 
            "par-categorie"=>[
                "title" => "Stats par Catégorie",
                "columns" => ["item-name"],
                "dimensions" => array_merge($this::GROUPE_DIM, $this::CATEGORIE_DIM),
                "operations" => ["transac-runtime", "runtime-N", "runtime-avg", "runtime-stddev"],
                "formats" => ["float", "int", "float", "float"],
                "results" => []
            ]
        ];
    }

    /**
     * Prepares dimensions, generates report file if not exists and extracts its data
     *
     * @return void
     */
    function prepare(): void 
    {
        $this->prepareMachines();
        $this->loadCategories();
        $this->loadGroupes();
        $this->loadMachinesGroupes();
        if($this->first) {
            self::mergeInCsv('categorie', $this->firstCategories, self::CATEGORIE_KEY);
            self::mergeInCsv('groupe', $this->firstGroupes, self::GROUPE_KEY);
            if(floatval($this->factel) < 7) {
                self::mergeInCsv('machgrp', $this->firstMachinesGroupes, self::MACHINE_KEY);
            }
            else {
                self::mergeInCsv('machine', $this->firstMachinesGroupes, self::MACHINE_KEY);
            }
            $this->first = false;
        }

        $this->processReportFile();
    }

    /**
     * Generates report file and returns its data
     *
     * @return array
     */
    function generate(): array
    {
        $runsArray = [];
        if(floatval($this->factel) < 8) {
            $columns = $this->bilansStats->getColumns($this->factel, 'cae');
            $lines = Csv::extract($this->getFileNameInBS('cae'));
            $stats = [];
            for($i=1;$i<count($lines);$i++) {
                $tab = explode(";", $lines[$i]);
                $machId = $tab[$columns["mach-id"]];
                $plateId = $this->getPlateformeFromMachine($machId);
                if($plateId && ($plateId == $this->plateforme)) {
                    $mu = ($tab[$columns["Tmach-HP"]] + $tab[$columns["Tmach-HC"]]) / 60;
                    $specials = ["mach022", "mach023", "mach036", "mach145"];
                    if(in_array($machId, $specials)) {
                        $mr = $mu;
                    }
                    else {
                        if(!empty($tab[$columns["staff-note"]]) && str_contains($tab[$columns["staff-note"]], "Before cap")) {
                            $from = strpos($tab[$columns["staff-note"]], "cap:") + 5;
                            $to = strpos($tab[$columns["staff-note"]], "min)") - 1;
                            $mr = floatval(substr($tab[$columns["staff-note"]], $from, $to-$from)) / 60;
                        }
                        else {
                            $mr = $mu;
                        }
                    }
                    if($mr > 0) {
                        if(!array_key_exists($machId, $stats)) {
                            $stats[$machId] = ["nb"=>0, "sum"=>0, "rts"=>[]];
                        }
                        $stats[$machId]["nb"] ++;
                        $stats[$machId]["sum"] += floatval($mr);
                        $stats[$machId]["rts"][] = $mr;
                    }
                }
            }
            foreach($stats as $id=>$value) {
                $avg = $this->monthAverage($value["sum"], $value["nb"]);
                $stddev = $this->monthStdDev($value["rts"], $avg, $value["nb"]);
                $runsArray[] = [$id, round($value["sum"], 3), $value["nb"], round($avg, 3), round($stddev, 3)];
            }
        }
        else {
            $columns = $this->bilansStats->getColumns($this->factel, 'Stat-m');
            $lines = Csv::extract($this->getFileNameInBS('Stat-m'));
            for($i=1;$i<count($lines);$i++) {
                $tab = explode(";", $lines[$i]);
                if($tab[$columns["flow-type"]] == "cae" && $tab[$columns["transac-runtime"]] > 0 && $tab[$columns["item-codeK"]] == "K1") {
                    $runsArray[] = [$tab[$columns["mach-id"]], round($tab[$columns["transac-runtime"]], 3), $tab[$columns["runtime-N"]], round($tab[$columns["runtime-avg"]], 3), round($tab[$columns["runtime-stddev"]], 3)]; 
                }
            }
        }
        return $runsArray;
    }

    /**
     * Returns the categorie concerned by a grouped machine for an item K or empty array for the last month
     *
     * @param string $machId machine id
     * @param string $itemK item K
     * @return array
     */
    function getFirstCategorieFromMachine(string $machId, string $itemK): array
    {
        $itemGrp = $this->firstMachinesGroupes[$machId]["item-grp"];
        if($itemGrp != "0") {
            $itemId = $this->firstGroupes[$itemGrp]["item-id-".$itemK];
            if($itemId != "0") {
                return $this->firstCategories[$itemId];
            }
        }
        return [];
    }

    /**
     * Maps report data for tabs tables and csv 
     *
     * @param array $runsArray report data
     * @return void
     */
    function mapping(array $runsArray): void
    {
        foreach($runsArray as $line) {
            $machine = $this->machines[$line[0]];
            $items = ["item-nbr"=>"0", "item-name"=>"0", "item-unit"=>"0"];
            $itemGrp = "0";
            if(array_key_exists($line[0], $this->firstMachinesGroupes)) {
                $itemGrp = $this->firstMachinesGroupes[$line[0]]["item-grp"];
                $categorie = $this->getFirstCategorieFromMachine($line[0], "K1");
                if(!empty($categorie)) {
                    $items = ["item-nbr"=>$categorie["item-nbr"], "item-name"=>$categorie["item-name"], "item-unit"=>$categorie["item-unit"]];
                }
            }
            $values = [
                "transac-runtime"=>$line[1], 
                "runtime-N"=>$line[2], 
                "runtime-avg"=>$line[3], 
                "runtime-stddev"=>$line[4]
            ];
            $ids = [
                "par-machine"=>$line[0], 
                "par-categorie"=>$itemGrp
            ];
            $extends = [
                "par-machine"=>[$machine, ["item-grp"=>$itemGrp], $items],
                "par-categorie"=>[["item-grp"=>$itemGrp], $items]
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

    /**
     * Returns average for the month
     *
     * @param float $sum hours sum for the month
     * @param integer $num runs number for the month
     * @return float
     */
    function monthAverage(float $sum, int $num): float
    {
        if($num == 0) {
            return 0.0;
        }
        return $sum / $num;
    }

    /**
     * Returns standard deviation for the month
     *
     * @param array $values values array for the month
     * @param float $avg average value for the month
     * @param integer $num runs number for the month
     * @return float
     */
    function monthStdDev(array $values, float $avg, int $num): float
    {
        $sum = 0;
        foreach($values as $value) {
            $sum += pow($value-$avg, 2);
        }
        if($num == 0 || $sum == 0) {
            return 0;
        }
        return sqrt(1 / $num * $sum);
    }

    /**
     * Returns average for the period
     *
     * @param array $nums array of months runs numbers
     * @param array $avgs array of months average values
     * @return float
     */
    function periodAverage(array $nums, array $avgs): float
    {
        $sum = 0;
        $numTot = 0;
        for($i=0; $i< count($nums); $i++) {
            $sum += $nums[$i]*$avgs[$i];
            $numTot += $nums[$i];
        }
        if($numTot == 0) {
            return 0;
        }
        return $sum / $numTot;
    }

    /**
     * Returns standard deviation for the period
     *
     * @param array $nums array of months runs numbers
     * @param array $avgs array of months average values
     * @param array $stddevs array of months standard deviation values
     * @param float $pAvg average value for the period
     * @return void
     */
    function periodStdDev(array $nums, array $avgs, array $stddevs, float $pAvg)
    {
        $sum = 0;
        $numTot = 0;
        for($i=0; $i< count($nums); $i++) {
            $sum += $nums[$i]*(pow($stddevs[$i], 2) + pow($avgs[$i]-$pAvg, 2));
            $numTot += $nums[$i];
        }
        if($numTot == 0 || $sum == 0) {
            return 0;
        }
        return sqrt(1 / $numTot * $sum);
    }

    /**
     * Displays title and tabs
     *
     * @return void
     */
    function display(): void
    {
        $doTotal = true;
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
                if($doTotal) {
                    $this->totalM += $sum;
                    $this->totalN += $numTot;
                }
            }
            $doTotal = false;
        }

        $title = '<div class="total">Statistiques machines : '.$this->period().' </div>';
        $title .= '<div class="subtotal">Nombre d’heures productives = '.$this->format($this->totalM, "float").'</div>';
        $title .= '<div class="subtotal">Nombre de runs productifs (temps machine > 0) = '.$this->format($this->totalN, "int").'</div>';
        $title .= '<div>
                        <svg class="icon red" aria-hidden="true">
                            <use xlink:href="#alert-triangle"></use>
                        </svg>
                        Les catégories de machines, et la répartition de machines dans les catégories sont définies 
                        par le dernier mois de la période : '.substr($this->to, 4, 2)."/".substr($this->to, 0, 4).'
                        <svg class="icon red" aria-hidden="true">
                            <use xlink:href="#alert-triangle"></use>
                        </svg>
                    
                    </div>';
        echo $this->templateDisplay($title);
    }
}
