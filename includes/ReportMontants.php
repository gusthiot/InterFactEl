<?php

class ReportMontants extends Report
{
    
    public function __construct($plateforme, $to, $from) 
    { 
        parent::__construct($plateforme, $to, $from);
        $this->reportKey = 'montants';
        $this->master = ["par-client"=>[], "par-class"=>[], "par-article"=>[], "par-client-class"=>[], "par-client-article"=>[], "par-article-class"=>[], "par-client-class-article"=>[]];
        $this->onglets = ["par-client" => "Par Client", "par-class" => "Par Classe", "par-article" => "Par Article", "par-client-class" => "Par Client & Classe", 
                            "par-client-article" => "Par Client & Article", "par-article-class" => "Par Article & Classe"];
        $this->columns = ["par-client" => ["client-name"], "par-class" => ["client-labelclass"], "par-article" => ["item-labelcode"], "par-client-class" => ["client-name", "client-labelclass"], 
                            "par-client-article" => ["client-name", "item-labelcode"], "par-article-class" => ["item-labelcode", "client-labelclass"]];
        $this->columnsCsv = ["par-client" => $this::D1, "par-class" => $this::D2, "par-article" => ["item-codeD", "item-labelcode"], "par-client-class" => array_merge($this::D1, $this::D2),
                                "par-client-article" => array_merge($this::D1, ["item-codeD", "item-labelcode"]), "par-article-class" => array_merge(["item-codeD", "item-labelcode"], $this::D2)];

    }

    function generate($suffix)
    {    
        $crpArray = [];
        if($this->factel == 6) {
            if(file_exists($this->dirRun."/Bilans_Stats/".$this->bilansStats[$this->factel]['Bilancrp-f']['prefix'].$suffix.".csv")) {
                $lines = Csv::extract($this->dirRun."/Bilans_Stats/".$this->bilansStats[$this->factel]['Bilancrp-f']['prefix'].$suffix.".csv");        
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
            $lines = Csv::extract($this->dirRun."/Bilans_Stats/".$this->bilansStats[$this->factel]['T1']['prefix'].$suffix.".csv");
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
            $lines = Csv::extract($this->dirRun."/Bilans_Stats/".$this->bilansStats[$this->factel]['Bilan-f']['prefix'].$suffix.".csv");
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
        $montantsColumns = [$this->paramtext->getParam("client-code"), $this->paramtext->getParam("client-class"), $this->paramtext->getParam("item-codeD"), $this->paramtext->getParam("total-fact")];
        Csv::write($this->dirRun."/REPORT/".$this->report[$this->factel][$this->reportKey]['prefix'].".csv", array_merge([$montantsColumns], $montantsArray));
        return $montantsArray;
    }

    function masterise($montantsArray)
    {
        foreach($montantsArray as $line) {
            $this->total += $line[3];
            $client = $this->clients[$line[0]];
            $class = $this->classes[$line[1]];
            $article = $this->articles[$line[2]];
            $montant = $line[3];
            $keys = ["par-client"=>$line[0], "par-class"=>$line[1], "par-article"=>$line[2], "par-client-class"=>$line[0]."-".$line[1], "par-client-article"=>$line[0]."-".$line[2],
                     "par-article-class"=>$line[2]."-".$line[1], "par-client-class-article"=>$line[0]."-".$line[1]."-".$line[2]];
            $extends = ["par-client"=>[$client], "par-class"=>[$class], "par-article"=>[$article], "par-client-class"=>[$client, $class], "par-client-article"=>[$client, $article],
                        "par-article-class"=>[$article, $class], "par-client-class-article"=>[$client, $class, $article]];
            $dimensions = ["par-client"=>[$this::D1], "par-class"=>[$this::D2], "par-article"=>[$this::D3], "par-client-class"=>[$this::D1, $this::D2], "par-client-article"=>[$this::D1, $this::D3],
                           "par-article-class"=>[$this::D3, $this::D2], "par-client-class-article"=>[$this::D1, $this::D2, $this::D3]];

            foreach($keys as $id=>$key) {
                $this->mapping($key, $id, $extends, $dimensions, $montant);
            }
        }
    }

    function mapping($key, $id, $extends, $dimensions, $montant) 
    {
        if(!array_key_exists($key, $this->master[$id])) {
            $this->master[$id][$key] = ["total-fact" => 0, "mois" => []];
            foreach($dimensions[$id] as $pos=>$dimension) {
                foreach($dimension as $d) {
                    $this->master[$id][$key][$d] = $extends[$id][$pos][$d];
                }
            }
        }
        if(!array_key_exists($this->monthly, $this->master[$id][$key]["mois"])) {
            $this->master[$id][$key]["mois"][$this->monthly] = 0;
        }
        $this->master[$id][$key]["total-fact"] += $montant;
        $this->master[$id][$key]["mois"][$this->monthly] += $montant;
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

    static function sortTotal($a, $b) 
    {
        return floatval($b["total-fact"]) - floatval($a["total-fact"]);
    }

    function display()
    {
        $this->csv = $this->csvHeader(array_merge($this::D1, $this::D2, $this::D3), "total-fact");
        foreach($this->master["par-client-class-article"] as $line) {
            if(floatval($line["total-fact"]) > 0) {
                $this->csv .= "\n".$this->csvLine(array_merge($this::D1, $this::D2, $this::D3), $line, "total-fact");
            }
        }
        echo $this->templateDisplay("Total facturé sur la période", "total-fact", "total-montants");
    }

}
