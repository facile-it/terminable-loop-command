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

    public function __construct(string $name = null)
    {
        $this->sleepDuration = 0;
        $this->signalShutdownRequested = false;

        parent::__construct($name);
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->trapSignals();

        $output->writeln('Starting ' . $this->getName(), OutputInterface::VERBOSITY_VERBOSE);

        if ($this->signalShutdownRequested) {
            $output->writeln('Signal received, skipping execution', OutputInterface::VERBOSITY_NORMAL);

            return self::REQUEST_TO_TERMINATE;
        }

        $exitCode = $this->commandBody($input, $output);

        $this->sleep($output);

        if ($this->signalShutdownRequested) {
            $output->writeln('Signal received, terminating with exit code ' . self::REQUEST_TO_TERMINATE, OutputInterface::VERBOSITY_NORMAL);

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
                $this->signalShutdownRequested = true;
                break;
        }
    }

    private function trapSignals(): void
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

    private function sleep(OutputInterface $output): void
    {
        $sleepCountDown = $this->sleepDuration;

        while (! $this->signalShutdownRequested && $sleepCountDown--) {
            sleep(1);
        }

        $output->writeln(
            sprintf('Slept %d second(s)', $this->sleepDuration - $sleepCountDown),
            OutputInterface::VERBOSITY_DEBUG
        );
    }
}
