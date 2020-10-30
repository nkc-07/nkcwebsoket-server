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
        $msgObject = json_decode($msg, true);

        if($from_param['mode'] === 'attendance') {
            $context = stream_context_create(
                array(
                    'http' => array(
                        'method'=> 'PUT',
                        'header'=> 'Content-type: application/json; charset=UTF-8',
                        'content' => http_build_query($msgObject)
                    )
                )
            );
            $json_object = json_decode(
                file_get_contents(
                    'http://localhost:8080/api/event/eventattendance.php',
                    false,
                    $context
                ),
                true
            );

            foreach ($this->clients as $client) {
                $client_parm = $this->parse_url_param($client->httpRequest->getRequestTarget());
                if (
                        // $from !== $client &&
                        $from_param['mode'] === $client_parm['mode'] &&
                        $from_param['participation_event'] == $client_parm['participation_event']
                    ) {
                    $client->send(json_encode($json_object['data']));
                }
            }
        } else if($from_param['mode'] === 'chat') {
            // $context = stream_context_create(
            //     array(
            //         'http' => array(
            //             'method'=> 'POST',
            //             'header'=> 'Content-type: application/json; charset=UTF-8',
            //             'content' => http_build_query($msgObject)
            //         )
            //     )
            // );
            // $json_object = json_decode(
            //     file_get_contents(
            //         'http://localhost:8080/api/group/groupchat.php',
            //         false,
            //         $context
            //     ),
            //     true
            // );
            $json_object = [
                'nickname' => 'test user',
                'icon' => 'test',
                'message' => 'test message'
            ];

            foreach ($this->clients as $client) {
                $client_parm = $this->parse_url_param($client->httpRequest->getRequestTarget());
                if (
                        $from !== $client &&
                        $from_param['mode'] === $client_parm['mode'] &&
                        $from_param['room'] == $client_parm['room']
                    ) {
                    $client->send(json_encode($json_object));
                }
            }
        }
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