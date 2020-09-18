<?php
namespace MyApp;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
 
class WebSocketServer implements MessageComponentInterface {
    protected $clients;
 
    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }
 
    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);

        echo "New connection! ({$conn->resourceId})\n";
    }
 
    public function onMessage(ConnectionInterface $from, $msg) {
        $from_param = $this->parse_url_param($from->httpRequest->getRequestTarget());

        foreach ($this->clients as $client) {
            $client_parm = $this->parse_url_param($client->httpRequest->getRequestTarget());
            if (
                    $from !== $client &&
                    $from_param['mode'] === $client_parm['mode'] &&
                    $from_param['participation_event'] == $client_parm['participation_event']
                ) {
                $client->send($msg);
            }
        }

        // TODO: チャット用処理
    }
 
    public function onClose(ConnectionInterface $conn) {
 
        $this->clients->detach($conn);
 
        echo "Connection {$conn->resourceId} has disconnected\n";
    }
 
    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
 
        $conn->close();
    }

    private function parse_url_param($string) {
        $query = str_replace("/?", "", $string);
        parse_str($query, $return_param);
        return $return_param;
    }
}