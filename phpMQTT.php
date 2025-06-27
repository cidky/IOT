<?php

namespace Bluerhinos;

class phpMQTT {
    private $socket;       
    private $msgid = 1;
    public $keepalive = 10; 
    public $timesinceping;
    public $topics = array();
    public $debug = false;

    private $address;
    private $port;
    private $clientid;

    private $username;
    private $password;

    private $cafile;

    private $will;
    private $clean = true;

    private $connected = false;
    private $message_length = 0;

    function __construct($address, $port, $clientid) {
        $this->broker($address, $port, $clientid);
    }

    function broker($address, $port, $clientid) {
        $this->address = $address;
        $this->port = $port;
        $this->clientid = $clientid;
    }

    function connect($clean = true, $will = NULL, $username = NULL, $password = NULL, $cafile = NULL) {
        if ($cafile) {
            $this->cafile = $cafile;
            $context = stream_context_create(["ssl" => [
                "verify_peer" => true,
                "verify_peer_name" => true,
                "cafile" => $cafile
            ]]);
            $this->socket = stream_socket_client("ssl://{$this->address}:{$this->port}", $errno, $errstr, 60, STREAM_CLIENT_CONNECT, $context);
        } else {
            $this->socket = stream_socket_client("tcp://{$this->address}:{$this->port}", $errno, $errstr, 60, STREAM_CLIENT_CONNECT);
        }

        if (!$this->socket) {
            if ($this->debug) echo "Error connecting to broker: $errstr ($errno)\n";
            return false;
        }

        stream_set_timeout($this->socket, 5);

        $this->clean = $clean;
        $this->will = $will;
        $this->username = $username;
        $this->password = $password;

        $i = 0;
        $buffer = "";

        $buffer .= chr(0x00) . chr(strlen("MQTT")) . "MQTT";
        $buffer .= chr(0x04); // Protocol Level 4 = MQTT 3.1.1

        $var = 0;
        $var += 0x02;
        if ($clean) $var += 0x02;

        if ($will) $var += 0x04 + 0x08;

        if ($username) $var += 0x80;
        if ($password) $var += 0x40;

        $buffer .= chr($var);
        $buffer .= chr($this->keepalive >> 8) . chr($this->keepalive & 0xFF);
        $buffer .= $this->strwritestring($this->clientid);

        if ($will) {
            $buffer .= $this->strwritestring($will["topic"]);
            $buffer .= $this->strwritestring($will["content"]);
        }

        if ($username) $buffer .= $this->strwritestring($username);
        if ($password) $buffer .= $this->strwritestring($password);

        $head = " ";
        $head = chr(0x10);
        $head .= $this->setmsglength(strlen($buffer));

        fwrite($this->socket, $head, strlen($head));
        fwrite($this->socket, $buffer, strlen($buffer));

        $string = $this->read(4);

        if (strlen($string) < 4) {
            if ($this->debug) {
                echo "❌ Tidak menerima respon CONNECT ACK dari broker MQTT.\n";
                echo "📭 Respon kosong atau tidak sesuai format.\n";
            }
            return false;
        }

        if ($this->debug) echo "Connection failed. Message: " . bin2hex($string) . "\n";
        return false;
    }

    function subscribe($topics) {
        $buffer = "";
        $id = $this->msgid++;
        $buffer .= chr($id >> 8) . chr($id % 256);

        foreach ($topics as $topic => $properties) {
            $buffer .= $this->strwritestring($topic);
            $buffer .= chr($properties["qos"]);
            $this->topics[$topic] = $properties;
        }

        $cmd = chr(0x82) . $this->setmsglength(strlen($buffer));
        fwrite($this->socket, $cmd, strlen($cmd));
        fwrite($this->socket, $buffer, strlen($buffer));

        $string = $this->read(5);

        if ($this->debug) echo "Subscribe Ack: " . bin2hex($string) . "\n";

        return true;
    }

    function proc() {
        if (!$this->connected) return false;

        $byte = ord($this->read(1));

        if (!$byte) return false;

        $cmd = $byte >> 4;

        $multiplier = 1;
        $value = 0;
        do {
            $digit = ord($this->read(1));
            $value += ($digit & 127) * $multiplier;
            $multiplier *= 128;
        } while (($digit & 128) != 0);

        if ($value) {
            $string = $this->read($value);
        }

        if ($cmd == 3) {
            $topiclen = (ord($string[0]) << 8) + ord($string[1]);
            $topic = substr($string, 2, $topiclen);
            $msg = substr($string, $topiclen + 2);

            if (isset($this->topics[$topic]["function"])) {
                $this->topics[$topic]["function"]($topic, $msg);
            }
        }

        if ($cmd == 13) {
            fwrite($this->socket, chr(0xC0) . chr(0x00), 2);
        }

        return true;
    }

    function read($int = 8192) {
        $string = "";
        $togo = $int;

        while (!feof($this->socket) && $togo > 0) {
            $fread = fread($this->socket, $togo);
            $string .= $fread;
            $togo = $int - strlen($string);
        }

        return $string;
    }

    function close() {
        fwrite($this->socket, chr(0xE0) . chr(0x00), 2);
        fclose($this->socket);
        $this->connected = false;
    }

    function strwritestring($str, $length = 0) {
        if ($length) {
            return chr(0) . chr($length) . $str;
        }
        return chr(strlen($str) >> 8) . chr(strlen($str) % 256) . $str;
    }

    function setmsglength($len) {
        $string = "";

        do {
            $digit = $len % 128;
            $len = $len >> 7;
            if ($len > 0) $digit = ($digit | 0x80);
            $string .= chr($digit);
        } while ($len > 0);

        return $string;
    }
}