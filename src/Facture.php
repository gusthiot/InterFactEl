<?php


class Facture {


    public $facture;

    function __construct($name) {
        $this->facture = "";
        if ((file_exists($name)) && (($open = fopen($name, "r")) !== false)) {
            $this->facture = fread($open, filesize($name));    
            fclose($open);
        }
        return $this->facture;
    }

    function getFacture() {
        return $this->facture;
    }

}
?>
