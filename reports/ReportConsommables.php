<?php

/**
 * ReportConsommables class allows to generate reports about consommables stats
 */
class ReportConsommables extends Report
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
        $this->reportKey = 'statlvr';
        $this->reportColumns = ["client-code", "user-sciper", "item-id", "transac-usage"];
        $this->tabs = [
            "consos" => [
                "title" => "Stats par Consommable",
                "columns" => ["item-name", "item-labelcode", "item-unit"],
                "dimensions" => array_merge($this::PRESTATION_DIM, $this::MACHINE_DIM),
                "operations" => ["transac-quantity"],
                "formats" => ["int"],
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
        $this->preparePrestations();
        $this->prepareUsers();

        $this->processReportFile();
    }

    /**
     * generates report file and returns its data
     *
     * @return array
     */
    function generate(): array
    {
        $consosArray = [];
        $loopArray = [];
        if(floatval($this->factel) < 10) {

            if(floatval($this->factel) < 7) {
                $columns = $this->bilansStats[$this->factel]['lvr']['columns'];
                $lines = Csv::extract($this->getFileNameInBS('lvr'));
                for($i=1;$i<count($lines);$i++) {
                    $tab = explode(";", $lines[$i]);
                    $itemId = $tab[$columns["item-id"]];
                    $plateId = $this->prestations[$itemId]["platf-code"];
                    if($plateId == $this->plateforme) {
                        $id = $tab[$columns["client-code"]]."--".$tab[$columns["user-id"]]."--".$tab[$columns["item-id"]];
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
                    if(($this->plateforme == $tab[$columns["platf-code"]]) && ($tab[$columns["flow-type"]] == "lvr")) {
                        $id = $tab[$columns["client-code"]]."--".$tab[$columns["user-id"]]."--".$tab[$columns["item-id"]];
                        if(!array_key_exists($id, $loopArray)) {
                            $loopArray[$id] = 0;
                        }
                        $loopArray[$id] += $tab[$columns["transac-usage"]];
                    }
                }
            }
            foreach($loopArray as $id=>$q) {
                $ids = explode("--", $id);
                $consosArray[] = [$ids[0], $this->sciper($ids[1]), $ids[2], $q];
            }
        }
        else {
            $columns = $this->bilansStats[$this->factel]['T3']['columns'];
            $lines = Csv::extract($this->getFileNameInBS('T3'));
            for($i=1;$i<count($lines);$i++) {
                $tab = explode(";", $lines[$i]);
                if(($tab[$columns["year"]] == $tab[$columns["editing-year"]]) && ($tab[$columns["month"]] == $tab[$columns["editing-month"]]) && ($tab[$columns["flow-type"]] == "lvr")) {
                    $id = $tab[$columns["client-code"]]."--".$tab[$columns["user-id"]]."--".$tab[$columns["item-id"]];
                    if(!array_key_exists($id, $loopArray)) {
                        $loopArray[$id] = ['Smu' => 0, 'Q' => 0];
                    }
                    $loopArray[$id]['Smu'] += $tab[$columns["transac-usage"]];
                    $loopArray[$id]['Q'] += $tab[$columns["transac-quantity"]];
                }
            }
            foreach($loopArray as $id=>$line) {
                $ids = explode("--", $id);
                intval($this->year.$this->month) > 202408 ? $q = $line['Smu'] : $q = $line['Q']; 
                $consosArray[] = [$ids[0], $this->sciper($ids[1]), $ids[2], $q];
            }
        }

        for($i=0;$i<count($consosArray);$i++) {
            $consosArray[$i][3] = round($consosArray[$i][3],3);
        }
        return $consosArray;
    }

    /**
     * maps report data for tabs tables and csv 
     *
     * @param array $consosArray report data
     * @return void
     */
    function mapping(array $consosArray): void
    {
        foreach($consosArray as $line) {
            $prestation = $this->prestations[$line[2]];
            if(!array_key_exists($line[2], $this->tabs["consos"]["results"])) {
                $this->tabs["consos"]["results"][$line[2]] = [];
                foreach($this->tabs["consos"]["dimensions"] as $dimension) {
                    $this->tabs["consos"]["results"][$line[2]][$dimension] = $prestation[$dimension];
                }
                foreach($this->tabs["consos"]["operations"] as $operation) {
                    $this->tabs["consos"]["results"][$line[2]][$operation] = 0;
                }
            }
            foreach($this->tabs["consos"]["operations"] as $operation) {
                $this->tabs["consos"]["results"][$line[2]][$operation] += $line[3];
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
        $title = '<div class="total">Statistiques consommables : '.$this->period().' </div>';
        echo $this->templateDisplay($title);
    }

}
