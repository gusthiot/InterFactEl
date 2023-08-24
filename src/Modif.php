<?php

require_once("Csv.php");

class Modif extends Csv 
{

    public array $modifs;

    function __construct($csv) 
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
