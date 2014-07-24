<?php
namespace Phasty\Server {
    use \Phasty\Events\Event;
    use \Phasty\Stream\Stream;
    abstract class Message extends \Phasty\Events\Eventable {
        protected $readStream = null;
        protected $writeStream = null;
        private   $buffer = "";

        /**
         * Try parse string into internals
         */
        abstract protected function tryParse($string);

        public function setReadStream(Stream $stream = null) {
            if ($this->readStream instanceof Stream) {
                $this->setReadStreamCallbacks("off");
            }
            if ($stream instanceof Stream) {
                $this->readStream = $stream;
                $this->setReadStreamCallbacks();
            }
        }

        public function setWriteStream(Stream $stream = null) {
            if ($this->writeStream instanceof Stream) {
                $this->setWriteStreamCallbacks("off");
            }
            if ($stream instanceof Stream) {
                $this->writeStream = $stream;
                $this->setWriteStreamCallbacks();
            }
        }

        public function removeReadStream() {
            $this->setReadStream(null);
        }

        public function removeWriteStream() {
            $this->setWriteStream(null);
        }

        public function getReadStream() {
            return $this->readStream;
        }

        public function getWriteStream() {
            return $this->writeStream;
        }

        /**
         * Events listener for connection data
         */
        public function onConnectionData(Event $event, $connection) {
            $this->buffer .= $event->getData();
            try {
                $parsed = mb_strlen($this->buffer) ? $this->tryParse($this->buffer) : false;
            } catch (\Exception $e) {
                $this->trigger("error", $e->getMessage());
                return;
            }
            if ($parsed) {
                $this->buffer = "";
                $connection->off("data", [ $this, "onConnectionData" ]);
                $this->trigger("read-complete");
            }
        }

        public function send() {
            $this->on("sent", "removeWriteStream");
            $this->writeStream->write((string)$this);
        }

        public function sent() {
            $this->trigger("sent");
        }

        /**
         * Should only clear write stream, used as callback
         */
        protected function clearWriteStream() {
            $this->writeStream = null;
        }

        protected function clearReadStream() {
            $this->writeStream = null;
        }

        protected function setWriteStreamCallbacks($function = "on") {
            $this->writeStream->$function("close", [ $this, "clearWriteStream" ]);
            $this->writeStream->$function("written", [ $this, "sent" ]);
        }

        protected function setReadStreamCallbacks($function = "on") {
            $this->readStream->$function("data", [ $this, "onConnectionData" ]);
            $this->readStream->$function("close", [ $this, "clearReadStream" ]);
        }

        public function dispatched() {
            $this->trigger("dispatched");
        }

        public function ready() {
            $this->trigger("ready");
        }
    }
}
