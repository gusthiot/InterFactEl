<?php

class State
{
    private string $last;
    private string $last_y;
    private string $last_m;
    private string $last_v;
    private string $last_r;

    private string $current;

    function __construct() 
    {
        $this->last = "";
        $this->last_y = "";
        $this->last_m = "";
        $this->last_v = "";
        $this->last_r = "";

        $this->current = "";
    }

    function getLast(): string
    {
        return $this->last;
    }

    function getLastMonth(): string
    {
        return $this->last_m;
    }

    function getLastYear(): string
    {
        return $this->last_y;
    }

    function getLastVersion(): string
    {
        return $this->last_v;
    }

    function getLastRun(): string
    {
        return $this->last_r;
    }

    function getCurrent(): string
    {
        return $this->current;
    }

    function getNextYear(): string
    {
        return $this->last_m == "12" ? self::addToString($this->last_y, 1) : $this->last_y; 
    }

    function getNextMonth(): string
    {
        return $this->last_m == "12" ? "01" : self::addToMonth($this->last_m, 1); 
    }

    static function getPreviousYear(string $year, string $month): string
    {
        return $month == "01" ? self::addToString($year, -1) : $year;
    }

    static function getPreviousMonth(string $year, string $month): string
    {
        return $month == "01" ? "12" : self::addToMonth($month, -1);
    }

    static function addToString(string $txt, int $num): string 
    {
        return strval(intval($txt) + $num);
    }

    static function addToMonth(string $month, int $num): string 
    {
        $m = intval($month) + $num;
        return $m < 10 ? "0".strval($m) : strval($m);
    }

    static function removeRun(string $path, string $todel): void
    {
        if(file_exists($path)) {
            foreach(self::scanDesc($path) as $version) {
                $dir = $path."/".$version."/".$todel;
                if (file_exists($dir) && is_dir($dir)) {
                    exec(sprintf("rm -rf %s", escapeshellarg($dir)));
                    rmdir($path."/".$version);
                    break;
                }
            }
            rmdir($path);
        }
    }

    static function delDir(string $dir): void 
    {
        foreach(array_diff(scandir($dir), ['.', '..']) as $file) {
            unlink($dir."/".$file);
        }
        rmdir($dir);
    }

    static function scanDesc(string $dir): array 
    {
        $files = array_diff(scandir($dir, SCANDIR_SORT_DESCENDING), ['.', '..']);
        $res = [];
        foreach($files as $file) {
            if(is_dir($dir."/".$file)) {
                $res[] = $file;
            }
        }
        return $res;
    }

    static function isNext($month, $year, $m, $y): bool
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

    static function isLater($month, $year, $m, $y): bool
    {
        if($year == $y) {
            return (intval($m) > (intval($month)));
        }
        else {
            if($y < $year) {
                return false;
            }
            else {
                return true;
            }
        }
    }

    static function isSame($month, $year, $m, $y): bool
    {
        if(intval($month) == intval($m) && $year == $y) {
            return true;
        }
        return false;
    }

    static function isNextOrSame($month, $year, $m, $y): bool
    {
        if(intval($month) == intval($m) && $year == $y) {
            return true;
        }
        else {
            return self::isNext($month, $year, $m, $y);
        }
    }

    function lastState(string $pathPlate, Lock $lock): void         
    {
        foreach(self::scanDesc($pathPlate) as $year) {
            foreach(self::scanDesc($pathPlate."/".$year) as $month) {
                foreach(self::scanDesc($pathPlate."/".$year."/".$month) as $version) {
                    if (file_exists($pathPlate."/".$year."/".$month."/".$version."/lockv.csv")) {
                        $this->last_y = $year;
                        $this->last_m = $month;
                        $this->last_v = $version;
                        $this->last_r = $lock->load($pathPlate."/".$year."/".$month."/".$version, "version");
                        $this->last = "(".$month." ".$year.", ".$version.")";
                        return;
                    }
                }
            }
        }
        return;
    } 

    function currentState(string $pathPlate): void         
    {
        foreach(self::scanDesc($pathPlate) as $year) {
            foreach(self::scanDesc($pathPlate."/".$year) as $month) {
                foreach(self::scanDesc($pathPlate."/".$year."/".$month) as $version) {
                    foreach(self::scanDesc($pathPlate."/".$year."/".$month."/".$version) as $run) {
                        if (!file_exists($pathPlate."/".$year."/".$month."/".$version."/".$run."/lock.csv")) {
                            $this->current = "(".$month." ".$year.", ".$version.")";
                            return;
                        }
                    }
                }
            }
        }
        return;
    }
}
