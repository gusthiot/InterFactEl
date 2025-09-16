<?php

/**
 * ReportRabais class allows to generate reports about rabais and bonus
 */
class ReportRabais extends Report
{
    /**
     * total amount
     *
     * @var float
     */
    private float $totalR;

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
        $this->totalR = 0.0;
        $this->reportKey = 'rabaisbonus';
        $this->reportColumns = ["client-code", "client-class", "item-codeD", "deduct-CHF", "subsid-deduct", "discount-bonus", "subsid-bonus"];
        $this->totalCsvData = [
            "dimensions" => array_merge($this::CLIENT_DIM, $this::CLASSE_DIM, $this::ARTICLE_DIM),
            "operations" => ["deduct-CHF", "subsid-deduct", "discount-bonus", "subsid-bonus", "total-subsid"],
            "results" => []
        ];
        $this->tabs = [
            "reimbursed" => [
                "title" => "A rembourser",
                "columns" => ["client-name"],
                "dimensions" => $this::CLIENT_DIM,
                "operations" => ["discount-bonus", "subsid-bonus", "total-subsid"],
                "formats" => ["fin", "fin", "fin"],
                "results" => []
            ], 
            "subsid" => [
                "title" => "Rabais et subsides par client",
                "columns" => ["client-name"],
                "dimensions" => $this::CLIENT_DIM,
                "operations" => ["deduct-CHF", "subsid-deduct", "discount-bonus", "subsid-bonus", "total-subsid"],
                "formats" => ["fin", "fin", "fin", "fin", "fin"],
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
        $this->prepareArticles();

        $this->processReportFile();
    }
    
    /**
     * generates report file and returns its data
     *
     * @return array
     */
    function generate(): array
    {
        $rabaisArray = [];
        if(floatval($this->factel) == 7 || floatval($this->factel) == 8) {
            $columns = $this->bilansStats->getColumns($this->factel, 'Bilan-f');
            $lines = Csv::extract($this->getFileNameInBS('Bilan-f'));
            for($i=1;$i<count($lines);$i++) {
                $tab = explode(";", $lines[$i]);
                if(($tab[$columns["platf-code"]] == $this->plateforme) && ($tab[$columns['client-code']] != $this->plateforme )) {
                    $rabaisArray[] = [$tab[$columns['client-code']], $tab[$columns['client-class']], $tab[$columns["item-codeD"]], round($tab[$columns["deduct-CHF"]], 3),
                                        round($tab[$columns["subsid-deduct"]], 3), round($tab[$columns["discount-bonus"]], 3), round($tab[$columns["subsid-bonus"]], 3)];
                }
            }
        }
        else {
            $columns = $this->bilansStats->getColumns($this->factel, 'Bilan-s');
            $lines = Csv::extract($this->getFileNameInBS('Bilan-s'));
            for($i=1;$i<count($lines);$i++) {
                $tab = explode(";", $lines[$i]);
                $code = $tab[$columns['client-code']];
                if($code != $this->plateforme) {
                    if(floatval($this->factel) < 7) {
                        if(floatval($this->factel) == 6) {
                            $msm = $tab[$columns["subsides-m"]];
                        }
                        else {
                            $msm = $tab[$columns["subsides-a"]] + $tab[$columns["subsides-o"]];
                        }
                        $clcl = $this->clientsClasses[$code]['client-class'];
                        $mbm = $tab[$columns["bonus-m"]];
                        $msc = $tab[$columns["subsides-c"]];
                        if($mbm > 0 || $msm > 0) {
                            $rabaisArray[] = [$code, $clcl, "M", 0, 0, round($mbm, 3), round($msm, 3)];
                        }
                        if($msc > 0) {
                            $rabaisArray[] = [$code, $clcl, "C", 0, 0, 0, round($msc, 3)];
                        }
                    }
                    else {
                        if($code != $this->plateforme) {
                            $rabaisArray[] = [$code, $tab[$columns['client-class']], $tab[$columns["item-codeD"]], round($tab[$columns["deduct-CHF"]], 3),
                                                round($tab[$columns["subsid-deduct"]], 3), round($tab[$columns["discount-bonus"]], 3), round($tab[$columns["subsid-bonus"]], 3)];
                        }
                    }
                }
            }
        }
        return $rabaisArray;
    }

    /**
     * maps report data for tabs tables and csv 
     *
     * @param array $rabaisArray report data
     * @return void
     */
    function mapping(array $rabaisArray): void
    {
        foreach($rabaisArray as $line) {
            $this->totalR += $line[3]+$line[4]+$line[5]+$line[6];
            $client = $this->clients[$line[0]];
            $classe = $this->classes[$line[1]];
            $article = $this->articles[$line[2]];
            $values = [
                "deduct-CHF"=>$line[3],
                "subsid-deduct"=>$line[4],
                "discount-bonus"=>$line[5],
                "subsid-bonus"=>$line[6]
            ];
            $ids = [
                "reimbursed"=>$line[0],
                "subsid"=>$line[0],
                "for-csv"=>$line[0]."-".$line[1]."-".$line[2]
            ];
            $extends = [
                "reimbursed"=>[$client],
                "subsid"=>[$client],
                "for-csv"=>[$client, $classe, $article]
            ];
            $dimensions = [
                "reimbursed"=>[$this::CLIENT_DIM],
                "subsid"=>[$this::CLIENT_DIM],
                "for-csv"=>[$this::CLIENT_DIM, $this::CLASSE_DIM, $this::ARTICLE_DIM]
            ];
            
            foreach($this->tabs as $tab=>$data) {
                if(!array_key_exists($ids[$tab], $this->tabs[$tab]["results"])) {
                    $this->tabs[$tab]["results"][$ids[$tab]] = ["total-subsid" => 0, "mois" => []];            
                    foreach($dimensions[$tab] as $pos=>$dimension) {
                        foreach($dimension as $d) {
                            $this->tabs[$tab]["results"][$ids[$tab]][$d] = $extends[$tab][$pos][$d];
                        }
                    }
                    foreach($values as $operation=>$value) {
                        if(in_array($operation, $this->tabs[$tab]["operations"])) {
                            $this->tabs[$tab]["results"][$ids[$tab]][$operation] = 0;
                        }
                    }
                }
                    
                if(!array_key_exists($this->monthly, $this->tabs[$tab]["results"][$ids[$tab]]["mois"])) {
                    $this->tabs[$tab]["results"][$ids[$tab]]["mois"][$this->monthly] = 0;
                }
                $total = 0;
                foreach($values as $operation=>$value) {
                    if(in_array($operation, $this->tabs[$tab]["operations"])) {
                        $this->tabs[$tab]["results"][$ids[$tab]][$operation] += $value;
                        $total += $value;
                    }
                }
                $this->tabs[$tab]["results"][$ids[$tab]]["total-subsid"] += $total;
                $this->tabs[$tab]["results"][$ids[$tab]]["mois"][$this->monthly] += $total;
            }
            // total csv
            if(!array_key_exists($ids["for-csv"], $this->totalCsvData["results"])) {
                $this->totalCsvData["results"][$ids["for-csv"]] = ["total-subsid" => 0, "mois" => []];            
                foreach($dimensions["for-csv"] as $pos=>$dimension) {
                    foreach($dimension as $d) {
                        $this->totalCsvData["results"][$ids["for-csv"]][$d] = $extends["for-csv"][$pos][$d];
                    }
                }
                foreach($values as $operation=>$value) {
                    if(in_array($operation, $this->totalCsvData["operations"])) {
                        $this->totalCsvData["results"][$ids["for-csv"]][$operation] = 0;
                    }
                }
            }
                
            if(!array_key_exists($this->monthly, $this->totalCsvData["results"][$ids["for-csv"]]["mois"])) {
                $this->totalCsvData["results"][$ids["for-csv"]]["mois"][$this->monthly] = 0;
            }
            $total = 0;
            foreach($values as $operation=>$value) {
                if(in_array($operation, $this->totalCsvData["operations"])) {
                    $this->totalCsvData["results"][$ids["for-csv"]][$operation] += $value;
                    $total += $value;
                }
            }
            $this->totalCsvData["results"][$ids["for-csv"]]["total-subsid"] += $total;
            $this->totalCsvData["results"][$ids["for-csv"]]["mois"][$this->monthly] += $total;
        }   
    }

    /**
     * displays title and tabs
     *
     * @return void
     */
    function display(): void
    {
        $title = '<div class="total">Total rabais et subsides sur la période '.$this->period().' : '.$this->format($this->totalR).' CHF</div>';
        $title .= $this->totalCsvLink("total-subsides", "total-subsid");
        echo $this->templateDisplay($title);
    }

}
