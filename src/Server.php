<?php
namespace Phasty\Server {
    use \Phasty\Stream;
    abstract class Server extends \Phasty\Events\Eventable {
        protected $parser = null;
        protected $defaultProtocol = null;
        protected $protocols = [];

        abstract protected function getRequestObject(Stream\Stream $stream);
        abstract protected function getResponseObject(Stream\Stream $stream);

        public function __construct(Stream\Socket\Server $serverSocket, Stream\StreamSet $streamSet = null) {
            if (is_null($streamSet)) {
                $streamSet = Stream\StreamSet::instance();
            }
            $serverSocket->on("connected", [ $this, "onNewConnection" ]);
        }
        public function onNewConnection($event) {
            $connection = $event->getData()->connection;
            $connection->on("before-close", function($event, $connection) {
                $this->trigger("client-disconnected", (object)[
                    "connection" => $connection
                ]);
            });
            $this->care($connection);
        }

        public function dispatchRequest($event, $request) {
            try {
                $response = $this->getResponseObject($request->getReadStream());
                $this->care($request);
            } catch (Server\Protocol\Exception\InvalidMessage $e) {
                $response = $this->getResponseObject();
                $response->setRequestIsInvalid();
                $response->send();
                $connection->write($e->getResponse());
                $connection->on("written", [ $connection, "close" ]);
            }
        }

        protected function setDefaultProtocol($protocol) {
            $this->defaultProtocol = $protocol;
        }

        public function addProtocol($protocol) {
            $this->protocols []= $protocol;
        }

        protected function care(\Phasty\Stream\Stream $connection, Protocol $protocol = null) {
            $protocol = $protocol instanceof Protocol ? $protocol : new $this->defaultProtocol();

            $request = $protocol->getRequestObject();
            $request->setReadStream($connection);

            $request->on("dispatched", function ($event, $request) use ($protocol) {
                $data = $event->getData();
                $nextProtocol = isset($data["nextProtocol"]) ? $data["nextProtocol"] : $protocol;
                $request->removeReadStream();
                $request->removeWriteStream();
                $this->care($request->getReadStream(), $nextProtocol);
            });

            $protocol->dispatch($request);

            $this->trigger("protocol-attached", (object)[
                "connection" => $connection,
                "protocol"   => $protocol
            ]);
        }
    }
}
