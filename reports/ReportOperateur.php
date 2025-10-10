<?php

/**
 * ReportOperateur class allows to generate reports about staff
 */
class ReportOperateur extends Report
{
    private float $totalO;

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
        $this->totalO = 0;
        $this->reportKey = 'statoper';
        $this->reportColumns = ["oper-sciper", "date", "flow-type", "transac-usage"];
        $this->totalCsvData = [
            "dimensions" => array_merge($this::OPER_DIM, ["date", "flow-type"]),
            "operations" => ["transac-usage"],
            "results" => []
        ];
        $this->tabs = [
            "par-staff" => [
                "title" => "Heures par staff",
                "columns" => ["oper-name", "oper-first"],
                "dimensions" => $this::OPER_DIM,
                "operations" => ["transac-usage"],
                "formats" => ["float"],
                "results" => []
            ],
            "par-staff-date" => [
                "title" => "Heures par staff, par date",
                "columns" => ["oper-name", "oper-first", "date"],
                "dimensions" => array_merge($this::OPER_DIM, ["date"]),
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
        $this->prepareUsers();

        $this->processReportFile();
    }

    /**
     * Generates report file and returns its data
     *
     * @return array
     */
    function generate(): array
    {
        $loopArray = [];

        if(floatval($this->factel) < 7) {
            $columns = $this->bilansStats->getColumns($this->factel, 'cae');
            $lines = Csv::extract($this->getFileNameInBS('cae'));
            for($i=1;$i<count($lines);$i++) {
                $tab = explode(";", $lines[$i]);
                $machId = $tab[$columns["mach-id"]];
                $plateId = $this->getPlateformeFromMachine($machId);
                if($plateId && ($plateId == $this->plateforme) && $tab[$columns["Toper"]] > 0) {
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
                elseif(floatval($this->factel) >= 8 && floatval($this->factel) < 9) {
                    $cond = ($this->plateforme == $tab[$columns["platf-code"]]) && ($tab[$columns["flow-type"]] == "cae") && ($tab[$columns["item-codeK"]] == "K2");
                }
                elseif(floatval($this->factel) >= 9 && floatval($this->factel) < 10) {
                    $datetime = explode(" ", $tab[$columns["transac-date"]]);
                    $parts = explode("-", $datetime[0]);
                    $cond = ($parts[0] == $this->year) && ($parts[1] == $this->month) && ($this->plateforme == $tab[$columns["platf-code"]]) && ($tab[$columns["flow-type"]] == "cae") && ($tab[$columns["item-codeK"]] == "K2");
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
     * Maps report data for tabs tables and csv 
     *
     * @param array $operArray report data
     * @return void
     */
    function mapping(array $operArray): void
    {
        $scipers = $this->scipers();
        foreach($operArray as $line) {
            if($line[0] != 0) {
                $user = $this->users[$scipers[$line[0]]];
            }
            else {
                $user = ["user-sciper"=>"0", "user-name"=>"", "user-first"=>"", "user-email"=>""];
            }
            $oper = ["oper-sciper"=>$user["user-sciper"], "oper-name"=>$user["user-name"], "oper-first"=>$user["user-first"]];
            $date =  ["date"=>$line[1]];
            $flow = ["flow-type"=>$line[2]];

            $ids = [
                "par-staff"=>$line[0],
                "par-staff-date"=>$line[0]."-".$line[1],
                "for-csv"=>$line[0]."-".$line[1]."-".$line[2]
            ];
            $extends = [
                "par-staff"=>[$oper],
                "par-staff-date"=>[$oper, $date],
                "for-csv"=>[$oper, $date, $flow]
            ];
            $dimensions = [
                "par-staff"=>[$this::OPER_DIM],
                "par-staff-date"=>[$this::OPER_DIM, ["date"]],
                "for-csv"=>[$this::OPER_DIM, ["date"], ["flow-type"]]
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
                        $this->tabs[$tab]["results"][$ids[$tab]][$operation] = 0;
                    }
                }
                $this->tabs[$tab]["results"][$ids[$tab]]["transac-usage"] += $line[3];
            } 
            // total csv
            if(!array_key_exists($ids["for-csv"], $this->totalCsvData["results"])) {
                $this->totalCsvData["results"][$ids["for-csv"]] = ["transac-usage" => 0];            
                foreach($dimensions["for-csv"] as $pos=>$dimension) {
                    foreach($dimension as $d) {
                        $this->totalCsvData["results"][$ids["for-csv"]][$d] = $extends["for-csv"][$pos][$d];
                    }
                }
            }
            $this->totalCsvData["results"][$ids["for-csv"]]["transac-usage"] += $line[3];
            $this->totalO += $line[3];
        }
    }

    /**
     * Displays title and tabs
     *
     * @return void
     */
    function display(): void
    {
        $title = '<div class="total">Statistiques opérateurs staff : '.$this->period().' </div>';
        $title .= '<div class="subtotal">Nombre d’heures opérateurs = '.$this->format($this->totalO, "float").'</div>';
        $title .= $this->totalCsvLink("total-operateurs", "transac-usage");
        echo $this->templateDisplay($title);
    }

}
