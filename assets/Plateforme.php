<?php

require_once("Csv.php");

class Plateforme extends Csv 
{
    private array $files;

    function __construct($csv) 
    {
        $this->files = [];
        $lines = $this->extract($csv);
        if(empty($lines)) {
            return false;
        }
        else {
            $num = 0;
            foreach($lines as $line) {
                $num++;
                if($num == 1) {
                    continue;
                } 
                $tab = explode(";", $line);
                $this->files[$tab[0]] = $tab[6];
            }
            return true;
        }
    }
    
    function getFile(string $key): string 
    {
        return $this->files[$key];
    }
}
?>
