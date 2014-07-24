<?php
namespace Phasty\Server {
    use \Phasty\Stream\Socket;
    use \Phasty\Stream\StreamSet;
    abstract class WebSocket extends Http {
        protected $messageFactory = null;
        public function __construct(Socket\Server $serverSocket, StreamSet $streamSet = null) {
            parent::__construct($serverSocket, $streamSet);

            $this->setDefaultProtocol("\\Phasty\\Server\\WebSocket\\DefaultProtocol");
            // $this->addProtocol(new WebSocket\ProtocolHixie76());
            // $this->addProtocol(new WebSocket\ProtocolHybi07());
            // $this->addProtocol(new WebSocket\ProtocolRfc6455());
        }
    }
}
