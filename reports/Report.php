<?php

abstract class Report
{
    
    const CLIENT_DIM = ["client-code", "client-sap", "client-name", "client-name2"];
    const CLASSE_DIM = ["client-class", "client-labelclass"];
    const ARTICLE_DIM = ["item-codeD", "item-order", "item-labelcode"];
    const PRESTATION_DIM = ["item-id", "item-nbr", "item-name", "item-unit", "item-codeD", "item-labelcode"];
    const MACHINE_DIM = ["mach-id", "mach-name"];
    const GROUPE_DIM = ["item-grp", "item-cae"];
    const CATEGORIE_DIM = ["item-nbr", "item-name", "item-unit"];
    const USER_DIM = ["user-sciper", "user-name", "user-first", "user-email"];
    const CODEK_DIM = ["item-codeK", "item-textK"];
    const SERVICE_DIM = ["item-text2K", "oper-note"];
    const PROJET_DIM = ["proj-id", "proj-nbr", "proj-name"];
    const OPER_DIM = ["oper-sciper", "oper-name", "oper-first", "date", "flow-type"];
    const CLIENT_KEY = "client-code";
    const CLASSE_KEY = "client-class";
    const ARTICLE_KEY = "item-codeD";
    const PRESTATION_KEY = "item-id";
    const CLASSEPRESTATION_KEY = "item-idclass";
    const MACHINE_KEY = "mach-id";
    const GROUPE_KEY = "item-grp";
    const CATEGORIE_KEY = "item-id";
    const USER_KEY = "user-id";
    const PROJET_KEY = "proj-id";
    
    protected $plateforme;
    protected $to;
    protected $from;
    protected $paramtext;

    protected $clients;
    protected $classes;
    protected $clientsClasses;
    protected $articles;
    protected $machines;
    protected $groupes;
    protected $machinesGroupes;
    protected $categories;
    protected $users;
    protected $prestations;

    protected $bilansStats;
    protected $in;
    protected $report;
    protected $reportKey;
    protected $reportColumns;

    protected $month;
    protected $year;
    protected $dirRun;
    protected $factel;
    protected $monthly;

    protected $monthList;
    protected $total;
    protected $totalCsv;
    protected $totalCsvData;
    protected $tabs;


