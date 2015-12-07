<?php
namespace Phasty\Server\Http {
    class Response extends Message {
        protected static $statuses = [
            100 => 'Continue',
            101 => 'Switching Protocols',
            102 => 'Processing',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            207 => 'Multi-Status',
            208 => 'Already Reported',
            226 => 'IM Used',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            307 => 'Temporary Redirect',
            308 => 'Permanent Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            422 => 'Unprocessable Entity',
            423 => 'Locked',
            424 => 'Failed Dependency',
            425 => 'Reserved for WebDAV advanced collections expired proposal',
            426 => 'Upgrade required',
            428 => 'Precondition Required',
            429 => 'Too Many Requests',
            431 => 'Request Header Fields Too Large',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            506 => 'Variant Also Negotiates (Experimental)',
            507 => 'Insufficient Storage',
            508 => 'Loop Detected',
            510 => 'Not Extended',
            511 => 'Network Authentication Required',
        ];

        public function setCode($code, $status = null) {
            $code = intval($code);
            $this->firstLine = [
                "version" => "1.1",
                "code" => $code,
                "status" => isset($status) ? $status : self::$statuses[ $code ]
            ];
            return $this;
        }

        public function getCode() {
            return $this->firstLine[ "code" ];
        }

        protected function getFirstLine() {
            if (!$this->firstLine) {
                return null;
            }
            return "HTTP/" . $this->firstLine[ "version" ] . " " . $this->firstLine[ "code" ] . " " . $this->firstLine[ "status" ];
        }

        static protected function parseFirstLine($firstLine) {
            $matched = preg_match(
                "#HTTP/(?<version>[01]\.[019])\s+(?<code>\d{3})\s+(?<message>\w+)#",
                $firstLine,
                $matches
            );
            if (!$matched) {
                return null;
            }
            unset($matches[0], $matches[1], $matches[2], $matches[3]);
            return $matches;
        }
    }
}
