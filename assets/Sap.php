<?php

require_once("Csv.php");

/**
 * Sap class represents a csv file with the bills list
 */
class Sap extends Csv 
{
    /**
     * The csv files names
     */
    const NAME = "sap.csv";

    /**
     * Array containing the bills as arrays
     *
     * @var array
     */
    private array $bills;

    /**
     * First line of the table containing the columns titles
     *
     * @var array
     */
    private array $title;

    /**
     * Class constructor
     *
     * @param string $dir directory where to find the csv file
     */
    function __construct(string $dir) 
    {
        $this->bills = [];
        $lines = self::extract($dir."/".self::NAME);
        $first = true;
        foreach($lines as $line) {
            $tab = explode(";", $line);
            if($first) {
                $first = false;
                $this->title = $tab;
            }
            else {
                $this->bills[$tab[1]] = $tab; 
            }
        }
    }

    /**
     * Getter for $title array variable
     *
     * @return array
     */
    function getTitle(): array
    {
        return $this->title;
    }

    /**
     * Getter for $bills array variable
     *
     * @return array
     */
    function getBills(): array
    {
        return $this->bills;
    }

    /**
     * Calculates the plateform status from its bills
     *
     * @return integer
     */
    function status(): int
    {
        $status = ['READY'=>0, 'ERROR'=>0, 'SENT'=>0];
        foreach($this->bills as $bill) {
                $status[$bill[3]] = 1;
        }
        return $status['READY'] + 2*$status['ERROR'] + 4*$status['SENT'];
    }
    
    /**
     * Returns a string with number of SENT/ERROR/READY bills
     *
     * @return string
     */
    function state(): string
    {
        $state = ['READY'=>0, 'ERROR'=>0, 'SENT'=>0];
        foreach($this->bills as $bill) {
                $state[$bill[3]] += 1;
        }
        return "SENT = ".$state['SENT'].", ERROR = ".$state['ERROR'].", READY = ".$state['READY'];
    }

    /**
     * Saves an array content to the csv file determined by its location (remove old content)
     *
     * @param string $dir directory where to save the csv file
     * @param array $content content to be saved
     * @return void
     */
    function save(string $dir, array $content): void 
    {
        $this->bills = $content;
        $data = [$this->title];
        foreach($this->bills as $line) {
            $data[] = $line;
        }
        self::write($dir."/".self::NAME, $data);
    }

    /**
     * Determines button color class depending on the billing status and on the locked run status
     *
     * @param integer $status billing status
     * @param string $lock locked run status
     * @return string
     */
    static function color(int $status, string $lock): string 
    {
        switch($status) {
            case 0:
                return $lock == "invalidate" ? "but-grey": "but-white";
            case 1:
                return $lock == "invalidate" ? "but-grey": "but-white";
            case 2:
                return $lock == "invalidate" ? "but-grey": "but-red";
            case 3:
                return $lock == "invalidate" ? "but-grey": "but-red";
            case 4:
                return "but-green";
            case 5:
                return "but-blue";
            case 6:
                return "but-orange";
            case 7:
                return "but-orange";
            default:
                return "";
        }
    }

}