    function __construct($plateforme, $to, $from) 
    {
        $this->plateforme = $plateforme;
        $this->to = $to;
        $this->from = $from;
        $this->paramtext = new ParamText();

        $this->clients = [];
        $this->classes = [];
        $this->clientsClasses = [];
        $this->articles = [];
        $this->machines = [];
        $this->machinesGroupes = [];
        $this->groupes = [];
        $this->categories = [];
        $this->users = [];
        $this->prestations = [];

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

    function prepareUsers()
    {
        if($this->factel > 11) {
            self::mergeInCsv('user', $this->users, self::USER_KEY);
        }
        else {
            $usersTemp = [];
            self::mergeInCsv('user', $usersTemp, self::USER_KEY);
            foreach($usersTemp as $code=>$line) {
                if(!array_key_exists($code, $this->users)) {
                    $data = $line;
                    $data["user-email"] = "";
                    $this->users[$code] = $data;
                }    
            }

        }
    }

    function prepareMachines() 
    {
        self::mergeInCsv('machine', $this->machines, self::MACHINE_KEY);
    }

    function loadGroupes()
    {
        $this->groupes = [];
        self::mergeInCsv('groupe', $this->groupes, self::GROUPE_KEY);
    }

    function loadCategories()
    {
        $this->categories = [];
        self::mergeInCsv('categorie', $this->categories, self::CATEGORIE_KEY);
    }

    function loadMachinesGroupes()
    {
        if($this->factel < 7) {
            $this->machinesGroupes = [];
            self::mergeInCsv('machgrp', $this->machinesGroupes, self::MACHINE_KEY);
        }
        else {
            $this->machinesGroupes = [];
            self::mergeInCsv('machine', $this->machinesGroupes, self::MACHINE_KEY);
        }
    }

    function preparePrestations()
    {
        $machinesTemp = [];
        self::mergeInCsv('machine', $machinesTemp, self::MACHINE_KEY);
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
        if($this->factel >= 10) {
            $classesPrestTemp = [];
            self::mergeInCsv('classeprestation', $classesPrestTemp, self::CLASSEPRESTATION_KEY);
        }
        foreach($prestationsTemp as $code=>$line) {
            if(!array_key_exists($code, $this->prestations)) {
                $data = $line;
                $line["mach-id"] == 0 ? $data["mach-name"] = "" : $data["mach-name"] = $machinesTemp[$line["mach-id"]]["mach-name"];
                if($this->factel < 8) {
                    $data["item-labelcode"] = $articlesTemp[$line["item-codeD"]]["item-labelcode"];
                }
                else {
                    if($this->factel >= 8 && $this->factel < 10) {
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
    }

    function getColumnsNames($columns) {
        $names = [];
        foreach($columns as $column) {
            $names[] = $this->paramtext->getParam($column);
        }
        return [$names];
    }

    function processReportFile()
    {
        if(is_array($this->reportKey)) {
            foreach($this->reportKey as $key) {
                $this->processOneReport($key, true);
            }
        }
        else {
            $this->processOneReport($this->reportKey);
        }
    }

    function processOneReport($key, $multiple=false)
    {
        $monthArray = [];
        $reportFile = $this->dirRun."/REPORT/".$this->report[$this->factel][$key]['prefix'].".csv";
        if(!file_exists($reportFile)) {
            if(!file_exists($this->dirRun."/REPORT/")) {
                mkdir($this->dirRun."/REPORT/");
            }
            $multiple ? $monthArray = $this->generate($key) : $monthArray = $this->generate();
            $multiple ? $columns = $this->reportColumns[$key] : $columns = $this->reportColumns;
            Csv::write($reportFile, array_merge($this->getColumnsNames($columns), $monthArray));

        }
        else {
            $lines = Csv::extract($reportFile); 
            for($i=1;$i<count($lines);$i++) {
                $monthArray[] = explode(";", $lines[$i]);
            }
        }

        $multiple ? $this->mapping($monthArray, $key) : $this->mapping($monthArray);

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

    function loopOnMonths()
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

            $this->monthly = $this->year."-".$this->month;
            $this->monthList[] = $this->monthly;

            $this->prepare();

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
            if(str_starts_with($file, $prefix) &&( str_contains($file, $prefix."_") || str_contains($file, $prefix."."))) {
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
    
    function csvHeader($dimensions, $operations, $withMonths = true) 
    {
        $header = "";
        $first = true;
        foreach($dimensions as $dimension) {
                $first ? $first = false : $header .= ";";
                $header .= $this->paramtext->getParam($dimension);
        }
        foreach($operations as $operation) {
            $header .= ";".$this->paramtext->getParam($operation);
        }
        if($withMonths) {
            foreach($this->monthList as $monthly) {
                $header .= ";".$monthly;
            }
        }
        return Csv::formatLine($header);
    }
    
    function csvLine($dimensions, $operations, $line, $withMonths = true) 
    {
        $data = "";
        $first = true;
        foreach($dimensions as $dimension) {
            $first ? $first = false : $data .= ";";
            $data .= $line[$dimension];
        }
        foreach($operations as $operation) {
            $data .= ";".$line[$operation];
        }
        if($withMonths) {
            foreach($this->monthList as $monthly) {
                $data .= ";";
                if(array_key_exists($monthly, $line["mois"])) {
                    $data .= $line["mois"][$monthly];
                }
            }
        }
        return Csv::formatLine($data);
    }

    function createTotalCsv($notBeNull)
    {
        $this->totalCsv = $this->csvHeader($this->totalCsvData["dimensions"], $this->totalCsvData["operations"]);
        foreach($this->totalCsvData["results"] as $line) {
            if(floatval($line[$notBeNull]) > 0) {
                $this->totalCsv .= "\n".$this->csvLine($this->totalCsvData["dimensions"], $this->totalCsvData["operations"], $line);
            }
        }
    }

    function period()
    {
        return substr($this->from, 4, 2)."/".substr($this->from, 0, 4)." - ".substr($this->to, 4, 2)."/".substr($this->to, 0, 4);
    }

    function totalCsvLink($csvKey)
    {
        return '<div class="total"><a href="data:text/plain;base64,'.base64_encode($this->totalCsv).'" download="'.$csvKey.'.csv"><button type="button" id="'.$csvKey.'" class="btn but-line">Download Csv</button></a></div>';
    }

    function templateDisplay($mainTitle)
    {
        $period = $this->period();
        $html = $mainTitle;
        $html .= '<ul class="nav nav-tabs" role="tablist">';
        $active = "active";
        foreach($this->tabs as $tab => $data) { 
            $html .= '<li class="nav-item">
                        <a class="nav-link '.$active.'" id="'.$tab.'-tab" data-toggle="tab" href="#'.$tab.'" role="tab" aria-controls="'.$tab.'" aria-selected="true">'.$data["title"].'</a>
                    </li>';
            $active = "";
        }
        $html .= '</ul>
                <div class="tab-content p-3">'.$this->generateTablesAndCsv().'</div>';
        echo $html;
    }

    function Format($val, $format="fin")
    {
        switch($format) {
            case "int": 
                return number_format(intval($val), 0, ".", "'");
                break;
            case "fin":
                return number_format(floatval($val), 2, ".", "'");
                break;
            default:
                return number_format(floatval($val), 3, ".", "'");
        }
    }

    function generateTablesAndCsv() 
    {
        $html = "";
        $show = "show active";
        foreach($this->tabs as $tab=>$data) {
            $first = array_key_first($data["results"]);
            if($first) {
                $withMonths = array_key_exists("mois", $data["results"][$first]);
                $html .= '<div class="tab-pane fade '.$show.'" id="'.$tab.'" role="tabpanel" aria-labelledby="'.$tab.'-tab">
                            <div class="over report-large"><table class="table report-table" id="'.$tab.'-table"><thead><tr>';
                $show = "";
                $csv = $this->csvHeader($data["dimensions"], $data["operations"], $withMonths);
                foreach($data["columns"] as $name) {
                    $html .= "<th class='sort-text'>".$this->paramtext->getParam($name)."</th>";
                }   
                foreach($data["operations"] as $operation) {   
                    $html .= "<th class='right sort-number'>".$this->paramtext->getParam($operation)."</th>";
                }
                $html .= "</tr></thead><tbody>";
                foreach($data["results"] as $line) {
                    $notNull = false;
                    foreach($data["operations"] as $operation) {
                        if(floatval($line[$operation]) > 0) {
                            $notNull = true;
                            break;
                        }
                    }
                    if($notNull) {
                        $html .= "<tr>";
                        foreach($data["columns"] as $name) {
                            $html .= "<td>".$line[$name]."</td>";
                        }
                        foreach($data["operations"] as $pos=>$operation) {
                            $html .= "<td class='right'>".$this->format($line[$operation], $data["formats"][$pos])."</td>";
                        }
                        $html .= "</tr>";
                        $csv .= "\n".$this->csvLine($data["dimensions"], $data["operations"], $line, $withMonths);
                    }
                }
                $html .= "</tbody></table></div>";
                $html .= '<a href="data:text/plain;base64,'.base64_encode($csv).'" download="'.$tab.'.csv"><button type="button" id="'.$tab.'-dl"  class="btn but-line">Download Csv</button></a></div>';
            }
        }
        return $html;
    }
}
