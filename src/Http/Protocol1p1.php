<?php
namespace Phasty\Server\Http {
    use \Phasty\Server\Message;
    class Protocol1p1 extends \Phasty\Server\Protocol {
        static protected $allowedMethods = [
            "GET" => true
        ];

        public function dispatch(Message $request) {
            $request->on("read-complete", [ $this, "route" ]);

            $request->on("error", function ($event, $request) {
                $response = new Response();
                $response->setWriteStream($request->getReadStream());
                $response->setCode(400);
                $response->setBody("Bad request");
                $response->on("sent", [ $request, "dispatched" ]);
                $response->send();
            });
        }

        public function route($event, Message $request, Message $response = null) {
            $response = $response instanceof Message ? $response : new Response();
            $response->setWriteStream($request->getReadStream());
            $response->on("ready", function($event, $response) use ($request) {
                $response
                    ->on("sent", [ $request, "dispatched" ])
                    ->send();
            });

            $this->server->trigger("request", (object)compact("request", "response"));
        }

        public static function match($request) {
            $method = strtoupper($request->getMethod());
            if (!isset(self::$allowedMethods[ $method ])) {
                return false;
            }
            return true;
        }

        public function getRequestObject() {
            return new Request();
        }
    }
}
