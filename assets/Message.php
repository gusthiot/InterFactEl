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

    const LABELS = ["msg1", "msg2", "msg3", "msg3.1", "msg3.2", "msg3.3", "msg3.4", "msg3.5", "msg3.6", "msg4", "msg5", "msg6", "msg7", "msg8"];

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
