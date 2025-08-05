<?php

/**
 * State class allows to manage the state of the plateform, and contains some useful functions for dates and directories
 */
class State
{
    /**
     * Last facturation done displayed title
     *
     * @var string
     */
    private string $last;

    /**
     * Last facturation done year
     *
     * @var string
     */
    private string $last_y;

    /**
     * Last facturation done month
     *
     * @var string
     */
    private string $last_m;

    /**
     * Last facturation done version
     *
     * @var string
     */
    private string $last_v;

    /**
     * Last facturation done run
     *
     * @var string
     */
    private string $last_r;

    /**
     * Last facturation done complete path
     *
     * @var string
     */
    private string $last_path;

    /**
     * Class constructor
     *
     * @param string $pathPlate path to plateform directory
     */
    function __construct(string $pathPlate) 
    {
        $this->lastState($pathPlate);
    }

    /**
     * Determines which was the last facturation, for a plateform, if one exists
     *
     * @param string $pathPlate path to plateform directory
     * @return void
     */
    private function lastState(string $pathPlate): void         
    {
        $this->last = "";
        $this->last_y = "";
        $this->last_m = "";
        $this->last_v = "";
        $this->last_r = "";
        if(file_exists($pathPlate)) {
            foreach(globReverse($pathPlate) as $dirYear) {
                $year = basename($dirYear);
                foreach(globReverse($dirYear) as $dirMonth) {
                    $month = basename($dirMonth);
                    foreach(globReverse($dirMonth) as $dirVersion) {
                        $version = basename($dirVersion);
                        if (file_exists($dirVersion."/".Lock::FILES['version'])) {
                            $this->last_y = $year;
                            $this->last_m = $month;
                            $this->last_v = $version;
                            $this->last_r = Lock::load($dirVersion, "version");
                            $this->last = "(".$month." ".$year.", ".$version.")";
                            $this->last_path = $pathPlate."/".$year."/".$month."/".$version."/".$this->last_r;
                            return;
                        }
                    }
                }
            }
        }
    } 

    /**
     * Determines which is the current facturation, for a plateform, if there is one
     *
     * @param string $pathPlate path to plateform directory
     * @return string current facturation as a string title
     */
    static function currentState(string $pathPlate): string         
    {
        if(file_exists($pathPlate)) {
            foreach(globReverse($pathPlate) as $dirYear) {
                $year = basename($dirYear);
                foreach(globReverse($dirYear) as $dirMonth) {
                    $month = basename($dirMonth);
                    foreach(globReverse($dirMonth) as $dirVersion) {
                        $version = basename($dirVersion);
                        foreach(globReverse($dirVersion) as $dirRun) {
                            if (!file_exists($dirRun."/".Lock::FILES['run'])) {
                                return "(".$month." ".$year.", ".$version.")";
                            }
                        }
                    }
                }
            }
        }
        return "";
    }
    
    /**
     * Getter for $last string variable
     *
     * @return string
     */
    function getLast(): string
    {
        return $this->last;
    }

    /**
     * Getter for $last_m string variable
     *
     * @return string
     */
    function getLastMonth(): string
    {
        return $this->last_m;
    }

    /**
     * Getter for $last_y string variable
     *
     * @return string
     */
    function getLastYear(): string
    {
        return $this->last_y;
    }

    /**
     * Getter for $last_v string variable
     *
     * @return string
     */
    function getLastVersion(): string
    {
        return $this->last_v;
    }

    /**
     * Getter for $last_r string variable
     *
     * @return string
     */
    function getLastRun(): string
    {
        return $this->last_r;
    }

    /**
     * Getter for $last_path string variable
     *
     * @return string
     */
    function getLastPath(): string
    {
        return $this->last_path;
    }

    /**
     * Returns the year of the month coming right after the last one
     *
     * @return string
     */
    function getNextYear(): string
    {
        return $this->last_m == "12" ? self::addToString($this->last_y, 1) : $this->last_y; 
    }

