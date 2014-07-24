<?php
namespace Phasty\Server {
    use \Phasty\Stream\Socket;
    use \Phasty\Stream\StreamSet;
    use \Phasty\Stream\Stream;
    abstract class Http extends \Phasty\Server\Server {
        public function __construct(Socket\Server $serverSocket, StreamSet $streamSet = null) {
            parent::__construct($serverSocket, $streamSet);
            $this->setDefaultProtocol("\\Phasty\\Server\\Http\\Protocol1p1");
        }
        public function getRequestObject(Stream $stream) {
            $request = new Http\Request();
            $request->setReadStream($stream);
            return $request;
        }
        public function getResponseObject(Stream $stream) {
            $request = new Http\Response();
            $request->setWriteStream($stream);
            return $request;
        }
    }
}
