<?php
namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQPublisher {
    private AMQPStreamConnection $connection;
    private \PhpAmqpLib\Channel\AMQPChannel $channel;

    public function __construct() {
        $host = env('RABBITMQ_HOST', 'rabbitmq');
        $port = env('RABBITMQ_PORT', 5672);
        $user = env('RABBITMQ_USER', 'guest');
        $pass = env('RABBITMQ_PASS', 'guest');

        $this->connection = new AMQPStreamConnection($host, $port, $user, $pass);
        $this->channel    = $this->connection->channel();
        $this->channel->exchange_declare('city.events', 'topic', false, true, false);
    }

    public function publish(string $routingKey, array $data): void {
        $msg = new AMQPMessage(
            json_encode($data),
            ['delivery_mode' => 2]
        );
        $this->channel->basic_publish($msg, 'city.events', $routingKey);
    }

    public function __destruct() {
        try {
            $this->channel->close();
            $this->connection->close();
        } catch (\Exception $e) {}
    }
}