<?php
namespace Phasty\Server {
    abstract class Protocol implements IProtocol {
        /**
         * @var server \Phasty\Server\Server instance that serves incoming connections
         */
        protected $server = null;

        public function __construct(\Phasty\Server\Server $server) {
            $this->server = $server;
        }
    }
}
