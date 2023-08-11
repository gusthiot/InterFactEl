<?php

require_once("Csv.php");

class Modif extends Csv {


    public $modifs;

    function __construct($csv) {
        $this->modifs = array();
        $lines = $this->extract($csv);
        foreach($lines as $line) {
            $this->modifs[] = explode(";", $line);
        }
    }
    
    function getModifs() {
        return $this->modifs;
    }

}
?>
