<?php

require_once("Csv.php");

/**
 * Gestionnaire class represents a csv file with users having rights to manage the billing
 */
class Gestionnaire extends Csv 
{

    /**
     * The csv file name
     */
    const NAME = "gestionnaire.csv";

    /**
     * Array containing user, as key, and its rights in an array, as value
     *
     * @var array
     */
    private array $gestionnaires;

    /**
     * Class constructor
     */
    function __construct() 
    {
        $this->gestionnaires = [];
        $lines = self::extract(CONFIG.self::NAME);
        foreach($lines as $line) {
            $tab = explode(";", $line);

            if(!array_key_exists($tab[0], $this->gestionnaires)) {
                $this->gestionnaires[$tab[0]]['complet'] = [];
                $this->gestionnaires[$tab[0]]['plates'] = [];
            }
            $this->gestionnaires[$tab[0]]['plates'][$tab[1]] = $tab[2];
            if($tab[3] == "COMPLET") {
                $this->gestionnaires[$tab[0]]['complet'][$tab[1]] = $tab[2];
            }
        }
    }
    
    /**
     * Gets the rights for a determined user
     *
     * @param string $login user by its login surname
     * @return array
     */
    function getGestionnaire(string $login): array
    {
        if (array_key_exists($login, $this->gestionnaires)) {
            return $this->gestionnaires[$login];
        }
        return [];
    }

}
