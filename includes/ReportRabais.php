<?php

class ReportRabais extends Report
{
    private $dsub;

    public function __construct($plateforme, $to, $from) 
    { 
        parent::__construct($plateforme, $to, $from);
        $this->reportKey = 'rabaisbonus';
        $this->totalKeys = ["total-subsid"];
        $this->reportColumns = ["client-code", "client-class", "item-codeD", "deduct-CHF", "subsid-deduct", "discount-bonus", "subsid-bonus"];
        $this->dsub = ["deduct-CHF", "subsid-deduct", "discount-bonus", "subsid-bonus"];
        $this->master = ["reimbursed"=>[], "subsid"=>[], "for-csv"=>[]];
        $this->onglets = ["reimbursed" => "A rembourser", "subsid" => "Rabais et subsides par client"];
        $this->columns = ["reimbursed" => ["client-name"], "subsid" => ["client-name"]];
        $this->columnsCsv = ["reimbursed" => array_merge($this::CLIENT_DIM, ["discount-bonus", "subsid-bonus"]), "subsid" => array_merge($this::CLIENT_DIM, $this->dsub)];
    
    }

    function prepare($suffix) {
        $this->prepareClients();
        $this->prepareClasses();
        $this->prepareClientsClasses();
        $this->prepareArticles();

        $this->processReportFile($suffix);
    }
    
    function generate($suffix)
    {
        $rabaisArray = [];
        if($this->factel == 7 || $this->factel == 8) {
            $columns = $this->bilansStats[$this->factel]['Bilan-f']['columns'];
            $lines = Csv::extract($this->getFileNameInBS('Bilan-f'));
            for($i=1;$i<count($lines);$i++) {
                $tab = explode(";", $lines[$i]);
                if(($tab[$columns["platf-code"]] == $this->plateforme) && ($tab[$columns['client-code']] != $this->plateforme )) {
                    $rabaisArray[] = [$tab[$columns['client-code']], $tab[$columns['client-class']], $tab[$columns["item-codeD"]], $tab[$columns["deduct-CHF"]],
                                        $tab[$columns["subsid-deduct"]], $tab[$columns["discount-bonus"]], $tab[$columns["subsid-bonus"]]];
                }
            }
        }
        else {
            $columns = $this->bilansStats[$this->factel]['Bilan-s']['columns'];
            $lines = Csv::extract($this->getFileNameInBS('Bilan-s'));
            for($i=1;$i<count($lines);$i++) {
                $tab = explode(";", $lines[$i]);
                $code = $tab[$columns['client-code']];
                if($code != $this->plateforme) {
                    if($this->factel < 7) {
                        if($this->factel == 6) {
                            $msm = $tab[$columns["subsides-m"]];
                        }
                        else {
                            $msm = $tab[$columns["subsides-a"]] + $tab[$columns["subsides-o"]];
                        }
                        $clcl = $this->clientsClasses[$code]['client-class'];
                        $mbm = $tab[$columns["bonus-m"]];
                        $msc = $tab[$columns["subsides-c"]];
                        if($mbm > 0 || $msm > 0) {
                            $rabaisArray[] = [$code, $clcl, "M", 0, 0, $mbm, $msm];
                        }
                        if($msc > 0) {
                            $rabaisArray[] = [$code, $clcl, "C", 0, 0, 0, $msc];
                        }
                    }
                    else {
                        if($code != $this->plateforme) {
                            $rabaisArray[] = [$code, $tab[$columns['client-class']], $tab[$columns["item-codeD"]], $tab[$columns["deduct-CHF"]],
                                                $tab[$columns["subsid-deduct"]], $tab[$columns["discount-bonus"]], $tab[$columns["subsid-bonus"]]];
                        }
                    }
                }
            }
        }

        for($i=0;$i<count($rabaisArray);$i++) {
            $rabaisArray[$i][3] = round($rabaisArray[$i][3],2);
            $rabaisArray[$i][4] = round($rabaisArray[$i][4],2);
            $rabaisArray[$i][5] = round($rabaisArray[$i][5],2);
            $rabaisArray[$i][6] = round($rabaisArray[$i][6],2);
        }
        return $rabaisArray;
    }


    function masterise($rabaisArray)
    {
        foreach($rabaisArray as $line) {
            $this->total += $line[3]+$line[4]+$line[5]+$line[6];
            $client = $this->clients[$line[0]];
            $class = $this->classes[$line[1]];
            $article = $this->articles[$line[2]];
            $montants = ["deduct-CHF"=>$line[3], "subsid-deduct"=>$line[4], "discount-bonus"=>$line[5], "subsid-bonus"=>$line[6]];
            $keys = ["reimbursed"=>$line[0], "subsid"=>$line[0], "for-csv"=>$line[0]."-".$line[1]."-".$line[2]];
            $extends = ["reimbursed"=>[$client], "subsid"=>[$client], "for-csv"=>[$client, $class, $article]];
            $dimensions = ["reimbursed"=>[$this::CLIENT_DIM], "subsid"=>[$this::CLIENT_DIM], "for-csv"=>[$this::CLIENT_DIM, $this::CLASSE_DIM, $this::ARTICLE_DIM]];
            $sums = ["reimbursed"=>["discount-bonus", "subsid-bonus"], "subsid"=>$this->dsub, "for-csv"=>$this->dsub];

            foreach($keys as $id=>$key) {
                $this->master = $this->mapping($key, $id, $extends, $dimensions, $montants, $sums);
            }
        }   
    }

    function mapping($key, $id, $extends, $dimensions, $montants, $sums) 
    {
        if(!array_key_exists($key, $this->master[$id])) {
            $this->master[$id][$key] = ["total-subsid" => 0, "mois" => []];            
            foreach($dimensions[$id] as $pos=>$dimension) {
                foreach($dimension as $d) {
                    $this->master[$id][$key][$d] = $extends[$id][$pos][$d];
                }
            }
            foreach($montants as $mt=>$val) {
                if(in_array($mt, $sums[$id])) {
                    $this->master[$id][$key][$mt] = 0;
                }
            }
        }
            
        if(!array_key_exists($this->monthly, $this->master[$id][$key]["mois"])) {
            $this->master[$id][$key]["mois"][$this->monthly] = 0;
        }
        $total = 0;
        foreach($montants as $mt=>$val) {
            if(in_array($mt, $sums[$id])) {
                $this->master[$id][$key][$mt] += $val;
                $total += $val;
            }
        }
        $this->master[$id][$key]["total-subsid"] += $total;
        $this->master[$id][$key]["mois"][$this->monthly] += $total;
        return $this->master;
    }

    static function sortTotal($a, $b) 
    {
        return floatval($b["total-subsid"]) - floatval($a["total-subsid"]);
    }

    function display()
    {
        $this->csv = $this->csvHeader(array_merge($this::CLIENT_DIM, $this::CLASSE_DIM, $this::ARTICLE_DIM, $this->dsub));
        foreach($this->master["for-csv"] as $line) {
            if(floatval($line["total-subsid"]) > 0) {
                $this->csv .= "\n".$this->csvLine(array_merge($this::CLIENT_DIM, $this::CLASSE_DIM, $this::ARTICLE_DIM, $this->dsub), $line);
            }
        }
        echo $this->templateDisplay("Total rabais et subsides sur la période", "total-subsides");
    }

}
