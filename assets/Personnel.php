<?php

require_once("Csv.php");

/**
 * Personnel class represents a csv file with users who could access the interface
 */
class Personnel extends Csv
{

    /**
     * The csv file name
     */
    const NAME = "personnel.csv";

    /**
     * Array containing users
     *
     * @var array
     */
    private array $personnes;

    /**
     * Class constructor
     */
    function __construct()
    {
        $this->personnes = [];
        $lines = self::extract(CONFIG.self::NAME, true);
        foreach($lines as $line) {
            $this->personnes[$line[0]] = $line;
        }
    }

}
