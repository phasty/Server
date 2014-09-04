<?php
namespace Phasty\Server\Http {
    class Request extends Message {
        static protected function parseFirstLine($firstLine) {
            $matched = preg_match(
                "#(?<method>GET|HEAD|POST|PUT|DELETE|TRACE|CONNECT)\s(?<path>\S+)\sHTTP/(?<version>1\.[01])#",
                $firstLine,
                $matches
            );
            if (!$matched) {
                return null;
            }
            unset($matches[0], $matches[1], $matches[2], $matches[3]);
            return $matches;
        }

        protected function getFirstLine() {
            $method = $this->getMethod();
            if (is_null($method)) {
                return null;
            }
            $path = $this->getPath();
            if (is_null($path)) {
                return null;
            }
            $version = $this->getVersion();
            if (is_null($version)) {
                return null;
            }
            return "$method $path HTTP/$version";
        }

        public function getMethod() {
            return isset($this->firstLine[ "method" ]) ? $this->firstLine[ "method" ] : null;
        }

        public function getPath() {
            return isset($this->firstLine[ "path" ]) ? $this->firstLine[ "path" ] : null;
        }

        public function getVersion() {
            return isset($this->firstLine[ "version" ]) ? $this->firstLine[ "version" ] : null;
        }
    }
}
