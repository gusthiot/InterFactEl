<?php

/**
 * Report abstract class contains all reports shared data and functions
 */
abstract class Report
{
    /**
     * Dimensions labels
     */
    const CLIENT_DIM = ["client-code", "client-sap", "client-name", "client-name2"];
    const CLASSE_DIM = ["client-class", "client-labelclass"];
    const ARTICLE_DIM = ["item-codeD", "item-order", "item-labelcode"];
    const PRESTATION_DIM = ["item-id", "item-nbr", "item-name", "item-unit", "item-codeD", "item-labelcode"];
    const MACHINE_DIM = ["mach-id", "mach-name"];
    const GROUPE_DIM = ["item-grp"];
    const CATEGORIE_DIM = ["item-nbr", "item-name", "item-unit"];
    const USER_DIM = ["user-sciper", "user-name", "user-first", "user-email"];
    const CODEK_DIM = ["item-codeK", "item-textK"];
    const SERVICE_DIM = ["item-text2K", "oper-note"];
    const PROJET_DIM = ["proj-id", "proj-nbr", "proj-name", "proj-expl"];
    const OPER_DIM = ["oper-sciper", "oper-name", "oper-first", "date", "flow-type"];
    const DATE_DIM = ["year", "month"];

    /**
     * Dimensions keys
     */
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

    /**
     * Dimensions data arrays
     */
    protected array $clients;
    protected array $classes;
    protected array $clientsClasses;
    protected array $articles;
    protected array $comptes;
    protected array $machines;
    protected array $groupes;
    protected array $machinesGroupes;
    protected array $categories;
    protected array $users;
    protected array $prestations;

    /**
     * bilans-stats.json content
     *
     * @var BSFile
     */
    protected BSFile $bilansStats;

    /**
     * in.json content
     *
     * @var BSFile
     */
    protected BSFile $in;

    /**
     * report.json content
     *
     * @var BSFile
     */
    protected BSFile $report;

    /**
     * report file prefix name(s)
     *
     * @var mixed
     */
    protected mixed $reportKey;

    /**
     * report file columns
     *
     * @var array
     */
    protected array $reportColumns;

    /**
     * processed plateform
     *
     * @var string
     */
    protected string $plateforme;

    /**
     * last month of the period
     *
     * @var string
     */
    protected string $to;

    /**
     * first month of the period
     *
     * @var string
     */
    protected string $from;

    /**
     * ParamText object to get columns labels
     *
     * @var ParamText
     */
    protected ParamText $paramtext;

    /**
     * unclosed month if exists
     *
     * @var string
     */
    protected string $open;

    /**
     * processed month
     *
     * @var string
     */
    protected string $month;

    /**
     * processed year
     *
     * @var string
     */
    protected string $year;

    /**
     * processed run directory
     *
     * @var string
     */
    protected string $dirRun;

    /**
     * processed facturation version
     *
     * @var string
     */
    protected string $factel;

    /**
     * processed "year-month"
     *
     * @var string
     */
    protected string $monthly;

    /**
     * list of "year-month" for the period
     *
     * @var array
     */
    protected array $monthList;

    /**
     * total csv data if needed
     *
     * @var array
     */
    protected array $totalCsvData;

    /**
     * tabs data for tables and csv
     *
     * @var array
     */
    protected array $tabs;


    /**
     * Class constructor
     *
     * @param string $plateforme reports for this given plateform
     * @param string $to last month of the period
     * @param string $from first month of the period
     */
    function __construct(string $plateforme, string $to, string $from) 
    {
        $this->plateforme = $plateforme;
        $this->to = $to;
        $this->from = $from;
        $this->paramtext = new ParamText();
    
        $this->open = "";

        $this->clients = [];
        $this->classes = [];
        $this->clientsClasses = [];
        $this->articles = [];
        $this->comptes = [];
        $this->machines = [];
        $this->machinesGroupes = [];
        $this->groupes = [];
        $this->categories = [];
        $this->users = [];
        $this->prestations = [];

        $this->monthList = [];

        $this->bilansStats = new BSFile("../bilans-stats.json", "Bilans_Stats");
        $this->in = new BSFile("../in.json", "IN");
        $this->report = new BSFile("../report.json", "REPORT");        
    }

