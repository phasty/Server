<?php
namespace Phasty\Server\WebSocket\ProtocolHixie75 {
    class Request extends \Phasty\Server\Http\Request {
        public function tryParse($string) {
            if (!mb_strlen($string)) {
                return false;
            }
            if (ord($string{0}) !== 0) {
                throw new \Exception("Invalid characters sequence: " . ord($string{0}));
            }
            if (ord(substr($string, -1)) !== 255) {
                return false;
            }
            $this->set([], [], $string);

            return true;
        }
    }
}
