<?php

$server = new Swoole\Websocket\Server('127.0.0.1', 9502);

$server->on('start', function ($server) {
    echo "Websocket Server is started at ws://127.0.0.1:9502\n";
});

$server->on('open', function($server, $req) {
    echo "connection open: {$req->fd}\n";

    // Envia a mensagem para todos os clientes menos para quem enviou
    foreach ($server->connections as $fd) {
        if ($server->isEstablished($fd)) {
            $server->push($fd, json_encode([
                'fd' => $fd,
                'me' => ($fd == $req->fd),
                'value' => ($fd == $req->fd) ? 'Você entrou na sala' : "{$fd} entrou na sala",
                'time' => (new DateTimeImmutable())->format('d/m/Y H:i:s')
            ]));
        }
    }
});

$server->on('message', function($server, $frame) {
    echo "Mensagem recebida\nfd: {$frame->fd}\nmensagem: {$frame->data}\n";
    
    // Envia a mensagem para todos os clientes menos para quem enviou
    foreach ($server->connections as $fd) {
        if ($server->isEstablished($fd)) {
            $server->push($fd, json_encode([
                'fd' => $fd,
                'me' => ($fd == $frame->fd),
                'value' => $frame->data,
                'time' => (new DateTimeImmutable())->format('d/m/Y H:i:s')
            ]));
        }
    }
});

$server->on('close', function($server, $fdClosing) {
    echo "Conexão fechada para o fd: {$fdClosing}\n";

    // Envia a mensagem para todos os clientes menos para quem enviou
    // Envia a mensagem para todos os clientes menos para quem enviou
    foreach ($server->connections as $fd) {
        if ($server->isEstablished($fd)) {
            $server->push($fd, json_encode([
                'fd' => $fd,
                'me' => ($fd == $fdClosing),
                'value' => ($fd == $fdClosing) ? 'Você saiu da sala' : "{$fd} saiu da sala",
                'time' => (new DateTimeImmutable())->format('d/m/Y H:i:s')
            ]));
        }
    }
});

$server->start();