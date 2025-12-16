<?php

/**
 * ReportMontants class allows to generate reports about clients, classes and articles distribution
 */
class ReportMontants extends Report
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
        $this->reportKey = 'montants';
        $this->reportColumns = ["client-code", "client-class", "item-codeD", "total-fact"];
        $this->totalCsvData = [
            "dimensions" => array_merge($this::CLIENT_DIM, $this::CLASSE_DIM, $this::ARTICLE_DIM),
            "operations" => ["total-fact"],
            "results" => []
        ];
        $this->tabs = [
            "par-client"=> [
                "title" => "Par Client",
                "columns" => ["client-name"],
                "dimensions" => $this::CLIENT_DIM,
                "operations" => ["total-fact"],
                "formats" => ["fin"],
                "results" => []
            ],
            "par-classe" => [
                "title" => "Par Classe",
                "columns" => ["client-labelclass"],
                "dimensions" => $this::CLASSE_DIM,
                "operations" => ["total-fact"],
                "formats" => ["fin"],
                "results" => []

            ],
            "par-article" =>[
                "title" => "Par Article",
                "columns" => ["item-labelcode"],
                "dimensions" => ["item-codeD", "item-labelcode"],
                "operations" => ["total-fact"],
                "formats" => ["fin"],
                "results" => []

            ],
            "par-client-classe" => [
                "title" => "Par Client & Classe",
                "columns" => ["client-name", "client-labelclass"],
                "dimensions" => array_merge($this::CLIENT_DIM, $this::CLASSE_DIM),
                "operations" => ["total-fact"],
                "formats" => ["fin"],
                "results" => []

            ],
            "par-client-article" => [
                "title" => "Par Client & Article",
                "columns" => ["client-name", "item-labelcode"],
                "dimensions" => array_merge($this::CLIENT_DIM, ["item-codeD", "item-labelcode"]),
                "operations" => ["total-fact"],
                "formats" => ["fin"],
                "results" => []

            ],
            "par-article-classe" => [
                "title" => "Par Article & Classe",
                "columns" => ["item-labelcode", "client-labelclass"],
                "dimensions" => array_merge(["item-codeD", "item-labelcode"], $this::CLASSE_DIM),
                "operations" => ["total-fact"],
                "formats" => ["fin"],
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
        $this->prepareClients();
        $this->prepareClasses();
        $this->prepareClientsClasses();
        $this->prepareArticles();

        $this->processReportFile();
    }

    /**
     * Generates report file and returns its data
     *
     * @return array
     */
    function generate(): array
    {
        $crpArray = [];
        if(floatval($this->factel) == 6) {
            $crpFileName = $this->getFileNameInBS('Bilancrp-f');
            if($crpFileName) {
                $lines = Csv::extract($crpFileName, true);
                $columns = $this->bilansStats->getColumns($this->factel, 'Bilancrp-f');
                foreach($lines as $line) {
                    $code = $line[$columns['client-code']];
                    $dM = $line[$columns["total-fact"]]-$line[$columns["total-fact-l"]]-$line[$columns["total-fact-c"]]-$line[$columns["total-fact-w"]]-$line[$columns["total-fact-x"]]-$line[$columns["total-fact-r"]];
                    $crpArray[$code] = ["dM" => $dM, "dMontants"=>[$line[$columns["total-fact-l"]], $line[$columns["total-fact-c"]], $line[$columns["total-fact-w"]], $line[$columns["total-fact-x"]], $line[$columns["total-fact-r"]]]];
                }
            }
        }

        $montantsArray = [];
        if(floatval($this->factel) >= 9) {
            $columns = $this->bilansStats->getColumns($this->factel, 'T1');
            $lines = Csv::extract($this->getFileNameInBS('T1'), true);
            $t1Array = [];
            foreach($lines as $line) {
                $code = $line[$columns['client-code']];
                $clcl = $line[$columns['client-class']];
                $item = $line[$columns['item-codeD']];
                if(!array_key_exists($code, $t1Array)) {
                    $t1Array[$code] = [];
                }
                if(!array_key_exists($clcl, $t1Array[$code])) {
                    $t1Array[$code][$clcl] = [];
                }
                if(!array_key_exists($item, $t1Array[$code][$clcl])) {
                    $t1Array[$code][$clcl][$item] = 0;
                }
                $t1Array[$code][$clcl][$item] += floatval($line[$columns["total-fact"]]);
            }
            foreach($t1Array as $code=>$pc) {
                foreach($pc as $clcl=>$pcl) {
                    foreach($pcl as $item=>$tot) {
                        $montantsArray[] = [$code, $clcl, $item, round((2*$tot),1)/2];
                    }
                }
            }
        }
        else {
            $columns = $this->bilansStats->getColumns($this->factel, 'Bilan-f');
            $lines = Csv::extract($this->getFileNameInBS('Bilan-f'), true);
            foreach($lines as $line) {
                $code = $line[$columns['client-code']];
                if($code != $this->plateforme) {
                    if(floatval($this->factel) < 7) {
                        $clcl = $this->clientsClasses[$code]['client-class'];
                    }
                    else {
                        $clcl = $line[$columns['client-class']];
                    }
                    if(floatval($this->factel) == 1) {
                        $montant = $line[$columns["somme-t"]] + $line[$columns["emolument-b"]] - $line[$columns["emolument-r"]] - $line[$columns["total-fact-l"]]
                                - $line[$columns["total-fact-c"]] - $line[$columns["total-fact-w"]] - $line[$columns["total-fact-x"]];
                        $this->facts($montantsArray, $montant, $line, $columns, $clcl);
                    }
                    elseif(floatval($this->factel) >=3 && floatval($this->factel) < 6) {
                        $montant = $line[$columns["total-fact"]] -$line[$columns["total-fact-l"]] - $line[$columns["total-fact-c"]]
                                - $line[$columns["total-fact-w"]] - $line[$columns["total-fact-x"]] - $line[$columns["total-fact-r"]];
                        $this->facts($montantsArray, $montant, $line, $columns, $clcl);
                    }
                    elseif(floatval($this->factel) == 6) {
                        $montant = $line[$columns["total-fact"]] - $line[$columns["total-fact-l"]] - $line[$columns["total-fact-c"]]
                                - $line[$columns["total-fact-w"]] - $line[$columns["total-fact-x"]] - $line[$columns["total-fact-r"]];
                        if(!empty($crpArray) && array_key_exists($code, $crpArray)) {
                            $this->facts($montantsArray, $montant, $line, $columns, $clcl, $crpArray[$code]["dM"], $crpArray[$code]["dMontants"]);
                        }
                        else {
                            $this->facts($montantsArray, $montant, $line, $columns, $clcl);
                        }
                    }
                    elseif(floatval($this->factel) == 7 || floatval($this->factel) == 8) {
                        if($line[$columns["platf-code"]] == $this->plateforme) {
                            $montant = $line[$columns["total-fact"]];
                            if(($line[$columns["item-codeD"]] == "R") && ($montant < 50)) {
                                $montant = 0;
                            }
                            $montantsArray[] = [$code, $clcl, $line[$columns["item-codeD"]], round((2*$montant),1)/2];
                        }
                    }
                }
            }
        }
        return $montantsArray;
    }

    /**
     * Generates amounts line
     *
     * @param array $montantsArray array containing lines for the report
     * @param float $montant total amount
     * @param array $tab base data line
     * @param array $columns base data line columns
     * @param string $clcl client classe
     * @param float $dM total amount substraction
     * @param array $dMontants part amounts substraction
     * @return void
     */
    function facts(array &$montantsArray, float $montant, array $tab, array $columns, string $clcl, float $dM=0.0, array $dMontants=[0, 0, 0, 0, 0]): void
    {
        $code = $tab[$columns['client-code']];
        $facts = ["total-fact-l", "total-fact-c", "total-fact-w", "total-fact-x", "total-fact-r"];
        $types = ["L", "C", "W", "X", "R"];
        if(($montant - $dM) > 0) {
            $montantsArray[] = [$code, $clcl, "M", round((2*($montant - $dM)),1)/2];
        }
        foreach($facts as $pos=>$fact) {
            if(array_key_exists($fact, $columns)) {
                if(($tab[$columns[$fact]] - $dMontants[$pos]) > 0) {
                    $montantsArray[] = [$code, $clcl, $types[$pos], round((2*($tab[$columns[$fact]] - $dMontants[$pos])),1)/2];
                }
            }
        }
    }

    /**
     * Maps report data for tabs tables and csv
     *
     * @param array $montantsArray report data
     * @return void
     */
    function mapping(array $montantsArray): void
    {
        foreach($montantsArray as $line) {
            $this->totalM += $line[3];
            $client = $this->clients[$line[0]];
            $classe = $this->classes[$line[1]];
            $article = $this->articles[$line[2]];
            $montant = $line[3];
            $ids = [
                "par-client"=>$line[0],
                "par-classe"=>$line[1],
                "par-article"=>$line[2],
                "par-client-classe"=>$line[0]."-".$line[1],
                "par-client-article"=>$line[0]."-".$line[2],
                "par-article-classe"=>$line[2]."-".$line[1],
                "for-csv"=>$line[0]."-".$line[1]."-".$line[2]
            ];
            $extends = [
                "par-client"=>[$client],
                "par-classe"=>[$classe],
                "par-article"=>[$article],
                "par-client-classe"=>[$client, $classe],
                "par-client-article"=>[$client, $article],
                "par-article-classe"=>[$article, $classe],
                "for-csv"=>[$client, $classe, $article]
            ];
            $dimensions = [
                "par-client"=>[$this::CLIENT_DIM],
                "par-classe"=>[$this::CLASSE_DIM],
                "par-article"=>[$this::ARTICLE_DIM],
                "par-client-classe"=>[$this::CLIENT_DIM, $this::CLASSE_DIM],
                "par-client-article"=>[$this::CLIENT_DIM, $this::ARTICLE_DIM],
                "par-article-classe"=>[$this::ARTICLE_DIM, $this::CLASSE_DIM],
                "for-csv"=>[$this::CLIENT_DIM, $this::CLASSE_DIM, $this::ARTICLE_DIM]
            ];

            foreach($this->tabs as $tab=>$data) {
                if(!array_key_exists($ids[$tab], $this->tabs[$tab]["results"])) {
                    $this->tabs[$tab]["results"][$ids[$tab]] = ["total-fact" => 0, "mois" => []];
                    foreach($dimensions[$tab] as $pos=>$dimension) {
                        foreach($dimension as $d) {
                            $this->tabs[$tab]["results"][$ids[$tab]][$d] = $extends[$tab][$pos][$d];
                        }
                    }
                }
                if(!array_key_exists($this->monthly, $this->tabs[$tab]["results"][$ids[$tab]]["mois"])) {
                    $this->tabs[$tab]["results"][$ids[$tab]]["mois"][$this->monthly] = 0;
                }
                $this->tabs[$tab]["results"][$ids[$tab]]["total-fact"] += $montant;
                $this->tabs[$tab]["results"][$ids[$tab]]["mois"][$this->monthly] += $montant;
            }
            // total csv
            if(!array_key_exists($ids["for-csv"], $this->totalCsvData["results"])) {
                $this->totalCsvData["results"][$ids["for-csv"]] = ["total-fact" => 0, "mois" => []];
                foreach($dimensions["for-csv"] as $pos=>$dimension) {
                    foreach($dimension as $d) {
                        $this->totalCsvData["results"][$ids["for-csv"]][$d] = $extends["for-csv"][$pos][$d];
                    }
                }
            }
            if(!array_key_exists($this->monthly, $this->totalCsvData["results"][$ids["for-csv"]]["mois"])) {
                $this->totalCsvData["results"][$ids["for-csv"]]["mois"][$this->monthly] = 0;
            }
            $this->totalCsvData["results"][$ids["for-csv"]]["total-fact"] += $montant;
            $this->totalCsvData["results"][$ids["for-csv"]]["mois"][$this->monthly] += $montant;
        }
    }

    /**
     * Displays title and tabs
     *
     * @return void
     */
    function display(): void
    {
        $title = '<div class="total">Total facturé sur la période '.$this->period().' : '.$this->format($this->totalM).' CHF</div>';
        $title .= $this->totalCsvLink("total-montants", "total-fact");
        echo $this->templateDisplay($title);
    }

}