    /**
     * Functions to prepare dimensions on all period
     *
     */

    /**
     * merges clients on period
     *
     * @return void
     */
    function prepareClients(): void
    {
        self::mergeInCsv('client', $this->clients, self::CLIENT_KEY);
    }

    /**
     * merges clients classes on period
     *
     * @return void
     */
    function prepareClasses(): void
    {
        self::mergeInCsv('classeclient', $this->classes, self::CLASSE_KEY);
    }

    /**
     * merges clientclasse bridge on period, for V < 7
     *
     * @return void
     */
    function prepareClientsClasses(): void
    {
        if(floatval($this->factel) < 7) {
            self::mergeInCsv('clientclasse', $this->clientsClasses, self::CLIENT_KEY);
        }
    }

    /**
     * merges SAP articles on period
     *
     * @return void
     */
    function prepareArticles(): void
    {
        if(floatval($this->factel) == 8) {
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

    /**
     * merges users on period
     *
     * @return void
     */
    function prepareUsers(): void 
    {
        if(floatval($this->factel) > 11) {
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

    /**
     * merges projects on period
     *
     * @return void
     */
    function prepareComptes(): void
    {
        self::mergeInCsv('compte', $this->comptes, self::PROJET_KEY);
    }

    /**
     * merges prestations on period
     *
     * @return void
     */
    function preparePrestations() :void
    {
        $machinesTemp = [];
        self::mergeInCsv('machine', $machinesTemp, self::MACHINE_KEY);
        $prestationsTemp = [];
        self::mergeInCsv('prestation', $prestationsTemp, self::PRESTATION_KEY);
        $articlesTemp = [];
        self::mergeInCsv('articlesap', $articlesTemp, self::ARTICLE_KEY);

        if(floatval($this->factel) > 7) {
            $idSaps = [];
            foreach($articlesTemp as $code=>$article) {
                $idSaps[$article["item-idsap"]] = $code;
            }
        }
        if(floatval($this->factel) >= 10) {
            $classesPrestTemp = [];
            self::mergeInCsv('classeprestation', $classesPrestTemp, self::CLASSEPRESTATION_KEY);
        }
        foreach($prestationsTemp as $code=>$line) {
            if(!array_key_exists($code, $this->prestations)) {
                $data = $line;
                if($line["mach-id"] == 0) {
                    $data["mach-name"] = "";
                    $data["item-extra"] = "FALSE";
                }
                else {
                    $data["mach-name"] = $machinesTemp[$line["mach-id"]]["mach-name"];
                    $data["item-extra"] = "TRUE";
                } 
                if(floatval($this->factel) < 8) {
                    $data["item-labelcode"] = $articlesTemp[$line["item-codeD"]]["item-labelcode"];
                }
                else {
                    if(floatval($this->factel) >= 8 && floatval($this->factel) < 10) {
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

    /**
     * merges machines on period
     *
     * @return void
     */
    function prepareMachines(): void
    {
        self::mergeInCsv('machine', $this->machines, self::MACHINE_KEY);
    }

    /**
     * Functions to load dimensions for one month
     */

    /**
     * loads groups for the month
     *
     * @return void
     */
    function loadGroupes(): void
    {
        $this->groupes = [];
        self::mergeInCsv('groupe', $this->groupes, self::GROUPE_KEY);
    }

    /**
     * loads categories for the month
     *
     * @return void
     */
    function loadCategories(): void
    {
        $this->categories = [];
        self::mergeInCsv('categorie', $this->categories, self::CATEGORIE_KEY);
    }

    /**
     * loads machines groups for the month
     *
     * @return void
     */
    function loadMachinesGroupes(): void
    {
        if(floatval($this->factel) < 7) {
            $this->machinesGroupes = [];
            self::mergeInCsv('machgrp', $this->machinesGroupes, self::MACHINE_KEY);
        }
        else {
            $this->machinesGroupes = [];
            self::mergeInCsv('machine', $this->machinesGroupes, self::MACHINE_KEY);
        }
    }

    /**
     * loads prestations for the month
     *
     * @return void
     */
    function loadPrestations(): void
    {
        $this->prestations = [];
        self::mergeInCsv('prestation', $this->prestations, self::PRESTATION_KEY);
    }

    /**
     * processes simplified reports csv file
     *
     * @return void
     */
    function processReportFile(): void
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

    /**
     * processes one simplified report file, if not existing
     *
     * @param string $key report key name
     * @param boolean $multiple if it's one report among more or not
     * @return void
     */
    function processOneReport(string $key, bool $multiple=false): void
    {
        $monthArray = [];
        $reportFile = $this->report->getCsvUrl($this->dirRun, $this->factel, $key);
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

    /**
     * returns the columns names from their keys
     *
     * @param array $columns columns keys
     * @return array
     */
    function getColumnsNames(array $columns): array 
    {
        $names = [];
        foreach($columns as $column) {
            $names[] = $this->paramtext->getParam($column);
        }
        return [$names];
    }

    /**
     * returns the plateform concerned by a machine or false
     *
     * @param string $machId machine id
     * @return string
     */
    function getPlateformeFromMachine(string $machId): string
    {
        if(array_key_exists($machId, $this->machinesGroupes)) {
            $categorie = $this->getCategorieFromMachine($machId, "K1");
            if(!empty($categorie)) {
                return $categorie["platf-code"];
            }
        }
        return false;
    }

    /**
     * returns the categorie concerned by a grouped machine for an item K or empty array
     *
     * @param string $machId machine id
     * @param string $itemK item K
     * @return array
     */
    function getCategorieFromMachine(string $machId, string $itemK): array
    {
        $itemGrp = $this->machinesGroupes[$machId]["item-grp"];
        if($itemGrp != "0") {
            $itemId = $this->groupes[$itemGrp]["item-id-".$itemK];
            if($itemId != "0") {
                return $this->categories[$itemId];
            }
        }
        return [];
    }

    /**
     * returns user sciper from its id, 0 if id = 0
     *
     * @param string $id user id
     * @return string
     */
    function sciper(string $id): string
    {
        if($id == "0") {
            return 0;
        } 
        return $this->users[$id]['user-sciper'];
    }

    /**
     * maps sciper->id user
     *
     * @return array
     */
    function scipers(): array
    {
        $scipers = [];
        foreach($this->users as $id=>$user) {
            $scipers[$user['user-sciper']] = $id;
        }
        return $scipers;
    }

    /**
     * loops on all the period, one month after another, from the last to the first
     *
     * @return void
     */
    function loopOnMonths(): void
    {
        $date = $this->to;
        while(true) {       
            $this->month = substr($date, 4, 2);
            $this->year = substr($date, 0, 4);
            $dir = DATA.$this->plateforme."/".$this->year."/".$this->month;

            if (file_exists($dir."/".Lock::FILES['month'])) {
                $version = Lock::load($dir, "month");
                $dirVersion = $dir."/".$version;
                $run = Lock::load($dirVersion, "version");
                $this->dirRun = $dirVersion."/".$run;
            }
            else {
                foreach(globReverse($dir) as $dirVersion) {
                    $run = Lock::load($dirVersion, "version");
                    if (!is_null($run)) {
                        $this->dirRun = $dirVersion."/".$run;
                        break;
                    }
                }
                $this->open = substr($date, 4, 2)." ".substr($date, 0, 4);
            }

            $infos = Info::load($this->dirRun);
            $this->factel = $infos["FactEl"][2];

            $this->monthly = $this->year."-".$this->month;
            $this->monthList[] = $this->monthly;

            // prepare is different for each report
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

    /**
     * finds complete filename for a Bilans&Stats file
     *
     * @param string $fileKey key to obtain the name prefix in json file
     * @return string complete filename or empty string
     */
    function getFileNameInBS(string $fileKey): string
    {
        return $this->bilansStats->findCsvUrl($this->dirRun, $this->factel, $fileKey);
    }

    /**
     * adds data from csv file to array if not already exists
     *
     * @param string $fileKey file key to get data from Json file
     * @param array $array array to merge in
     * @param string $idKey key of the targeted data
     * @return void
     */
    function mergeInCsv(string $fileKey, array &$array, string $idKey): void 
    {
        $columns = $this->in->getColumns($this->factel, $fileKey);
        $lines = Csv::extract($this->in->getCsvUrl($this->dirRun, $this->factel, $fileKey));                
        for($i=1;$i<count($lines);$i++) {
            $tab = explode(";", $lines[$i]);
            $code = $tab[$columns[$idKey]];
            if(!array_key_exists($code, $array)) {
                $data = [];
                foreach(array_keys($columns) as $key) {
                    $data[$key] = str_replace('"', '', $tab[$columns[$key]]);
                }
                $array[$code] = $data;
            }
        }
    }
    
    /**
     * generates csv header
     *
     * @param array $dimensions dimensions columns keys
     * @param array $operations operations columns keys
     * @param boolean $withMonths if column for each month is expected or not
     * @return string
     */
    function csvHeader(array $dimensions, array $operations, bool $withMonths = true): string 
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
    
    /**
     * generates csv line
     *
     * @param array $dimensions dimensions columns keys
     * @param array $operations operations columns keys
     * @param array $line line data
     * @param boolean $withMonths if column for each month is expected or not
     * @return string
     */
    function csvLine(array $dimensions, array $operations, array $line, bool $withMonths = true): string 
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

    /**
     * returns link for all data csv file
     *
     * @param string $csvKey csv link id
     * @param string $notBeNull the data, usually a final total, that should not be null if we want to generate the file
     * @return string
     */
    function totalCsvLink(string $csvKey, string $notBeNull): string
    {
        $totalCsv = $this->csvHeader($this->totalCsvData["dimensions"], $this->totalCsvData["operations"]);
        foreach($this->totalCsvData["results"] as $line) {
            if(floatval($line[$notBeNull]) > 0) {
                $totalCsv .= "\n".$this->csvLine($this->totalCsvData["dimensions"], $this->totalCsvData["operations"], $line);
            }
        }
        return '<div class="total"><a href="data:text/plain;base64,'.base64_encode($totalCsv).'" download="'.$csvKey.'.csv"><button type="button" id="'.$csvKey.'" class="btn but-line">Download Csv</button></a></div>';
    }

    /**
     * returns formatted number
     *
     * @param mixed $val number value in indetermined format
     * @param string $format expected format (int, fin(ancial) or float)
     * @return string
     */
    function format(mixed $val, string $format="fin"): string
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

    /**
     * returns period as text
     *
     * @return string
     */
    function period(): string
    {
        return substr($this->from, 4, 2)."/".substr($this->from, 0, 4)." - ".substr($this->to, 4, 2)."/".substr($this->to, 0, 4);
    }

    /**
     * displays title and tabs with tables and csv links
     *
     * @param string $mainTitle content of the title part
     * @param boolean $null if we accepts results = 0 or not
     * @return void
     */
    function templateDisplay(string $mainTitle, bool $null=false): void
    {
        $period = $this->period();
        $html = $mainTitle;
        if($this->open) {
            $html .= '<div>
                        <svg class="icon red" aria-hidden="true">
                            <use xlink:href="#alert-triangle"></use>
                        </svg>
                        Le mois '.$this->open.' n’est pas clôturé, des factures pourraient être refaites, impactant les statistiques
                        <svg class="icon red" aria-hidden="true">
                            <use xlink:href="#alert-triangle"></use>
                        </svg>
                    
                    </div>';
        }
        $html .= '<ul class="nav nav-tabs" role="tablist">';
        $active = "active";
        foreach($this->tabs as $tab => $data) { 
            $html .= '<li class="nav-item">
                        <a class="nav-link '.$active.'" id="'.$tab.'-tab" data-toggle="tab" href="#'.$tab.'" role="tab" aria-controls="'.$tab.'" aria-selected="true">'.$data["title"].'</a>
                    </li>';
            $active = "";
        }
        $html .= '</ul>
                <div class="tab-content p-3">'.$this->generateTablesAndCsv($null).'</div>';
        echo $html;
    }

    /**
     * generates tabs with tables and csv links
     *
     * @param boolean $null if we accepts results = 0 or not
     * @return string
     */
    function generateTablesAndCsv(bool $null=false): string 
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
                    $notNull = true;
                    if(!$null) {
                        $notNull = false;
                        foreach($data["operations"] as $operation) {
                            if(floatval($line[$operation]) > 0) {
                                $notNull = true;
                                break;
                            }
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
