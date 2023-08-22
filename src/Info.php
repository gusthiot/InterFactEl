<?php

require_once("Csv.php");

class Info extends Csv {


    public $infos;

    function load($dir) {
        $this->infos = [];
        $lines = $this->extract($dir."/info.csv");
        foreach($lines as $line) {
            $tab = explode(";", $line);
            $this->infos[$tab[0]] = $tab;
        }
        return $this->infos;
    }

    function save($dir, $content) {
        $data = [];
        foreach($content as $line) {
            $data[] = $line;
        }
        $this->write($dir."/info.csv", $data);
    }
}
?>
