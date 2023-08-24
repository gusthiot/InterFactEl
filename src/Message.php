<?php

require_once("Csv.php");

class Message extends Csv 
{

    const CSV = "../CONFIG/message.csv";

    public array $messages;

    function __construct() 
    {
        $this->messages = [];
        $lines = $this->extract(self::CSV);
        foreach($lines as $line) {
            $tab = explode(";", $line);
            $this->messages[$tab[0]] = $tab[1];
        }
    }
    
    function getMessage(string $id): string 
    {
        return $this->messages[$id];
    }

}
?>
