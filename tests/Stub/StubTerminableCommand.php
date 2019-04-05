<?php

declare(strict_types=1);

namespace Facile\TerminableLoop\Tests\Stub;

use Facile\TerminableLoop\AbstractTerminableCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class StubTerminableCommand extends AbstractTerminableCommand
{
    public function __construct()
    {
        parent::__construct('stub:terminable:sleep');
    }

    public function configure(): void
    {
        $this->addOption('stub', null, InputOption::VALUE_REQUIRED, 'Stubbed execution duration time', 0);
        $this->addOption('sleep', null, InputOption::VALUE_REQUIRED, 'Sleep duration time', 0);
    }

    protected function commandBody(InputInterface $input, OutputInterface $output): int
    {
        $stubDuration = $this->getOption($input, 'stub');
        $sleepDuration = $this->getOption($input, 'sleep');

        if ($stubDuration) {
            $process = new Process(['sleep', $stubDuration]);
            $process->run();
        }

        $output->writeln('Stub execution terminated');

        if ($sleepDuration > 0) {
            $this->setSleepDuration($sleepDuration);
        } else {
            $output->writeln('No sleep');
        }

        return 1; //force exit from bash
    }

    /**
     * @throws \InvalidArgumentException
     */
    protected function getOption(InputInterface $input, string $name): int
    {
        $paramValue = $input->getOption($name);

        if (is_numeric($paramValue)) {
            $value = (int) $paramValue;

            if ($value < 0) {
                throw new \InvalidArgumentException('Not a positive integer value');
            }

            return $value;
        }

        throw new \InvalidArgumentException(
            'Can\'t return an int from ' . print_r($paramValue, true)
        );
    }
}
