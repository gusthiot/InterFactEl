<?php

class ReportUsages extends Report
{
    private $totalM;
    private $totalN;

    public function __construct($plateforme, $to, $from) 
    { 
        parent::__construct($plateforme, $to, $from);
        $this->totalM = 0;
        $this->totalN = 0;
        $this->reportKey = 'statcae';
        $this->reportColumns = ["client-code", "client-class", "user-sciper", "item-codeK", "mach-id", "transac-usage", "transac-runcae"];
        $this->tabs = [
            "par-machine" => [
                "title" => "Stats par Machine",
                "columns" => ["mach-name"],
                "dimensions" => array_merge($this::MACHINE_DIM, $this::GROUPE_DIM, ["item-nbr", "item-name"]),
                "operations" => ["stat-hmach", "stat-run", "stat-nbuser", "stat-nbclient"],
                "results" => []

            ], 
            "par-client"=>[
                "title" => "Stats par Client",
                "columns" => ["client-name"],
                "dimensions" => $this::CLIENT_DIM,
                "operations" => ["stat-hmach", "stat-run"],
                "results" => []
            ], 
            "par-user"=>[
                "title" => "Stats par Utilisateur",
                "columns" => ["user-sciper", "user-name", "user-first"],
                "dimensions" => $this::USER_DIM,
                "operations" => ["stat-hmach", "stat-run"],
                "results" => []
            ], 
            "par-client-user"=>[
                "title" => "Stats par Client par Utilisateur",
                "columns" => ["client-name", "user-sciper", "user-name", "user-first"],
                "dimensions" => array_merge($this::CLIENT_DIM, $this::USER_DIM),
                "operations" => ["stat-hmach", "stat-run"],
                "results" => []
            ], 
            "par-client-classe"=>[
                "title" => "Stats par Client par Classe client",
                "columns" => ["client-name", "client-labelclass"],
                "dimensions" => array_merge($this::CLIENT_DIM, $this::CLASSE_DIM),
                "operations" => ["stat-hmach", "stat-run"],
                "results" => []
            ], 
            "use-machine"=>[
                "title" => "Utilisation par machine",
                "columns" => ["mach-name", "item-nbr", "item-name", "item-unit", "item-textK"],
                "dimensions" => array_merge($this::MACHINE_DIM, $this::GROUPE_DIM, $this::CATEGORIE_DIM, $this::CODEK_DIM),
                "operations" => ["transac-usage"],
                "results" => []
            ], 
            "use-categorie"=>[
                "title" => "Utilisation par Catégorie",
                "columns" => ["item-nbr", "item-name", "item-unit", "item-textK"],
                "dimensions" => array_merge($this::GROUPE_DIM, $this::CATEGORIE_DIM, $this::CODEK_DIM),
                "operations" => ["transac-usage"],
                "results" => []
            ]
        ];
    }

    function prepare() 
    {
        $this->prepareClients();
        $this->prepareClasses();
        $this->prepareClientsClasses();
        $this->prepareMachines();
        $this->prepareGroupes();
        $this->prepareCategories();
        $this->prepareUsers();

        $this->processReportFile();
    }

