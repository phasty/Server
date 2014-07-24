<?php
namespace Phasty\Server\WebSocket {
    use \Phasty\Server\WebSocket\ProtocolRfc6455\Response;
    use \Phasty\Server\WebSocket\ProtocolRfc6455\Request;
    use \Phasty\Server\Message;
    class ProtocolRfc6455 extends Protocol {
        // Is protocol switched
        protected $upgraded = false;

        public static function match($request) {
            return
                $request->hasHeader("Origin") &&
                $request->hasHeader("Sec-WebSocket-Key") &&
                $request->hasHeader("Sec-WebSocket-Version");
        }

        public function dispatch(Message $request) {
            $request->on("read-complete", [ $this, "route" ]);

            $request->on("error", function ($event, $request) {
                $response = new Response();
                $response->setWriteStream($request->getReadStream());
                $response->setOpCode(Response::OPCODE_CTRL_CLOSE);
                $response->setBody("Protocol error");
                $response->on("sent", [ $request, "dispatched" ]);
                $response->send();
            });
        }

        protected function sendClose(Request $request) {
            $response = $this->getResponseObject();
            $response->setWriteStream($request->getReadStream());
            $response->setOpCode(Request::OPCODE_CTRL_CLOSE);
            $response->on("sent", [ $request, "ready" ]);
            $response->send();
        }

        protected function sendPong(Request $request) {
            $response = $this->getResponseObject();
            $response->setWriteStream($request->getReadStream());
            $response->setOpCode(Request::OPCODE_CTRL_PONG);
            $response->setPayload($request->getPayload());
            $response->on("sent", [ $request, "ready" ]);
            $response->send();
        }

        public function route($event, Message $request, Message $response = null) {
            if ($this->upgraded) {
                switch ($request->getOpCode()) {
                    case Request::OPCODE_CTRL_CLOSE: {
                        $this->sendClose($request);
                        break;
                    }
                    case Request::OPCODE_CTRL_PING: {
                        $this->sendPong($request);
                        break;
                    }
                    case Request::OPCODE_CTRL_PONG: {
                        break;
                    }
                    case Request::OPCODE_NCTRL_TEXT:
                    case Request::OPCODE_NCTRL_BIN: {
                        parent::route($event, $request, new Response());
                        break;
                    }
                    default: {
                        throw new \Exception("Unknown opcode " . var_export($request->getOpCode(), 1));
                    }
                }
            } else {
                $response = new \Phasty\Server\Http\Response();
                $response->setWriteStream($request->getReadStream());
                $hash = $this->getAcceptHash($request->getHeader("Sec-WebSocket-Key")[ 0 ]);
                $origin = $request->getHeader("Origin")[ 0 ];
                $location = "ws://" . $request->getHeader("Host")[ 0 ] . $request->getPath();
                $response
                    ->setCode(101)
                    ->setHeader("Upgrade", "websocket")
                    ->setHeader("Connection", "Upgrade")
                    ->setHeader("Origin", $origin)
                    ->setHeader("Sec-WebSocket-Accept", $hash);
                if ($request->hasHeader("Sec-WebSocket-Protocol")) {
                    $request->setHeader("Sec-WebSocket-Protocol", "chat");
                }
                $response
                    ->on("sent", function () use ($request) {
                        $this->upgraded = true;
                        $request->trigger("dispatched", [
                            "nextProtocol" => $this
                        ]);
                    })->send();
            }
        }

        protected function getAcceptHash($hash) {
            $hash .= "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";
            $hash = sha1($hash, true);
            return base64_encode($hash);
        }

        public function getRequestObject() {
            return new Request();
        }

        public function getResponseObject() {
            return new Response();
        }
    }
}
