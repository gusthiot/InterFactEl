<?php

/**
 * ReportTransactions class allows to generate reports about users and clients transactions
 */
class ReportTransactions extends Report
{
    /**
     * total of transactions
     *
     * @var integer
     */
    private int $totalT;
    
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
            "par-classe" => [
                "title" => "par Classe",
                "columns" => ["client-labelclass"],
                "dimensions" => $this::CLASSE_DIM,
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
            ],
            "par-client-classe" => [
                "title" => "par Client par Classe",
                "columns" => ["client-name", "client-labelclass"],
                "dimensions" => array_merge($this::CLIENT_DIM, $this::CLASSE_DIM),
                "operations" => ["transac-nbr-cae", "transac-nbr-lvr", "transac-nbr-srv", "transac-nbr-noshow", "transac-nbr"],
                "formats" => ["int", "int", "int", "int", "int"],
                "results" => []
            ],
            "par-classe-user" => [
                "title" => "par Classe par Utilisateur",
                "columns" => ["client-labelclass", "user-name", "user-first"],
                "dimensions" => array_merge($this::CLASSE_DIM, $this::USER_DIM),
                "operations" => ["transac-nbr-cae", "transac-nbr-lvr", "transac-nbr-srv", "transac-nbr-noshow", "transac-nbr"],
                "formats" => ["int", "int", "int", "int", "int"],
                "results" => []
            ],
            "par-client-classe-user" => [
                "title" => "par Client par Classe par Utilisateur",
                "columns" => ["client-name", "client-labelclass", "user-name", "user-first"],
                "dimensions" => array_merge($this::CLIENT_DIM, $this::CLASSE_DIM, $this::USER_DIM),
                "operations" => ["transac-nbr-cae", "transac-nbr-lvr", "transac-nbr-srv", "transac-nbr-noshow", "transac-nbr"],
                "formats" => ["int", "int", "int", "int", "int"],
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
        $this->prepareClients();
        $this->prepareClasses();
        $this->prepareClientsClasses();
        $this->prepareUsers();
        $this->loadPrestations();
        $this->prepareMachines();
        $this->loadCategories();
        $this->loadGroupes();
        $this->loadMachinesGroupes();

        $this->processReportFile();
    }

    /**
     * generates report file and returns its data
     *
     * @return array
     */
    function generate(): array
    {
        $transArray = [];
        $loopArray = [];

        if(floatval($this->factel) < 7) {
            foreach(['cae', 'lvr'] as $flux) {
                $columns = $this->bilansStats->getColumns($this->factel, $flux);
                $lines = Csv::extract($this->getFileNameInBS($flux));
                for($i=1;$i<count($lines);$i++) {
                    $tab = explode(";", $lines[$i]);
                    $code = $tab[$columns["client-code"]];
                    $clcl = $this->clientsClasses[$code]['client-class'];
                    if($flux == 'cae') {
                        $n = 0;
                        $machId = $tab[$columns["mach-id"]];
                        $plateId = $this->getPlateformeFromMachine($machId);
                        if($tab[$columns["Tmach-HP"]] > 0) {
                            $n++;
                        }
                        if($tab[$columns["Tmach-HC"]] > 0) {
                            $n++;
                        }
                        if($tab[$columns["Toper"]] > 0) {
                            $n++;
                        }
                    }
                    else {
                        $n = 1;
                        $itemId = $tab[$columns["item-id"]];
                        $plateId = $this->prestations[$itemId]["platf-code"];
                    }                    
                    if($plateId && ($plateId == $this->plateforme) && ($code != $plateId)) {
                        $id = $code."--".$clcl."--".$tab[$columns["user-id"]]."--".$flux;
                        if(!array_key_exists($id, $loopArray)) {
                            $loopArray[$id] = 0;
                        }
                        $loopArray[$id] += $n;
                    }
                }
            }
        }
        else {
            $columns = $this->bilansStats->getColumns($this->factel, 'T3');
            $lines = Csv::extract($this->getFileNameInBS('T3'));
            for($i=1;$i<count($lines);$i++) {
                $tab = explode(";", $lines[$i]);
                if($this->factel >= 7 && floatval($this->factel) < 9) {
                    $cond = ($this->plateforme == $tab[$columns["platf-code"]]) && ($tab[$columns["client-code"]] != $tab[$columns["platf-code"]]);
                }
                elseif($this->factel >= 9 && floatval($this->factel) < 10) {
                    $datetime = explode(" ", $tab[$columns["transac-date"]]);
                    $parts = explode("-", $datetime[0]);
                    $cond = ($parts[0] == $this->year) && ($parts[1] == $this->month) && ($tab[$columns["transac-valid"]] != 2) && ($tab[$columns["client-code"]] != $tab[$columns["platf-code"]]);
                }
                else {
                    $cond = ($tab[$columns["year"]] == $tab[$columns["editing-year"]]) && ($tab[$columns["month"]] == $tab[$columns["editing-month"]]) && ($tab[$columns["transac-valid"]] != 2) && ($tab[$columns["client-code"]] != $tab[$columns["platf-code"]]);
                }    
                if($cond) {
                    $id = $tab[$columns["client-code"]]."--".$tab[$columns["client-class"]]."--".$tab[$columns["user-id"]]."--".$tab[$columns["flow-type"]];
                    if(!array_key_exists($id, $loopArray)) {
                        $loopArray[$id] = 0;
                    }
                    $loopArray[$id] ++;
                }
            }
        }
        foreach($loopArray as $id=>$q) {
            $ids = explode("--", $id);
            $transArray[] = [$ids[0], $ids[1], $this->sciper($ids[2]), $ids[3], $q];
        }
        return $transArray;
    }

