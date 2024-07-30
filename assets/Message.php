<?php

require_once("Csv.php");

/**
 * Message class represents a csv file with standard messages for specific situations
 */
class Message extends Csv 
{

    /**
     * The csv file name
     */
    const NAME = "message.csv";

    /**
     * Array containing the messages, by specific keys
     *
     * @var array
     */
    private array $messages;

    /**
     * Class constructor
     */
    function __construct() 
    {
        $this->messages = [];
        $lines = self::extract(CONFIG.self::NAME);
        foreach($lines as $line) {
            $tab = explode(";", $line);
            $this->messages[$tab[0]] = $tab[1];
        }
    }
    
    /**
     * Gets the message for a specific key
     *
     * @param string $id specific key
     * @return string
     */
    function getMessage(string $id): string 
    {
        if(array_key_exists($id, $this->messages)) {
            return $this->messages[$id];
        }
        else {
            return "";
        }
    }

}
