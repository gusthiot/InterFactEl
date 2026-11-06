<?php

require_once("Csv.php");

/**
 * Class representing
 */
class MessageSap extends Csv
{
    /**
     * The csv file name
     */
    const NAME = "messagesap.csv";

    /**
     * Tree containing the messages, by specific keys
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
        $lines = self::extract(CONFIG.self::NAME, true);
        foreach($lines as $line) {
            if(!array_key_exists($line[0], $this->messages)) {
                $this->messages[$line[0]] = [];
            }
            if(!array_key_exists($line[1], $this->messages[$line[0]])) {
                $this->messages[$line[0]][$line[1]] = [];
            }
            $this->messages[$line[0]][$line[1]][$line[2]] = $line[3];
        }
    }

    /**
     * Gets message
     *
     * @return string
     */
    function getMessage(string $n_app, string $n_ord, string $n_fin): string
    {
        if(array_key_exists($n_app, $this->messages)) {
            if(array_key_exists($n_ord, $this->messages[$n_app])) {
                if(array_key_exists($n_fin, $this->messages[$n_app][$n_ord])) {
                    return $this->messages[$n_app][$n_ord][$n_fin];
                }
            }
        }
        return "";
    }
}
