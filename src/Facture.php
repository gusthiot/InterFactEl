<?php


class Facture 
{


    public string $facture;

    function __construct(string $name) 
    {
        $this->facture = "";
        if ((file_exists($name)) && (($open = fopen($name, "r")) !== false)) {
            $this->facture = fread($open, filesize($name));    
            fclose($open);
        }
    }

    function getFacture(): string 
    {
        return $this->facture;
    }

}
?>
