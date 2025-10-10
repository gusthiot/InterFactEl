<?php

/**
 * ReportPropres class allows to generate reports about articles, projects and machines for self amounts
 */
class ReportPropres extends Report
{
    /**
     * Total amount
     *
     * @var float
     */
    private float $totalM;
        
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
        $this->totalM = 0.0;
        $this->reportKey = 'consopltf';
        $this->reportColumns = ["proj-id", "item-id", "valuation-net"];
        $this->totalCsvData = [
            "dimensions" => array_merge($this::PROJET_DIM, ["proj-expl"], $this::PRESTATION_DIM, $this::MACHINE_DIM, ["item-extra"]),
            "operations" => ["valuation-net"],
            "results" => []
        ];
        $this->tabs = [
            "par-article" => [
                "title" => "Par article",
                "columns" => ["item-nbr", "item-name"],
                "dimensions" => array_merge($this::PRESTATION_DIM, $this::MACHINE_DIM, ["item-extra"]),
                "operations" => ["valuation-net"],
                "formats" => ["fin"],
                "results" => []
            ],
            "par-projet" => [
                "title" => "Par projet",
                "columns" => ["proj-nbr", "proj-name"],
                "dimensions" => array_merge($this::PROJET_DIM, ["proj-expl"]),
                "operations" => ["valuation-net"],
                "formats" => ["fin"],
                "results" => []
            ],
            "par-projet-machine" => [
                "title" => "Par projet & machine",
                "columns" => ["proj-nbr", "proj-name", "mach-id", "mach-name"],
                "dimensions" => array_merge($this::PROJET_DIM, ["proj-expl"], $this::MACHINE_DIM, ["item-extra"]),
                "operations" => ["conso-propre-march-expl", "conso-propre-extra-expl", "conso-propre-march-proj", "conso-propre-extra-proj"],
                "formats" => ["fin", "fin", "fin", "fin"],
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
        $this->prepareComptes();
        $this->preparePrestations();

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
        $columns = $this->bilansStats->getColumns($this->factel, 'T3');
        $lines = Csv::extract($this->getFileNameInBS('T3'));
        for($i=1;$i<count($lines);$i++) {
            $tab = explode(";", $lines[$i]);
            if(($tab[$columns["flow-type"]] == "lvr") && ($tab[$columns["client-code"]] == $tab[$columns["platf-code"]]) && ($tab[$columns["item-flag-conso"]] == "OUI")) {
                if(floatval($this->factel) < 9) {
                    $cond = ($this->plateforme == $tab[$columns["platf-code"]]);
                }
                elseif(floatval($this->factel) >= 9 && floatval($this->factel) < 10) {
                    $datetime = explode(" ", $tab[$columns["transac-date"]]);
                    $parts = explode("-", $datetime[0]);
                    $cond = ($parts[0] == $this->year) && ($parts[1] == $this->month) && ($this->plateforme == $tab[$columns["platf-code"]]) && ($tab[$columns["transac-valid"]] != 2);
                }
                else {
                    $cond = ($tab[$columns["year"]] == $tab[$columns["editing-year"]]) && ($tab[$columns["month"]] == $tab[$columns["editing-month"]]) && ($tab[$columns["transac-valid"]] != 2);

                }                
                if($cond) {
                    $id = $tab[$columns["proj-id"]]."--".$tab[$columns["item-id"]];
                    if(!array_key_exists($id, $loopArray)) {
                        $loopArray[$id] = 0;
                    }
                    $loopArray[$id] += $tab[$columns["valuation-net"]];
                }
            }
        }
        $pltfArray = [];
        foreach($loopArray as $id=>$mu) {
            $ids = explode("--", $id);
            $pltfArray[] = [$ids[0], $ids[1], $mu];
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
            $compte = $this->comptes[$line[0]];
            $prestation = $this->prestations[$line[1]];

            $ids = [
                "par-article"=>$line[1], 
                "par-projet"=>$line[0], 
                "par-projet-machine"=>$line[0]."-".$prestation["mach-id"],
                "for-csv"=>$line[0]."-".$line[1]."-".$prestation["mach-id"]
            ];
            $extends = [
                "par-article"=>[$prestation],
                "par-projet"=>[$compte],
                "par-projet-machine"=>[$compte, $prestation],
                "for-csv"=>[$compte, $prestation]
            ];
            $dimensions = [
                "par-article"=>[array_merge($this::PRESTATION_DIM, $this::MACHINE_DIM, ["item-extra"])],
                "par-projet"=>[array_merge($this::PROJET_DIM, ["proj-expl"])],
                "par-projet-machine"=>[array_merge($this::PROJET_DIM, ["proj-expl"]), array_merge($this::MACHINE_DIM, ["item-extra"])],
                "for-csv"=>[array_merge($this::PROJET_DIM, ["proj-expl"]), array_merge($this::PRESTATION_DIM, $this::MACHINE_DIM, ["item-extra"])]
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
                if($tab == "par-projet-machine") {
                    if($compte["proj-expl"] == "TRUE") {
                        if($prestation["item-extra"] == "TRUE") {
                            $this->tabs[$tab]["results"][$ids[$tab]]["conso-propre-extra-expl"] += $line[2];
                        }
                        else {
                            $this->tabs[$tab]["results"][$ids[$tab]]["conso-propre-march-expl"] += $line[2];
                        }
                    }
                    else {
                        if($prestation["item-extra"] == "TRUE") {
                            $this->tabs[$tab]["results"][$ids[$tab]]["conso-propre-extra-proj"] += $line[2];
                        }
                        else {
                            $this->tabs[$tab]["results"][$ids[$tab]]["conso-propre-march-proj"] += $line[2];
                        }
                    }
                }
                else {
                    $this->tabs[$tab]["results"][$ids[$tab]]["valuation-net"] += $line[2];
                }
            }
            // total csv
            if(!array_key_exists($ids["for-csv"], $this->totalCsvData["results"])) {
                $this->totalCsvData["results"][$ids["for-csv"]] = ["valuation-net" => 0];            
                foreach($dimensions["for-csv"] as $pos=>$dimension) {
                    foreach($dimension as $d) {
                        $this->totalCsvData["results"][$ids["for-csv"]][$d] = $extends["for-csv"][$pos][$d];
                    }
                }
            }
            $this->totalCsvData["results"][$ids["for-csv"]]["valuation-net"] += $line[2];

            $this->totalM += $line[2];
        }
    }


    /**
     * Displays title and tabs
     *
     * @return void
     */
    function display(): void
    {
        $title = '<div class="total">Total des consommations propres sur la période (CHF) : '.$this->period().' </div>';
        $title .= '<div class="subtotal">'.$this->format($this->totalM).'</div>';        
        $title .= $this->totalCsvLink("total-propres", "valuation-net");
        echo $this->templateDisplay($title);
    }

}
