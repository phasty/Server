<?php
namespace Phasty\Server {
    interface IProtocol {
        static function match($request);

        function dispatch(Message $request);

        function getRequestObject();
    }
}
