<?php

declare(strict_types=1);

namespace SuiteCRM\Custom\Queue;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class QueueService
{
   
    public function getConnection(): AMQPStreamConnection
    {
        return new AMQPStreamConnection('127.0.0.1', 5672, 'guest', 'guest');
    }
}