    function generate()
    {
        $usagesArray = [];
        $loopArray = [];
        if($this->factel < 7) {
            $columns = $this->bilansStats[$this->factel]['cae']['columns'];
            $lines = Csv::extract($this->getFileNameInBS('cae'));
            for($i=1;$i<count($lines);$i++) {
                $tab = explode(";", $lines[$i]);
                $machId = $tab[$columns["mach-id"]];
                $itemGrp = $this->machines[$machId]["item-grp"];
                $itemId = $this->groupes[$itemGrp]["item-id-K1"];
                $plateId = $this->categories[$itemId]["platf-code"];
                if($plateId == $this->plateforme) {
                    $mu1 = ($tab[$columns["Tmach-HP"]] + $tab[$columns["Tmach-HC"]]) / 60;
                    $mu2 = $tab[$columns["Toper"]]  / 60;
                    $nr1 = 1;
                    $nr3 = 0;
                    if($tab[$columns["client-code"]] != $this->plateforme) {
                        $nr3 = 1;
                    }
                    $id = $tab[$columns["client-code"]]."-".$tab[$columns["user-id"]]."-".$tab[$columns["mach-id"]];
                    if(!array_key_exists($id, $loopArray)) {
                        $loopArray[$id] = ['Smu1' => 0, 'Smu2' => 0, 'Snr1' => 0, 'Snr3' => 0];
                    }
                    $loopArray[$id]['Smu1'] += $mu1;
                    $loopArray[$id]['Smu2'] += $mu2;
                    $loopArray[$id]['Snr1'] += $nr1;
                    $loopArray[$id]['Snr3'] += $nr3;
                }
            }
            foreach($loopArray as $id=>$line) {
                $ids = explode("-", $id);
                $classe = $this->clientsClasses[$ids[0]]['client-class'];
                $sciper = 0;
                $ids[1] == 0 ? $sciper = 0 : $sciper = $this->users[$ids[1]]['user-sciper'];/*
                    (array_key_exists($ids[1], $this->users) ? $sciper = $this->users[$ids[1]]['user-sciper'] : 
                        (!in_array($ids[1], $this->manquant) ? $this->manquant[] = $ids[1] : "" )
                );*/
                if($line['Smu1'] > 0 && $line['Snr1'] > 0) {
                    $usagesArray[] = [$ids[0], $classe, $sciper, 'K1', $ids[2], $line['Smu1'], $line['Snr1']];
                }                
                if($line['Smu2'] > 0) {
                    $usagesArray[] = [$ids[0], $classe, $sciper, 'K2', $ids[2], $line['Smu2'], 0];
                }
                if($line['Snr3'] > 0) {
                    $usagesArray[] = [$ids[0], $classe, $sciper, 'K3', $ids[2], $line['Snr3'], 0];
                } 
            }
        }
        elseif($this->factel == 7) {
            $columns = $this->bilansStats[$this->factel]['T3']['columns'];
            $lines = Csv::extract($this->getFileNameInBS('T3'));
            for($i=1;$i<count($lines);$i++) {
                $tab = explode(";", $lines[$i]);
                if(($this->plateforme == $tab[$columns["platf-code"]]) && ($tab[$columns["flow-type"]] == "cae")) {
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
                    $nr = 0;
                    if(($itemK == "K1") && ($tab[$columns["proj-expl"]] == "FALSE")) {
                        $nr = 1;
                    }

                    $id = $tab[$columns["client-code"]]."-".$tab[$columns["client-class"]]."-".$tab[$columns["user-id"]]."-".$tab[$columns["mach-id"]]."-".$itemK;
                    if(!array_key_exists($id, $loopArray)) {
                        $loopArray[$id] = ['Smu' => 0, 'Snr' => 0];
                    }
                    $loopArray[$id]['Smu'] += $tab[$columns["transac-usage"]];
                    $loopArray[$id]['Snr'] += $nr;
                }
            }
            foreach($loopArray as $id=>$line) {
                $ids = explode("-", $id);
                $sciper = $this->users[$ids[2]]['user-sciper'];
                if($line['Smu'] > 0 && $line['Snr'] > 0) {
                    $usagesArray[] = [$ids[0], $ids[1], $sciper, $ids[4], $ids[3], $line['Smu'], $line['Snr']];
                }
            }
        }
        elseif($this->factel == 8 || $this->factel == 9) {
            $columns = $this->bilansStats[$this->factel]['T3']['columns'];
            $lines = Csv::extract($this->getFileNameInBS('T3'));
            $nrArray = [];
            for($i=1;$i<count($lines);$i++) {
                $tab = explode(";", $lines[$i]);
                if(($this->plateforme == $tab[$columns["platf-code"]]) && ($tab[$columns["flow-type"]] == "cae")) {
                    $id = $tab[$columns["client-code"]]."-".$tab[$columns["client-class"]]."-".$tab[$columns["user-id"]]."-".$tab[$columns["mach-id"]]."-".$tab[$columns["item-codeK"]];
                    if(!array_key_exists($id, $loopArray)) {
                        $loopArray[$id] = ['Smu' => 0];
                    }
                    $loopArray[$id]['Smu'] += $tab[$columns["transac-usage"]];
                    
                    $idn = $tab[$columns["client-code"]]."-".$tab[$columns["client-class"]]."-".$tab[$columns["user-id"]]."-".$tab[$columns["mach-id"]];
                    if(!array_key_exists($idn, $nrArray)) {
                        $nrArray[$idn] = 0;
                    }
                    if($tab[$columns["transac-runcae"]] > 0) {
                       $nrArray[$idn] += $tab[$columns["transac-runcae"]];
                    }
                }
            }
            foreach($loopArray as $id=>$line) {
                $ids = explode("-", $id);
                $idn = $ids[0]."-".$ids[1]."-".$ids[2]."-".$ids[3];
                $ids[4] == "K1" ? $nr = $nrArray[$idn] : $nr = 0;
                $sciper = $this->users[$ids[2]]['user-sciper'];
                if($line['Smu'] > 0) {
                    $usagesArray[] = [$ids[0], $ids[1], $sciper, $ids[4], $ids[3], $line['Smu'], $nr];
                }
            }
        }
        else {
            $columns = $this->bilansStats[$this->factel]['T3']['columns'];
            $lines = Csv::extract($this->getFileNameInBS('T3'));
            $nrArray = [];
            for($i=1;$i<count($lines);$i++) {
                $tab = explode(";", $lines[$i]);
                if(($this->year == $tab[$columns["editing-year"]]) && ($this->month == $tab[$columns["editing-month"]]) && ($tab[$columns["flow-type"]] == "cae")) {
                    $id = $tab[$columns["client-code"]]."-".$tab[$columns["client-class"]]."-".$tab[$columns["user-id"]]."-".$tab[$columns["mach-id"]]."-".$tab[$columns["item-codeK"]];
                    if(!array_key_exists($id, $loopArray)) {
                        $loopArray[$id] = ['Smu' => 0];
                    }
                    $loopArray[$id]['Smu'] += $tab[$columns["transac-usage"]];
                    
                    $idn = $tab[$columns["client-code"]]."-".$tab[$columns["client-class"]]."-".$tab[$columns["user-id"]]."-".$tab[$columns["mach-id"]];
                    if(!array_key_exists($idn, $nrArray)) {
                        $nrArray[$idn] = 0;
                    }
                    if($tab[$columns["transac-runcae"]] > 0) {
                       $nrArray[$idn] += $tab[$columns["transac-runcae"]];
                    }
                }
            }
            foreach($loopArray as $id=>$line) {
                $ids = explode("-", $id);
                $idn = $ids[0]."-".$ids[1]."-".$ids[2]."-".$ids[3];
                $ids[4] == "K1" ? $nr = $nrArray[$idn] : $nr = 0;
                $sciper = $this->users[$ids[2]]['user-sciper'];
                if($line['Smu'] > 0) {
                    $usagesArray[] = [$ids[0], $ids[1], $sciper, $ids[4], $ids[3], $line['Smu'], $nr];
                }
            }
        }

        for($i=0;$i<count($usagesArray);$i++) {
            $usagesArray[$i][5] = round($usagesArray[$i][5],3);
        }
        return $usagesArray;
    }

