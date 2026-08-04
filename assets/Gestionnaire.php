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
    private array $rights;

    private array $content;

    private array $plateformes;

    /**
     * Class constructor
     */
    function __construct()
    {
        $this->rights = [];
        $this->content = [];
        $this->plateformes = [];
        $lines = self::extract(CONFIG.self::NAME, true);
        foreach($lines as $line) {

            $line2 = [$line[0], $line[1], 0, 0, 0, $line[3]];

            if(!array_key_exists($line[0], $this->rights)) {
                foreach(self::RIGHTS as $name=>$pos) {
                    $this->rights[$line[0]][$name] = [];
                }
            }

            if(!array_key_exists($line[0], $this->plateformes)) {
                $this->plateformes[$line[0]] = [];
            }

            if(!array_key_exists($line[1], $this->plateformes[$line[0]])) {
                $this->plateformes[$line[0]][$line[1]] = [];
                foreach(self::RIGHTS as $name=>$pos) {
                    $this->plateformes[$line[0]][$line[1]][$name] = 0;
                }
            }

            foreach(self::RIGHTS as $name=>$pos) {
                if(self::hasRight($line[2], $pos) && ($line[3] > 0)) {
                    $this->rights[$line[0]][$name][$line[1]] = $line[3];

                    $this->plateformes[$line[0]][$line[1]][$name] = 1;
                    $line2[2-$pos+2] = 1;
                }
            }

            $this->content[] = $line2;
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
    function getRights(string $login): array
    {
        if(array_key_exists($login, $this->rights)) {
            return $this->rights[$login];
        }
        return [];
    }

    function getPlateformes(string $login): array
    {
        if(array_key_exists($login, $this->plateformes)) {
            return $this->plateformes[$login];
        }
        return [];
    }

    function getContent() {
        return $this->content;
    }
}
