<?php

/**
 * ReportUsages class allows to generate reports about machines, clients and users usages stats
 */
class ReportUsages extends Report
{
    /**
     * total of hours
     *
     * @var float
     */
    private float $totalM;

    /**
     * total of runs
     *
     * @var integer
     */
    private int $totalN;

    /**
     * total machines categories changes
     *
     * @var array
     */
    private array $totalChange;

    /**
     * machines categories
     *
     * @var array
     */
    private array $changeMachines;

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
        $this->totalM = 0;
        $this->totalN = 0;
        $this->totalChange = [];
        $this->changeMachines = [];
        $this->reportKey = 'statcae';
        $this->reportColumns = ["client-code", "client-class", "user-sciper", "item-codeK", "mach-id", "item-nbr", "item-name", "item-unit", "transac-usage", "transac-runcae"];
        $this->tabs = [
            "par-machine" => [
                "title" => "Stats par Machine",
                "columns" => ["mach-name"],
                "dimensions" => $this::MACHINE_DIM,
                "operations" => ["stat-hmach", "stat-hoper", "stat-run-user", "stat-nbuser", "stat-nbclient", "stat-run"],
                "formats" => ["float", "float", "int", "int", "int", "int"],
                "results" => []

            ], 
            "par-client"=>[
                "title" => "Stats par Client",
                "columns" => ["client-name"],
                "dimensions" => $this::CLIENT_DIM,
                "operations" => ["stat-hmach", "stat-run"],
                "formats" => ["float", "int"],
                "results" => []
            ], 
            "par-user"=>[
                "title" => "Stats par Utilisateur",
                "columns" => ["user-sciper", "user-name", "user-first"],
                "dimensions" => $this::USER_DIM,
                "operations" => ["stat-hmach", "stat-run"],
                "formats" => ["float", "int"],
                "results" => []
            ], 
            "par-client-user"=>[
                "title" => "Stats par Client par Utilisateur",
                "columns" => ["client-name", "user-sciper", "user-name", "user-first"],
                "dimensions" => array_merge($this::CLIENT_DIM, $this::USER_DIM),
                "operations" => ["stat-hmach", "stat-run"],
                "formats" => ["float", "int"],
                "results" => []
            ], 
            "par-client-classe"=>[
                "title" => "Stats par Client par Classe client",
                "columns" => ["client-name", "client-labelclass"],
                "dimensions" => array_merge($this::CLIENT_DIM, $this::CLASSE_DIM),
                "operations" => ["stat-hmach", "stat-run"],
                "formats" => ["float", "int"],
                "results" => []
            ], 
            "use-machine-categorie"=>[
                "title" => "Utilisation par Machine par Catégorie",
                "columns" => ["mach-name", "item-textK", "item-nbr", "item-name", "item-unit"],
                "dimensions" => array_merge($this::MACHINE_DIM, $this::CODEK_DIM, $this::CATEGORIE_DIM),
                "operations" => ["transac-usage"],
                "formats" => ["float"],
                "results" => []
            ], 
            "use-categorie"=>[
                "title" => "Utilisation par Catégorie",
                "columns" => ["item-nbr", "item-name", "item-unit", "item-textK"],
                "dimensions" => array_merge($this::CATEGORIE_DIM, $this::CODEK_DIM),
                "operations" => ["transac-usage"],
                "formats" => ["float", "int"],
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
        $this->prepareClients();
        $this->prepareClasses();
        $this->prepareClientsClasses();
        $this->prepareMachines();
        $this->prepareUsers();
        $this->loadCategories();
        $this->loadGroupes();
        $this->loadMachinesGroupes();
        $this->processReportFile();
    }

    /**
     * generates report file and returns its data
     *
     * @return array
     */
    function generate(): array
    {
        $usagesArray = [];
        $loopArray = [];
        if(floatval($this->factel) < 7) {
            $columns = $this->bilansStats[$this->factel]['cae']['columns'];
            $lines = Csv::extract($this->getFileNameInBS('cae'));
            for($i=1;$i<count($lines);$i++) {
                $tab = explode(";", $lines[$i]);
                $machId = $tab[$columns["mach-id"]];
                $plateId = $this->getPlateformeFromMachine($machId);
                if($plateId && ($plateId == $this->plateforme)) {
                    $mu1 = ($tab[$columns["Tmach-HP"]] + $tab[$columns["Tmach-HC"]]) / 60;
                    $mu2 = $tab[$columns["Toper"]]  / 60;
                    $nr1 = 1;
                    $nr3 = 0;
                    if($tab[$columns["client-code"]] != $this->plateforme) {
                        $nr3 = 1;
                    }
                    $id = $tab[$columns["client-code"]]."--".$tab[$columns["user-id"]]."--".$tab[$columns["mach-id"]];
                    if(!array_key_exists($id, $loopArray)) {
                        $loopArray[$id] = ['Smu1' => 0, 'Smu2' => 0, 'Snr1' => 0, 'Snr3' => 0];
                    }
                    $loopArray[$id]['Smu1'] += $mu1;
                    $loopArray[$id]['Smu2'] += $mu2;
                    $loopArray[$id]['Snr1'] += $nr1;
                    $loopArray[$id]['Snr3'] += $nr3;
                }
            }
            foreach($loopArray as $id=>$line) {
                $ids = explode("--", $id);
                $classe = $this->clientsClasses[$ids[0]]['client-class'];
                $sciper = $this->sciper($ids[1]);
                foreach(['K1', 'K2', 'K3'] as $itemK) {
                    $categorie = $this->getCategorie($ids[2], $itemK);
                    if($categorie[0] != "0") {
                        $mu = $line['Snr3'];
                        $n = 0;
                        if($itemK == 'K1') {
                            $mu = $line['Smu1'];
                            $n = $line['Snr1'];
                        }
                        if($itemK == 'K2') {
                            $mu = $line['Smu2'];
                        }
                        $usagesArray[] = [$ids[0], $classe, $sciper, $itemK, $ids[2], $categorie[0], $categorie[1], $categorie[2], round($mu, 3), $n];
                    }
                }
            }
        }
        elseif(floatval($this->factel) == 7) {
            $columns = $this->bilansStats[$this->factel]['T3']['columns'];
            $lines = Csv::extract($this->getFileNameInBS('T3'));
            for($i=1;$i<count($lines);$i++) {
                $tab = explode(";", $lines[$i]);
                if(($this->plateforme == $tab[$columns["platf-code"]]) && ($tab[$columns["flow-type"]] == "cae")) {
                    $letter = substr($tab[$columns["item-nbr"]], 0, 1);
                    switch($letter) {
                        case "E": 
                            $itemK = "K1";
                            break;
                        case "S":
                            $itemK = "K2";
                            break;
                        case "U":
                            $itemK = "K3";
                            break;
                        case "X":
                            $itemK = "K4";
                            break;
                    }
                    $nr = 0;
                    if(($itemK == "K1") && ($tab[$columns["proj-expl"]] == "FALSE")) {
                        $nr = 1;
                    }

                    $id = $tab[$columns["client-code"]]."--".$tab[$columns["client-class"]]."--".$tab[$columns["user-id"]]."--".$tab[$columns["mach-id"]]."--".$itemK;
                    if(!array_key_exists($id, $loopArray)) {
                        $loopArray[$id] = ['Smu' => 0, 'Snr' => 0];
                    }
                    $loopArray[$id]['Smu'] += $tab[$columns["transac-usage"]];
                    $loopArray[$id]['Snr'] += $nr;
                }
            }
            foreach($loopArray as $id=>$line) {
                $ids = explode("--", $id);
                $categorie = $this->getCategorie($ids[3], $ids[4]);
                if($categorie[0] != "0") {
                    $usagesArray[] = [$ids[0], $ids[1], $this->sciper($ids[2]), $ids[4], $ids[3], $categorie[0], $categorie[1], $categorie[2], round($line['Smu'], 3), $line['Snr']];
                }
            }
        }
        else {
            $columns = $this->bilansStats[$this->factel]['T3']['columns'];
            $lines = Csv::extract($this->getFileNameInBS('T3'));
            $nrArray = [];
            for($i=1;$i<count($lines);$i++) {
                $tab = explode(";", $lines[$i]);
                if(floatval($this->factel) == 8) {
                    $cond = ($this->plateforme == $tab[$columns["platf-code"]]) && ($tab[$columns["flow-type"]] == "cae");
                }
                elseif(floatval($this->factel) == 9) {
                    $datetime = explode(" ", $tab[$columns["transac-date"]]);
                    $date = explode("-", $datetime[0]);
                    $aa = $date[0];
                    $mm = $date[1];
                    $cond = ($aa == $this->year) && ($mm == $this->month) && ($tab[$columns["flow-type"]] == "cae");
                }
                else {
                    $cond = ($tab[$columns["year"]] == $tab[$columns["editing-year"]]) && ($tab[$columns["month"]] == $tab[$columns["editing-month"]]) && ($tab[$columns["flow-type"]] == "cae");
                }
                if($cond) {
                    $id = $tab[$columns["client-code"]]."--".$tab[$columns["client-class"]]."--".$tab[$columns["user-id"]]."--".$tab[$columns["mach-id"]]."--".$tab[$columns["item-codeK"]];
                    if(!array_key_exists($id, $loopArray)) {
                        $loopArray[$id] = ['Smu' => 0];
                    }
                    $loopArray[$id]['Smu'] += $tab[$columns["transac-usage"]];
                    
                    $idn = $tab[$columns["client-code"]]."--".$tab[$columns["client-class"]]."--".$tab[$columns["user-id"]]."--".$tab[$columns["mach-id"]];
                    if(!array_key_exists($idn, $nrArray)) {
                        $nrArray[$idn] = 0;
                    }
                    if($tab[$columns["transac-runcae"]] > 0) {
                    $nrArray[$idn] += $tab[$columns["transac-runcae"]];
                    }
                }
            }
            foreach($loopArray as $id=>$line) {
                $ids = explode("--", $id);
                $idn = $ids[0]."--".$ids[1]."--".$ids[2]."--".$ids[3];
                $ids[4] == "K1" ? $nr = $nrArray[$idn] : $nr = 0;
                $categorie = $this->getCategorie($ids[3], $ids[4]);
                if($categorie[0] != "0") {
                    $usagesArray[] = [$ids[0], $ids[1], $this->sciper($ids[2]), $ids[4], $ids[3], $categorie[0], $categorie[1], $categorie[2], round($line['Smu'], 3), $nr];
                }
            }
        }
        return $usagesArray;
    }

    /**
     * returns categories data for a machine and an item K
     *
     * @param string $machId machine id
     * @param string $itemK item K
     * @return array
     */
    function getCategorie(string $machId, string $itemK): array
    {
        $categorie = $this->getCategorieFromMachine($machId, $itemK);
        if(!empty($categorie)) {
            return [$categorie["item-nbr"], $categorie["item-name"], $categorie["item-unit"]];
        }
        return ["0", "0", "0"];
    }

    /**
     * maps report data for tabs tables and csv 
     *
     * @param array $montantsArray report data
     * @return void
     */
    function mapping(array $usagesArray): void
    {
        $scipers = $this->scipers();
        foreach($usagesArray as $line) {
            $client = $this->clients[$line[0]];
            $classe = $this->classes[$line[1]];
            if($line[2] != 0) {
                $user = $this->users[$scipers[$line[2]]];
            }
            else {
                $user = "";
            }
            $codeK = ["item-codeK"=>$line[3], "item-textK"=>$this->paramtext->getParam("item-".$line[3])];
            $machine = $this->machines[$line[4]];
            $catName = str_replace('"', '', $line[6]);
            $categorie = ["item-nbr"=>$line[5], "item-name"=>$catName, "item-unit"=>$line[7]];
            $values = [
                "transac-usage"=>$line[8], 
                "transac-runcae"=>$line[9]
            ];
            $ids = [
                "par-machine" => $line[4], 
                "par-client" => $line[0], 
                "par-user" => $line[2], 
                "par-client-user" => $line[0]."-".$line[2], 
                "par-client-classe" => $line[0]."-".$line[1],
                "use-machine-categorie" => $line[4]."-".$line[3]."-".$line[5]."-".$catName."-".$line[7],
                "use-categorie"=> $line[5]."-".$catName."-".$line[7]
            ];
            $extends = [
                "par-machine"=>[$machine],
                "par-client" => [$client], 
                "par-user" => [$user], 
                "par-client-user" => [$client, $user], 
                "par-client-classe" => [$client, $classe], 
                "use-machine-categorie" => [$machine, $codeK, $categorie], 
                "use-categorie"=>[$categorie, $codeK]
            ];
            $dimensions = [
                "par-machine"=>[$this::MACHINE_DIM], 
                "par-client" => [$this::CLIENT_DIM], 
                "par-user" => [$this::USER_DIM], 
                "par-client-user" => [$this::CLIENT_DIM, $this::USER_DIM], 
                "par-client-classe" => [$this::CLIENT_DIM, $this::CLASSE_DIM], 
                "use-machine-categorie" => [$this::MACHINE_DIM, $this::CODEK_DIM, $this::CATEGORIE_DIM], 
                "use-categorie"=>[$this::CATEGORIE_DIM, $this::CODEK_DIM]
            ];

            foreach($this->tabs as $tab=>$data) {
                if(in_array($tab, ["use-machine-categorie", "use-categorie"]) || $line[3] == "K1" || ($tab == "par-machine" && ($line[3] == "K2" || $line[3] == "K3"))) {
                    if($tab == "par-machine" && $line[3] == "K1") {
                        $this->totalM += $values["transac-usage"];
                        $this->totalN += $values["transac-runcae"];
                    }
                    if($tab == "par-user" && $line[2] == 0) {
                        continue;
                    }
                    if(!array_key_exists($ids[$tab], $this->tabs[$tab]["results"])) {
                        $this->tabs[$tab]["results"][$ids[$tab]] = ["mois"=>[]]; 
                        foreach($dimensions[$tab] as $pos=>$dimension) {
                            foreach($dimension as $d) {
                                if($extends[$tab][$pos] != "") {
                                    $this->tabs[$tab]["results"][$ids[$tab]][$d] = $extends[$tab][$pos][$d];
                                }
                                else {
                                    $this->tabs[$tab]["results"][$ids[$tab]][$d] = "";
                                }
                            }
                        }
                        foreach($this->tabs[$tab]["operations"] as $operation) {
                            $this->tabs[$tab]["results"][$ids[$tab]][$operation] = 0;
                        }
                        if($tab == "par-machine") {
                            $this->tabs[$tab]["results"][$ids[$tab]]["users"] = [];
                            $this->tabs[$tab]["results"][$ids[$tab]]["clients"] = [];
                        }
                    }
                    if(!array_key_exists($this->monthly, $this->tabs[$tab]["results"][$ids[$tab]]["mois"])) {
                        $this->tabs[$tab]["results"][$ids[$tab]]["mois"][$this->monthly] = 0;
                    }
                    if($line[3] == "K1") {
                        $this->tabs[$tab]["results"][$ids[$tab]]["mois"][$this->monthly] += $values["transac-runcae"];
                    }
                    if(in_array($tab, ["use-machine-categorie", "use-categorie"])) {
                        $this->tabs[$tab]["results"][$ids[$tab]]["transac-usage"] += $values["transac-usage"];
                    }
                    else {
                        if($line[3] == "K1") {
                            $this->tabs[$tab]["results"][$ids[$tab]]["stat-hmach"] += $values["transac-usage"];
                            $this->tabs[$tab]["results"][$ids[$tab]]["stat-run"] += $values["transac-runcae"];
                        }
                        if($line[3] == "K2" && $tab == "par-machine") {
                            $this->tabs[$tab]["results"][$ids[$tab]]["stat-hoper"] += $values["transac-usage"];

                        }
                        if($line[3] == "K3" && $tab == "par-machine") {
                            $this->tabs[$tab]["results"][$ids[$tab]]["stat-run-user"] += $values["transac-usage"];
                        }
                    }
                    if($tab == "par-machine" && $this->plateforme != $line[0]) {
                        if($line[2] != 0 && !in_array($line[2], $this->tabs[$tab]["results"][$ids[$tab]]["users"])) {
                            $this->tabs[$tab]["results"][$ids[$tab]]["users"][] = $line[2];
                        }
                        if(!in_array($line[0], $this->tabs[$tab]["results"][$ids[$tab]]["clients"])) {
                            $this->tabs[$tab]["results"][$ids[$tab]]["clients"][] = $line[0];
                        }
                    }
                }
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
        foreach($this->tabs["par-machine"]["results"] as $key=>$cells) {
            $this->tabs["par-machine"]["results"][$key]["stat-nbuser"] = count($this->tabs["par-machine"]["results"][$key]["users"]);
            $this->tabs["par-machine"]["results"][$key]["stat-nbclient"] = count($this->tabs["par-machine"]["results"][$key]["clients"]);
        }
        foreach($this->tabs["use-machine-categorie"]["results"] as $key=>$cells) {
            $cmKey = $cells["mach-id"]."--".$cells["item-codeK"];
            $catKey = $cells["item-nbr"]."--".$cells["item-name"]."--".$cells["item-unit"];
            if(array_key_exists($cmKey, $this->changeMachines)) {
                if(!in_array($catKey, $this->changeMachines[$cmKey])) {
                    $this->changeMachines[$cmKey][] = $catKey;
                    if(!in_array($cells["mach-id"], $this->totalChange)) {
                        $this->totalChange[] = $cells["mach-id"];
                    }
                }
            }
            else {
                $this->changeMachines[$cmKey] = [$catKey];
            }
        }

        $title = '<div class="total">Statistiques machines : '.$this->period().' </div>';
        $title .= '<div class="subtotal">Nombre d’heures productives = '.$this->format($this->totalM, "float").'</div>';
        $title .= '<div class="subtotal">Nombre de runs CAE productifs = '.$this->format($this->totalN, "int").'</div>';
        $change = count($this->totalChange);
        if($change > 0) {
            if($change == 1) {
                $sentence = "1 machine a changé de catégorie";
            }
            else {
                $sentence = $change." machines ont changé de catégories";
            }
            $title .= '<div>
                            <svg class="icon red" aria-hidden="true">
                                <use xlink:href="#alert-triangle"></use>
                            </svg>
                            '.$sentence.' sur la période
                            <svg class="icon red" aria-hidden="true">
                                <use xlink:href="#alert-triangle"></use>
                            </svg>
                        
                        </div>';
        }
        
        echo $this->templateDisplay($title);
    }
}
