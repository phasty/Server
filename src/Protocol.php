<?php
namespace Phasty\Server {
    abstract class Protocol {
        /**
         * @var server \Phasty\Server\Server instance that serves incoming connections
         */
        protected $server = null;

        abstract static public function match($request);

        abstract public function dispatch(Message $request);

        abstract public function getRequestObject();

        public function __construct(\Phasty\Server\Server $server) {
            $this->server = $server;
        }
    }
}
