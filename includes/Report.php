<?php

abstract class Report
{
    
    const CLIENT_DIM = ["client-code", "client-sap", "client-name", "client-name2"];
    const CLASSE_DIM = ["client-class", "client-labelclass"];
    const ARTICLE_DIM = ["item-codeD", "item-order", "item-labelcode"];
    const PRESTATION_DIM = ["item-id", "item-nbr", "item-name", "item-unit", "item-codeD", "item-labelcode"];
    const MACHINE_DIM = ["mach-id", "mach-name"];
    const GROUPE_DIM = ["item-grp"];
    const CATEGORIE_DIM = ["item-nbr", "item-name", "item-unit"];
    const CLIENT_KEY = "client-code";
    const CLASSE_KEY = "client-class";
    const ARTICLE_KEY = "item-codeD";
    const PRESTATION_KEY = "item-id";
    const CLASSEPRESTATION_KEY = "item-idclass";
    const MACHINE_KEY = "mach-id";
    const GROUPE_KEY = "item-grp";
    const CATEGORIE_KEY = "item-id";
    
    protected $plateforme;
    protected $to;
    protected $from;
    protected $paramtext;

    /** only prepare */
    protected $clients;
    protected $classes;
    protected $clientsClasses;
    protected $articles;
    protected $machines;
    protected $groupes;
    protected $categories;

    protected $bilansStats;
    protected $in;
    protected $report;
    protected $reportKey;
    protected $reportColumns;
    protected $idKey;

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

    protected $totalKeys;

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
        $this->machines = [];
        $this->groupes = [];
        $this->categories = [];

        $this->monthList = [];

        $this->bilansStats = self::getJsonStructure("../bilans-stats.json");
        $this->in = self::getJsonStructure("../in.json");
        $this->report = self::getJsonStructure("../report.json");

