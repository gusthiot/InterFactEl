<?php

require_once("Csv.php");

/**
 * Superviseur class represents a csv file with users with supervisor rights
 */
class Superviseur extends Csv
{

    /**
     * The csv file name
     */
    const NAME = "superviseur.csv";

    /**
     * Array containing users with supervisor rights
     *
     * @var array
     */
    private array $superviseurs;

    /**
     * Class constructor
     */
    function __construct()
    {
        $this->superviseurs = [];
        $lines = self::extract(CONFIG.self::NAME);
        foreach($lines as $line) {
            $this->superviseurs[] = $line[0];
        }
    }

    /**
     * Checks is a user has supervisor rights
     *
     * @param string $login user by its login surname
     * @return boolean
     */
    function isSuperviseur(string $login): bool
    {
        if (in_array($login, $this->superviseurs)) {
            return true;
        }
        return false;
    }
}
