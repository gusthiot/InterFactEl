<?php

require_once("Csv.php");

class Message extends Csv {

    public $csv = "../CONFIG/message.csv";

    public $messages;

    function __construct() {
        $this->messages = array();
        $lines = $this->extract($this->csv);
        foreach($lines as $line) {
            $tab = explode(";", $line);
            $this->messages[$tab[0]] = $tab[1];
        }
    }
    
    function getMessage($id) {
        return $this->messages[$id];
    }

}
?>
