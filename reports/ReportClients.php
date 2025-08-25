<?php

/**
 * ReportClients class allows to generate reports about number of users and clients
 */
class ReportClients extends Report
{
    /**
     * total number of clients
     *
     * @var array
     */
    private array $totalC;

    /**
     * total number of users
     *
     * @var array
     */
    private array $totalU;
        
    /**
     * Class constructor
     *
     * @param string $plateforme reports for this given plateform
     * @param string $to last month of the period
     * @param string $from first month of the period
     */
    function __construct(string $plateforme, string $to, string $from)
    { 
        parent::__construct($plateforme, $to, $from);
        $this->totalC = [];
        $this->totalU = [];
        $this->reportKey = 'statdate';
        $this->reportColumns = ["client-code", "client-class", "user-sciper", "date"];
        $this->tabs = [
            "user-jour" => [
                "title" => "nbre utilisateurs par jour, par semaine",
                "columns" => ["year", "month", "day", "week-nbr"],
                "dimensions" => array_merge($this::DATE_DIM, ["day", "week-nbr"]),
                "operations" => ["stat-nbuser-d", "stat-nbuser-w"],
                "formats" => ["int", "int"],
                "results" => [],
                "weeks" => []
            ],
            "user-mois" => [
                "title" => "nbre utilisateurs par mois",
                "columns" => ["year", "month"],
                "dimensions" => $this::DATE_DIM,
                "operations" => ["stat-nbuser-m", "stat-nbuser-3m", "stat-nbuser-6m", "stat-nbuser-12m"],
                "formats" => ["int", "int", "int", "int"],
                "results" => []
            ],
            "client-mois" => [
                "title" => "nbre clients par mois",
                "columns" => ["year", "month"],
                "dimensions" => $this::DATE_DIM,
                "operations" => ["stat-nbclient-m", "stat-nbclient-3m", "stat-nbclient-6m", "stat-nbclient-12m"],
                "formats" => ["int", "int", "int", "int"],
                "results" => []
            ],
            "client-classe" => [
                "title" => "nbre clients par classe",
                "columns" => ["client-class", "client-labelclass"],
                "dimensions" => $this::CLASSE_DIM,
                "operations" => ["stat-nbclient"],
                "formats" => ["int"],
                "results" => []
            ],
            "user-client" => [
                "title" => "nbre utilisateurs par client",
                "columns" => ["client-code", "client-sap", "client-name", "client-name2"],
                "dimensions" => $this::CLIENT_DIM,
                "operations" => ["stat-nbuser"],
                "formats" => ["int"],
                "results" => []
            ]
        ];

    }

    /**
     * prepares dimensions, generates report file if not exists and extracts its data
     *
     * @return void
     */
    function prepare(): void 
    {
        $this->prepareMachines();
        $this->loadCategories();
        $this->loadGroupes();
        $this->loadMachinesGroupes();
        $this->prepareClients();
        $this->prepareClientsClasses();
        $this->prepareClasses();
        $this->prepareUsers();
        $this->preparePrestations();

        $this->processReportFile();
    }

