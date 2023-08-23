<?php

require_once("Csv.php");

class Result extends Csv {

    public $results;

    function __construct($csv) {
        $this->results = [];
        $lines = $this->extract($csv);
        foreach($lines as $line) {
            $tab = explode(";", $line);
            $this->results[$tab[0]] = $tab[2];
        }
    }
    
    function getResult($key) {
        return $this->results[$key];
    }
}
?>
