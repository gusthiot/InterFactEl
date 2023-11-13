<?php

require_once("Csv.php");

class Result extends Csv 
{
    private array $results;

    function load(string $csv): bool 
    {
        $this->results = [];
        $lines = $this->extract($csv);
        if(empty($lines)) {
            return false;
        }
        else {
            foreach($lines as $line) {
                $tab = explode(";", $line);
                $this->results[$tab[0]] = $tab[2];
            }
        }
    }
    
    function getResult(string $key): string 
    {
        return $this->results[$key];
    }
}
?>
