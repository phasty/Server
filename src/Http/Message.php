<?php
namespace Phasty\Server\Http {
    abstract class Message extends \Phasty\Server\Message {
        protected $firstLine = [];

        protected $headers = [];

        protected $body = null;

        abstract protected function getFirstLine();

        /**
         * Create message from string
         *
         * @param string $string Message
         *
         * @return mixed static|null
         */
        static public function create($string) {
            $parsed = static::parseString($string);
            if (is_null($parsed)) {
                return null;
            }
            return new static($parsed[ "firstLine" ], $parsed[ "headers" ], $parsed[ "body" ]);

        }

        public function tryParse($string) {
            if (!$string = ltrim($string)) {
                return false;
            }
            $parsed = self::parseString($string);

            if (!$parsed) {
                return false;
            }

            $this->set($parsed["firstLine"], $parsed[ "headers"], $parsed[ "body" ]);

            return true;
        }

        protected function set($firstLine, $headers, $body) {
            $this->firstLine = $firstLine;
            $this->headers   = $headers;
            $this->body      = $body;
        }

        /**
         *
         */
        public function getHeader($name) {
            $name = mb_strtolower($name);
            if (!isset($this->headers[ $name ])) {
                return null;
            }
            return $this->headers[ $name ];
        }

        public function hasHeader($name) {
            return isset($this->headers[ strtolower($name) ]);
        }

        public function setHeader($name, $value) {
            $this->headers[ strtolower($name) ] = (array)$value;
            return $this;
        }

        public function removeHeader($name) {
            $name = strtolower($name);
            if (isset($this->headers[ $name ])) {
                return false;
            }
            unset($this->headers[ $name ]);
            return true;
        }
        public function getHeaders() {
            return $this->headers;
        }

        public function setHeaders(array $headers) {
            $this->headers = [];
            $this->addHeaders($headers);
            return $this;
        }

        public function addHeaders(array $headers) {
            if (empty($headers)) {
                return;
            }
            $this->headers = array_merge_recursive($this->headers, $headers);
            return $this;
        }

        public function clearHeaders() {
            $this->headers = [];
            return $this;
        }

        public function getBody() {
            return $this->body;
        }

        public function setBody($body) {
            $this->body = $body;
            if ($body) {
                $this->setHeader("Content-Length", strlen($this->body));
            } else {
                $this->removeHeader("Content-Length");
            }
            return $this;
        }

        public function __toString() {
            // $this->body = "Wow, this works";

            $DLM = "\r\n";
            $firstLine = $this->getFirstLine();
            if (!$firstLine) {
                return null;
            }
            $flatHeaders = "";
            if (!empty($this->headers)) {
                $headersNames  = array_keys($this->headers);
                $headersValues = array_values($this->headers);

                foreach ($headersNames as $index => $name) {
                    foreach ($headersValues[ $index ] as $value) {
                        $flatHeaders .= "$name: $value$DLM";
                    }
                }
            }
            return
                $firstLine . $DLM .
                "Server: Phasty/1.0" . $DLM .
                "X-Random: " . rand() . $DLM .
                // "Content-Length: " . mb_strlen($this->body) . $DLM .
                $flatHeaders . $DLM .
                $this->body . "\n";
        }

        // TODO: неправильно отрабатывает при некорректном запросе
        static public function parseString($string) {
            $result = [
                "headers" => [],
                "body" => null
            ];
            $message = preg_split("#\r?\n#mu", $string, 2);
            if (!isset($message[1])) {
                return false;
            }
            $dlmPos = mb_strlen($message[ 0 ]);
            $DLM = $string{ $dlmPos } == "\r" ? "\r\n" : "\n";

            $result[ "firstLine" ] = static::parseFirstLine(rtrim($message[ 0 ]));

            if (empty($result[ "firstLine" ])) {
                throw new \Exception(400);
            }
            //like: GET / HTTP/1.1
            if (empty($message[ 1 ]) || strpos($message[ 1 ], $DLM . $DLM) === false) {
                return null;
            }
            // like: GET / HTTP/1.1\r\n\r\n
            if ($message[ 1 ] === $DLM . $DLM) {
                return $result;
            }
            /**
             *  headers like:
             *      >name: value
             *      >name1: value1
             *      >
             *      >BODY
             */
            $message = explode($DLM . $DLM, $message[ 1 ], 2);
            $rawHeaders = $message[ 0 ];
            $headers = [];
            $header = strtok($rawHeaders, $DLM);
            while ($header !== false) {
                $header = explode(":", $header, 2);
                if (!isset($header[ 1 ])) {
                    return null;
                }
                //TODO: ну его нахуй, эти заголовки никому не нужны,
                //      а я выебываюсь безусловно и использую дорогую функцию,
                //      надо сделать генерацию только при запросе
                $headerName = mb_strtolower($header[ 0 ]);
                if (!isset($result[ "headers" ][ $headerName ])) {
                    $result[ "headers" ][ $headerName ] = [];
                }
                $result[ "headers" ][ $headerName ] []= ltrim($header[ 1 ]);
                $header = strtok($DLM);
            }
            if (isset($result[ "headers" ][ "content-length" ])) {
                if (strlen($message [ 1 ]) < $result[ "headers" ][ "content-length" ][ 0 ]) {
                    return null;
                }
            }
            $result[ "body" ] = $message[ 1 ];
            return $result;
        }

        protected function parseFirstLine($firstLine) {
            throw new \BadMethodCallException("Method " . get_called_class()  . "::" . __FUNCTION__ . " not implemented");
        }
    }
}
