<?php

require_once("Csv.php");

class Superviseur extends Csv {

    public $csv = "CONFIG/superviseur.csv";

    public $superviseurs;

    function __construct() {
        $this->superviseurs = array();
        $lines = $this->extract($this->csv);
        foreach($lines as $line) {
            $this->superviseurs[] = $line;
        }
    }
    
    function isSuperviseur($login) {
        if (in_array($login, $this->superviseurs)) {
            return TRUE;
        }
        return FALSE;
    }
}
?>
