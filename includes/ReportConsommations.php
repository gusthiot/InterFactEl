<?php

class ReportConsommations extends Report
{
    private $prestations;
    
    public function __construct($plateforme, $to, $from) 
    { 
        parent::__construct($plateforme, $to, $from);
        $this->prestations = [];
        $this->tabs = [
            "consos" => [
                "title" => "Consommations propre",
                "columns" => ["item-nbr", "item-name"],
                "dimensions" => array_merge($this::PRESTATION_DIM, $this::MACHINE_DIM),
                "operations" => ["conso-propre-march-expl", "conso-propre-extra-expl", "conso-propre-march-proj", "conso-propre-extra-proj"],
                "results" => []
            ]
        ];

    }

    function prepare() {
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

            if(!array_key_exists($itemId, $this->tabs["consos"]["results"])) {
                $this->tabs["consos"]["results"][$itemId] = [];
                foreach($this->tabs["consos"]["dimensions"] as $dimension) {
                    $this->tabs["consos"]["results"][$itemId][$dimension] = $prestation[$dimension];
                }
                foreach($this->tabs["consos"]["operations"] as $operation) {
                    $this->tabs["consos"]["results"][$itemId][$operation] = 0;
                }
            }
            foreach($this->tabs["consos"]["operations"] as $operation) {
                $this->tabs["consos"]["results"][$itemId][$operation] += $tab[$columns[$operation]];
                $this->total += $tab[$columns[$operation]];
            }

        }
    }

    static function sortTotal($a, $b) 
    {
        return floatval($b["item-nbr"]) - floatval($a["item-nbr"]);
    }

    function display()
    {
        $title = '<div class="total">Total des consommations propres sur la période '.$this->period().' : '.number_format(floatval($this->total), 2, ".", "'").' CHF</div>';
        echo $this->templateDisplay($title, false);
    }

}