    /**
     * generates report file and returns its data
     *
     * @return array
     */
    function generate(): array
    {        
        $clientArray = [];

        if(floatval($this->factel) < 7) {
            foreach(['cae', 'lvr'] as $flux) {
                $columns = $this->bilansStats->getColumns($this->factel, $flux);
                $lines = Csv::extract($this->getFileNameInBS($flux));
                for($i=1;$i<count($lines);$i++) {
                    $tab = explode(";", $lines[$i]);
                    $code = $tab[$columns["client-code"]];
                    if($flux == 'cae') {
                        $machId = $tab[$columns["mach-id"]];
                        $plateId = $this->getPlateformeFromMachine($machId);
                    }
                    else {
                        $itemId = $tab[$columns["item-id"]];
                        $plateId = $this->prestations[$itemId]["platf-code"];
                    }
                    if($plateId && ($plateId == $this->plateforme) && ($code != $plateId)) {
                        $datetime = explode(" ", $tab[$columns["transac-date"]]);
                        $id = $code."--".$tab[$columns["user-id"]]."--".$datetime[0];
                        $clcl = $this->clientsClasses[$code]['client-class'];
                        $sciper = $this->sciper($tab[$columns["user-id"]]);
                        $clientArray[$id] = [$code, $clcl, $sciper, $datetime[0]];
                    }
                }
            }
        }
        else {
            $columns = $this->bilansStats->getColumns($this->factel, 'T3');
            $lines = Csv::extract($this->getFileNameInBS('T3'));
            for($i=1;$i<count($lines);$i++) {
                $tab = explode(";", $lines[$i]);
                $code = $tab[$columns["client-code"]];
                $clcl = $tab[$columns["client-class"]];
                if(floatval($this->factel) >= 7 && floatval($this->factel) < 9) {
                    $cond = ($this->plateforme == $tab[$columns["platf-code"]]) && ($tab[$columns["platf-code"]] != $code);
                }
                elseif(floatval($this->factel) >= 9 && floatval($this->factel) < 10) {
                    $cond = ($this->plateforme == $tab[$columns["platf-code"]]) && ($code != $tab[$columns["platf-code"]]) && ($tab[$columns["transac-valid"]] != 2);
                }
                else {
                    $cond = ($tab[$columns["year"]] == $tab[$columns["editing-year"]]) && ($tab[$columns["month"]] == $tab[$columns["editing-month"]]) && ($code != $tab[$columns["platf-code"]]) && ($tab[$columns["transac-valid"]] != 2);

                }
                if($cond) {
                    $datetime = explode(" ", $tab[$columns["transac-date"]]);
                    $id = $code."--".$clcl."--".$tab[$columns["user-id"]]."--".$datetime[0];
                    $sciper = $this->sciper($tab[$columns["user-id"]]);
                    $clientArray[$id] = [$code, $clcl, $sciper, $datetime[0]];
                }
            }
        }
        return $clientArray;
    }

