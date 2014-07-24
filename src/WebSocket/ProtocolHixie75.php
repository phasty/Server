<?php
namespace Phasty\Server\WebSocket {
    use \Phasty\Server\WebSocket\ProtocolHixie75\Response;
    use \Phasty\Server\WebSocket\ProtocolHixie75\Request;
    use \Phasty\Server\Message;
    class ProtocolHixie75 extends \Phasty\Server\Http\Protocol1p1 {
        // Is protocol switched
        protected $upgraded = false;

        public static function match($request) {
            return
                mb_strtolower($request->getHeader("WebSocket-Protocol")[0]) == "sample" &&
                $request->hasHeader("Origin");
        }

        public function route($event, Message $request, Message $response = null) {
            if ($this->upgraded) {
                parent::route($event, $request, new Response());
            } else {
                $response = new \Phasty\Server\Http\Response();
                $response->setWriteStream($request->getReadStream());
                $origin = $request->getHeader("Origin")[ 0 ];
                $location = "ws://" . $request->getHeader("Host")[ 0 ] . $request->getPath();
                $response
                    ->setCode(101, "Web Socket Protocol Handshake")
                    ->setHeader("Upgrade", "WebSocket")
                    ->setHeader("Connection", "Upgrade")
                    ->setHeader("WebSocket-Origin", $origin)
                    ->setHeader("WebSocket-Location", $location)
                    ->setHeader("WebSocket-Protocol", "sample")
                    ->on("sent", function () use ($request) {
                        $this->upgraded = true;
                        $request->trigger("dispatched", [
                            "nextProtocol" => $this
                        ]);
                    })->send();
            }
        }

        public function getRequestObject() {
            return new Request();
        }
    }
}
