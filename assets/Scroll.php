<?php

require_once("Csv.php");

class Scroll extends Csv
{

    const NAME = "scroll.csv";

    static function load(): array
    {
        $messages = [];
        $messages = self::extract("./".self::NAME);
        return $messages;
    }
/*
    static function save(string $dir, array $content): void
    {
        $data = [];
        foreach($content as $line) {
            $data[] = $line;
        }
        self::write($dir."/".self::NAME, $data);
    }
        */
}
