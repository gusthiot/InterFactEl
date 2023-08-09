<?php

require_once("Csv.php");

class Gestionnaire extends Csv {

    public $csv = "CONFIG/gestionnaire.csv";

    public $gestionnaires;

    function __construct() {
        $this->gestionnaires = array();
        $lines = $this->extract($this->csv);
        foreach($lines as $line) {
            $tab = explode(";", $line);
            if(array_key_exists($tab[0], $this->gestionnaires)) {
                $this->gestionnaires[$tab[0]][$tab[1]] = $tab[2];

            }
            else {
                $this->gestionnaires[$tab[0]] = array($tab[1]=>$tab[2]);
            }
        }
    }
    
    function getGestionnaire($login) {
        return $this->gestionnaires[$login];
    }

    function isGestionnaire($login) {
        if (array_key_exists($login, $this->gestionnaires)) {
            return TRUE;
        }
        return FALSE;
    }
}
?>
