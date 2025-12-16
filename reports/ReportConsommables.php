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
     * Prepares dimensions, generates report file if not exists and extracts its data
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
     * Generates report file and returns its data
     *
     * @return array
     */
    function generate(): array
    {
        $consosArray = [];
        $loopArray = [];
        if(floatval($this->factel) < 10) {
            if(floatval($this->factel) < 7) {
                $columns = $this->bilansStats->getColumns($this->factel, 'lvr');
                $lines = Csv::extract($this->getFileNameInBS('lvr'), true);
                foreach($lines as $line) {
                    $itemId = $line[$columns["item-id"]];
                    $plateId = $this->prestations[$itemId]["platf-code"];
                    if($plateId == $this->plateforme) {
                        $id = $line[$columns["client-code"]]."--".$line[$columns["user-id"]]."--".$itemId;
                        if(!array_key_exists($id, $loopArray)) {
                            $loopArray[$id] = 0;
                        }
                        $loopArray[$id] += $line[$columns["transac-usage"]];
                    }
                }
            }
            else {
                $columns = $this->bilansStats->getColumns($this->factel, 'T3');
                $lines = Csv::extract($this->getFileNameInBS('T3'), true);
                foreach($lines as $line) {
                    if(($this->plateforme == $line[$columns["platf-code"]]) && ($line[$columns["flow-type"]] == "lvr")) {
                        if(floatval($this->factel) >= 9 && floatval($this->factel) < 10) {
                            $datetime = explode(" ", $line[$columns["transac-date"]]);
                            $parts = explode("-", $datetime[0]);
                            $cond = ($parts[0] == $this->year) && ($parts[1] == $this->month);
                        }
                        else {
                            $cond = true;
                        }
                        if($cond) {
                            $id = $line[$columns["client-code"]]."--".$line[$columns["user-id"]]."--".$line[$columns["item-id"]];
                            if(!array_key_exists($id, $loopArray)) {
                                $loopArray[$id] = 0;
                            }
                            $loopArray[$id] += $line[$columns["transac-quantity"]];
                        }
                    }
                }
            }
            foreach($loopArray as $id=>$q) {
                $ids = explode("--", $id);
                $consosArray[] = [$ids[0], $this->sciper($ids[1]), $ids[2], round($q, 3)];
            }
        }
        else {
            $columns = $this->bilansStats->getColumns($this->factel, 'T3');
            $lines = Csv::extract($this->getFileNameInBS('T3'), true);
            foreach($lines as $line) {
                if(($line[$columns["invoice-year"]] == $line[$columns["editing-year"]]) && ($line[$columns["invoice-month"]] == $line[$columns["editing-month"]]) && ($line[$columns["flow-type"]] == "lvr")) {
                    $id = $line[$columns["client-code"]]."--".$line[$columns["user-id"]]."--".$line[$columns["item-id"]];
                    if(!array_key_exists($id, $loopArray)) {
                        $loopArray[$id] = ['Smu' => 0, 'Q' => 0];
                    }
                    $loopArray[$id]['Smu'] += $line[$columns["transac-usage"]];
                    $loopArray[$id]['Q'] += $line[$columns["transac-quantity"]];
                }
            }
            foreach($loopArray as $id=>$line) {
                $ids = explode("--", $id);
                intval($this->year.$this->month) > 202408 ? $q = $line['Smu'] : $q = $line['Q'];
                $consosArray[] = [$ids[0], $this->sciper($ids[1]), $ids[2], round($q, 3)];
            }
        }

        return $consosArray;
    }

    /**
     * Maps report data for tabs tables and csv
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
     * Displays title and tabs
     *
     * @return void
     */
    function display(): void
    {
        $title = '<div class="total">Statistiques consommables : '.$this->period().' </div>';
        echo $this->templateDisplay($title);
    }

}
