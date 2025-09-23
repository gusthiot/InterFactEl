<?php

/**
 * ReportPlateforme class allows to generate reports about projects and staff
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
        $this->tabs = [
            "par-projet" => [
                "title" => "Stats par Projet",
                "columns" => ["proj-nbr", "proj-name", "item-name", "item-textK"],
                "dimensions" => array_merge($this::PROJET_DIM, $this::GROUPE_DIM, $this::CATEGORIE_DIM, $this::CODEK_DIM),
                "operations" => ["transac-usage"],
                "formats" => ["float"],
                "results" => []
            ]
        ];
    }

    /**
     * prepares dimensions, generates report file if not exists and extracts its data
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
     * generates report file and returns its data
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
            $lines = Csv::extract($this->getFileNameInBS('cae'));
            for($i=1;$i<count($lines);$i++) {
                $tab = explode(";", $lines[$i]);
                $machId = $tab[$columns["mach-id"]];
                $expl = $cptExplArray[$tab[$columns["proj-id"]]];
                $plateId = $this->getPlateformeFromMachine($machId);
                if($plateId && ($plateId == $this->plateforme) && ($tab[$columns["client-code"]] == $plateId) && ($expl["proj-expl"] == "FALSE")) {
                    $mu1 = ($tab[$columns["Tmach-HP"]]+$tab[$columns["Tmach-HC"]]) / 60;
                    $mu2 = $tab[$columns["Toper"]] / 60;
                    $id = $tab[$columns["proj-id"]]."--".$tab[$columns["mach-id"]];
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
            $lines = Csv::extract($this->getFileNameInBS('T3'));
            for($i=1;$i<count($lines);$i++) {
                $tab = explode(";", $lines[$i]);
                $fceCond = ($tab[$columns["flow-type"]] == "cae") && ($tab[$columns["client-code"]] == $tab[$columns["platf-code"]]) && ($tab[$columns["proj-expl"] == "FALSE"]);
                if(floatval($this->factel) < 9) {
                    $cond = ($tab[$columns["platf-code"]] == $this->plateforme) && $fceCond;
                }
                elseif(floatval($this->factel) >= 9 && floatval($this->factel) < 10) {
                $datetime = explode(" ", $tab[$columns["transac-date"]]);
                $parts = explode("-", $datetime[0]);
                    $cond = ($parts[0] == $this->year) && ($parts[1] == $this->month) && ($tab[$columns["platf-code"]] == $this->plateforme) && $fceCond;
                }
                else {
                    $cond = ($tab[$columns["year"]] == $tab[$columns["editing-year"]]) && ($tab[$columns["month"]] == $tab[$columns["editing-month"]]) && $fceCond;
                }
                if($cond) {
                    if(floatval($this->factel) == 7) {
                        $letter = substr($tab[$columns["item-nbr"]], 0, 1);
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
                        $itemK = $tab[$columns["item-codeK"]];
                    }
                    if(floatval($this->factel) < 11) {
                        $machId = $tab[$columns["mach-id"]];
                        if(array_key_exists($machId, $this->machinesGroupes)) {
                            $itemGrp = $this->machinesGroupes[$machId]["item-grp"];
                        }
                        else {
                            $itemGrp = "0";
                        }
                    }
                    else {
                        $itemGrp = $tab[$columns["item-grp"]];
                    }
                    $id = $tab[$columns["proj-id"]]."--".$itemGrp."--".$itemK;
                    if(!array_key_exists($id, $loopArray)) {
                        $loopArray[$id] = 0;
                    }
                    $loopArray[$id] += $tab[$columns["transac-usage"]];
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
     * maps report data for tabs tables and csv 
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
            $extends = [$projet, $groupe, $categorie, $codeK];
            $dimensions = [$this::PROJET_DIM, $this::GROUPE_DIM, $this::CATEGORIE_DIM, $this::CODEK_DIM];
            $id = $line[0]."-".$line[2]; 

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
        }
    }

    /**
     * displays title and tabs
     *
     * @return void
     */
    function display(): void
    {
        $title = '<div class="total">Statistiques plateforme : '.$this->period().' </div>';
        echo $this->templateDisplay($title);
    }

}
