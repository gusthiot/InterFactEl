<?php

require_once("Csv.php");

/**
 * Modif class represents a csv file with last version actions
 */
class Modif extends Csv 
{

    /**
     * Extracts the csv file content as an array and return it
     *
     * @param string $csv name of the csv file
     * @return array
     */
    static function load(string $csv): array 
    {
        $modifs = [];
        $lines = self::extract($csv);
        foreach($lines as $line) {
            $modifs[] = explode(";", $line);
        }
        return $modifs;
    }
    
}
