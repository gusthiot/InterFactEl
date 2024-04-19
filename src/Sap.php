<?php

require_once("Csv.php");

class Sap extends Csv 
{
    const NAME = "/sap.csv";
    private array $bills;
    private array $title;

    function load(string $dir): array 
    {
        $this->bills = [];
        $lines = $this->extract($dir.self::NAME);
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
        return $this->bills;
    }

    function status(): int
    {
        $status = ['READY'=>0, 'ERROR'=>0, 'SENT'=>0];
        foreach($this->bills as $bill) {
                $status[$bill[3]] = 1;
        }
        return $status['READY'] + 2*$status['ERROR'] + 4*$status['SENT'];
    }
    
    function state(): string
    {
        $state = ['READY'=>0, 'ERROR'=>0, 'SENT'=>0];
        foreach($this->bills as $bill) {
                $state[$bill[3]] += 1;
        }
        return "SENT = ".$state['SENT'].", ERROR = ".$state['ERROR'].", READY = ".$state['READY'];
    }

    function save(string $dir, array $content): void 
    {
        $this->bills = $content;
        $data = [$this->title];
        foreach($this->bills as $line) {
            $data[] = $line;
        }
        $this->write($dir.self::NAME, $data);
    }

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
?>
