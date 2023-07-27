<?php

require_once("Csv.php");

class Info extends Csv {


    public $infos;

    function __construct($csv) {
        $this->infos = array();
        $lines = $this->extract($csv);
        foreach($lines as $line) {
            $tab = explode(";", $line);
            $this->infos[$tab[1]] = $tab[2];
        }
    }
    
    function getInfos() {
        return $this->infos;
    }

}
?>
