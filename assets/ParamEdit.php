<?php

require_once("Csv.php");

/**
 * ParamEdit class represents a csv file with edition parameters
 */
class ParamEdit extends Csv
{

    /**
     * The csv files names
     */
    const NAME = "paramedit.csv";

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
            $this->params[$line[0]] = $line[1];
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
