<?php

require_once("Csv.php");

/**
 * Paramedit class represents a csv file with run parameters
 *  - edit : paramedit.csv contains edition parameters
 *  - text : paramtext.csv contains text parameters
 *  - result : result.csv contains metadata from previous run
 */
class ParamRun extends Csv 
{ 

    /**
     * The csv files names
     */
    const NAMES = ['edit'=>"paramedit.csv", 'text'=>"paramtext.csv", 'result'=>"result.csv"];

    /**
     * The csv column containing relevant data
     */
    const FIELDS = ['edit'=>1, 'text'=>1, 'result'=>2];

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
     * @param string $type type of csv file (edit, text, result)
     */
    function __construct(string $dir, string $type) 
    {
        $this->params = [];
        $lines = self::extract($dir.self::NAMES[$type]);
        foreach($lines as $line) {
            $tab = explode(";", $line);
            $this->params[$tab[0]] = $tab[self::FIELDS[$type]];
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
