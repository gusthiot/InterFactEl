<?php

require_once("Csv.php");

class Gestionnaire extends Csv 
{

    const NAME = "gestionnaire.csv";

    private array $gestionnaires;

    function __construct() 
    {
        $this->gestionnaires = ['sciper'=>'000000', 'plates'=>[], 'tarifs'=>[]];
        $lines = $this->extract(CONFIG.self::NAME);
        foreach($lines as $line) {
            $tab = explode(";", $line);

            if(!array_key_exists($tab[0], $this->gestionnaires)) {
                $this->gestionnaires[$tab[0]]['sciper'] = $tab[1];
                $this->gestionnaires[$tab[0]]['tarifs'] = [];
                $this->gestionnaires[$tab[0]]['plates'] = [];
            }
            $this->gestionnaires[$tab[0]]['plates'][$tab[2]] = $tab[3];
            if($tab[4] == "COMPLET") {
                $this->gestionnaires[$tab[0]]['tarifs'][$tab[2]] = $tab[3];
            }


        }
    }
    
    function getGestionnaire(string $login): array
    {
        if (array_key_exists($login, $this->gestionnaires)) {
            return $this->gestionnaires[$login];
        }
        return [];
    }

}
?>
