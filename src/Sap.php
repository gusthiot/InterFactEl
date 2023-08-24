<?php

require_once("Csv.php");

class Sap extends Csv 
{

    function load(string $dir): array 
    {
        $bills = [];
        $lines = $this->extract($dir."/sap.csv");
        foreach($lines as $line) {
            $tab = explode(";", $line);
            $bills[$tab[1]] = $tab; 
        }
        return $bills;
    }
    
    function save(string $dir, array $content): void 
    {
        $data = [];
        foreach($content as $line) {
            $data[] = $line;
        }
        $this->write($dir."/sap.csv", $data);
    }
}
?>
