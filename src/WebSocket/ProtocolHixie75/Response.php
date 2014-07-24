<?php
namespace Phasty\Server\WebSocket\ProtocolHixie75 {
    class Response extends \Phasty\Server\Http\Response {
        public function __toString() {
            return ord(0) . $this->getBody() . ord(255);
        }
    }
}
