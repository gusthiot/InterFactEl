<?php

class ConcatT
{
    const COLUMNS = [
        "t1" => ["invoice-year", "invoice-month", "invoice-id", "invoice-ref", "invoice-type",
            "client-code", "client-sap", "client-name", "client-idclass", "client-class",
            "client-labelclass", "proj-id", "proj-nbr", "proj-name", "item-idsap", "item-codeD", 
            "item-order", "item-labelcode", "total-fact"],
        "t2" => ["invoice-year", "invoice-month", "invoice-id", "invoice-type", "platf-name",
            "client-code", "client-sap", "client-name", "client-idclass", "client-class", 
            "client-labelclass", "proj-id", "proj-nbr", "proj-name", "user-id", "user-name-f", 
            "date-start-y", "date-start-m", "date-end-y", "date-end-m", "item-idsap", 
            "item-codeD", "item-order", "item-labelcode", "item-id", "item-nbr", "item-name", 
            "transac-quantity", "item-unit", "valuation-price", "sum-deduct", "total-fact"],
        "t3" => ["editing-year", "editing-month", "year", "month", "invoice-year", "invoice-month",
            "client-code", "client-sap", "client-name", "client-class", "client-labelclass", 
            "oper-id", "oper-name", "oper-note", "staff-note", "mach-id", "mach-name", "mach-extra",
            "user-id", "user-sciper", "user-name", "user-first", "proj-id", "proj-nbr", "proj-name",
            "proj-expl", "flow-type", "item-grp", "item-id", "item-codeK", "item-textK",
            "item-text2K", "item-nbr", "item-name", "item-unit", "item-codeD", "item-labelcode", 
            "item-extra", "transac-date", "transac-valid", "transac-quantity", "transac-usage", 
            "transac-runtime", "valuation-price", "valuation-brut", "discount-type", "discount-CHF",
            "valuation-net", "subsid-ok", "deduct-CHF", "subsid-deduct", "total-fact", 
            "discount-bonus", "subsid-bonus"]
    ];

    const KEYS = [
        "t1" => "T1",
        "t2" => "T2",
        "t3" => "T3"
    ];

    const SAVES = [
        "t1" => "T1.csv",
        "t2" => "T2.csv",
        "t3f" => "T3fact.csv",
        "t3s" => "T3stat.csv"
    ];

    static function run($from, $to, $plateforme, $type)
    {
        $bilansStats = new BSFile("../bilans-stats.json", "Bilans_Stats");
        if(strlen($type) == 2) {
            $columns = self::COLUMNS[$type];
            $key = self::KEYS[$type];
        }
        else {
            $columns = self::COLUMNS["t3"];
            $key = self::KEYS["t3"];
        }
        $date = $from;
        $first = true;
        while(true) {
            $content = [];
            if($first) {
                $first = false;
                $line = [];
                $paramtext = new ParamText();
                foreach($columns as $label) {
                    $line[] = $paramtext->getParam($label);
                }
                $content[] = Csv::formatLine($line);
            }
            $month = substr($date, 4, 2);
            $year = substr($date, 0, 4);
            $dir = DATA.$plateforme."/".$year."/".$month;

            if (file_exists($dir."/".Lock::FILES['month'])) {
                $version = Lock::load($dir, "month");
                $dirVersion = $dir."/".$version;
                $run = Lock::load($dirVersion, "version");
                $dirRun = $dirVersion."/".$run;
            }
            else {
                foreach(globReverse($dir) as $dirVersion) {
                    $run = Lock::load($dirVersion, "version");
                    if (!is_null($run)) {
                        $dirRun = $dirVersion."/".$run;
                        break;
                    }
                }
            }
            
            $infos = Info::load($dirRun);
            $factel = $infos["FactEl"][2];
            $name = $bilansStats->findCsvUrl($dirRun, $factel, $key);
            $csv = Csv::extract($name);
            if(!empty($csv)) {
                $positions = $bilansStats->getColumns($factel, $key);
                for($i=1;$i<count($csv);$i++) {
                    $tab = explode(";", $csv[$i]);
                    $cond = true;
                    if(in_array($type, ["t3f", "t3s"])) {
                        if($factel <= 9) {
                            $eY = $year;
                            $eM = $month;
                            $datetime = explode(" ", $tab[$positions["transac-date"]]);
                            $parts = explode("-", $datetime[0]);
                            $tY = $parts[0];
                            $tM = $parts[1];
                        }
                        else {
                            $eY = $tab[$positions["editing-year"]];
                            $eM = $tab[$positions["editing-month"]];
                            $tY = $tab[$positions["year"]];
                            $tM = $tab[$positions["month"]];
                        }
                        if(($type == "t3f") && !(($tab[$positions["invoice-year"]] == $eY) && ($tab[$positions["invoice-month"]] == $eM))) {
                            $cond = false;
                        }
                        if(($type == "t3s") && !(($tY == $eY) && ($tM == $eM))) {
                            $cond = false;
                        }
                    }
                    if($cond) {
                        $line = [];
                        foreach($columns as $column) {
                            if(array_key_exists($column, $positions)) {
                                $line[] = $tab[$positions[$column]];
                            }
                            else {   
                                switch($column) {
                                    case "editing-year":
                                        $line[] = $eY;
                                        break;
                                    case "editing-month":
                                        $line[] = $eM;
                                        break;
                                    case "year":
                                        $line[] = $tY;
                                        break;
                                    case "month":
                                        $line[] = $tM;
                                        break;
                                    case "transac-valid":
                                        $line[] = "1";
                                        break;
                                    default:
                                        $line[] = "";
                                }
                            }
                        }
                        $content[] = Csv::formatLine($line);
                    }                   
                }
            }

            Csv::append(TEMP.self::SAVES[$type], $content);

            if($date == $to) {
                break;
            }

            if($month == "12") {
                $date += 89;
            }
            else {
                $date++;
            }
        }
        Lock::saveByName("../".USER.".lock", TEMP.self::SAVES[$type]);
    }
}
