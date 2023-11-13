<?php

require_once("Csv.php");

class Paramedit extends Csv 
{ 

    private array $params;

    function load(string $csv): bool 
    {
        $this->params = [];
        $lines = $this->extract($csv);
        if(empty($lines)) {
            return false;
        }
        else {
            foreach($lines as $line) {
                $tab = explode(";", $line);
                $this->params[$tab[0]] = $tab[1];
            }
            return true;
        }
    }
    
    function getParam(string $key): string 
    {
        return $this->params[$key];
    }

}
?>
