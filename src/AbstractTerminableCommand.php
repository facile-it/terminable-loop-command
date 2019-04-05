<?php

declare(strict_types=1);

namespace Facile\TerminableLoop;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractTerminableCommand extends Command
{
    private const REQUEST_TO_TERMINATE = 143;

    /** @var int */
    private $sleepDuration;

    /** @var bool */
    private $signalShutdownRequested;

    public function __construct(string $name)
    {
        $this->sleepDuration = 0;
        $this->signalShutdownRequested = false;

        parent::__construct($name);
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Run TerminableCommand');

        if ($this->signalShutdownRequested) {
            $output->writeln('Sigterm presente, non eseguo il comando ' . $this->getName());

            return self::REQUEST_TO_TERMINATE;
        }

        $exitCode = $this->commandBody($input, $output);

        $this->sleep();

        if ($this->signalShutdownRequested) {
            $output->writeln('Sigterm presente esco dal comando ' . $this->getName());

            return self::REQUEST_TO_TERMINATE;
        }

        return $exitCode;
    }

    abstract protected function commandBody(InputInterface $input, OutputInterface $output): int;

    public function handleSignal($signal): void
    {
        switch ($signal) {
            // Shutdown signals
            case SIGTERM:
            case SIGINT:
                $this->onReceivedSignal();
                break;
        }
    }

    protected function onReceivedSignal(): void
    {
        $this->signalShutdownRequested = true;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        pcntl_async_signals(true);

        // Add the signal handler
        pcntl_signal(SIGTERM, [$this, 'handleSignal']);
        pcntl_signal(SIGINT, [$this, 'handleSignal']);
    }

    protected function getSleepDuration(): int
    {
        return $this->sleepDuration;
    }

    protected function setSleepDuration(int $sleepDuration): void
    {
        if ($sleepDuration < 0) {
            throw new \InvalidArgumentException('Invalid timeout provided to ' . __METHOD__);
        }

        $this->sleepDuration = $sleepDuration;
    }

    private function sleep(): void
    {
        $sleepDuration = $this->sleepDuration;

        while (! $this->signalShutdownRequested && $sleepDuration--) {
            sleep(1);
        }
    }
}
