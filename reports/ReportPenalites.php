<?php

/**
 * ReportPenalites class allows to generate reports about penalties stats
 */
class ReportPenalites extends Report
{
    /**
     * total penalties for K5
     *
     * @var float
     */
    private float $total5;

    /**
     * total penalties for K6
     *
     * @var float
     */
    private float $total6;
    
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
        $this->total5 = 0.0;
        $this->total6 = 0.0;
        $this->reportKey = 'statnoshow';
        $this->reportColumns = ["client-code", "user-sciper", "item-codeK", "mach-id", "transac-quantity"];
        $this->tabs = [
            "par-machine" => [
                "title" => "Stats par Machine",
                "columns" => ["mach-name"],
                "dimensions" => $this::MACHINE_DIM,
                "operations" => ["transac-quantity"],
                "formats" => ["float"],
                "results" => []
            ],
            "par-client" => [
                "title" => "Stats par Client",
                "columns" => ["client-name"],
                "dimensions" => $this::CLIENT_DIM,
                "operations" => ["transac-quantity"],
                "formats" => ["float"],
                "results" => []
            ],
            "par-user" => [
                "title" => "Stats par Utilisateur",
                "columns" => ["user-sciper", "user-name", "user-first"],
                "dimensions" => $this::USER_DIM,
                "operations" => ["transac-quantity"],
                "formats" => ["float"],
                "results" => []
            ],
            "par-client-user" => [
                "title" => "Stats par Client par Utilisateur",
                "columns" => ["client-name", "user-sciper", "user-name", "user-first"],
                "dimensions" => array_merge($this::CLIENT_DIM, $this::USER_DIM),
                "operations" => ["transac-quantity"],
                "formats" => ["float"],
                "results" => []
            ],
            "par-machine-user" => [
                "title" => "Stats par Machine par Utilisateur",
                "columns" => ["mach-name", "item-textK", "user-sciper", "user-name", "user-first"],
                "dimensions" => array_merge($this::MACHINE_DIM, $this::CODEK_DIM, $this::USER_DIM),
                "operations" => ["transac-quantity"],
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
        $this->prepareClients();
        $this->prepareUsers();
        $this->prepareMachines();

        $this->processReportFile();
    }

    /**
     * generates report file and returns its data
     *
     * @return array
     */
    function generate(): array
    {
        $loopArray = [];
        $columns = $this->bilansStats->getColumns($this->factel, 'T3');
        $lines = Csv::extract($this->getFileNameInBS('T3'));
        for($i=1;$i<count($lines);$i++) {
            $tab = explode(";", $lines[$i]);
            if(floatval($this->factel) < 8) {
                if(($tab[$columns["platf-code"]] == $this->plateforme) && ($tab[$columns["flow-type"]] == "noshow") && ($tab[$columns["platf-code"]] != $tab[$columns["client-code"]])) {
                    $itemN = $tab[$columns["item-nbr"]];
                    if(substr($itemN, 0, 1) == "P") {
                        $itemK = "K5";
                    }
                    else {
                        $itemK = "K6";
                    }
                    $id = $tab[$columns["client-code"]]."--".$tab[$columns["user-id"]]."--".$tab[$columns["mach-id"]]."--".$itemK;
                    if(!array_key_exists($id, $loopArray)) {
                        $loopArray[$id] = 0;
                    }
                    $loopArray[$id] += $tab[$columns["transac-quantity"]];
                }
            }
            elseif(floatval($this->factel) >= 8 && floatval($this->factel) < 10) {
                if(($tab[$columns["platf-code"]] == $this->plateforme) && ($tab[$columns["flow-type"]] == "noshow") && ($tab[$columns["platf-code"]] != $tab[$columns["client-code"]])) {
                    $id = $tab[$columns["client-code"]]."--".$tab[$columns["user-id"]]."--".$tab[$columns["mach-id"]]."--".$tab[$columns["item-codeK"]];
                    if(!array_key_exists($id, $loopArray)) {
                        $loopArray[$id] = 0;
                    }
                    $loopArray[$id] += $tab[$columns["transac-quantity"]];
                }
            }
            else {
                if(($tab[$columns["year"]] == $tab[$columns["editing-year"]]) && ($tab[$columns["month"]] == $tab[$columns["editing-month"]]) && ($tab[$columns["flow-type"]] == "noshow") && ($tab[$columns["platf-code"]] != $tab[$columns["client-code"]])) {
                    $id = $tab[$columns["client-code"]]."--".$tab[$columns["user-id"]]."--".$tab[$columns["mach-id"]]."--".$tab[$columns["item-codeK"]];
                    if(!array_key_exists($id, $loopArray)) {
                        $loopArray[$id] = 0;
                    }
                    $loopArray[$id] += $tab[$columns["transac-quantity"]];
                }
            }
        }
        $penosArray = [];
        foreach($loopArray as $id=>$q) {
            $ids = explode("--", $id);
            $penosArray[] = [$ids[0], $this->sciper($ids[1]), $ids[3], $ids[2], round($q,3)];
        }
        return $penosArray;
    }

    /**
     * maps report data for tabs tables and csv 
     *
     * @param array $penosArray report data
     * @return void
     */
    function mapping(array $penosArray): void
    {   
        $scipers = $this->scipers();
        foreach($penosArray as $line) {
            $client = $this->clients[$line[0]];
            $user = $this->users[$scipers[$line[1]]];
            $machine = $this->machines[$line[3]];
            $codeK = ["item-codeK"=>$line[2], "item-textK"=>$this->paramtext->getParam("item-".$line[2])];
            $value = $line[4];
            $ids = [
                "par-machine"=>$line[3], 
                "par-client"=>$line[0], 
                "par-user"=>$line[1], 
                "par-client-user"=>$line[0]."-".$line[1],
                "par-machine-user"=>$line[3]."-".$line[2]."-".$line[1]
            ];
            $extends = [
                "par-machine"=>[$machine],
                "par-client"=>[$client],
                "par-user"=>[$user],
                "par-client-user"=>[$client, $user],
                "par-machine-user"=>[$machine, $codeK, $user]
            ];
            $dimensions = [
                "par-machine"=>[$this::MACHINE_DIM], 
                "par-client"=>[$this::CLIENT_DIM],
                "par-user"=>[$this::USER_DIM],
                "par-client-user"=>[$this::CLIENT_DIM, $this::USER_DIM],
                "par-machine-user"=>[$this::MACHINE_DIM, $this::CODEK_DIM, $this::USER_DIM]
            ];

            foreach($this->tabs as $tab=>$data) {
                if(!array_key_exists($ids[$tab], $this->tabs[$tab]["results"])) {
                    $this->tabs[$tab]["results"][$ids[$tab]] = [];
                    foreach($dimensions[$tab] as $pos=>$dimension) {
                        foreach($dimension as $d) {
                            $this->tabs[$tab]["results"][$ids[$tab]][$d] = $extends[$tab][$pos][$d];
                        }
                    }
                    $this->tabs[$tab]["results"][$ids[$tab]]["transac-quantity"] = 0;
                }
                $this->tabs[$tab]["results"][$ids[$tab]]["transac-quantity"] += $value;
            }
            if($line[2] == "K5") {
                $this->total5 += $value;
            }
            else {
                $this->total6 += $value;
            }
        }
    }

    /**
     * displays title and tabs
     *
     * @return void
     */
    function display(): void
    {
        $title = '<div class="total">Statistiques pénalités : '.$this->period().' </div>';
        $title .= '<div class="subtotal">Nombre d’heures de pénalités en heures pleines = '.$this->format($this->total5, "float").'</div>';
        $title .= '<div class="subtotal">Nombre d’heures de pénalités en heures creuses = '.$this->format($this->total6, "float").'</div>';
        echo $this->templateDisplay($title);
    }

}
