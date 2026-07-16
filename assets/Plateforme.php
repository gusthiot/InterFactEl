<?php

require_once("Csv.php");

/**
 * Plateforme class represents a csv file with plateformes list
 */
class Plateforme extends Csv
{

    /**
     * The csv file name
     */
    const NAME = "listeplateforme.csv";

    /**
     * Array containing plateformes
     *
     * @var array
     */
    private array $plateformes;

    /**
     * Class constructor
     */
    function __construct()
    {
        $this->plateformes = [];
        $lines = self::extract(CONFIG.self::NAME, true);
        foreach($lines as $line) {
            $this->plateformes[$line[0]] = $line;
        }
    }

    /**
     * Returns a plateforme name from its id
     *
     * @param string $id plateforme id
     * @return string
     */
    function getName(string $id): string {
        return $this->plateformes[$id][1];
    }

}
