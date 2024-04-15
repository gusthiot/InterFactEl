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
        return (string)((int)($txt) + $num);
    }

    static function addToMonth(string $month, int $num): string 
    {
        $m = (int)($month) + $num;
        return $m < 10 ? "0".(string)($m) : (string)($m);
    }

    static function removeRun(string $path, string $todel): void
    {
        if(file_exists($path)) {
            foreach(self::scanDescSan($path) as $version) {
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
        foreach(self::scanDescSan($dir) as $file) {
            unlink($dir."/".$file);
        }
        rmdir($dir);
    }

    const ESCAPED = ['..', '.','logfile.log', 'lockm.csv', 'lockv.csv', 'parametres.zip', 'label.txt'];

    static function scanDescSan(string $dir): array 
    {
        return array_diff(scandir($dir, SCANDIR_SORT_DESCENDING), self::ESCAPED);
    }

    static function isNext($month, $year, $m, $y): bool
    {
        if($year == $y) {
            return ((int)$m == ((int)($month)+1));
        }
        else {
            if($y < $year) {
                return false;
            }
            else {
                return ((int)$m == 1 && (int)$month == 12);
            }
        }
    }

    static function isSame($month, $year, $m, $y): bool
    {
        if((int)$month == (int)$m && $year == $y) {
            return true;
        }
        return false;
    }

    static function isNextOrSame($month, $year, $m, $y) //: bool
    {
        if((int)$month == (int)$m && $year == $y) {
            return true;
        }
        else {
            return self::isNext($month, $year, $m, $y);
        }
    }

    function lastState(string $pathPlate, Lock $lock): void         
    {
        foreach(self::scanDescSan($pathPlate) as $year) {
            foreach(self::scanDescSan($pathPlate."/".$year) as $month) {
                foreach(self::scanDescSan($pathPlate."/".$year."/".$month) as $version) {
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
        foreach(self::scanDescSan($pathPlate) as $year) {
            foreach(self::scanDescSan($pathPlate."/".$year) as $month) {
                foreach(self::scanDescSan($pathPlate."/".$year."/".$month) as $version) {
                    foreach(self::scanDescSan($pathPlate."/".$year."/".$month."/".$version) as $run) {
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