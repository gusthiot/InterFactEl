<?php

/**
 * ReportPlateforme class allows to generate reports about projects
 */
class ReportPlateforme extends Report
{
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
        $this->reportKey = 'statpltf';
        $this->reportColumns = ["proj-id", "item-grp", "item-codeK", "transac-usage"];
        $this->totalCsvData = [
            "dimensions" => array_merge($this::PROJET_DIM, $this::GROUPE_DIM, $this::CATEGORIE_DIM, $this::CODEK_DIM),
            "operations" => ["transac-usage"],
            "results" => []
        ];
        $this->tabs = [
            "par-projet" => [
                "title" => "Stats par Projet",
                "columns" => ["proj-nbr", "proj-name", "item-name"],
                "dimensions" => array_merge($this::PROJET_DIM, $this::CATEGORIE_DIM),
                "operations" => ["transac-usage"],
                "formats" => ["float"],
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
        $this->loadCategories();
        $this->loadGroupes();
        $this->prepareMachines();
        $this->loadMachinesGroupes();
        $this->prepareComptes();

        $this->processReportFile();
    }

    /**
     * Generates report file and returns its data
     *
     * @return array
     */
    function generate(): array
    {
        $cptExplArray = [];
        if(floatval($this->factel) < 7) {
            self::mergeInCsv('cptexpl', $cptExplArray, self::PROJET_KEY);
        }

        $pltfArray = [];
        $loopArray = [];

        if(floatval($this->factel) < 7) {
            $columns = $this->bilansStats->getColumns($this->factel, 'cae');
            $lines = Csv::extract($this->getFileNameInBS('cae'), true);
            foreach($lines as $line) {
                $machId = $line[$columns["mach-id"]];
                $expl = $cptExplArray[$line[$columns["proj-id"]]];
                $plateId = $this->getPlateformeFromMachine($machId);
                if($plateId && ($plateId == $this->plateforme) && ($line[$columns["client-code"]] == $plateId) && ($expl["proj-expl"] == "FALSE")) {
                    $mu1 = ($line[$columns["Tmach-HP"]]+$line[$columns["Tmach-HC"]]) / 60;
                    $mu2 = $line[$columns["Toper"]] / 60;
                    $id = $line[$columns["proj-id"]]."--".$line[$columns["mach-id"]];
                    if(!array_key_exists($id, $loopArray)) {
                        $loopArray[$id] = [0, 0];
                    }
                    $loopArray[$id][0] += $mu1;
                    $loopArray[$id][1] += $mu2;
                }
            }
            foreach($loopArray as $id=>$mu) {
                $ids = explode("--", $id);
                $itemGrp = $this->machinesGroupes[$ids[1]]["item-grp"];
                if($mu[0] > 0) {
                    $pltfArray[] = [$ids[0], $itemGrp, "K1", $mu[0]];
                }
                if($mu[1] > 0) {
                    $pltfArray[] = [$ids[0], $itemGrp, "K2", $mu[1]];
                }
            }
        }
        else {
            $columns = $this->bilansStats->getColumns($this->factel, 'T3');
            $lines = Csv::extract($this->getFileNameInBS('T3'), true);
            foreach($lines as $line) {
                $fceCond = ($line[$columns["flow-type"]] == "cae") && ($line[$columns["client-code"]] == $line[$columns["platf-code"]]) && ($line[$columns["proj-expl"] == "FALSE"]);
                if(floatval($this->factel) < 9) {
                    $cond = ($line[$columns["platf-code"]] == $this->plateforme) && $fceCond;
                }
                elseif(floatval($this->factel) >= 9 && floatval($this->factel) < 10) {
                $datetime = explode(" ", $line[$columns["transac-date"]]);
                $parts = explode("-", $datetime[0]);
                    $cond = ($parts[0] == $this->year) && ($parts[1] == $this->month) && ($line[$columns["platf-code"]] == $this->plateforme) && $fceCond;
                }
                else {
                    $cond = ($line[$columns["year"]] == $line[$columns["editing-year"]]) && ($line[$columns["month"]] == $line[$columns["editing-month"]]) && $fceCond;
                }
                if($cond) {
                    if(floatval($this->factel) == 7) {
                        $letter = substr($line[$columns["item-nbr"]], 0, 1);
                        switch($letter) {
                            case "E":
                                $itemK = "K1";
                                break;
                            case "S":
                                $itemK = "K2";
                                break;
                            case "U":
                                $itemK = "K3";
                                break;
                            case "X":
                                $itemK = "K4";
                                break;
                        }
                    }
                    else {
                        $itemK = $line[$columns["item-codeK"]];
                    }
                    if(floatval($this->factel) < 11) {
                        $machId = $line[$columns["mach-id"]];
                        if(array_key_exists($machId, $this->machinesGroupes)) {
                            $itemGrp = $this->machinesGroupes[$machId]["item-grp"];
                        }
                        else {
                            $itemGrp = "0";
                        }
                    }
                    else {
                        $itemGrp = $line[$columns["item-grp"]];
                    }
                    $id = $line[$columns["proj-id"]]."--".$itemGrp."--".$itemK;
                    if(!array_key_exists($id, $loopArray)) {
                        $loopArray[$id] = 0;
                    }
                    $loopArray[$id] += $line[$columns["transac-usage"]];
                }
            }
            foreach($loopArray as $id=>$mu) {
                $ids = explode("--", $id);
                $pltfArray[] = [$ids[0], $ids[1], $ids[2], $mu];
            }
        }
        return $pltfArray;
    }

    /**
     * Maps report data for tabs tables and csv
     *
     * @param array $pltfArray report data
     * @return void
     */
    function mapping(array $pltfArray): void
    {
        foreach($pltfArray as $line) {
            $projet = $this->comptes[$line[0]];
            if(floatval($this->factel) < 7) {
                if(!array_key_exists("proj-nbr", $projet)) {
                    $projet["proj-nbr"] = "";
                }
            }
            $groupe = $this->groupes[$line[1]];
            $itemId = $groupe["item-id-".$line[2]];
            $categorie = $this->categories[$itemId];
            $codeK = ["item-codeK"=>$line[2], "item-textK"=>$this->paramtext->getParam("item-".$line[2])];
            $extends = [$projet, $categorie];
            $dimensions = [$this::PROJET_DIM, $this::CATEGORIE_DIM];
            $extends_glob = [$projet, $groupe, $categorie, $codeK];
            $dimensions_glob = [$this::PROJET_DIM, $this::GROUPE_DIM, $this::CATEGORIE_DIM, $this::CODEK_DIM];
            $id = $line[0]."-".$line[2]."-".$itemId;
            $id_glob = $line[0]."-".$line[2]."-".$line[1]."-".$itemId;

            if(!array_key_exists($id, $this->tabs["par-projet"]["results"])) {
                $this->tabs["par-projet"]["results"][$id] = [];
                foreach($dimensions as $pos=>$dimension) {
                    foreach($dimension as $d) {
                        $this->tabs["par-projet"]["results"][$id][$d] = $extends[$pos][$d];
                    }
                }
                foreach($this->tabs["par-projet"]["operations"] as $operation) {
                    $this->tabs["par-projet"]["results"][$id][$operation] = 0;
                }
            }
            $this->tabs["par-projet"]["results"][$id]["transac-usage"] += $line[3];
            // total csv
            if(!array_key_exists($id_glob, $this->totalCsvData["results"])) {
                $this->totalCsvData["results"][$id_glob] = ["transac-usage" => 0];
                foreach($dimensions_glob as $pos=>$dimension) {
                    foreach($dimension as $d) {
                        $this->totalCsvData["results"][$id_glob][$d] = $extends_glob[$pos][$d];
                    }
                }
            }
            $this->totalCsvData["results"][$id_glob]["transac-usage"] += $line[3];
        }
    }

    /**
     * Displays title and tabs
     *
     * @return void
     */
    function display(): void
    {
        $title = '<div class="total">Statistiques plateforme : '.$this->period().' </div>';
        $title .= $this->totalCsvLink("total-plateforme", "transac-usage");
        echo $this->templateDisplay($title);
    }

}
