<?php

require_once("Csv.php");

/**
 * Gestionnaire class represents a csv file with users having rights to manage the billing
 */
class Gestionnaire extends Csv 
{

    /**
     * The available rights and their bit position
     */
    const RIGHTS = ["reporting"=>0, "facturation"=>1, "tarifs"=>2];

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
                foreach(self::RIGHTS as $name=>$pos) {
                    $this->gestionnaires[$tab[0]][$name] = [];
                }
            }

            foreach(self::RIGHTS as $name=>$pos) {
                if(self::hasRight($tab[3], $pos)) {
                    $this->gestionnaires[$tab[0]][$name][$tab[1]] = $tab[2];
                }
            }
        }
    }

    /**
     * Checks if mixed right contains specific bit right
     *
     * @param integer $right mixed rigth
     * @param integer $pos specific bit right
     * @return boolean
     */
    static function hasRight(int $right, int $pos) : bool
    {
        return $right & (1 << $pos);
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
