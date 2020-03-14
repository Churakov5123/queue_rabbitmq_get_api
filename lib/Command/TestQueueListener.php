<?php
/**
 *  слушатель реализован в виде запуска  консольной команды  фаила php /usr/bin/php cli.php queue-listen:test
 *
 */
declare(strict_types=1);

namespace SuiteCRM\Custom\Command;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use SuiteCRM\Custom\DataProcessor\TestDataProcessor;
use SuiteCRM\Custom\Queue\QueueService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestQueueListener extends Command
{

    private const QUEUE_NAME = 'test';
    /**
     * @var TestDataProcessor
     */
    private $processor;
    /**
     * @var QueueService
     */
    private $queueService;

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->processor = new TestDataProcessor();
        $this->queueService = new QueueService();
    }

    protected function configure()
    {
        $this->setName('queue-listen:test');
        $this->setDescription('Command for listening test queue');
    }

    protected function execute(InputInterface $input, OutputInterface $output)

    {
        $output->writeln('<comment>Test queue listener started</comment>');

        // 1. Подключиться к серверу очередей и создать если нету очередь 
        $connection = $this->queueService->getConnection();

        // 2. Инициализировать очередь
        $channel = $connection->channel();
        $channel->queue_declare(self::QUEUE_NAME, false, true, false, false); // к очереди 

        // 3. Прослушка и обработка сообщений в очереди 

        $channel->basic_consume(
            self::QUEUE_NAME,
            'cli-php-script',
            false,
            true,
            false,
            false,
                function (AMQPMessage $message) use ($output) {
                    $this->processMessage($message, $output);
                }
            );

        register_shutdown_function(function () use ($channel, $connection) {
            $channel->close();
            $connection->close();
        }, $channel, $connection);

        // Запуск прослушки канала c очередью 
        // Loop as long as the channel has callbacks registered
        while ($channel->is_consuming()) {
            $channel->wait();
        }

        return 0;
    }

    // cам обработка сообщений  !!!
    private function processMessage(AMQPMessage $message, OutputInterface $output): void
    {
        $payload = json_decode($message->getBody(), true);

        $start = microtime(true);

        $output->writeln('Job process started...');

        $this->processor->process($payload);

        $output->writeln('Job processed at '
            . date('Y-m-d H:i:s')
            . ' on '
            . round(microtime(true) - $start, 2));
    }
}