    /**
     * maps report data for tabs tables and csv 
     *
     * @param array $montantsArray report data
     * @return void
     */
    function mapping(array $transArray): void
    {   
        $scipers = $this->scipers();
        foreach($transArray as $line) {
            $client = $this->clients[$line[0]];
            $classe = $this->classes[$line[1]];
            if($line[2] != 0) {
                $user = $this->users[$scipers[$line[2]]];
            }
            else {
                $user = ["user-sciper"=>"0", "user-name"=>"", "user-first"=>"", "user-email"=>""];
            }
            $ids = [
                "par-client"=>$line[0], 
                "par-classe"=>$line[1], 
                "par-user"=>$line[2], 
                "par-client-user"=>$line[0]."-".$line[2], 
                "par-client-classe"=>$line[0]."-".$line[1], 
                "par-classe-user"=>$line[1]."-".$line[2], 
                "par-client-classe-user"=>$line[0]."-".$line[1]."-".$line[2]
            ];
            $extends = [
                "par-client"=>[$client],
                "par-classe"=>[$classe],
                "par-user"=>[$user],
                "par-client-user"=>[$client, $user],
                "par-client-classe"=>[$client, $classe],
                "par-classe-user"=>[$classe, $user],
                "par-client-classe-user"=>[$client, $classe, $user]
            ];
            $dimensions = [
                "par-client"=>[$this::CLIENT_DIM],
                "par-classe"=>[$this::CLASSE_DIM],
                "par-user"=>[$this::USER_DIM],
                "par-client-user"=>[$this::CLIENT_DIM, $this::USER_DIM],
                "par-client-classe"=>[$this::CLIENT_DIM, $this::CLASSE_DIM],
                "par-classe-user"=>[$this::CLASSE_DIM, $this::USER_DIM],
                "par-client-classe-user"=>[$this::CLIENT_DIM, $this::CLASSE_DIM, $this::USER_DIM]
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
                $this->tabs[$tab]["results"][$ids[$tab]]["transac-nbr"] += $line[4];
                $this->tabs[$tab]["results"][$ids[$tab]]["transac-nbr-".$line[3]] += $line[4];
                $this->tabs[$tab]["results"][$ids[$tab]]["mois"][$this->monthly] += $line[4];
            }
            $this->totalT += $line[4];
        }
    }

    /**
     * displays title and tabs
     *
     * @return void
     */
    function display(): void
    {
        $nbUsers = count($this->tabs["par-user"]["results"]);
        if(array_key_exists(0, $this->tabs["par-user"]["results"])) {
            $nbUsers -= 1;
        }
        $title = '<div class="total">Statistiques utilisateurs et clients : '.$this->period().' </div>';
        $title .= '<div class="subtotal">Nombre total de transactions = '.$this->format($this->totalT, "int").'</div>';
        $title .= '<div class="subtotal">Nombre de clients = '.$this->format(count($this->tabs["par-client"]["results"]), "int").'</div>';
        $title .= '<div class="subtotal">Nombre d’utilisateurs = '.$this->format($nbUsers, "int").'</div>';
        echo $this->templateDisplay($title, true);
    }

}
