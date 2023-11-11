<?php

// Copyright (C) 2023 I2G <https://i2g.gusthiot.ch>
// Copyright (C) 2003-2021 EPFL <https://www.epfl.ch>
// Copyright (C) 2021 Liip SA <https://www.liip.ch>
// Copyright (C) 2021 Doran Kayoumi
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


require_once("TequilaException.php");

class TequilaClient {
    const VERSION = "4.2.0";

    const TEQUILA_BIN    = "/cgi-bin/tequila";
    const COOKIE_NAME    = "TequilaPHP";
    const COOKIE_LIFE    = 0;
    const BODY_GLUE      = "+";

    const SESSION_KEY      = "Tequila-Session-Key";
    const SESSION_USER      = "user";
    const SESSION_CREATION = "Tequila-Session-Creation";

    const LANGUAGE_FRENCH  = 0;
    const LANGUAGE_ENGLISH = 1;
    const LANGUAGE_GERMAN  = 2;

    const SERVER_ENDPOINTS = ["createrequest", "fetchattributes", "logout"];

    private $serverURL;
    private $timeout;
    private $logoutURL;
    private $language;
    private $applicationURL;
    private $applicationName;

    private $attributes = [];
    private $key;

    private $stack;

    /**
     * @brief Class constructor
     *
     * @codeCoverageIgnore
     *
     * @param $server - Tequila server URL
     * @param $timeout - Session timeout
     * @param $applicationName - Name of the application using the client
     * @param $applicationURL - (optional) URL the application using the client
     * @param $language - (Default: English) Language of the application
     */
    function __construct(
        string $server,
        int $timeout,
        string $applicationName,
        string $applicationURL = "",
        int $language = self::LANGUAGE_ENGLISH,
        bool $debug = false
    ) {
        $this->serverURL = $server . self::TEQUILA_BIN;
        $this->timeout = $timeout;
        $this->language = $language;
        $this->applicationURL = $applicationURL;
        $this->applicationName = $applicationName;
        $this->debug = (bool) $debug;

        // if no application URL was specified, we try to generate it
        if (empty($this->applicationURL)) {
            $this->applicationURL = $this->serverApplicationURL();
        }
    }

    /**
     * @brief User authentication to server
     *
     * @param $wantedAttributes - (optional) List of attributes about the user that the server will return
     * @param $filters - (optional) Filters that will be applied to the user attributes
     * @param $authorised - (optional) Tequila server restrictions to lift
     * @param $allowedRequestHosts - (optional) List of hosts (clustering) that are allowed to communicate with Tequila for the service
     * @param $authstrength - (optional) Allows to activate Tequila 2FA. Value must be 3 for secure code
     */
    public function authenticate(
        array $wantedAttributes = [],
        string $filters = "",
        string $authorised = "",
        array $allowedRequestHosts = [],
        string $authstrength = ""
    ) {

        $this->log(__FUNCTION__ . "(...)");

        if ($this->preExistingSession()) {
            return;
        }

        // fetchAttributes needs valid auth_check param and a valid session creation.
        if (!empty($_COOKIE[self::COOKIE_NAME]) && isset($_GET["auth_check"])) {
            $attributes = $this->fetchAttributes($_COOKIE[self::COOKIE_NAME], $_GET["auth_check"], $allowedRequestHosts);

            if (
                !empty($attributes) &&
                isset($attributes['user']) &&
                isset($attributes['key'])
            ) {
                // Only create a valid session and keep the key if the mandatory attributes are present.
                $this->key = $_COOKIE[self::COOKIE_NAME];
                $this->createSession($attributes);
                return;
            }
        }

        $this->key = $this->createRequest($wantedAttributes, $filters, $authorised, $authstrength);
        setcookie(self::COOKIE_NAME,
            $this->key,
            self::COOKIE_LIFE,
            "",
            "",
            (strpos($this->applicationURL, "https://") === 0),
            true
        );

        header("Location: {$this->serverURL}/requestauth?requestkey={$this->key}");
        exit;
    }

    /**
     * @brief Logout from Tequila server
     *
     * @param $redirectUrl - (optional) URL to redirect to after logout
     */
    public function logout(string $redirectUrl = "") {
        $this->log(__FUNCTION__ . "(...)");

        // Delete cookie by setting expiration time in the past with root path
        setcookie(self::COOKIE_NAME, "", time() - 3600);

        unset($_SESSION[self::SESSION_KEY]);
        unset($_SESSION[self::SESSION_USER]);
        unset($_SESSION[self::SESSION_CREATION]);

        $this->contactServer("logout");

        $redirectUrl = empty($redirectUrl) ? $this->applicationURL : urlencode($redirectUrl);
        header("Location: {$this->serverURL}/logout?urlaccess={$redirectUrl}");
        unset($this->key);
    }

