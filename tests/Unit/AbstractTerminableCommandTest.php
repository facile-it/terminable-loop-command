<?php

declare(strict_types=1);

namespace Facile\TerminableLoop\Tests\Unit;

use Facile\TerminableLoop\AbstractTerminableCommand;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AbstractTerminableCommandTest extends TestCase
{
    public function testSetSleepDuration(): void
    {
        $stubCommand = new class() extends AbstractTerminableCommand {
            protected function commandBody(InputInterface $input, OutputInterface $output): int
            {
                return 0;
            }
        };

        $input = $this->prophesize(InputInterface::class);
        $output = $this->prophesize(OutputInterface::class);
        $output->writeln(Argument::containingString('Starting'), OutputInterface::VERBOSITY_VERBOSE)
            ->shouldBeCalledTimes(1);
        $output->writeln('Slept 1 second(s)', OutputInterface::VERBOSITY_DEBUG)
            ->shouldBeCalledTimes(1);

        $exitCode = $stubCommand->run($input->reveal(), $output->reveal());

        $this->assertSame(0, $exitCode);
    }
}
