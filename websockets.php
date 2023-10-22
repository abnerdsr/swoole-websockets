<?php

$server = new Swoole\Websocket\Server('127.0.0.1', 9502);

/**
 * Classe para um Objeto Valor de Mensagem
 * Assim padronizamos o envio de mensagens
 */
class Message {
    /**
     * Carrega as variaveis necessárias a partir do construtor
     */
    public function __construct(public int $fd, public string $message, public bool $me)
    {
        //
    }

    /**
     * Retorna a mensagem em um formato padrão
     */
    public function message(): string
    {
        return json_encode([
            'fd' => $this->fd,
            'me' => $this->me,
            'value' => $this->message,
             'time' => (new DateTimeImmutable())->format('d/m/Y H:i:s')
        ]);
    }
}

/**
 * Função anonima para processar mensagens do servidor
 * 
 * @param int $fdSender - File Descriptor de quem enviou a mensagem
 * @param string $message - Mensagem que esta chegando
 * @param string $messageForMe - Mensagem enviada quando é para o próprio fd que enviou
 * @return void
 */
function processaMensagem(int $fdSender, string $message, ?string $messageForMe = null): void
{
    // Captura a instancia do servidor atual
    global $server;
    // Envia a mensagem para todos os clientes conectados
    foreach ($server->connections as $fd) {
        if ($server->isEstablished($fd)) {
            $server->push(
                $fd, 
                (new Message(
                    fd: $fdSender, 
                    message: ($fdSender == $fd) && $messageForMe ? $messageForMe : str_replace('{fd}', "$fdSender", $message), 
                    me: ($fdSender == $fd))
                )->message()
            );
        }
    }
}

$server->on('start', function ($server) {
    echo "Websocket Server is started at ws://127.0.0.1:9502\n";
});

$server->on('open', function($server, $req) {
    echo "connection open: {$req->fd}\n";
    processaMensagem(fdSender: $req->fd, messageForMe: 'Você entrou na sala', message: "{fd} entrou na sala");
});

$server->on('message', function($server, $frame) {
    echo "Mensagem recebida\nfd: {$frame->fd}\nmensagem: {$frame->data}\n";
    processaMensagem(fdSender: $frame->fd, message: $frame->data);
});

$server->on('close', function($server, $fdClosing) {
    echo "Conexão fechada para o fd: {$fdClosing}\n";
    processaMensagem(fdSender: $fdClosing, messageForMe: 'Você saiu da sala', message: "{fd} saiu da sala");
});

$server->start();