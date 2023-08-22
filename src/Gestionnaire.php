<?php

require_once("Csv.php");

class Gestionnaire extends Csv {

    public $csv = "CONFIG/gestionnaire.csv";

    public $gestionnaires;

    function __construct() {
        $this->gestionnaires = array('sciper'=>'000000', 'plates'=>array());
        $lines = $this->extract($this->csv);
        foreach($lines as $line) {
            $tab = explode(";", $line);
            if(array_key_exists($tab[0], $this->gestionnaires)) {
                $this->gestionnaires[$tab[0]]['plates'][$tab[2]] = $tab[3];

            }
            else {
                $this->gestionnaires[$tab[0]]['sciper'] = $tab[1];
                $this->gestionnaires[$tab[0]]['plates'] = array($tab[2]=>$tab[3]);
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
