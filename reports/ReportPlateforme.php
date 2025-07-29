<?php

class ReportPlateforme extends Report
{
        
    public function __construct($plateforme, $to, $from) 
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

    function prepare() {
        $this->loadCategories();
        $this->loadGroupes();
        $this->prepareUsers();
        $this->prepareMachines();
        $this->prepareComptes();

        $this->processReportFile();
    }

    function generate($key) 
    {
        if($key == "statoper") {
            return $this->generateOper();
        }
        if($key == "statpltf") {
            return $this->generatePltf();
        }
    }

    function generateOper()
    {
        $operArray = [];
        $loopArray = [];

        if($this->factel < 7) {
            $columns = $this->bilansStats[$this->factel]['cae']['columns'];
            $lines = Csv::extract($this->getFileNameInBS('cae'));
            for($i=1;$i<count($lines);$i++) {
                $tab = explode(";", $lines[$i]);
                $machId = $tab[$columns["mach-id"]];
                if(array_key_exists($machId, $this->machines)) {
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
        elseif($this->factel == 7) {
            $columns = $this->bilansStats[$this->factel]['T3']['columns'];
            $lines = Csv::extract($this->getFileNameInBS('T3'));
            for($i=1;$i<count($lines);$i++) {
                $tab = explode(";", $lines[$i]);
                $letterK = substr($tab[$columns["item-nbr"]], 0, 1);
                if(($this->plateforme == $tab[$columns["platf-code"]]) && ($tab[$columns["flow-type"]] == "cae") && ($letterK == "S")) { // S => K2
                    $datetime = explode(" ", $tab[$columns["transac-date"]]);
                    $id = $tab[$columns["oper-id"]]."--".$datetime[0]."--cae";
                    if(!array_key_exists($id, $loopArray)) {
                        $loopArray[$id] = 0;
                    }
                    $loopArray[$id] += $tab[$columns["transac-usage"]];
                }
            }
        }
        elseif($this->factel >= 8 && $this->factel < 10) {
            $columns = $this->bilansStats[$this->factel]['T3']['columns'];
            $lines = Csv::extract($this->getFileNameInBS('T3'));
            for($i=1;$i<count($lines);$i++) {
                $tab = explode(";", $lines[$i]);
                if(($this->plateforme == $tab[$columns["platf-code"]]) && ($tab[$columns["flow-type"]] == "cae") && ($tab[$columns["item-codeK"]] == "K2")) {
                    $datetime = explode(" ", $tab[$columns["transac-date"]]);
                    $id = $tab[$columns["oper-id"]]."--".$datetime[0]."--cae";
                    if(!array_key_exists($id, $loopArray)) {
                        $loopArray[$id] = 0;
                    }
                    $loopArray[$id] += $tab[$columns["transac-usage"]];
                }
            }
        }
        elseif($this->factel == 10) {
            $columns = $this->bilansStats[$this->factel]['T3']['columns'];
            $lines = Csv::extract($this->getFileNameInBS('T3'));
            for($i=1;$i<count($lines);$i++) {
                $tab = explode(";", $lines[$i]);
                if(($tab[$columns["year"]] == $tab[$columns["editing-year"]]) && ($tab[$columns["month"]] == $tab[$columns["editing-month"]]) && ($tab[$columns["flow-type"]] == "cae") && ($tab[$columns["item-codeK"]] == "K2")) {
                    $datetime = explode(" ", $tab[$columns["transac-date"]]);
                    $id = $tab[$columns["oper-id"]]."--".$datetime[0]."--cae";
                    if(!array_key_exists($id, $loopArray)) {
                        $loopArray[$id] = 0;
                    }
                    $loopArray[$id] += $tab[$columns["transac-usage"]];
                }
            }
        }
        else {
            $columns = $this->bilansStats[$this->factel]['T3']['columns'];
            $lines = Csv::extract($this->getFileNameInBS('T3'));
            for($i=1;$i<count($lines);$i++) {
                $tab = explode(";", $lines[$i]);
                if(($tab[$columns["year"]] == $tab[$columns["editing-year"]]) && ($tab[$columns["month"]] == $tab[$columns["editing-month"]]) && (in_array($tab[$columns["flow-type"]], ["cae", "srv"])) && ($tab[$columns["item-codeK"]] == "K2")) {
                    $datetime = explode(" ", $tab[$columns["transac-date"]]);
                    $id = $tab[$columns["oper-id"]]."--".$datetime[0]."--".$tab[$columns["flow-type"]];
                    if(!array_key_exists($id, $loopArray)) {
                        $loopArray[$id] = 0;
                    }
                    $loopArray[$id] += $tab[$columns["transac-usage"]];
                }
            }
        }
        foreach($loopArray as $id=>$q) {
            $ids = explode("--", $id);
            $sciper = 0;
            $ids[0] == 0 ? $sciper = 0 : $sciper = $this->users[$ids[0]]['user-sciper'];
            $operArray[] = [$sciper, $ids[1], $ids[2], $q];
        }
        return $operArray;
    }

    function generatePltf()
    {
        $cptExplArray = [];
        if($this->factel < 7) {
            self::mergeInCsv('cptexpl', $cptExplArray, self::PROJET_KEY);
        }
        
        $pltfArray = [];
        $loopArray = [];

        if($this->factel < 7) {
            $columns = $this->bilansStats[$this->factel]['cae']['columns'];
            $lines = Csv::extract($this->getFileNameInBS('cae'));
            for($i=1;$i<count($lines);$i++) {
                $tab = explode(";", $lines[$i]);
                $machId = $tab[$columns["mach-id"]];
                if(array_key_exists($machId, $this->machines)) {
                    $itemGrp = $this->machines[$machId]["item-grp"];
                    $itemId = $this->groupes[$itemGrp]["item-id-K1"];
                    $expl = $cptExplArray[$tab[$columns["proj-id"]]];
                    if(($tab[$columns["client-code"]] == $this->categories[$itemId]["platf-code"]) && ($expl == "FALSE")) {
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
            }
            foreach($loopArray as $id=>$mu) {
                $ids = explode("--", $id);
                $itemGrp = $this->machines[$ids[1]]["item-grp"];
                $pltfArray[] = [$ids[0], $itemGrp, "K1", $mu[0]];
                $pltfArray[] = [$ids[0], $itemGrp, "K2", $mu[1]];
            }
        }
        elseif($this->factel == 7) {
            $columns = $this->bilansStats[$this->factel]['T3']['columns'];
            $lines = Csv::extract($this->getFileNameInBS('T3'));
            for($i=1;$i<count($lines);$i++) {
                $tab = explode(";", $lines[$i]);
                $machId = $tab[$columns["mach-id"]];
                if(array_key_exists($machId, $this->machines)) {
                    $itemGrp = $this->machines[$machId]["item-grp"];
                    if(($tab[$columns["flow-type"]] == "cae") && ($tab[$columns["client-code"]] == $tab[$columns["platf-code"]]) && ($tab[$columns["proj-expl"] == "FALSE"])) {
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

                        $id = $tab[$columns["proj-id"]]."--".$itemGrp."--".$itemK;
                        if(!array_key_exists($id, $loopArray)) {
                            $loopArray[$id] = 0;
                        }
                        $loopArray[$id] += $tab[$columns["transac-usage"]];
                    }
                }
            }
            foreach($loopArray as $id=>$mu) {
                $ids = explode("--", $id);
                $pltfArray[] = [$ids[0], $ids[1], $ids[2], $mu];
            }
        }
        elseif($this->factel >= 8 || $this->factel < 10) {
            $columns = $this->bilansStats[$this->factel]['T3']['columns'];
            $lines = Csv::extract($this->getFileNameInBS('T3'));
            for($i=1;$i<count($lines);$i++) {
                $tab = explode(";", $lines[$i]);
                $machId = $tab[$columns["mach-id"]];
                if(array_key_exists($machId, $this->machines)) {
                    $itemGrp = $this->machines[$machId]["item-grp"];
                    if(($tab[$columns["flow-type"]] == "cae") && ($tab[$columns["client-code"]] == $tab[$columns["platf-code"]]) && ($tab[$columns["proj-expl"] == "FALSE"])) {
                        $id = $tab[$columns["proj-id"]]."--".$itemGrp."--".$tab[$columns["item-codeK"]];
                        if(!array_key_exists($id, $loopArray)) {
                            $loopArray[$id] = 0;
                        }
                        $loopArray[$id] += $tab[$columns["transac-usage"]];
                    }
                }
            }
            foreach($loopArray as $id=>$mu) {
                $ids = explode("--", $id);
                $pltfArray[] = [$ids[0], $ids[1], $ids[2], $mu];
            }
        }
        elseif($this->factel = 10) {
            $columns = $this->bilansStats[$this->factel]['T3']['columns'];
            $lines = Csv::extract($this->getFileNameInBS('T3'));
            for($i=1;$i<count($lines);$i++) {
                $tab = explode(";", $lines[$i]);
                $machId = $tab[$columns["mach-id"]];
                if(array_key_exists($machId, $this->machines)) {
                    $itemGrp = $this->machines[$machId]["item-grp"];
                    if(($tab[$columns["year"]] == $tab[$columns["editing-year"]]) && ($tab[$columns["month"]] == $tab[$columns["editing-month"]]) && ($tab[$columns["flow-type"]] == "cae") && ($tab[$columns["client-code"]] == $tab[$columns["platf-code"]]) && ($tab[$columns["proj-expl"] == "FALSE"])) {
                        $id = $tab[$columns["proj-id"]]."--".$itemGrp."--".$tab[$columns["item-codeK"]];
                        if(!array_key_exists($id, $loopArray)) {
                            $loopArray[$id] = 0;
                        }
                        $loopArray[$id] += $tab[$columns["transac-usage"]];
                    }
                }
            }
            foreach($loopArray as $id=>$mu) {
                $ids = explode("--", $id);
                $pltfArray[] = [$ids[0], $ids[1], $ids[2], $mu];
            }
        }
        else {
            $columns = $this->bilansStats[$this->factel]['T3']['columns'];
            $lines = Csv::extract($this->getFileNameInBS('T3'));
            for($i=1;$i<count($lines);$i++) {
                $tab = explode(";", $lines[$i]);
                if(($tab[$columns["year"]] == $tab[$columns["editing-year"]]) && ($tab[$columns["month"]] == $tab[$columns["editing-month"]]) && ($tab[$columns["flow-type"]] == "cae") && ($tab[$columns["client-code"]] == $tab[$columns["platf-code"]]) && ($tab[$columns["proj-expl"] == "FALSE"])) {
                    $id = $tab[$columns["proj-id"]]."--".$tab[$columns["item-grp"]]."--".$tab[$columns["item-codeK"]];
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


    function mapping($monthArray, $key) 
    {
        if($key == "statoper") {
            $this->mappingOper($monthArray);
        }
        if($key == "statpltf") {
            $this->mappingPltf($monthArray);
        }
    }

    function mappingOper($operArray)
    {   
        $scipers = [];
        foreach($this->users as $id=>$user) {
            $scipers[$user['user-sciper']] = $id;
        }
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


    function mappingPltf($pltfArray)
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


    function display()
    {
        $title = '<div class="total">Statistiques plateforme : '.$this->period().' </div>';
        echo $this->templateDisplay($title);
    }

}
