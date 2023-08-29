<?php

require_once("Csv.php");

class Journal extends Csv 
{

    private array $modifs;

    function __construct(string $csv) 
    {
        $this->modifs = [];
        $lines = $this->extract($csv);
        foreach($lines as $line) {
            $this->modifs[] = explode(";", $line);
        }
    }
    
    function getModifs(): array 
    {
        return $this->modifs;
    }

}
?>
