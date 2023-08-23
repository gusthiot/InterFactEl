<?php

require_once("Csv.php");

class Journal extends Csv {


    public $modifs;

    function __construct($csv) {
        $this->modifs = [];
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
