<?php

declare(strict_types=1);

namespace Facile\TerminableLoop\Tests\E2E;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class TerminateCommandTest extends TestCase
{
    private const BASH_COMMAND = __DIR__ . '/../../bin/terminable-loop-command.sh';
    private const CONSOLE_COMMAND = __DIR__ . '/../Stub/console';
    private const STUB_COMMAND = 'stub:terminable:sleep';

    /**
     * @dataProvider commandLineProvider
     *
     * @param string[] $commandLine
     */
    public function testStubCommand(array $commandLine): void
    {
        $process = new Process($commandLine);
        $process->setTimeout(1);
        $process->enableOutput();
        $process->run();

        $this->assertCommandIsFound($process);
        $this->assertStringContainsString('Starting ' . self::STUB_COMMAND, $process->getOutput());
        $this->assertStringContainsString('No sleep', $process->getOutput());
        $this->assertSame(1, $process->getExitCode());
    }

    /**
     * @return string[][][]
     */
    public function commandLineProvider(): array
    {
        return [
            [
                [
                    self::CONSOLE_COMMAND,
                    self::STUB_COMMAND,
                    '-vvv',
                ],
            ],
            [
                [
                    self::BASH_COMMAND,
                    self::CONSOLE_COMMAND,
                    self::STUB_COMMAND,
                    '-vvv',
                ],
            ],
        ];
    }

    public function testSigTermDuringCommandBody(): void
    {
        $process = new Process([
            self::BASH_COMMAND,
            self::CONSOLE_COMMAND,
            self::STUB_COMMAND,
            '--stub=3',
            '--sleep=1',
            '-vvv',
        ]);
        $process->setTimeout(5);
        $process->enableOutput();
        $process->start();

        sleep(1);
        $process->signal(SIGTERM);

        $process->wait();

        $this->assertCommandIsFound($process);
        $this->assertStringContainsString('Starting ' . self::STUB_COMMAND, $process->getOutput());
        $this->assertStringNotContainsString('Signal received, skipping execution', $process->getOutput());
        $this->assertStringContainsString('Slept 0 second(s)', $process->getOutput());
        $this->assertSame(143, $process->getExitCode());
    }

    public function testSigTermDuringSleep(): void
    {
        $process = new Process([
            self::BASH_COMMAND,
            self::CONSOLE_COMMAND,
            self::STUB_COMMAND,
            '--stub=0',
            '--sleep=1000',
            '-vvv',
        ]);
        $process->setTimeout(5);
        $process->enableOutput();
        $process->start();

        sleep(1);
        $process->signal(SIGTERM);

        $process->wait();

        $this->assertCommandIsFound($process);
        $this->assertStringContainsString('Starting ' . self::STUB_COMMAND, $process->getOutput());
        $this->assertStringNotContainsString('Signal received, skipping execution', $process->getOutput());
        $this->assertRegExp('/Slept (0|1) second\(s\)/', $process->getOutput());
        $this->assertSame(143, $process->getExitCode());
    }

    /**
     * @param Process<string> $process
     */
    private function assertCommandIsFound(Process $process): void
    {
        $this->assertNotEquals(127, $process->getExitCode(), 'Command not found: ' . $process->getCommandLine());
    }
}
