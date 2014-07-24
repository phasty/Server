<?php
namespace Phasty\Server\Http {
    /**
     * TODO:  переписать на нормальные роуты, возможно подключить symfony
     */
    class Router {
        static protected $invoker = null;

        static public function setInvoker($invoker) {
            self::$invoker = $invoker;
        }
        static public function route(\Phasty\Server\Message $request, \Phasty\Server\Message $response) {
            call_user_func_array(self::$invoker, func_get_args());
        }
    }
}
