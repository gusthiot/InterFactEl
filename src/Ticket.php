<?php


class Ticket 
{


    private string $ticket;

    function __construct(string $name) 
    {
        $this->ticket = "";
        if ((file_exists($name)) && (($open = fopen($name, "r")) !== false)) {
            $this->ticket = fread($open, filesize($name));    
            fclose($open);
        }
    }

    function getTicket(): string 
    {
        return $this->ticket;
    }

}
?>
