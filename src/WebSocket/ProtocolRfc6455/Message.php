<?php
namespace Phasty\Server\WebSocket\ProtocolRfc6455 {
    class Message extends \Phasty\Server\Message {
        protected $masked = false;
        protected $opCode = null;
        protected $fin    = 1;
        protected $rsv1   = 0;
        protected $rsv2   = 0;
        protected $rsv3   = 0;
        protected $mask   = null;
        protected $payload = null;

        const OPCODE_CTRL_CLOSE = 0x08;
        const OPCODE_CTRL_PING  = 0x09;
        const OPCODE_CTRL_PONG  = 0x0A;

        const OPCODE_NCTRL_TEXT = 0x01;
        const OPCODE_NCTRL_BIN  = 0x02;

        public function setMasked($masked = true) {
            $this->masked = (bool)$masked;
        }

        public function setOpCode($opCode) {
            if ($opCode > 0b1111) {
                throw new \Exception("Invalid opcode value");
            }
            $this->opCode = $opCode;
        }

        public function getOpCode() {
            return $this->opCode;
        }

        public function setPayload($payload = null) {
            $this->payload = $payload;
        }

        public function getPayload() {
            return $this->payload;
        }

        public function __toString() {
            $totalPayloadLen = strlen($this->payload);
            $payloadLen = $totalPayloadLen < 125 ? $totalPayloadLen :
                          ($totalPayloadLen < 65535 ? 126 : 127);
            $frame =
                pack(
                    "n",
                    $a = $this->fin    << 15 |
                    $this->rsv1   << 14 |
                    $this->rsv2   << 13 |
                    $this->rsv3   << 12 |
                    $this->opCode << 8 |
                    $this->masked << 7  |
                    $payloadLen
                );
                // die((string)decbin($a));
            if ($payloadLen > 125) {
                if ($payloadLen == 126) {
                    $frame .= pack("n", $totalPayloadLen);
                } else {
                    // TODO: $totalPayloadLen > 4.294.967.295
                    $frame .= pack("N2", 0, $totalPayloadLen);
                }
            }
            if ($this->masked) {
                $frame .= $this->mask = pack("N", rand());
                return $frame . $this->xorBuffer($this->payload, $this->mask);
            } else {
                return $frame . $this->payload;
            }
        }

        protected function xorBuffer($buffer, $mask) {
            if (!strlen($buffer)) {
                return "";
            }
            $maskLen = strlen($mask);
            // Note: using str_pad match slower
            $xorBuffer = str_repeat($mask, ceil(strlen($buffer) / $maskLen));
            return $buffer ^ $xorBuffer;
        }

        /**
         * Decode frame RFC 6455
         *
         * TODO: make it deterministic finite automaton
         *
         * @link http://tools.ietf.org/html/rfc6455#section-5.2
         */
        protected function tryParse($buffer) {
            $bufferLen = strlen($buffer);
            // No header
            if ($bufferLen < 2) {
                return false;
            }
            list(, $requiredHeader) = unpack("n*", $buffer);
            $this->masked = (bool)($buffer && (1 << 7));

            // mask bit is set, but no mask present
            if ($this->masked && $bufferLen < 6) {
                return false;
            }

            $this->fin    = (bool)($requiredHeader & (1 << 15));
            $this->rsv1   = (bool)($requiredHeader & (1 << 14));
            $this->rsv2   = (bool)($requiredHeader & (1 << 13));
            $this->rsv3   = (bool)($requiredHeader & (1 << 12));
            $this->opCode = ($requiredHeader & (0b1111 << 8)) >> 8;

            $payloadLen = $requiredHeader & 127;
            $buffer = substr($buffer, 2);
            $bufferLen -= 2;
            if ($payloadLen == 126) {
                if ($bufferLen < 2) {
                    return false;
                }
                list(, $payloadLen) = unpack("n", $buffer);
                if ($payloadLen < 126) {
                    throw new \Exception("Invalid payload length field for 16 bits");
                }
                $buffer = substr($buffer, 2);
                $bufferLen -= 2;
            } elseif ($payloadLen == 127) {// 127 - 64 bits
                if ($bufferLen < 8) {
                    return false;
                }
                //TODO: wrong read of 64 bit integers
                list(,, $payloadLen) = unpack("N2", $buffer);
                if ($payloadLen < 65535) {
                    throw new \Exception("Invalid payload length field for 64 bits");
                }
                $buffer = substr($buffer, 8);
                $bufferLen -= 8;
            }
            // no mask buffer
            if ($this->masked) {
                if ($bufferLen < 4) {
                    return false;
                }
                $this->mask = substr($buffer, 0, 4);
                $buffer = substr($buffer, 4);
                $bufferLen -= 4;

                $this->payload = $this->xorBuffer($buffer, $this->mask);

            }
            if ($bufferLen < $payloadLen) {
                return false;
            }
            $buffer = substr($buffer, $payloadLen);
            $bufferLen -= $payloadLen;

            $this->payload = $this->masked ?
                $this->xorBuffer($buffer, $this->mask) :
                $buffer;
            return $bufferLen ? $bufferLen : true;
        }
    }
}