    /**
     * maps report data for tabs tables and csv 
     *
     * @param array $clientArray report data
     * @return void
     */
    function mapping(array $clientArray): void
    {
        foreach($clientArray as $id=>$line) {
            $client = $this->clients[$line[0]];
            $classe = $this->classes[$line[1]];
            $date = new DateTimeImmutable($line[3]);
            $datetb = ["year"=>$date->format('Y'), "month"=>$date->format('m'), "day"=>$date->format('d'), "week-nbr"=>$date->format('W')];

            $ids = [
                "user-jour"=>$line[3],
                "user-mois"=>$date->format('Y-m'),
                "client-mois"=>$date->format('Y-m'),
                "client-classe"=>$line[1],
                "user-client"=>$line[0]
            ];

            $extends = [
                "user-jour"=>[$datetb],
                "user-mois"=>[$datetb],
                "client-mois"=>[$datetb],
                "client-classe"=>[$classe],
                "user-client"=>[$client]
            ];
            $dimensions = [
                "user-jour"=>[array_merge($this::DATE_DIM, ["day", "week-nbr"])],
                "user-mois"=>[$this::DATE_DIM],
                "client-mois"=>[$this::DATE_DIM],
                "client-classe"=>[$this::CLASSE_DIM],
                "user-client"=>[$this::CLIENT_DIM]
            ];

            foreach($this->tabs as $tab=>$data) {
                if(in_array($tab, ["user-jour", "user-mois", "user-client"])) {
                    if($line[2] == 0) {
                        continue;
                    }
                }
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

                    if(in_array($tab, ["user-jour", "user-mois", "user-client"])) {
                        $this->tabs[$tab]["results"][$ids[$tab]]["users"] = [];
                    }
                    else {
                        $this->tabs[$tab]["results"][$ids[$tab]]["clients"] = [];
                    }

                    if($tab == "user-jour") {
                        if($date->format('w') == 1) {
                            $this->tabs[$tab]["weeks"][$date->format('W')] = [];
                        }
                    }

                    if(in_array($tab, ["user-mois", "client-mois"])) {
                        $tab == "user-mois" ? $pre = "users" : $pre = "clients";
                        foreach([3, 6, 12] as $before) {
                            if(intval($this->month) < $before) {
                                $mb = State::addToMonth($this->month, 13-$before);
                                $yb = State::addToString($this->year, -1);
                            }
                            else {
                                $mb = State::addToMonth($this->month, 1-$before);
                                $yb = $this->year;
                            }
                            if (file_exists(DATA.$this->plateforme."/".$yb."/".$mb)) {
                                $this->tabs[$tab]["results"][$ids[$tab]][$pre."-".$before."m"] = [];
                            }
                        }
                    }
                }
                if(in_array($tab, ["user-jour", "user-mois", "user-client"])) {
                    if(!in_array($line[2], $this->tabs[$tab]["results"][$ids[$tab]]["users"])) {
                        $this->tabs[$tab]["results"][$ids[$tab]]["users"][] = $line[2];
                    }
                }
                else {
                    if(!in_array($line[0], $this->tabs[$tab]["results"][$ids[$tab]]["clients"])) {
                        $this->tabs[$tab]["results"][$ids[$tab]]["clients"][] = $line[0];
                    }
                }
                if($tab == "user-jour") {
                    if(array_key_exists($date->format('W'), $this->tabs[$tab]["weeks"])) {
                        $this->tabs[$tab]["weeks"][$date->format('W')][] = $line[2];
                    }
                }
                if($tab == "user-mois") {
                    $this->putInNext($tab, $ids[$tab], "users", $line[2]);
                }
                if($tab == "client-mois") {
                    $this->putInNext($tab, $ids[$tab], "clients", $line[0]);
                }
            }
            if($line[2] != 0 && !in_array($line[2], $this->totalU)) {
                $this->totalU[] = $line[2];
            }
            if(!in_array($line[0], $this->totalC)) {
                $this->totalC[] = $line[0];
            }
        }
    }

    function putInNext($tab, $date, $type, $value)
    {
        foreach([3, 6, 12] as $before) {
            $ms = $date;
            for($i=0;$i<$before;$i++) {
                if(!$this->isLater($ms)) {
                    if(array_key_exists($type."-".$before."m", $this->tabs[$tab]["results"][$ms])) {
                        if(!in_array($value, $this->tabs[$tab]["results"][$ms][$type."-".$before."m"])) {
                            $this->tabs[$tab]["results"][$ms][$type."-".$before."m"][] = $value;
                        }
                    }
                }
                $ms = $this->nextDate($ms);
            }
        }
    }

    function putInFrom($ecart, $tab, $type, $value)
    {
        $ms = substr($this->from, 0, 4)."-".substr($this->from, 4, 2);
        while(true) {
            if(array_key_exists($type."-12m", $this->tabs[$tab]["results"][$ms])) {
                if(!in_array($value, $this->tabs[$tab]["results"][$ms][$type."-12m"])) {
                    $this->tabs[$tab]["results"][$ms][$type."-12m"][] = $value;
                }
            }
            if($ecart < 6) {
                if(array_key_exists($type."-6m", $this->tabs[$tab]["results"][$ms])) {
                    if(!in_array($value, $this->tabs[$tab]["results"][$ms][$type."-6m"])) {
                        $this->tabs[$tab]["results"][$ms][$type."-6m"][] = $value;
                    }
                }
            }
            if($ecart < 3) {
                if(array_key_exists($type."-3m", $this->tabs[$tab]["results"][$ms])) {
                    if(!in_array($value, $this->tabs[$tab]["results"][$ms][$type."-3m"])) {
                        $this->tabs[$tab]["results"][$ms][$type."-3m"][] = $value;
                    }
                }
            }
            $ms = $this->nextDate($ms);
            $ecart++;
            if($ecart > 11 || $this->isLater($ms)) {
                break;
            }
        }
    }

    function nextDate(string $date): string
    {
        $tb = explode("-", $date);
        $y = $tb[1] == "12" ? State::addToString($tb[0], 1) : $tb[0];
        $m = $tb[1] == "12" ? "01" : State::addToMonth($tb[1], 1);
        return $y."-".$m;
    }


    function previousDate(string $date): string
    {
        $tb = explode("-", $date);
        $y = $tb[1] == "01" ? State::addToString($tb[0], -1) : $tb[0];
        $m = $tb[1] == "01" ? "12" : State::addToMonth($tb[1], -1);
        return $y."-".$m;
    }

    function isLater(string $date): bool
    {
        $tb = explode("-", $date);
        $m = substr($this->to, 4, 2);
        $y = substr($this->to, 0, 4);
        if($y == $tb[0]) {
            return (intval($tb[1]) > (intval($m)));
        }
        else {
            if($tb[0] < $y) {
                return false;
            }
            else {
                return true;
            }
        }
    }

    /**
     * displays title and tabs
     *
     * @return void
     */
    function display(): void
    {
        $date = $this->from;
        $month = substr($date, 4, 2);
        for($i=1;$i<12;$i++) {
            if($month == "01") {
                $date -= 89;
            }
            else {
                $date--;
            }
            $month = substr($date, 4, 2);
            $year = substr($date, 0, 4);
            $dir = DATA.$this->plateforme."/".$year."/".$month;
            if (!file_exists($dir)) {
                break;
            }
            $version = Lock::load($dir, "month");
            $dirVersion = $dir."/".$version;
            $run = Lock::load($dirVersion, "version");
            $dirRun = $dirVersion."/".$run;
            $infos = Info::load($dirRun);
            $factel = $infos["FactEl"][2];

            $monthArray = [];
            $reportFile = $this->report->getCsvUrl($dirRun, $factel, $this->reportKey);
            if(!file_exists($reportFile)) {
                if(!file_exists($dirRun."/REPORT/")) {
                    mkdir($dirRun."/REPORT/");
                }
                $monthArray = $this->generate();
                $columns = $this->reportColumns;
                Csv::write($reportFile, array_merge($this->getColumnsNames($columns), $monthArray));

            }
            else {
                $lines = Csv::extract($reportFile);
                for($j=1;$j<count($lines);$j++) {
                    $monthArray[] = explode(";", $lines[$j]);
                }
            }
            foreach($monthArray as $id=>$line) {
                $this->putInFrom($i, "user-mois", "users", $line[2]);
                $this->putInFrom($i, "client-mois", "clients", $line[0]);
            }            
        }

        foreach($this->tabs["user-jour"]["results"] as $jour=>$data) {
            $this->tabs["user-jour"]["results"][$jour]["stat-nbuser-d"] = count($data["users"]);
                $date = new DateTimeImmutable($jour);
                if($date->format('w') == 0) {
                    if(array_key_exists($date->format('W'), $this->tabs["user-jour"]["weeks"])) {
                        $this->tabs["user-jour"]["results"][$jour]["stat-nbuser-w"] = count($this->tabs["user-jour"]["weeks"][$date->format('W')]);
                    }
                }
        }
        foreach($this->tabs["user-mois"]["results"] as $mois=>$data) {
            $this->tabs["user-mois"]["results"][$mois]["stat-nbuser-m"] = count($data["users"]);
            foreach([3, 6, 12] as $before) {
                if(array_key_exists("users-".$before."m", $this->tabs["user-mois"]["results"][$mois])) {
                    $this->tabs["user-mois"]["results"][$mois]["stat-nbuser-".$before."m"] = count($this->tabs["user-mois"]["results"][$mois]["users-".$before."m"]);
                }
            }
        }
        foreach($this->tabs["client-mois"]["results"] as $mois=>$data) {
            $this->tabs["client-mois"]["results"][$mois]["stat-nbclient-m"] = count($data["clients"]);
            foreach([3, 6, 12] as $before) {
                if(array_key_exists("clients-".$before."m", $this->tabs["client-mois"]["results"][$mois])) {
                    $this->tabs["client-mois"]["results"][$mois]["stat-nbclient-".$before."m"] = count($this->tabs["client-mois"]["results"][$mois]["clients-".$before."m"]);
                }
            }
        }
        foreach($this->tabs["client-classe"]["results"] as $classe=>$data) {
            $this->tabs["client-classe"]["results"][$classe]["stat-nbclient"] = count($data["clients"]);
        }
        foreach($this->tabs["user-client"]["results"] as $client=>$data) {
            $this->tabs["user-client"]["results"][$client]["stat-nbuser"] = count($data["users"]);
        }

        $title = '<div class="total">Statistiques nombre utilisateurs et clients : '.$this->period().' </div>';
        $title .= '<div class="subtotal">Nombre de clients = '.$this->format(count($this->totalC), "int").'</div>';
        $title .= '<div class="subtotal">Nombre d\'utilisateurs = '.$this->format(count($this->totalU), "int").'</div>';
        echo $this->templateDisplay($title);
    }

}
