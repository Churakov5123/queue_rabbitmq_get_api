<?php

namespace SuiteCRM\Custom\Controller;

use Api\V8\Controller\BaseController;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\StatusCode;
use SuiteCRM\Custom\Queue\TestQueue;
use SuiteCRM\Custom\Queue\QueueService;

class TestController extends BaseController
{
    /**
     * @var QueueService
     */
    private $queueService;

    public function __construct()
    {
        $this->queueService = new QueueService();
    }


    public function addRecord(Request $request): Response
    {
        $testArr  = $request->getParsedBody();
		//предварительная очистка базы

        $sql = "DELETE FROM  accounts_api_packets_1_c";
        $GLOBALS['db']->query($sql);

        $sql = "DELETE FROM  api_packets";
        $GLOBALS['db']->query($sql);

        $sql = "DELETE FROM  apir_rates";
        $GLOBALS['db']->query($sql);

        $sql = "DELETE FROM  api_packets_apir_rates_1_c";
        $GLOBALS['db']->query($sql);

        // 1. Подключиться к серверу очередей и создать если нету очередь Test
        $connection = $this->queueService->getConnection();

        // 2. Инициализировать очередь
        $channel = $connection->channel();
        $channel->queue_declare(TestQueue::QUEUE_NAME, false, true, false, false);
        $channel->exchange_declare(TestQueue::EXCHANGE, AMQPExchangeType::DIRECT, false, true, false);
        $channel->queue_bind(TestQueue::QUEUE_NAME, TestQueue::EXCHANGE);

        foreach ($testArr as $crm) {
            $message = new AMQPMessage(
                json_encode($crm),
                [
                    'content_type' => 'application/json',
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_NON_PERSISTENT
                ]
            );
            $channel->basic_publish($message, TestQueue::EXCHANGE); // публикует сообщения  то есть данные идущие по API с названием очереди  Test
        }
        $channel->close();
        $connection->close();

        return $this->generateResponse(new Response(), [
            'status' => 'success',
        ], StatusCode::HTTP_OK);

    }

}

