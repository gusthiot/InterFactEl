<?php

require_once("Csv.php");

class Paramedit extends Csv 
{

    public array $params;

    function load(string $csv): void 
    {
        $this->params = [];
        $lines = $this->extract($csv);
        foreach($lines as $line) {
            $tab = explode(";", $line);
            $this->params[$tab[0]] = $tab[1];
        }
    }
    
    function getParam(string $key): string 
    {
        return $this->params[$key];
    }

}
?>
