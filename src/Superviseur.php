<?php

require_once("Csv.php");

class Superviseur extends Csv 
{

    const CSV = "CONFIG/superviseur.csv";

    public array $superviseurs;

    function __construct() 
    {
        $this->superviseurs = [];
        $lines = $this->extract(self::CSV);
        foreach($lines as $line) {
            $this->superviseurs[] = $line;
        }
    }
    
    function isSuperviseur(string $login): bool 
    {
        if (in_array($login, $this->superviseurs)) {
            return true;
        }
        return false;
    }
}
?>
