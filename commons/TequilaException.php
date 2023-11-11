<?php

// Copyright (C) 2021 Doran Kayoumi
// Copyright (C) 2021 Liip SA <https://www.liip.ch>
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>

class TequilaException extends \Exception {

    const DEBUG = false;

    public function __construct(string $message = "", int $code = 0, Throwable $previous = null) {
        // @codeCoverageIgnoreStart
        if (self::DEBUG) {
            error_log("tequila-php-client Exception: ".$message);
        }
        // @codeCoverageIgnoreEnd
        parent::__construct($message, $code, $previous);
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
