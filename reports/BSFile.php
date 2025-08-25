<?php

class BSFile
{
    protected $structure;
    protected $subDir;

    function __construct(string $url, string $subDir) 
    {
        $this->structure = self::getJsonStructure($url);
        $this->subDir = $subDir;
    }

    function getPrefix($factel, $fileKey)
    {
        return $this->structure[$factel][$fileKey]['prefix'];
    }

    function getColumns($factel, $fileKey)
    {
        return $this->structure[$factel][$fileKey]['columns'];
    }

    function getCsvUrl($dirRun, $factel, $fileKey)
    {
        return $dirRun."/".$this->subDir."/".$this->getPrefix($factel, $fileKey).".csv";
    }

    function findCsvUrl($dirRun, $factel, string $fileKey): string
    {
        $files = scandir($dirRun."/".$this->subDir."/");
        $prefix = $this->getPrefix($factel, $fileKey);
        foreach ($files as $file) {
            if(str_starts_with($file, $prefix) &&( str_contains($file, $prefix."_") || str_contains($file, $prefix."."))) {
                return $dirRun."/".$this->subDir."/".$file;
            }
        }
        return "";
    }
    /**
     * extracts content from Json structure file
     *
     * @param string $url Json file relative url
     * @return array content or empty array
     */
    static function getJsonStructure(string $url): array 
    {
        $structure = [];
        if ((file_exists($url)) && (($open = fopen($url, "r")) !== false)) {
            $structure = json_decode(fread($open, filesize($url)), true);
            fclose($open);
        }
        return $structure;
    }
    
}
