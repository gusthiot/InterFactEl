<?php

require_once("Csv.php");

class Result extends Csv 
{
    public array $results;

    function __construct(string $csv) 
    {
        $this->results = [];
        $lines = $this->extract($csv);
        foreach($lines as $line) {
            $tab = explode(";", $line);
            $this->results[$tab[0]] = $tab[2];
        }
    }
    
    function getResult(string $key): string 
    {
        return $this->results[$key];
    }
}
?>
