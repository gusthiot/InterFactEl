<?php

/**
 * Facture class represents a json file with one bill for one client
 */
class Facture 
{

    /**
     * Extracts the json file content in an encoded string
     *
     * @param string $name the json file name
     * @return string
     */
    static function load(string $name): string
    {
        $facture = "";
        if ((file_exists($name)) && (($open = fopen($name, "r")) !== false)) {
            $facture = fread($open, filesize($name));    
            fclose($open);
        }
        return $facture;
    }

}
