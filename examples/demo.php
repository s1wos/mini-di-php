<?php
require __DIR__ . '/../vendor/autoload.php';

use MiniDI\Container;

interface Logger
{
    public function log(string $message): void;
}

class FileLogger implements Logger
{
    public function __construct(private readonly string $file)
    {
    }

    public function log(string $message): void
    {
        file_put_contents($this->file, date('c') . " : " . $message . PHP_EOL, FILE_APPEND);
    }
}

class Mailer
{
    public function __construct(private Logger $logger)
    {
    }

    public function send(string $to, string $msg): void
    {
        $this->logger->log("Email to $to: $msg");
        echo "Отправлено письмо на $to\n";
    }
}

$di = new Container();
$di->singleton(Logger::class, fn() => new FileLogger(__DIR__ . '/app.log'));
$di->bind('mailer', Mailer::class);

$mailer = $di->make('mailer');
$mailer->send('user@example.com', 'Привет из Mini-DI!');

echo "Лог записан в examples/app.log" . PHP_EOL;
