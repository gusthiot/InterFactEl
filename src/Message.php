<?php

require_once("Csv.php");

class Message extends Csv 
{

    const NAME = "message.csv";

    private array $messages;

    function __construct() 
    {
        $this->messages = [];
        $lines = $this->extract(CONFIG.self::NAME);
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
