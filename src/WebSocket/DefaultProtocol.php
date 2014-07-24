<?php
namespace Phasty\Server\WebSocket {
    use \Phasty\Server\Message;
    class DefaultProtocol extends \Phasty\Server\Http\Protocol1p1 {
        public function route($event, Message $request, Message $response = null) {
            if ($request->hasHeader("Upgrade") &&
                $request->hasHeader("Connection") &&

                mb_strtolower($request->getHeader("Upgrade")[ 0 ]) == "websocket" &&
                mb_strtolower($request->getHeader("Connection")[ 0 ]) == "upgrade"
            ) {
                foreach ([
                    "ProtocolRfc6455",
                    "ProtocolHixie75",
                ] as $protocol) {
                    $protocol = "\\Phasty\\Server\\WebSocket\\$protocol";
                    if (!$protocol::match($request)) {
                        continue;
                    }
                    $protocol = new $protocol();
                    $protocol->route($event, $request);
                    return;
                }
            }
            parent::route($event, $request);
        }
    }
}
