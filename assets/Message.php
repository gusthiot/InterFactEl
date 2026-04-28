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

    const LABELS = ["msg1", "msg2", "msg3", "msg3.1", "msg3.2", "msg3.3", "msg3.4", "msg3.5", "msg3.6", "msg4", "msg5", "msg6", "msg7", "msg8", "msg9", "msg10",
                        "plateforme01", "plateforme02",
                        "articlesap01",
                        "overhead01", "overhead02", "overhead03",
                        "base01",
                        "classeclient01", "classeclient02", "classeclient03", "classeclient04", "classeclient05", "classeclient06", "classeclient07",
                        "partenaire01", "partenaire02", "partenaire03",
                        "classeprestation01", "classeprestation02", "classeprestation03", "classeprestation04", "classeprestation05", "classeprestation06",
                        "categorie01", "categorie02", "categorie03", "categorie04", "categorie05", "categorie06",
                        "groupe01", "groupe02", "groupe03", "groupe04",
                        "coeffprestation01", "coeffprestation02", "coeffprestation03", "coeffprestation04", "coeffprestation05",
                        "basecateg01", "basecateg02", "basecateg03", "basecateg04", "basecateg05",
                        "logo01",
                        "grille01", "grille02"];

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
            $this->messages[$line[0]] = $line[1];
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

    /**
     * Gets all messages
     *
     * @return array
     */
    function getMessages(): array
    {
        return $this->messages;
    }

}
