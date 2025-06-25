<?php

class ReportServices extends Report
{
    
    public function __construct($plateforme, $to, $from) 
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

    function prepare() {
        $this->loadCategories();
        $this->loadGroupes();
        $this->loadMachinesGroupes();
        
        $this->processReportFile();
    }

    function generate()
    {
        $servicesArray = [];
        $loopArray = [];
        $columns = $this->bilansStats[$this->factel]['T3']['columns'];
        $lines = Csv::extract($this->getFileNameInBS('T3'));
        for($i=1;$i<count($lines);$i++) {
            $tab = explode(";", $lines[$i]);
            if(($tab[$columns["year"]] == $tab[$columns["editing-year"]]) && ($tab[$columns["month"]] == $tab[$columns["editing-month"]]) && ($tab[$columns["flow-type"]] == "srv")) {
                $id = $tab[$columns["client-code"]]."--".$tab[$columns["client-class"]]."--".$tab[$columns["item-text2K"]]."--".$tab[$columns["oper-note"]]."--".$tab[$columns["item-grp"]]."--".$tab[$columns["item-codeK"]];
                if(!array_key_exists($id, $loopArray)) {
                    $loopArray[$id] = ['Smu' => 0, 'Q' => 0];
                }
                $loopArray[$id]['Smu'] += $tab[$columns["transac-usage"]];
                $loopArray[$id]['Q'] += $tab[$columns["transac-quantity"]];
            }
        }
        foreach($loopArray as $id=>$line) {
            $ids = explode("--", $id);
            $servicesArray[] = [$ids[0], $ids[1], $ids[2], $ids[3], $ids[4], $ids[5], $line["Q"], $line["Smu"]];
        }

        for($i=0;$i<count($servicesArray);$i++) {
            $servicesArray[$i][6] = round($servicesArray[$i][6],3);
            $servicesArray[$i][7] = round($servicesArray[$i][7],3);
        }
        return $servicesArray;
    }


    function mapping($servicesArray)
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
                foreach($values as $operation=>$value) {
                    $this->tabs[$tab]["results"][$ids[$tab]][$operation] = $value;
                }
            }
        }
    }

    function display()
    {
        $title = '<div class="total">Statistiques services : '.$this->period().' </div>';
        echo $this->templateDisplay($title);
    }

}
