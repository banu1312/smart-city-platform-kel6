<?php
namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQPublisher {
    private ?AMQPStreamConnection $connection = null;
    private ?\PhpAmqpLib\Channel\AMQPChannel $channel = null;

    private function connect(): void {
        if ($this->channel !== null) {
            return;
        }

        $this->connection = new AMQPStreamConnection(
            config('rabbitmq.host'),
            config('rabbitmq.port'),
            config('rabbitmq.user'),
            config('rabbitmq.pass'),
        );
        $this->channel = $this->connection->channel();
        $this->channel->exchange_declare(config('rabbitmq.exchange'), 'topic', false, true, false);
    }

    public function publish(string $routingKey, array $data): void {
        $this->connect();

        $msg = new AMQPMessage(
            json_encode($data),
            ['delivery_mode' => 2]
        );
        $this->channel->basic_publish($msg, config('rabbitmq.exchange'), $routingKey);
    }

    public function __destruct() {
        try {
            $this->channel?->close();
            $this->connection?->close();
        } catch (\Exception $e) {}
    }
}
