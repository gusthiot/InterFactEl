<?php

require_once("Csv.php");

class Sap extends Csv {


    public $bills;

    function __construct($csv) {
        $this->bills = array();
        $lines = $this->extract($csv);
        foreach($lines as $line) {
            $this->bills[] = explode(";", $line);
        }
    }
    
    function getBills() {
        return $this->bills;
    }

}
?>
