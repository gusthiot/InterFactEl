<?php

require_once("Csv.php");

class Paramtext extends Csv {

    public $params;

    function __construct($csv) {
        $this->params = [];
        $lines = $this->extract($csv);
        foreach($lines as $line) {
            $tab = explode(";", $line);
            $this->params[$tab[0]] = $tab[1];
        }
    }
    
    function getParam($key) {
        return $this->params[$key];
    }

}
?>