    /**
     * @brief Sends an authentication request
     *
     * @param $wantedAttributes - (optional) List of attributes about the user that the server will return
     * @param $filters - (optional) Filters that will be applied to the user attributes
     * @param $authorised - (optional) Tequila server restrictions to lift
     *
     * @return Key returned by the Tequila server
     */
    private function createRequest(
        array $wantedAttributes = [],
        string $filters = "",
        string $authorised = "",
        string $authstrength = ""
    ) : string {

        $body = [];

        $body["urlaccess"]       = $this->applicationURL;
        $body["dontappendkey"]   = "1";
        $body["language"]        = $this->language;
        $body["service"]         = $this->applicationName;
        $body["request"]         = implode(self::BODY_GLUE, $wantedAttributes);
        $body["allows"]          = $filters;
        $body["require"]         = $authorised;
        $body["mode_auth_check"] = "1";
        $body["authstrength"]    = $authstrength;

        $res = $this->contactServer('createrequest', $body);

        preg_match('/^(?P<key>\w+)=(?P<value>\w+)\s*$/', $res, $matches);
        if (!empty($matches["key"]) && $matches["key"] == "key") {
            $this->log(__FUNCTION__ . "(...): ".$matches["value"]);
            return $matches["value"];
        }
        $this->log(__FUNCTION__ . "(...): got no requestkey");
        throw new TequilaException("No requestkey obtained from createRequest");
    }

    /**
     * @brief Retrieve the attributes of an authenticated user
     *        (i.e. the ones requested when establishing an authentication)
     *
     * @param $key - the request key
     *
     * @return Array containing all the user attributes
     */
    private function fetchAttributes(string $key, string $auth_check, array $allowedRequestHosts = []) : array {
        $this->log(__FUNCTION__ . "(...)");

        $body = [];
        $body["key"] = $key;
        $body["auth_check"] = $auth_check;

        $body["allowedrequesthosts"] = implode("|", $allowedRequestHosts);
        try {
            $res = $this->contactServer('fetchattributes', $body);
        } catch (TequilaException $e) {
            // fetchAttributes failed, return empty
            return [];
        }

        $result = [];
        $attributes = explode("\n", $res);

        foreach ($attributes as $attribute) {
            $attribute = trim($attribute);

            if (!$attribute) {
                continue;
            }

            list($key, $val) = explode("=", $attribute, 2);
            $result[$key] = $val;
        }
        return $result;
    }

    /**
     * @brief Sends a POST request to one of the servers endpoints
     *
     * @param $endpoint - the server endpoint to contact
     * @param $fields - (optional) the fields to add to the requests body
     *
     * @throws TequilaException if the server returns a code other than 200, that no connection could be established
     *                          or that we're trying to acces an unknow endpoint
     *
     * @return Body of the server response
     */
    private function contactServer($endpoint, $fields = []) : string {
        // check if it's a valid endpoint
        if (!in_array($endpoint, self::SERVER_ENDPOINTS)) {
            throw new TequilaException("Unknown endpoint {$endpoint}");
        }
        $this->log(__FUNCTION__ ."(".$endpoint.", ".preg_replace("#\r|\n#", "", var_export($fields, true)).")");

        return $this->curlRequest($this->serverURL."/".$endpoint, $fields);



        try {
            $client = new GuzzleHttp\Client([
                'base_uri' => $this->serverURL . "/",
                "handler" => $this->stack
            ]);

            /**
             * Note: First things first, sorry for the horrible code that follows.
             * So, the Tequila server doesn't understand/use normal POST requests and
             * only works cleartext body.
             */
            $reqBody = [];
            if (is_array($fields) && count($fields)) {
                foreach ($fields as $key => $val) {
                    $reqBody[] = "{$key}={$val}";
                }
            }
            $response = $client->request("POST", $endpoint, [
                "headers" => ["User-Agent" => "Tequila-PHP-Client/".self::VERSION],
                "body" => implode("\n", $reqBody) . "\n",
            ]);

            return $response->getBody();

        } catch (GuzzleHttp\Exception\RequestException $e) {
            if (!$e->hasResponse()) {
                throw new TequilaException("No response from server : {$e->getMessage()}", 1, $e);
            }

            $response = $e->getResponse();

            throw new TequilaException("Unexpected return from server : [{$response->getStatusCode()}] {$response->getReasonPhrase()}", 1, $e);
        } catch (GuzzleHttp\Exception\ConnectException $e) {
            throw new TequilaException("Connection to {$this->serverURL} server failed", 0, $e);
        }
    }

