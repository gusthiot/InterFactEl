<?php

require_once("Csv.php");

/**
 * Version class represents a csv file with versions information
 */
class Version extends Csv
{

    /**
     * the csv file name
     */
    const NAME = "version.csv";

    /**
     * Extracts the csv file content as an array and return it, with each metadata name as key
     *
     * @param string $dir directory where to find the csv file
     * @return array
     */
    static function load(string $dir): array
    {
        $infos = [];
        $lines = self::extract($dir."/".self::NAME);
        foreach($lines as $line) {
            $infos[$line[0]] = $line;
        }
        return $infos;
    }

}