    /**
     * Returns the month of the month coming right after the last one
     *
     * @return string
     */
    function getNextMonth(): string
    {
        return $this->last_m == "12" ? "01" : self::addToMonth($this->last_m, 1); 
    }

    /**
     * Checks if a month follows, directly or not, another one
     *
     * @param string $month month to check
     * @param string $year year to check
     * @param string $m month as reference
     * @param string $y year as reference
     * @return boolean
     */
    function isLater(string $month, string $year): bool
    {
        if($this->last_y == $year) {
            return (intval($month) > (intval($this->last_m)));
        }
        else {
            if($year < $this->last_y) {
                return false;
            }
            else {
                return true;
            }
        }
    }

    /**
     * Checks if it's the same month
     *
     * @param string $month month to check
     * @param string $year year to check
     * @return boolean
     */
    function isSame(string $month, string $year): bool
    {
        if(intval($this->last_m) == intval($month) && $this->last_y == $year) {
            return true;
        }
        return false;
    }

    /**
     * Returns the year coming right before a given month
     *
     * @param string $year given year
     * @param string $month given month
     * @return string
     */
    static function getPreviousYear(string $year, string $month): string
    {
        return $month == "01" ? self::addToString($year, -1) : $year;
    }

    /**
     * Returns the month coming right before a given month
     *
     * @param string $year given year
     * @param string $month given month
     * @return string
     */
    static function getPreviousMonth(string $year, string $month): string
    {
        return $month == "01" ? "12" : self::addToMonth($month, -1);
    }

    /**
     * Adds an integer to a string formatted number
     *
     * @param string $txt string formatted number
     * @param integer $num integer to add
     * @return string
     */
    static function addToString(string $txt, int $num): string 
    {
        return strval(intval($txt) + $num);
    }

    /**
     * Adds an integer to a string formatted month
     *
     * @param string $month string formatted month
     * @param integer $num integer to add
     * @return string
     */
    static function addToMonth(string $month, int $num): string 
    {
        $m = intval($month) + $num;
        return $m < 10 ? "0".strval($m) : strval($m);
    }

    /**
     * Removes a directory and its content, recursively
     *
     * @param string $dir directory to remove
     * @return void
     */
    static function delDir(string $dir): bool 
    {
        if (file_exists($dir) && is_dir($dir)) {
            exec(sprintf("rm -rf %s", escapeshellarg($dir)));
            return true;
        }
        return false;
    }

    /**
     * Checks if a month directly follows another one
     *
     * @param string $month month to check
     * @param string $year year to check
     * @param string $m month as reference
     * @param string $y year as reference
     * @return boolean
     */
    static function isNext(string $month, string $year, string $m, string $y): bool
    {
        if($year == $y) {
            return (intval($m) == (intval($month)+1));
        }
        else {
            if($y < $year) {
                return false;
            }
            else {
                return (intval($m) == 1 && intval($month) == 12);
            }
        }
    }

    /**
     * Checks if it's the same month, or  if it directly follows the other one
     *
     * @param string $month month to check
     * @param string $year year to check
     * @param string $m month as reference
     * @param string $y year as reference
     * @return boolean
     */
    static function isNextOrSame(string $month, string $year, string $m, string $y): bool
    {
        if(intval($month) == intval($m) && $year == $y) {
            return true;
        }
        else {
            return self::isNext($month, $year, $m, $y);
        }
    }

    /**
     * Copies a directory recursively
     *
     * @param string $src source directory
     * @param string $dst destination directory (should not exist yet)
     * @return void
     */
    static function recurseCopy(string $src, string $dst): void
    {
        $dir = opendir($src);
        if (file_exists($dst) || mkdir($dst, 0755, true)) {
            while(false !== ( $file = readdir($dir)) ) {
                if (( $file != '.' ) && ( $file != '..' )) {
                    if ( is_dir($src . '/' . $file) ) {
                        self::recurseCopy($src . '/' . $file, $dst . '/' . $file);
                    } else {
                        copy($src . '/' . $file,$dst . '/' . $file);
                    }
                }
            }
        }
        closedir($dir);
    }
}
