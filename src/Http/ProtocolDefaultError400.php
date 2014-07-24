<?php
namespace Phasty\Server\Http {
    class ProtocolDefaultError400 extends \Phasty\Server\Protocol {
        public function care(\Phasty\Server\Message $request, \Phasty\Server\Message $response) {
            $response->setCode(400);
            $response->send();
        }

        public function match($request) {
            throw new \Phasty\Server\Protocol\Exception\InvalidMessage();
        }
    }
}
