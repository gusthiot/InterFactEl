<?php

class ReportTransactions extends Report
{
    private $totalT;
    
    public function __construct($plateforme, $to, $from) 
    { 
        parent::__construct($plateforme, $to, $from);
        $this->totalT = 0;
        $this->reportKey = 'stattran';
        $this->reportColumns = ["client-code", "user-sciper", "flow-type", "transac-nbr"];
        $this->tabs = [
            "par-user" => [
                "title" => "par Utilisateur",
                "columns" => ["user-name", "user-first"],
                "dimensions" => $this::USER_DIM,
                "operations" => ["transac-nbr-cae", "transac-nbr-lvr", "transac-nbr-srv", "transac-nbr-noshow", "transac-nbr"],
                "formats" => ["int", "int", "int", "int", "int"],
                "results" => []
            ],
            "par-client" => [
                "title" => "par Client",
                "columns" => ["client-name"],
                "dimensions" => $this::CLIENT_DIM,
                "operations" => ["transac-nbr-cae", "transac-nbr-lvr", "transac-nbr-srv", "transac-nbr-noshow", "transac-nbr"],
                "formats" => ["int", "int", "int", "int", "int"],
                "results" => []
            ],
            "par-client-user" => [
                "title" => "par Client par Utilisateur",
                "columns" => ["client-name", "user-name", "user-first"],
                "dimensions" => array_merge($this::CLIENT_DIM, $this::USER_DIM),
                "operations" => ["transac-nbr-cae", "transac-nbr-lvr", "transac-nbr-srv", "transac-nbr-noshow", "transac-nbr"],
                "formats" => ["int", "int", "int", "int", "int"],
                "results" => []
            ]
        ];

    }

    function prepare() {
        $this->prepareClients();
        $this->prepareUsers();
        $this->preparePrestations();
        $this->prepareMachines();

        $this->processReportFile();
    }

    function generate()
    {
        $transArray = [];
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
                    if($tab[$columns["client-code"]] != $this->categories[$itemId]["platf-code"]) {
                        $id = $tab[$columns["client-code"]]."--".$tab[$columns["user-id"]];
                        if(!array_key_exists($id, $loopArray)) {
                            $loopArray[$id] = 0;
                        }
                        $loopArray[$id] += 1;
                    }
                }
            }
            foreach($loopArray as $id=>$q) {
                $ids = explode("--", $id);
                $sciper = 0;
                $ids[1] == 0 ? $sciper = 0 : $sciper = $this->users[$ids[1]]['user-sciper'];
                $transArray[] = [$ids[0], $sciper, "cae", $q];
            }
            $columns = $this->bilansStats[$this->factel]['lvr']['columns'];
            $lines = Csv::extract($this->getFileNameInBS('lvr'));
            for($i=1;$i<count($lines);$i++) {
                $tab = explode(";", $lines[$i]);
                $itemId = $tab[$columns["item-id"]];
                $plateId = $this->prestations[$itemId]["platf-code"];
                if(($plateId == $this->plateforme) && ($tab[$columns["client-code"]] != $plateId)) {
                    $id = $tab[$columns["client-code"]]."--".$tab[$columns["user-id"]];
                    if(!array_key_exists($id, $loopArray)) {
                        $loopArray[$id] = 0;
                    }
                    $loopArray[$id] += 1;
                }
            }
            foreach($loopArray as $id=>$q) {
                $ids = explode("--", $id);
                $sciper = 0;
                $ids[1] == 0 ? $sciper = 0 : $sciper = $this->users[$ids[1]]['user-sciper'];
                $transArray[] = [$ids[0], $sciper, "lvr", $q];
            }
        }
        elseif($this->factel >= 7 && $this->factel < 9) {
            $columns = $this->bilansStats[$this->factel]['T3']['columns'];
            $lines = Csv::extract($this->getFileNameInBS('T3'));
            for($i=1;$i<count($lines);$i++) {
                $tab = explode(";", $lines[$i]);
                if(($this->plateforme == $tab[$columns["platf-code"]]) && ($tab[$columns["client-code"]] != $tab[$columns["platf-code"]])) {
                    $id = $tab[$columns["client-code"]]."--".$tab[$columns["user-id"]]."--".$tab[$columns["flow-type"]];
                    if(!array_key_exists($id, $loopArray)) {
                        $loopArray[$id] = 0;
                    }
                    $loopArray[$id] += 1;
                }
            }
            foreach($loopArray as $id=>$q) {
                $ids = explode("--", $id);
                $sciper = 0;
                $ids[1] == 0 ? $sciper = 0 : $sciper = $this->users[$ids[1]]['user-sciper'];
                $transArray[] = [$ids[0], $sciper, $ids[2], $q];
            }
        }
        elseif($this->factel >= 9 && $this->factel < 10) {
            $columns = $this->bilansStats[$this->factel]['T3']['columns'];
            $lines = Csv::extract($this->getFileNameInBS('T3'));
            for($i=1;$i<count($lines);$i++) {
                $tab = explode(";", $lines[$i]);
                if(($tab[$columns["transac-valid"]] != 2) && ($tab[$columns["client-code"]] != $tab[$columns["platf-code"]])) {
                    $id = $tab[$columns["client-code"]]."--".$tab[$columns["user-id"]]."--".$tab[$columns["flow-type"]];
                    if(!array_key_exists($id, $loopArray)) {
                        $loopArray[$id] = 0;
                    }
                    $loopArray[$id] += 1;
                }
            }
            foreach($loopArray as $id=>$q) {
                $ids = explode("--", $id);
                $sciper = 0;
                $ids[1] == 0 ? $sciper = 0 : $sciper = $this->users[$ids[1]]['user-sciper'];
                $transArray[] = [$ids[0], $sciper, $ids[2], $q];
            }
        }
        else {
            $columns = $this->bilansStats[$this->factel]['T3']['columns'];
            $lines = Csv::extract($this->getFileNameInBS('T3'));
            $nrArray = [];
            for($i=1;$i<count($lines);$i++) {
                $tab = explode(";", $lines[$i]);
                if(($tab[$columns["year"]] == $tab[$columns["editing-year"]]) && ($tab[$columns["month"]] == $tab[$columns["editing-month"]]) && ($tab[$columns["transac-valid"]] != 2) && ($tab[$columns["client-code"]] != $tab[$columns["platf-code"]])) {
                    $id = $tab[$columns["client-code"]]."--".$tab[$columns["user-id"]]."--".$tab[$columns["flow-type"]];
                    if(!array_key_exists($id, $loopArray)) {
                        $loopArray[$id] = 0;
                    }
                    $loopArray[$id] += 1;
                }
            }
            foreach($loopArray as $id=>$q) {
                $ids = explode("--", $id);
                $sciper = 0;
                $ids[1] == 0 ? $sciper = 0 : $sciper = $this->users[$ids[1]]['user-sciper'];
                $transArray[] = [$ids[0], $sciper, $ids[2], $q];
            }
        }

        return $transArray;
    }


    function mapping($transArray)
    {   
        $scipers = [];
        foreach($this->users as $id=>$user) {
            $scipers[$user['user-sciper']] = $id;
        }
        foreach($transArray as $line) {
            $client = $this->clients[$line[0]];
            if($line[1] != 0) {
                $user = $this->users[$scipers[$line[1]]];
            }
            else {
                $user = ["user-sciper"=>"0", "user-name"=>"", "user-first"=>"", "user-email"=>""];
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
