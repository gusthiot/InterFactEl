<?php

class ReportConsommations extends Report
{
    private $prestations;
    
    public function __construct($plateforme, $to, $from) 
    { 
        parent::__construct($plateforme, $to, $from);
        $this->prestations = [];
        //$this->reportKey = 'prestations';
        $this->totalKeys = ["conso-propre-march-expl", "conso-propre-extra-expl", "conso-propre-march-proj", "conso-propre-extra-proj"];
        $this->master = ["consos"=>[], "for-csv"=>[]];
        $this->onglets = ["consos" => "Consommations propre"];
        $this->columns = ["consos" => ["item-nbr", "item-name"]];
        $this->columnsCsv = ["consos" => array_merge($this::PRESTATION_DIM, $this::MACHINE_DIM)];

    }

    function prepare($suffix) {
        $this->prepareMachines();

        $prestationsTemp = [];
        self::mergeInCsv('prestation', $prestationsTemp, self::PRESTATION_KEY);
        $articlesTemp = [];
        self::mergeInCsv('articlesap', $articlesTemp, self::ARTICLE_KEY);

        if($this->factel > 7) {
            $idSaps = [];
            foreach($articlesTemp as $code=>$article) {
                $idSaps[$article["item-idsap"]] = $code;
            }
        }
        if($this->factel > 9) {
            $classesPrestTemp = [];
            self::mergeInCsv('classeprestation', $classesPrestTemp, self::CLASSEPRESTATION_KEY);
        }
        foreach($prestationsTemp as $code=>$line) {
            if(!array_key_exists($code, $this->prestations)) {
                $data = $line;
                $line["mach-id"] == 0 ? $data["mach-name"] = "" : $data["mach-name"] = $this->machines[$line["mach-id"]]["mach-name"];
                if($this->factel == 7) {
                    $data["item-labelcode"] = $articlesTemp[$line["item-codeD"]]["item-labelcode"];
                }
                else {
                    if($this->factel == 8 || $this->factel == 9) {
                        $idSap = $idSaps[$line["item-idsap"]];
                    }
                    else {
                        $classePrest = $classesPrestTemp[$line["item-idclass"]];
                        $idSap = $idSaps[$classePrest["item-idsap"]];
                    }
                    $data["item-codeD"] = $articlesTemp[$idSap]["item-codeD"];
                    $data["item-labelcode"] = $articlesTemp[$idSap]["item-labelcode"];
                }
                $this->prestations[$code] = $data;
            }
        }


        $columns = $this->bilansStats[$this->factel]['Bilan-c']['columns'];
        $lines = Csv::extract($this->getFileNameInBS('Bilan-c'));
        for($i=1;$i<count($lines);$i++) {
            $tab = explode(";", $lines[$i]);
            $itemId = $tab[$columns['item-id']];
            $prestation = $this->prestations[$itemId];

            if(!array_key_exists($itemId, $this->master["consos"])) {
                $this->master["consos"][$itemId] = [];
                foreach(array_merge($this::PRESTATION_DIM, $this::MACHINE_DIM) as $d) {
                    $this->master["consos"][$itemId][$d] = $prestation[$d];
                }
                foreach($this->totalKeys as $mt) {
                    $this->master["consos"][$itemId][$mt] = 0;
                }
            }
            foreach($this->totalKeys as $mt) {
                $this->master["consos"][$itemId][$mt] += $tab[$columns[$mt]];
                $this->total += $tab[$columns[$mt]];
            }

        }
    }

    static function sortTotal($a, $b) 
    {
        return floatval($b["item-nbr"]) - floatval($a["item-nbr"]);
    }

    function display()
    {
        echo $this->templateDisplay("Total des consommations propres sur la période", "", false);
    }

}
