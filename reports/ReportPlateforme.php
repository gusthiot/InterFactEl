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
        $this->reportKey = ['statpltf', 'statoper'];
        $this->reportColumns = [
            "statpltf" => ["proj-id", "item-grp", "item-codeK", "transac-usage"],
            "statoper" => ["oper-sciper", "date", "flow-type", "transac-usage"]
        ];
        $this->tabs = [
            "par-projet" => [
                "title" => "Stats par Projet",
                "columns" => ["proj-nbr", "proj-name", "item-name", "item-textK"],
                "dimensions" => array_merge($this::PROJET_DIM, $this::GROUPE_DIM, $this::CATEGORIE_DIM, $this::CODEK_DIM),
                "operations" => ["transac-usage"],
                "formats" => ["float"],
                "results" => []
            ],
            "par-staff" => [
                "title" => "Heures opérateurs par Staff",
                "columns" => ["oper-name", "oper-first", "date", "flow-type"],
                "dimensions" => $this::OPER_DIM,
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
        $this->prepareUsers();
        $this->prepareMachines();
        $this->loadGroupes();
        $this->loadMachinesGroupes();
        $this->prepareComptes();

        $this->processReportFile();
    }

    /**
     * generates report file defined by its key and returns its data
     *
     * @param string $key report key
     * @return array
     */
    function generate(string $key): array 
    {
        if($key == "statoper") {
            return $this->generateOper();
        }
        if($key == "statpltf") {
            return $this->generatePltf();
        }
    }

    /**
     * generates report file for staff and returns its data
     *
     * @return array
     */
    function generateOper(): array
    {
        $loopArray = [];

        if(floatval($this->factel) < 7) {
            $columns = $this->bilansStats->getColumns($this->factel, 'cae');
            $lines = Csv::extract($this->getFileNameInBS('cae'));
            for($i=1;$i<count($lines);$i++) {
                $tab = explode(";", $lines[$i]);
                $machId = $tab[$columns["mach-id"]];
                $plateId = $this->getPlateformeFromMachine($machId);
                if($plateId && ($plateId == $this->plateforme)) {
                    $mu2 = $tab[$columns["Toper"]] / 60;
                    $datetime = explode(" ", $tab[$columns["transac-date"]]);
                    $id = $tab[$columns["oper-id"]]."--".$datetime[0]."--cae";
                    if(!array_key_exists($id, $loopArray)) {
                        $loopArray[$id] = 0;
                    }
                    $loopArray[$id] += $mu2;
                }
            }
        }
        else {
            $columns = $this->bilansStats->getColumns($this->factel, 'T3');
            $lines = Csv::extract($this->getFileNameInBS('T3'));
            for($i=1;$i<count($lines);$i++) {
                $tab = explode(";", $lines[$i]);
                if(floatval($this->factel) == 7) {
                    $letterK = substr($tab[$columns["item-nbr"]], 0, 1);
                    $cond = ($this->plateforme == $tab[$columns["platf-code"]]) && ($tab[$columns["flow-type"]] == "cae") && ($letterK == "S"); // S => K2 
                }
                elseif(floatval($this->factel) >= 8 && floatval($this->factel) < 10) {
                    $cond = ($this->plateforme == $tab[$columns["platf-code"]]) && ($tab[$columns["flow-type"]] == "cae") && ($tab[$columns["item-codeK"]] == "K2");
                }
                elseif(floatval($this->factel) == 10) {
                    $cond = ($tab[$columns["year"]] == $tab[$columns["editing-year"]]) && ($tab[$columns["month"]] == $tab[$columns["editing-month"]]) && ($tab[$columns["flow-type"]] == "cae") && ($tab[$columns["item-codeK"]] == "K2");
                }
                else {
                    $cond = ($tab[$columns["year"]] == $tab[$columns["editing-year"]]) && ($tab[$columns["month"]] == $tab[$columns["editing-month"]]) && (in_array($tab[$columns["flow-type"]], ["cae", "srv"])) && ($tab[$columns["item-codeK"]] == "K2");
                }
                if($cond) {
                    $datetime = explode(" ", $tab[$columns["transac-date"]]);
                    $id = $tab[$columns["oper-id"]]."--".$datetime[0]."--".$tab[$columns["flow-type"]];
                    if(!array_key_exists($id, $loopArray)) {
                        $loopArray[$id] = 0;
                    }
                    $loopArray[$id] += $tab[$columns["transac-usage"]];
                }
            }
        }
        $operArray = [];
        foreach($loopArray as $id=>$q) {
            $ids = explode("--", $id);
            $operArray[] = [$this->sciper($ids[0]), $ids[1], $ids[2], $q];
        }
        return $operArray;
    }

    /**
     * generates report file for projects and returns its data
     *
     * @return array
     */
    function generatePltf(): array
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
                if($plateId && ($plateId == $this->plateforme) && ($tab[$columns["client-code"]] == $plateId) && ($expl == "FALSE")) {
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
                $pltfArray[] = [$ids[0], $itemGrp, "K1", $mu[0]];
                $pltfArray[] = [$ids[0], $itemGrp, "K2", $mu[1]];
            }
        }
        else {
            $columns = $this->bilansStats->getColumns($this->factel, 'T3');
            $lines = Csv::extract($this->getFileNameInBS('T3'));
            for($i=1;$i<count($lines);$i++) {
                $tab = explode(";", $lines[$i]);
                $fceCond = ($tab[$columns["flow-type"]] == "cae") && ($tab[$columns["client-code"]] == $tab[$columns["platf-code"]]) && ($tab[$columns["proj-expl"] == "FALSE"]);
                if(floatval($this->factel) < 10) {
                    $cond = ($tab[$columns["platf-code"]] == $this->plateforme) && $fceCond;
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
     * maps report data defined by its key for tabs tables and csv 
     *
     * @param array $monthArray report data
     * @param string $key report key
     * @return void
     */
    function mapping(array $monthArray, string $key): void
    {
        if($key == "statoper") {
            $this->mappingOper($monthArray);
        }
        if($key == "statpltf") {
            $this->mappingPltf($monthArray);
        }
    }

    /**
     * maps report data for staff for tabs tables and csv 
     *
     * @param array $operArray report data
     * @return void
     */
    function mappingOper(array $operArray): void
    {   
        $scipers = $this->scipers();
        foreach($operArray as $line) {
            if($line[0] != 0) {
                $user = $this->users[$scipers[$line[0]]];
            }
            else {
                $user = ["user-sciper"=>"0", "user-name"=>"", "user-first"=>"", "user-email"=>""];
            }
            $oper = ["oper-sciper"=>$user["user-sciper"], "oper-name"=>$user["user-name"], "oper-first"=>$user["user-first"], "date"=>$line[1], "flow-type"=>$line[2]];

            if(!array_key_exists($line[0], $this->tabs["par-staff"]["results"])) {
                $this->tabs["par-staff"]["results"][$line[0]] = [];
                foreach($this::OPER_DIM as $d) {
                    $this->tabs["par-staff"]["results"][$line[0]][$d] = $oper[$d];
                }
                foreach($this->tabs["par-staff"]["operations"] as $operation) {
                    $this->tabs["par-staff"]["results"][$line[0]][$operation] = 0;
                }
            } 
            $this->tabs["par-staff"]["results"][$line[0]]["transac-usage"] += $line[3];
        }
    }

    /**
     * maps report data for projects for tabs tables and csv 
     *
     * @param array $pltfArray report data
     * @return void
     */
    function mappingPltf(array $pltfArray): void
    {   
        foreach($pltfArray as $line) {
            $projet = $this->comptes[$line[0]];
            $groupe = $this->groupes[$line[1]];
            $itemId = $groupe["item-id-".$line[2]];
            $categorie = $this->categories[$itemId];
            $codeK = ["item-codeK"=>$line[2], "item-textK"=>$this->paramtext->getParam("item-".$line[2])];
            $extends = [$projet, $groupe, $categorie, $codeK];
            $dimensions = [$this::PROJET_DIM, $this::GROUPE_DIM, $this::CATEGORIE_DIM, $this::CODEK_DIM];

            if(!array_key_exists($line[0], $this->tabs["par-projet"]["results"])) {
                $this->tabs["par-projet"]["results"][$line[0]] = [];
                foreach($dimensions as $pos=>$dimension) {
                    foreach($dimension as $d) {
                        $this->tabs["par-projet"]["results"][$line[0]][$d] = $extends[$pos][$d];
                    }
                }
                foreach($this->tabs["par-projet"]["operations"] as $operation) {
                    $this->tabs["par-projet"]["results"][$line[0]][$operation] = 0;
                }
            }
            $this->tabs["par-projet"]["results"][$line[0]]["transac-usage"] += $line[3];
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
