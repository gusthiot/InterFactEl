<?php

/**
 * BSFile class allows to manipulates a json file from a run directory
 */
class BSFile
{
    /**
     * Content of the file
     *
     * @var array
     */
    protected array $structure;

    /**
     * Run sub-directory
     *
     * @var string
     */
    protected string $subDir;

    /**
     * Class constructor
     *
     * @param string $url Json file relative url
     * @param string $subDir run sub-directory
     */
    function __construct(string $url, string $subDir)
    {
        $this->structure = self::getJsonStructure($url);
        $this->subDir = $subDir;
    }

    /**
     * Returns file prefix
     *
     * @param string $factel processed facturation version
     * @param string $fileKey key for a given file
     * @return string
     */
    function getPrefix(string $factel, string $fileKey): string
    {
        return $this->structure[$factel][$fileKey]['prefix'];
    }

    /**
     * Returns array of file columns positions
     *
     * @param string $factel processed facturation version
     * @param string $fileKey key for a given file
     * @return array
     */
    function getColumns(string $factel, string $fileKey): array
    {
        return $this->structure[$factel][$fileKey]['columns'];
    }

    /**
     * Returns csv file url
     *
     * @param string $dirRun run directory
     * @param string $factel processed facturation version
     * @param string $fileKey key for a given file
     * @return string
     */
    function getCsvUrl(string $dirRun, string $factel, string $fileKey): string
    {
        return $dirRun."/".$this->subDir."/".$this->getPrefix($factel, $fileKey).".csv";
    }

    /**
     * Finds csv file url when the name is more complex
     *
     * @param string $dirRun run directory
     * @param string $factel processed facturation version
     * @param string $fileKey key for a given file
     * @return string
     */
    function findCsvUrl(string $dirRun, string $factel, string $fileKey): string
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
     * Extracts content from Json structure file
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
