<?php

require_once("Csv.php");

/**
 * Result class represents a csv file with the facturation result parameters
 */
class Result extends Csv
{

    /**
     * The csv files names
     */
    const NAME = "result.csv";

    /**
     * Array containing the parameters, by specific keys
     *
     * @var array
     */
    private array $params;

    /**
     * Class constructor
     *
     * @param string $dir directory where to find the csv file
     */
    function __construct(string $dir)
    {
        $this->params = [];
        $lines = self::extract($dir.self::NAME);
        foreach($lines as $line) {
            $this->params[$line[0]] = $line[2];
        }
    }

    /**
     * Gets the parameter for a specific key
     *
     * @param string $key specific key
     * @return string
     */
    function getParam(string $key): string
    {
        if(array_key_exists($key, $this->params)) {
            return $this->params[$key];
        }
        else {
            return "";
        }
    }

}
