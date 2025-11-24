<?php

/**
 * Ticket class represents a json file with all billing data
 */
class Ticket
{

    /**
     * The json file name
     */
    const NAME = "ticket.json";

    /**
     * Extracts the json file content in an encoded string
     *
     * @param string $dir directory where to find the json file
     * @return string
     */
    static function load(string $dir): string
    {
        $ticket = "";
        $name = $dir."/".self::NAME;
        if ((file_exists($name)) && (($open = fopen($name, "r")) !== false)) {
            $ticket = fread($open, filesize($name));
            fclose($open);
        }
        return $ticket;
    }

}