    private function curlRequest($url, $fields = array()) {

        $ch = curl_init ();
        
        curl_setopt ($ch, CURLOPT_HEADER,         false);
        curl_setopt ($ch, CURLOPT_POST,           true);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, false);

        curl_setopt ($ch, CURLOPT_URL, $url);

        /* If fields where passed as parameters, */
        if (is_array ($fields) && count ($fields)) {
            $pFields = array ();
            foreach ($fields as $key => $val) {
                $pFields[] = sprintf('%s=%s', $key, $val);
            }
            $query = implode("\n", $pFields) . "\n";
            curl_setopt ($ch, CURLOPT_POSTFIELDS, $query);
        }
        
        $response = curl_exec($ch);
        if($response === false) {    
            throw new TequilaException("Unexpected return from server : [".curl_getinfo ($ch, CURLINFO_HTTP_CODE)."] ".curl_error($ch), 1, $e);
        }
        else {
            return $response;
        }
    }




    /**
     * @brief Check if a session was previously established
     *
     * @return True if a session was previously established
     * @return False if no session was previously established
     */
    private function preExistingSession() : bool {
        if (empty($_SESSION)) {
            return false;
        }

        // check if the session hasn't expired.
        if (
            !array_key_exists(self::SESSION_CREATION, $_SESSION) or
            (time() - $_SESSION[self::SESSION_CREATION]) > $this->timeout
        ) {
            return false;
        }

        if (!array_key_exists(self::SESSION_KEY, $_SESSION)) {
            return false;
        }

        $this->key = $_SESSION[self::SESSION_KEY];

        return true;
    }

    /**
     * @brief Establish a new session
     *
     * @param $attributes - the user attributes returned by the server
     */
    private function createSession(array $attributes) {
        $_SESSION[self::SESSION_CREATION] = time();

        foreach ($attributes as $key => $val) {
            $this->attributes[$key] = $val;
        }
        if (array_key_exists("key", $attributes)) {
            $_SESSION[self::SESSION_KEY] = $attributes["key"];
        }
        if (array_key_exists("user", $attributes)) {
            $_SESSION[self::SESSION_USER] = $attributes["user"];
        }
    }

    /**
     * @brief Determine the applicationURL from $_SERVER
     */
    private function serverApplicationURL(array $server = []) : string {
        // Primarily for tests
        // @codeCoverageIgnoreStart
        if (empty($server)) {
            global $_SERVER;
            $server = $_SERVER;
        }
        // @codeCoverageIgnoreEnd
        $protocol = !empty($server['HTTPS']) && $server['HTTPS'] == 'on' ? "https://" : "http://";

        $port = !empty($server["SERVER_PORT"]) ? $server["SERVER_PORT"] : ($protocol == "https://" ? 443 : 80);
        $dotport = ":".$port;

        if (
            ($protocol == "https://" && $port == 443) ||
            ($protocol == "http://" && $port == 80)
        ) {
            $dotport = "";
        }

        $php_self = !empty($server["PHP_SELF"]) ? $server["PHP_SELF"] : "/";

        $applicationURL = $protocol . $server['SERVER_NAME'] . $dotport . $php_self;

        if (!empty($server['PATH_INFO'])) {
            $applicationURL .= $server['PATH_INFO'];
        }
        if (!empty($server['QUERY_STRING'])) {
            $applicationURL .= "?" . $server['QUERY_STRING'];
        }
        $this->log(__FUNCTION__ ."(): {$applicationURL}");
        return $applicationURL;
    }

    /**
     * Getter for key
     *
     * @codeCoverageIgnore
     */
    public function getKey() : string {
        return $this->key;
    }

    /**
     * Getter for attributes
     *
     * @codeCoverageIgnore
     */
    public function getAttributes() : array {
        return $this->attributes;
    }


    /**
     * If debug mode enabled
     *
     * @return bool
     */
    public function is_debugging() {
        return $this->debug;
    }

    /**
     * A debug function, dumps to the php log
     *
     * @codeCoverageIgnore
     * @param string $msg Log message
     */
    private function log($msg) {
        if ($this->is_debugging()) {
            error_log('tequila-php-client: ' . $msg);
        }
    }
}

