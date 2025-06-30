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
                "columns" => ["proj-nbr", "proj-name","item-name", "item-textK"],
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
        $this->prepareUsers();
        $this->prepareMachines();

        $this->processReportFile();
    }

    function generate($key) 
    {
        if($key == "statoper") {
            $this->generateOper();
        }
        if($key == "statpltf") {
            $this->generatePltf();
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
    }


    function mapping($transArray)
    {   
        $scipers = [];
        foreach($this->users as $id=>$user) {
            $scipers[$user['user-sciper']] = $id;
        }
        foreach($transArray as $line) {
            $client = $this->clients[$line[0]];
            if($line[2] != 0) {
                $user = $this->users[$scipers[$line[1]]];
            }
            else {
                $user = "";
            }
            $ids = [
                "par-client"=>$line[0], 
                "par-user"=>$line[1], 
                "par-client-user"=>$line[0]."-".$line[1]
            ];
            $extends = [
                "par-client"=>[$client],
                "par-user"=>[$user],
                "par-client-user"=>[$client, $user]
            ];
            $dimensions = [
                "par-client"=>[$this::CLIENT_DIM],
                "par-user"=>[$this::USER_DIM],
                "par-client-user"=>[$this::CLIENT_DIM, $this::USER_DIM]
            ];

            foreach($this->tabs as $tab=>$data) {
                if($tab != "par-client" && $line[2] == 0) {
                    continue;
                }
                if(!array_key_exists($ids[$tab], $this->tabs[$tab]["results"])) {
                    $this->tabs[$tab]["results"][$ids[$tab]] = ["mois" => []];
                    foreach($dimensions[$tab] as $pos=>$dimension) {
                        foreach($dimension as $d) {
                            $this->tabs[$tab]["results"][$ids[$tab]][$d] = $extends[$tab][$pos][$d];
                        }
                    }
                    foreach($this->tabs[$tab]["operations"] as $operation) {
                        $this->tabs[$tab]["results"][$ids[$tab]][$operation] = 0;
                    }
                }     
                if(!array_key_exists($this->monthly, $this->tabs[$tab]["results"][$ids[$tab]]["mois"])) {
                    $this->tabs[$tab]["results"][$ids[$tab]]["mois"][$this->monthly] = 0;
                }
                $this->tabs[$tab]["results"][$ids[$tab]]["transac-nbr"] += $line[3];
                $this->tabs[$tab]["results"][$ids[$tab]]["transac-nbr-".$line[2]] += $line[3];
                $this->tabs[$tab]["results"][$ids[$tab]]["mois"][$this->monthly] += $line[3];
            }
            $this->totalT += $line[3];
        }
    }

    function display()
    {
        $title = '<div class="total">Statistiques utilisateurs et clients : '.$this->period().' </div>';
        $title .= '<div class="subtotal">Nombre total de transactions = '.$this->format($this->totalT, "int").'</div>';
        $title .= '<div class="subtotal">Nombre de clients = '.$this->format(count($this->tabs["par-client"]["results"]), "int").'</div>';
        $title .= '<div class="subtotal">Nombre d’utilisateurs = '.$this->format(count($this->tabs["par-user"]["results"]), "int").'</div>';
        echo $this->templateDisplay($title);
    }

}
