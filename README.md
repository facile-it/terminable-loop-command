# facile-it/terminable-loop-command

[![Latest Stable Version](https://poser.pugx.org/facile-it/terminable-loop-command/version)](https://packagist.org/packages/facile-it/terminable-loop-command)
[![Latest Unstable Version](https://poser.pugx.org/facile-it/terminable-loop-command/v/unstable)](//packagist.org/packages/facile-it/terminable-loop-command)
[![Build Status](https://travis-ci.org/facile-it/terminable-loop-command.svg?branch=master)](https://travis-ci.org/facile-it/terminable-loop-command)
[![Coverage Status](https://coveralls.io/repos/github/facile-it/terminable-loop-command/badge.svg?branch=master)](https://coveralls.io/github/facile-it/terminable-loop-command?branch=master)

A Shell+PHP combination to run Symfony console commands in loop under a daemon or Kubernetes, instead of using a long running process.

This package contains a *shell script* and an *abstract Symfony Console Command class*; you need to write your command extending that class, and launch it through the shell script. Ideally, the script has to be used as a container entry point, and/or launched with a supervisor, like Docker Compose, Kubernetes, `supervisord`.

## Installation
```bash
composer require facile-it/terminable-loop-command
```

## Usage
Launch the shell script appending the desired PHP script to be executed in a loop:
```bash
vendor/bin/terminable-loop-command.sh my_custom_command.php
```
... where `my_custom_command.php` launches your command class, which must extend `AbstractTerminableCommand` (see the [test stub in this repo](https://github.com/facile-it/terminable-loop-command/blob/master/tests/Stub/StubTerminableCommand.php)) 

### Example with a command in a common Symfony app 
When using it inside a Symfony application, do not forget to call `bin/console` as first argument:
```bash
vendor/bin/terminable-loop-command.sh bin/console my:command --optionA
```
... where the command is something like this:
```php
<?php

namespace Acme\Command;

use Facile\TerminableLoop\AbstractTerminableCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MyCommand extends AbstractTerminableCommand
{
    public function __construct()
    {
        parent::__construct('my:command');
    }

    protected function commandBody(InputInterface $input, OutputInterface $output): int
    {
        $this->setSleepDuration(60);

        // do something
        
        if (! $this->shouldSleep()) {
            // you can customize sleep duration during execution, even conditionally
            $this->setSleepDuration(0); 
        }

        return 0;
    }
}
```

## Why?
When running a PHP application, you may encounter the need of a *background command* that runs continuously. You can try to write it as a long running process, but it can be prone to memory leaks and other issues.

With this small Shell+PHP combination, you can have a simple loop that:

 - starts the command
 - does something
 - sleeps for a custom amount of time
 - shuts down and restarts back again

The shell script intercepts SIGTERM/SIGKILL signals so, when they are received, the PHP script is stopped ASAP but gracefully, since the execution of the body of the command is never truncated.

This means that you can easily obtain *a daemon PHP script without running in memory issues*; if you run this *in a Kubernetes environment this will be very powerful*, since the orchestrator will take care of running the script, and at the same time it will apply the [proper restart policies](https://kubernetes.io/docs/concepts/workloads/pods/pod-lifecycle/#restart-policy) in case of crashes. Last but not least, the signal handling will play nice with shutdown requests, like during the roll out of a new deployment.

## How it works
The shell script is pretty basic, calling the desired command in a loop, until it exits with an exit code different than `0`; it also traps SIGTERM/SIGKILL signals and forwards them to the PHP process.

The PHP Command is designed to first execute a main task (the `AbstractTerminableCommand::commandBody()` function) and afterwards sleep for a custom amount of time, which can be customized at any time during the command execution; this is powerful since you can let your command logic decide how much to wait between two command executions, even nothing.
 
The PHP class also gracefully handles the signals, which means that if the signal is received during the `commandBody()` function, it will wait for its conclusion; if it's received during the sleep phase, it will terminate it immediately. In case of termination due to signal, the command exits with and exit code of `143`, which [means exactly that we're exiting due to the signal](https://stackoverflow.com/questions/25304728/c-application-terminates-with-143-exit-code-what-does-it-mean): this will interrupt the shell script loop execution, without being considered as an error from the point of view of the supervising agent, like Kubernetes.
