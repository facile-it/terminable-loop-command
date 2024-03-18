<?php

declare(strict_types=1);

namespace Facile\TerminableLoop\Tests\Unit;

use Facile\TerminableLoop\AbstractTerminableCommand;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Bridge\PhpUnit\ClockMock;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AbstractTerminableCommandTest extends TestCase
{
    use ProphecyTrait;

    public static function setUpBeforeClass(): void
    {
        ClockMock::register(AbstractTerminableCommand::class);
    }

    protected function tearDown(): void
    {
        ClockMock::withClockMock(false);
    }

    public function testSetSleepDuration(): void
    {
        $stubCommand = new class ('dummy:command') extends AbstractTerminableCommand {
            protected function commandBody(InputInterface $input, OutputInterface $output): int
            {
                $this->setSleepDuration(100);

                $output->writeln('Testing getSleepDuration: ' . $this->getSleepDuration());

                return 0;
            }
        };

        $output = $this->prophesize(OutputInterface::class);
        $output->writeln(Argument::containingString('Starting'), OutputInterface::VERBOSITY_VERBOSE)
            ->shouldBeCalledTimes(1);
        $output->writeln('Testing getSleepDuration: 100')
            ->shouldBeCalledTimes(1);
        $output->writeln('Slept 100 second(s)', OutputInterface::VERBOSITY_DEBUG)
            ->shouldBeCalledTimes(1);

        ClockMock::withClockMock(true);
        $start = ClockMock::time();
        $exitCode = $stubCommand->run(new ArrayInput([]), $output->reveal());
        $end = ClockMock::time();

        $this->assertSame(0, $exitCode);
        $this->assertGreaterThanOrEqual(99, $end - $start);
        $this->assertLessThanOrEqual(100, $end - $start);
    }

    public function testSetSleepDurationWithNegativeValue(): void
    {
        $stubCommand = new class ('dummy:command') extends AbstractTerminableCommand {
            protected function commandBody(InputInterface $input, OutputInterface $output): int
            {
                $this->setSleepDuration(-1);

                return 0;
            }
        };

        $output = $this->prophesize(OutputInterface::class);
        $output->writeln(Argument::containingString('Starting'), OutputInterface::VERBOSITY_VERBOSE)
            ->shouldBeCalledTimes(1);

        $this->expectException(\InvalidArgumentException::class);

        $stubCommand->run(new ArrayInput([]), $output->reveal());
    }

    /**
     * @dataProvider signalProvider
     */
    public function testReceiveSignalDuringCommandBody(int $signal): void
    {
        $stubCommand = new class ($signal) extends AbstractTerminableCommand {
            /** @var int */
            private $signal;

            public function __construct(int $signal)
            {
                parent::__construct('dummy:command');
                $this->signal = $signal;
            }

            protected function commandBody(InputInterface $input, OutputInterface $output): int
            {
                $this->handleSignal($this->signal);

                return 0;
            }
        };

        $output = $this->prophesize(OutputInterface::class);
        $output->writeln(Argument::containingString('Starting'), OutputInterface::VERBOSITY_VERBOSE)
            ->shouldBeCalledTimes(1);
        $output->writeln('Signal received, terminating with exit code 143', OutputInterface::VERBOSITY_NORMAL)
            ->shouldBeCalledTimes(1);

        $exitCode = $stubCommand->run(new ArrayInput([]), $output->reveal());

        $this->assertSame(143, $exitCode);
    }

    /**
     * @dataProvider signalProvider
     */
    public function testReceiveSignalBeforeCommandBody(int $signal): void
    {
        $stubCommand = new class ('dummy:command') extends AbstractTerminableCommand {
            protected function commandBody(InputInterface $input, OutputInterface $output): int
            {
                return 0;
            }
        };

        $output = $this->prophesize(OutputInterface::class);
        $output->writeln(Argument::containingString('Starting'), OutputInterface::VERBOSITY_VERBOSE)
            ->shouldBeCalledTimes(1)
            ->will(function () use ($stubCommand, $signal) {
                $stubCommand->handleSignal($signal);
            });
        $output->writeln('Signal received, skipping execution', OutputInterface::VERBOSITY_NORMAL)
            ->shouldBeCalledTimes(1);

        $exitCode = $stubCommand->run(new ArrayInput([]), $output->reveal());

        $this->assertSame(143, $exitCode);
    }

    /**
     * @return array{0: int}[]
     */
    public function signalProvider(): array
    {
        return [
            [SIGINT],
            [SIGTERM],
        ];
    }
}
