<?php

class ReportMontants extends Report
{
    
    public function __construct($plateforme, $to, $from) 
    { 
        parent::__construct($plateforme, $to, $from);
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

    function prepare() {
        $this->prepareClients();
        $this->prepareClasses();
        $this->prepareClientsClasses();
        $this->prepareArticles();

        $this->processReportFile();
    }

    function generate()
    {
        $crpArray = [];
        if($this->factel == 6) {
            $crpFileName = $this->getFileNameInBS('Bilancrp-f');
            if($crpFileName) {
                $lines = Csv::extract($crpFileName);        
                $columns = $this->bilansStats[$this->factel]['Bilancrp-f']['columns'];
                for($i=1;$i<count($lines);$i++) {
                    $tab = explode(";", $lines[$i]);
                    $code = $tab[$columns['client-code']];
                    $dM = $tab[$columns["total-fact"]]-$tab[$columns["total-fact-l"]]-$tab[$columns["total-fact-c"]]-$tab[$columns["total-fact-w"]]-$tab[$columns["total-fact-x"]]-$tab[$columns["total-fact-r"]];
                    $crpArray[$code] = ["dM" => $dM, "dMontants"=>[$tab[$columns["total-fact-l"]], $tab[$columns["total-fact-c"]], $tab[$columns["total-fact-w"]], $tab[$columns["total-fact-x"]], $tab[$columns["total-fact-r"]]]];
                }
            }
        }

        $montantsArray = [];
        if($this->factel >= 9) {
            $columns = $this->bilansStats[$this->factel]['T1']['columns'];
            $lines = Csv::extract($this->getFileNameInBS('T1'));
            $t1Array = [];
            for($i=1;$i<count($lines);$i++) {
                $tab = explode(";", $lines[$i]);
                $code = $tab[$columns['client-code']];
                $clcl = $tab[$columns['client-class']];
                $item = $tab[$columns['item-codeD']];
                if(!array_key_exists($code, $t1Array)) {
                    $t1Array[$code] = [];
                }
                if(!array_key_exists($clcl, $t1Array[$code])) {
                    $t1Array[$code][$clcl] = [];
                }
                if(!array_key_exists($item, $t1Array[$code][$clcl])) {
                    $t1Array[$code][$clcl][$item] = 0;
                }
                $t1Array[$code][$clcl][$item] += floatval($tab[$columns["total-fact"]]);
            }
            foreach($t1Array as $code=>$pc) {
                foreach($pc as $clcl=>$pcl) {
                    foreach($pcl as $item=>$tot) {
                        $montantsArray[] = [$code, $clcl, $item, $tot];
                    }
                }
            }
        }
        else {
            $columns = $this->bilansStats[$this->factel]['Bilan-f']['columns'];
            $lines = Csv::extract($this->getFileNameInBS('Bilan-f'));
            for($i=1;$i<count($lines);$i++) {
                $tab = explode(";", $lines[$i]);
                $code = $tab[$columns['client-code']];
                if($code != $this->plateforme) {
                    if($this->factel < 7) {
                        $clcl = $this->clientsClasses[$code]['client-class'];
                    }
                    else {
                        $clcl = $tab[$columns['client-class']];
                    }
                    if($this->factel == 1) {
                        $montant = $tab[$columns["somme-t"]] + $tab[$columns["emolument-b"]] - $tab[$columns["emolument-r"]] - $tab[$columns["total-fact-l"]]
                                - $tab[$columns["total-fact-c"]] - $tab[$columns["total-fact-w"]] - $tab[$columns["total-fact-x"]];
                        $montantsArray = $this->facts($montantsArray, $montant, $tab, $columns, $clcl);
                    }
                    elseif($this->factel >=3 && $this->factel < 6) {
                        $montant = $tab[$columns["total-fact"]] -$tab[$columns["total-fact-l"]] - $tab[$columns["total-fact-c"]]
                                - $tab[$columns["total-fact-w"]] - $tab[$columns["total-fact-x"]] - $tab[$columns["total-fact-r"]];
                        $montantsArray = $this->facts($montantsArray, $montant, $tab, $columns, $clcl);
                    }
                    elseif($this->factel == 6) {
                        $montant = $tab[$columns["total-fact"]] - $tab[$columns["total-fact-l"]] - $tab[$columns["total-fact-c"]]
                                - $tab[$columns["total-fact-w"]] - $tab[$columns["total-fact-x"]] - $tab[$columns["total-fact-r"]];
                        if(!empty($crpArray) && array_key_exists($code, $crpArray)) {
                            $montantsArray = $this->facts($montantsArray, $montant, $tab, $columns, $clcl, $crpArray[$code]["dM"], $crpArray[$code]["dMontants"]);
                        } 
                        else {
                            $montantsArray = $this->facts($montantsArray, $montant, $tab, $columns, $clcl);
                        }        
                    }
                    elseif($this->factel == 7 || $this->factel == 8) {
                        if($tab[$columns["platf-code"]] == $this->plateforme) {
                            $montant = $tab[$columns["total-fact"]];
                            if(($tab[$columns["item-codeD"]] == "R") && ($montant < 50)) {
                                $montant = 0;
                            }
                            $montantsArray[] = [$code, $clcl, $tab[$columns["item-codeD"]], $montant];
                        }
                    }
                }
            }
        }
        for($i=0;$i<count($montantsArray);$i++) {
            $montantsArray[$i][3] = round((2*$montantsArray[$i][3]),1)/2;
        }
        return $montantsArray;
    }

    function mapping($montantsArray)
    {
        foreach($montantsArray as $line) {
            $this->total += $line[3];
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

    function facts($montantsArray, $montant, $tab, $columns, $clcl, $dM=0, $dMontants=[0, 0, 0, 0, 0]) 
    {
        $code = $tab[$columns['client-code']];
        $facts = ["total-fact-l", "total-fact-c", "total-fact-w", "total-fact-x", "total-fact-r"];
        $types = ["L", "C", "W", "X", "R"];
        if(($montant - $dM) > 0) {
            $montantsArray[] = [$code, $clcl, "M", ($montant - $dM)];
        }
        foreach($facts as $pos=>$fact) {
            if(array_key_exists($fact, $columns)) {
                if(($tab[$columns[$fact]] - $dMontants[$pos]) > 0) {
                    $montantsArray[] = [$code, $clcl, $types[$pos], ($tab[$columns[$fact]] - $dMontants[$pos])];
                }
            }
        }
        return $montantsArray;
    }

    function display()
    {
        $this->createTotalCsv("total-fact");
        $title = '<div class="total">Total facturé sur la période '.$this->period().' : '.$this->format($this->total, "fin").' CHF</div>';
        $title .= $this->totalCsvLink("total-montants");
        echo $this->templateDisplay($title);
    }

}
