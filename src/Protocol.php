<?php
namespace Phasty\Server {
    interface Protocol {
        static public function match($request);

        public function dispatch(Message $request);

        public function getRequestObject();
    }
}