        $this->total = 0;
        
    }

    function prepareClients()
    {
        self::mergeInCsv('client', $this->clients, self::CLIENT_KEY);
    }

    function prepareClasses()
    {
        self::mergeInCsv('classeclient', $this->classes, self::CLASSE_KEY);
    }

    function prepareClientsClasses()
    {
        if($this->factel < 7) {
            self::mergeInCsv('clientclasse', $this->clientsClasses, self::CLIENT_KEY);
        }
    }

    function prepareArticles()
    {
        if($this->factel == 8) {
            $articlesTemp = [];
            $ordersTemp = [];
            self::mergeInCsv('articlesap', $articlesTemp, self::ARTICLE_KEY);
            self::mergeInCsv('ordersap', $ordersTemp, self::ARTICLE_KEY);
            foreach($articlesTemp as $code=>$line) {
                if(!array_key_exists($code, $this->articles)) {
                    $data = $line;
                    $data["item-order"] = $ordersTemp[$code]["item-order"];
                    $this->articles[$code] = $data;
                }
            }
        }
        else {
            self::mergeInCsv('articlesap', $this->articles, self::ARTICLE_KEY);
        }
    }

    function prepareMachines() 
    {
        if($this->factel < 7) {
            $machinesTemp = [];
            $groupesTemp = [];
            self::mergeInCsv('machine', $machinesTemp, self::MACHINE_KEY);
            self::mergeInCsv('machgrp', $groupesTemp, self::MACHINE_KEY);    
            foreach($machinesTemp as $code=>$line) {
                if(!array_key_exists($code, $this->machines)) {
                    $data = $line;
                    $data["item-grp"] = $groupesTemp[$code]["item-grp"];
                    $this->machines[$code] = $data;
                }
            }
        }
        else {
            self::mergeInCsv('machine', $this->machines, self::MACHINE_KEY);
        }
    }

    function prepareGroupes()
    {
        self::mergeInCsv('groupe', $this->groupes, self::GROUPE_KEY);
    }

    function prepareCategories()
    {
        self::mergeInCsv('categorie', $this->categories, self::CATEGORIE_KEY);
    }

    function getColumnsNames() {
        $names = [];
        foreach($this->reportColumns as $column) {
            $names[] = $this->paramtext->getParam($column);
        }
        return [$names];
    }

    function processReportFile($suffix)
    {
        $monthArray = [];
        $reportFile = $this->dirRun."/REPORT/".$this->report[$this->factel][$this->reportKey]['prefix'].".csv";
        if(!file_exists($reportFile)) {
            if(!file_exists($this->dirRun."/REPORT/")) {
                mkdir($this->dirRun."/REPORT/");
            }
            $monthArray = $this->generate($suffix);
            Csv::write($reportFile, array_merge($this->getColumnsNames(), $monthArray));

        }
        else {
            $lines = Csv::extract($reportFile); 
            for($i=1;$i<count($lines);$i++) {
                $monthArray[] = explode(";", $lines[$i]);
            }
        }

        $this->masterise($monthArray);
    }

    function monthAverage($sum, $num)
    {
        if($num == 0) {
            return 0;
        }
        return $sum / $num;
    }

    function monthStdDev($values, $avg, $num)
    {
        $sum = 0;
        foreach($values as $value) {
            $sum += pow($value-$avg, 2);
        }
        if($num == 0 || $sum == 0) {
            return 0;
        }
        return sqrt(1 / $num * $sum);
    }

    function periodAverage($nums, $avgs)
    {
        $sum = 0;
        $numTot = 0;
        for($i=0; $i< count($nums); $i++) {
            $sum += $nums[$i]*$avgs[$i];
            $numTot += $nums[$i];
        }
        if($numTot == 0) {
            return 0;
        }
        return $sum / $numTot;
    }

    function periodStdDev($nums, $avgs, $stddevs, $pAvg)
    {
        $sum = 0;
        $numTot = 0;
        for($i=0; $i< count($nums); $i++) {
            $sum += $nums[$i]*(pow($stddevs[$i], 2) + pow($avgs[$i]-$pAvg, 2));
            $numTot += $nums[$i];
        }
        if($numTot == 0 || $sum == 0) {
            return 0;
        }
        return sqrt(1 / $numTot * $sum);
    }

    function loopOnMonths($dataGest)
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

            $this->prepare($suffix);

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

    function getFileNameInBS($fileKey)
    {
        $files = scandir($this->dirRun."/Bilans_Stats/");
        $prefix = $this->bilansStats[$this->factel][$fileKey]['prefix'];
        foreach ($files as $file) {
            if(str_contains($file, $prefix."_") || str_contains($file, $prefix.".")) {
                return $this->dirRun."/Bilans_Stats/".$file;
            }
        }
        return false;
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
    
    function mergeInCsv($fileKey, &$array, $idKey) 
    {
        $fileData = $this->in[$this->factel][$fileKey];
        $columns = $fileData['columns'];
        $names = array_keys($columns);
        $name = $fileData['prefix'];
        $lines = Csv::extract($this->dirRun."/IN/".$name.".csv");                
        for($i=1;$i<count($lines);$i++) {
            $tab = explode(";", $lines[$i]);
            $code = $tab[$columns[$idKey]];
            if(!array_key_exists($code, $array)) {
                $data = [];
                foreach($names as $key) {
                    $data[$key] = str_replace('"', '', $tab[$columns[$key]]);
                }
                $array[$code] = $data;
            }
        }
    }
    
    function csvHeader($columns, $withMonths = true) 
    {
        $header = "";
        $first = true;
        foreach($columns as $name) {
                $first ? $first = false : $header .= ";";
                $header .= $this->paramtext->getParam($name);
        }
        foreach($this->totalKeys as $totalKey) {
            $header .= ";".$this->paramtext->getParam($totalKey);
        }
        if($withMonths) {
            foreach($this->monthList as $monthly) {
                $header .= ";".$monthly;
            }
        }
        return $header;
    }
    
    function csvLine($columns, $line, $withMonths = true) 
    {
        $data = "";
        $first = true;
        foreach($columns as $name) {
            $first ? $first = false : $data .= ";";
            $data .= $line[$name];
        }
        foreach($this->totalKeys as $totalKey) {
            $data .= ";".$line[$totalKey];
        }
        if($withMonths) {
            foreach($this->monthList as $monthly) {
                $data .= ";";
                if(array_key_exists($monthly, $line["mois"])) {
                    $data .= $line["mois"][$monthly];
                }
            }
        }
        return $data;
    }

    function templateDisplay($mainTitle, $csvKey="", $withMonths=true, $sorts=[])
    {
        $period = substr($this->from, 4, 2)."/".substr($this->from, 0, 4)." - ".substr($this->to, 4, 2)."/".substr($this->to, 0, 4);
        $html = '<div class="total">'.$mainTitle.' '.$period.' : '.number_format(floatval($this->total), 2, ".", "'").' CHF</div>';
        if(!empty($csvKey)) {
            $html .= '<div class="total"><a href="data:text/plain;base64,'.base64_encode($this->csv).'" download="'.$csvKey.'.csv"><button type="button" id="'.$csvKey.'" class="btn but-line">Download Csv</button></a></div>';
        }
        $html .= '<ul class="nav nav-tabs" role="tablist">';
        $active = "active";
        foreach($this->onglets as $id => $title) { 
            $html .= '<li class="nav-item">
                        <a class="nav-link '.$active.'" id="'.$id.'-tab" data-toggle="tab" href="#'.$id.'" role="tab" aria-controls="'.$id.'" aria-selected="true">'.$title.'</a>
                    </li>';
            $active = "";
        }
        $sort = "";
        if(!empty($sorts)) {
            $sort = $sorts[$id];
        }
        else {
            $sort = 'sortTotal';
        }
        $html .= '</ul>
                <div class="tab-content p-3">'.$this->generateTablesAndCsv($withMonths, $sort).'</div>';
        echo $html;
    }
    
    function generateTablesAndCsv($withMonths=true, $sort) 
    {
        $html = "";
        $show = "show active";
        foreach($this->columns as $id=>$names) {
            uasort($this->master[$id], array($this, $sort));
            $html .= '<div class="tab-pane fade '.$show.'" id="'.$id.'" role="tabpanel" aria-labelledby="'.$id.'-tab">
                        <div class="over report-large"><table class="table report-table" id="'.$id.'-table"><thead><tr>';
            $show = "";
            $csv = $this->csvHeader($this->columnsCsv[$id], $withMonths);
            foreach($names as $name) {
                $html .= "<th class='sort-text'>".$this->paramtext->getParam($name)."</th>";
            }   
            foreach($this->totalKeys as $totalKey) {   
                $html .= "<th class='right sort-number'>".$this->paramtext->getParam($totalKey)."</th>";
            }
            $html .= "</tr></thead><tbody>";
            foreach($this->master[$id] as $line) {
                $notNull = false;
                foreach($this->totalKeys as $totalKey) {
                    if(floatval($line[$totalKey]) > 0) {
                        $notNull = true;
                        break;
                    }
                }
                if($notNull) {
                    $html .= "<tr>";
                    foreach($names as $name) {
                        $html .= "<td>".$line[$name]."</td>";
                    }
                    foreach($this->totalKeys as $totalKey) {
                        $html .= "<td class='right'>".number_format(floatval($line[$totalKey]), 2, ".", "'")."</td>";
                    }
                    $html .= "</tr>";
                    $csv .= "\n".$this->csvLine($this->columnsCsv[$id], $line, $withMonths);
                }
            }
            $html .= "</tbody></table></div>";
            $html .= '<a href="data:text/plain;base64,'.base64_encode($csv).'" download="'.$id.'.csv"><button type="button" id="'.$id.'-dl"  class="btn but-line">Download Csv</button></a></div>';
        }
        return $html;
    }
    
    
}
