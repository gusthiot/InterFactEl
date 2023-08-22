<?php

require_once("Csv.php");

class Sap extends Csv {


    public $bills;

    function load($dir) {
        $this->bills = array();
        $lines = $this->extract($dir."/sap.csv");
        foreach($lines as $line) {
            $tab = explode(";", $line);
            $this->bills[$tab[1]] = $tab; 
        }
        return $this->bills;
    }
    
    function save($dir, $content) {
        $data = [];
        foreach($content as $line) {
            $data[] = $line;
        }
        $this->write($dir."/sap.csv", $data);
    }
}
?>