    function mapping($usagesArray)
    {
        //$scipers = array_column
        foreach($usagesArray as $line) {
            $client = $this->clients[$line[0]];
            $classe = $this->classes[$line[1]];
            //array_search
            if($line[2] != 0) {
                $user = $this->users[$line[2]];
            }
            else {
                $user = "";
            }
            $codeK = ["item-codeK"=>$line[3], "item-textK"=>$this->paramtext->getParam("item-".$line[3])];
            $machine = $this->machines[$line[4]];
            $groupe = $this->groupes[$machine["item-grp"]];
            $categorie = $this->categories[$groupe["item-id-K1"]];
            $values = [
                "transac-usage"=>$line[5], 
                "transac-runcae"=>$line[6]
            ];
            $ids = [
                "par-machine" => $line[3], 
                "par-client" => $line[0], 
                "par-user" => $line[2], 
                "par-client-user" => $line[0]."-".$line[2], 
                "par-client-classe" => $line[0]."-".$line[1],
                "use-machine" => $line[3], 
                "use-categorie"=> $groupe["item-id-K1"]
            ];
            $extends = [
                "par-machine"=>[$machine, $groupe, $categorie],
                "par-client" => [$client], 
                "par-user" => [$user], 
                "par-client-user" => [$client, $user], 
                "par-client-classe" => [$client, $classe], 
                "use-machine" => [$machine, $groupe, $categorie, $codeK], 
                "use-categorie"=>[$groupe, $categorie, $codeK]
            ];
            $dimensions = [
                "par-machine"=>[$this::MACHINE_DIM, $this::GROUPE_DIM, $this::CATEGORIE_DIM], 
                "par-client" => [$this::CLIENT_DIM], 
                "par-user" => [$this::USER_DIM], 
                "par-client-user" => [$this::CLIENT_DIM, $this::USER_DIM], 
                "par-client-classe" => [$this::CLIENT_DIM, $this::CLASSE_DIM], 
                "use-machine" => [$this::MACHINE_DIM, $this::GROUPE_DIM, $this::CATEGORIE_DIM, $this::CODEK_DIM], 
                "use-categorie"=>[$this::GROUPE_DIM, $this::CATEGORIE_DIM, $this::CODEK_DIM]
            ];
/*
            foreach($this->tabs as $tab=>$data) {
                if(!array_key_exists($ids[$tab], $this->tabs[$tab]["results"])) {
                    $this->tabs[$tab]["results"][$ids[$tab]] = ["mois" => []];            
                    foreach($dimensions[$tab] as $pos=>$dimension) {
                        foreach($dimension as $d) {
                            $this->tabs[$tab]["results"][$ids[$tab]][$d] = $extends[$tab][$pos][$d];
                        }
                    }
                    foreach($this->tabs[$tab]["operations"] as $operation) {
                        $this->tabs[$tab]["results"][$ids[$tab]][$operation] = [];
                    }
                }
                if(!array_key_exists($this->monthly, $this->tabs[$tab]["results"][$ids[$tab]]["mois"])) {
                    $this->tabs[$tab]["results"][$ids[$tab]]["mois"][$this->monthly] = 0;
                }
                foreach($values as $operation=>$value) {
                    $this->tabs[$tab]["results"][$ids[$tab]][$operation][] = $value;
                }
                $this->tabs[$tab]["results"][$ids[$tab]]["mois"][$this->monthly] += $values["transac-runcae"];
            } */
        }
    }

    function display() 
    {
        /*
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
        $title .= '<div class="subtotal">Nombre de runs CAE productifs = '.$this->totalN.'</div>';
        echo $this->templateDisplay($title, false);
        */
        print_r($this->manquant);
    }
}
