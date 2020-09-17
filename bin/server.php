<?php
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use MyApp\WebSocketServer;

require_once './vendor/autoload.php';

$openPort = 8080;   // デフォルト値

$commandsList  = array(
    'help' =>   '--help                 簡単な説明、コマンドを表示します',
    'port:' =>  '--port <port number>   websocketサーバーに使用するためのポートを指定します(デフォルトは8080)',
);

$options = getopt('', array_keys($commandsList));

if(in_array('help', array_keys($options))) {
    print("php .\bin\server.php <option>    websocketサーバーを起動します\n\n");
    foreach ($commandsList as $key => $value) {
        print($value."\n");
    }
    exit;

} else if(in_array('port', array_keys($options))) {
    $openPort = $options['port'];
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new WebSocketServer()
        )
    ),
    $openPort
);

$server->run();