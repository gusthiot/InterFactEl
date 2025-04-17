<?php

abstract class Report
{
    
    const D1 = ["client-code", "client-sap", "client-name", "client-name2"];
    const D2 = ["client-class", "client-labelclass"];
    const D3 = ["item-codeD", "item-order", "item-labelcode"];
    
    protected $plateforme;
    protected $to;
    protected $from;
    protected $paramtext;

    /** only prepare */
    protected $clients;
    protected $classes;
    protected $clientsClasses;
    protected $articles;

    protected $bilansStats;
    protected $in;
    protected $report;
    protected $reportKey;

    protected $month;
    protected $year;

    protected $dirRun;
    protected $factel;
    protected $monthly;
    /** */

    protected $monthList;
    protected $total;

    protected $master;

    /** only display */
    protected $onglets;
    protected $columns;
    protected $columnsCsv;
    protected $csv;

    function __construct($plateforme, $to, $from) 
    {
        $this->plateforme = $plateforme;
        $this->to = $to;
        $this->from = $from;
        $state = new State(DATA.$plateforme);
        $this->paramtext = new ParamRun($state->getLastPath()."/IN/", 'text');

        $this->clients = [];
        $this->classes = [];
        $this->clientsClasses = [];
        $this->articles = [];
        $this->monthList = [];

        $this->bilansStats = self::getJsonStructure("../bilans-stats.json");
        $this->in = self::getJsonStructure("../in.json");
        $this->report = self::getJsonStructure("../report.json");

        $this->total = 0;
        
    }

    abstract function generate($suffix);
    abstract function masterise($montantsArray);

    function prepare($dataGest)
    {
        $date = $this->to;
        while(true) {       
            $this->month = substr($date, 4, 2);
            $this->year = substr($date, 0, 4);
            $dir = DATA.$this->plateforme."/".$this->year."/".$this->month;
            $dirVersion = array_reverse(glob($dir."/*", GLOB_ONLYDIR))[0];
            $run = Lock::load($dirVersion, "version");
            $this->dirRun = $dirVersion."/".$run;
    
            $infos = Info::load($this->dirRun);
            $this->factel = $infos["FactEl"][2];
    
            $versionTab = explode("/", $dirVersion);
            $version = $versionTab[count($versionTab)-1];
            if($this->factel > 8) {
                $suffix = "_".$dataGest['reporting'][$this->plateforme]."_".$this->year."_".$this->month."_".$version;
            }
            else {
                $suffix = "_".$this->year."_".$this->month;
            }
            $this->monthly = $this->year."-".$this->month;
            $this->monthList[] = $this->monthly;
            $this->clients = self::getDirectoryCsv($this->dirRun."/IN/", $this->in[$this->factel]['client'], $this->clients);
            $this->classes = self::getDirectoryCsv($this->dirRun."/IN/", $this->in[$this->factel]['classeclient'], $this->classes);
            if($this->factel < 7) {
                $this->clientsClasses = self::getDirectoryCsv($this->dirRun."/IN/", $this->in[$this->factel]['clientclasse'], $this->clientsClasses);
            }
            if($this->factel == 8) {
                $articlesTemp = [];
                $ordersTemp = [];
                $articlesTemp = self::getDirectoryCsv($this->dirRun."/IN/", $this->in[$this->factel]['articlesap'], $articlesTemp);
                $ordersTemp = self::getDirectoryCsv($this->dirRun."/IN/", $this->in[$this->factel]['ordersap'], $ordersTemp);
                foreach($articlesTemp as $code=>$line) {
                    if(!array_key_exists($code, $this->articles)) {
                        $data = $line;
                        $data["item-order"] = $ordersTemp[$code]["item-order"];
                        $this->articles[$code] = $data;
                    }
                }
            }
            else {
                $this->articles = self::getDirectoryCsv($this->dirRun."/IN/", $this->in[$this->factel]['articlesap'], $this->articles);
            }
    
            $montantsArray = [];

            if(!file_exists($this->dirRun."/REPORT/".$this->report[$this->factel][$this->reportKey]['prefix'].".csv")) {
                if(!file_exists($this->dirRun."/REPORT/")) {
                    mkdir($this->dirRun."/REPORT/");
                }
                $montantsArray = $this->generate($suffix);
    
            }
            else {
                $lines = Csv::extract($this->dirRun."/REPORT/".$this->report[$this->factel][$this->reportKey]['prefix'].".csv"); 
                for($i=1;$i<count($lines);$i++) {
                    $montantsArray[] = explode(";", $lines[$i]);
                }
            }
    
            $this->masterise($montantsArray);
    
            if($date == $this->from) {
                break;
            }
    
            if($this->month == "01") {
                $date -= 89;
            }
            else {
                $date--;
            }
        }
        sort($this->monthList);
    }


