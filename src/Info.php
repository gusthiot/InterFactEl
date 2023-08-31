<?php

require_once("Csv.php");

class Info extends Csv 
{
    const NAME = "/info.csv";

    function load(string $dir): array 
    {
        $infos = [];
        $lines = $this->extract($dir.self::NAME);
        foreach($lines as $line) {
            $tab = explode(";", $line);
            $infos[$tab[0]] = $tab;
        }
        return $infos;
    }

    function save(string $dir, array $content): void 
    {
        $data = [];
        foreach($content as $line) {
            $data[] = $line;
        }
        $this->write($dir.self::NAME, $data);
    }
}
?>
