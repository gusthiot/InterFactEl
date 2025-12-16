<?php

/**
 * ReportServices class allows to generate reports about services stats
 */
class ReportServices extends Report
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
        $this->reportKey = 'statsrv';
        $this->reportColumns = ["client-code", "client-class", "item-text2K", "oper-note", "item-grp", "item-codeK", "transac-quantity", "transac-usage"];
        $this->tabs = [
            "services" => [
                "title" => "Stats par Services",
                "columns" => ["item-text2K", "oper-note", "item-textK", "item-name", "item-unit"],
                "dimensions" => array_merge($this::SERVICE_DIM, $this::CODEK_DIM, $this::GROUPE_DIM, $this::CATEGORIE_DIM),
                "operations" => ["transac-quantity", "transac-usage"],
                "formats" => ["float", "float"],
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
        $this->loadMachinesGroupes();

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
        $lines = Csv::extract($this->getFileNameInBS('T3'), true);
        foreach($lines as $line) {
            if(($line[$columns["year"]] == $line[$columns["editing-year"]]) && ($line[$columns["month"]] == $line[$columns["editing-month"]]) && ($line[$columns["flow-type"]] == "srv")) {
                $id = $line[$columns["client-code"]]."--".$line[$columns["client-class"]]."--".$line[$columns["item-text2K"]]."--".$line[$columns["oper-note"]]."--".$line[$columns["item-grp"]]."--".$line[$columns["item-codeK"]];
                if(!array_key_exists($id, $loopArray)) {
                    $loopArray[$id] = ['Smu' => 0, 'Q' => 0];
                }
                $loopArray[$id]['Smu'] += $line[$columns["transac-usage"]];
                $loopArray[$id]['Q'] += $line[$columns["transac-quantity"]];
            }
        }
        $servicesArray = [];
        foreach($loopArray as $id=>$line) {
            $ids = explode("--", $id);
            $servicesArray[] = [$ids[0], $ids[1], $ids[2], $ids[3], $ids[4], $ids[5], round($line["Q"], 3), round($line["Smu"], 3)];
        }
        return $servicesArray;
    }

    /**
     * Maps report data for tabs tables and csv
     *
     * @param array $servicesArray report data
     * @return void
     */
    function mapping(array $servicesArray): void
    {
        foreach($servicesArray as $line) {
            $groupe = $this->groupes[$line[4]];
            $itemId = $groupe["item-id-".$line[5]];
            $categorie = $this->categories[$itemId];
            $codeK = ["item-codeK"=>$line[5], "item-textK"=>$this->paramtext->getParam("item-".$line[5])];
            $service = ["item-text2K"=>$line[2], "oper-note"=>$line[3]];
            $values = [
                "transac-quantity"=>$line[6],
                "transac-usage"=>$line[7]
            ];
            $ids = [
                "services"=>$line[2]."-".$line[3]."-".$line[5]."-".$line[4]
            ];
            $extends = [
                "services"=>[$service, $codeK, $groupe, $categorie]
            ];
            $dimensions = [
                "services"=>[$this::SERVICE_DIM, $this::CODEK_DIM, $this::GROUPE_DIM, $this::CATEGORIE_DIM]
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
                foreach($this->tabs[$tab]["operations"] as $operation) {
                    $this->tabs[$tab]["results"][$ids[$tab]][$operation] += $values[$operation];
                }
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
        $title = '<div class="total">Statistiques services : '.$this->period().' </div>';
        echo $this->templateDisplay($title);
    }

}