    static function getJsonStructure($name) 
    {
        $structure = "";
        if ((file_exists($name)) && (($open = fopen($name, "r")) !== false)) {
            $structure = json_decode(fread($open, filesize($name)), true);
            fclose($open);
        }
        return $structure;
    }
    
    static function getDirectoryCsv($dir, $fileData, $result) 
    {
        $columns = $fileData['columns'];
        $names = array_keys($columns);
        $name = $fileData['prefix'];
        $lines = Csv::extract($dir.$name.".csv");                
        for($i=1;$i<count($lines);$i++) {
            $tab = explode(";", $lines[$i]);
            $code = $tab[$columns[$names[0]]];
            if(!array_key_exists($code, $result)) {
                $data = [];
                foreach($names as $key) {
                    $data[$key] = str_replace('"', '', $tab[$columns[$key]]);
                }
                $result[$code] = $data;
            }
        }
        return $result;
    }
    
    function csvHeader($columns, $totalKey) 
    {
        $header = "";
        $first = true;
        foreach($columns as $name) {
                $first ? $first = false : $header .= ";";
                $header .= $this->paramtext->getParam($name);
        }
        $header .= ";".$this->paramtext->getParam($totalKey);
        foreach($this->monthList as $monthly) {
            $header .= ";".$monthly;
        }
        return $header;
    }
    
    function csvLine($columns, $line, $totalKey) 
    {
        $data = "";
        $first = true;
        foreach($columns as $name) {
            $first ? $first = false : $data .= ";";
            $data .= $line[$name];
        }
        $data .= ";".$line[$totalKey];
        foreach($this->monthList as $monthly) {
            $data .= ";";
            if(array_key_exists($monthly, $line["mois"])) {
                $data .= $line["mois"][$monthly];
            }
        }
        return $data;
    }

    function templateDisplay($mainTitle, $totalKey, $key)
    {
        $period = substr($this->from, 4, 2)."/".substr($this->from, 0, 4)." - ".substr($this->to, 4, 2)."/".substr($this->to, 0, 4);
        $html = '<div class="total">'.$mainTitle.' '.$period.' : '.number_format(floatval($this->total), 2, ".", "'").' CHF</div>
                <div class="total"><a href="data:text/plain;base64,'.base64_encode($this->csv).'" download="'.$key.'.csv"><button type="button" id="'.$key.'" class="btn but-line">Download Csv</button></a></div>
                <ul class="nav nav-tabs" role="tablist">';
        $active = "active";
        foreach($this->onglets as $id => $title) { 
            $html .= '<li class="nav-item">
                        <a class="nav-link '.$active.'" id="'.$id.'-tab" data-toggle="tab" href="#'.$id.'" role="tab" aria-controls="'.$id.'" aria-selected="true">'.$title.'</a>
                    </li>';
            $active = "";
        }
        $html .= '</ul>
                <div class="tab-content p-3">'.$this->generateTablesAndCsv($totalKey).'</div>';
        echo $html;
    }

    abstract static function sortTotal($a, $b);
    
    function generateTablesAndCsv($totalKey) 
    {
        $html = "";
        $show = "show active";
        foreach($this->columns as $id=>$names) {
            uasort($this->master[$id], array($this, 'sortTotal'));
            $html .= '<div class="tab-pane fade '.$show.'" id="'.$id.'" role="tabpanel" aria-labelledby="'.$id.'-tab">
                        <div class="over report-large"><table class="table report-table" id="'.$id.'-table"><thead><tr>';
            $show = "";
            $csv = $this->csvHeader($this->columnsCsv[$id], $totalKey);
            foreach($names as $name) {
                $html .= "<th class='sort-text'>".$this->paramtext->getParam($name)."</th>";
            }      
            $html .= "<th class='right sort-number'>".$this->paramtext->getParam($totalKey)."</th></tr></thead><tbody>";
            foreach($this->master[$id] as $line) {
                if(floatval($line[$totalKey]) > 0) {
                    $html .= "<tr>";
                    foreach($names as $name) {
                        $html .= "<td>".$line[$name]."</td>";
                    }
                    $html .= "<td class='right'>".number_format(floatval($line[$totalKey]), 2, ".", "'")."</td></tr>";
                    $csv .= "\n".$this->csvLine($this->columnsCsv[$id], $line, $totalKey);
                }
            }
            $html .= "</tbody></table></div>";
            $html .= '<a href="data:text/plain;base64,'.base64_encode($csv).'" download="'.$id.'.csv"><button type="button" id="'.$id.'-dl"  class="btn but-line">Download Csv</button></a></div>';
        }
        return $html;
    }
    
    
}
