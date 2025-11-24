<?php

require_once("Csv.php");

/**
 * Info class represents a csv file with metadata about a run
 */
class Info extends Csv
{

    /**
     * the csv file name
     */
    const NAME = "info.csv";

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
            $tab = explode(";", $line);
            $infos[$tab[0]] = $tab;
        }
        return $infos;
    }

    /**
     * Saves an array content to the csv file determined by its location (remove old content)
     *
     * @param string $dir directory where to save the csv file
     * @param array $content content to be saved
     * @return void
     */
    static function save(string $dir, array $content): void
    {
        $data = [];
        foreach($content as $line) {
            $data[] = $line;
        }
        self::write($dir."/".self::NAME, $data);
    }
}
