# facile-it/terminable-loop-command

[![Latest Stable Version](https://poser.pugx.org/facile-it/terminable-loop-command/version)](https://packagist.org/packages/facile-it/terminable-loop-command)
[![Latest Unstable Version](https://poser.pugx.org/facile-it/terminable-loop-command/v/unstable)](//packagist.org/packages/facile-it/terminable-loop-command)
[![Build Status](https://travis-ci.org/facile-it/terminable-loop-command.svg?branch=master)](https://travis-ci.org/facile-it/terminable-loop-command)
[![Coverage Status](https://coveralls.io/repos/github/facile-it/terminable-loop-command/badge.svg?branch=master)](https://coveralls.io/github/facile-it/terminable-loop-command?branch=master)

A Shell+PHP wrapper to run Symfony console commands in loop under a daemon or Kubernetes

## Installation
```bash
composer require facile-it/terminable-loop-command
```

## Usage
```bash
vendor/bin/terminable-loop-command.sh my_custom_command.php
```
... where `my_custom_command.php` launches your command class, which must extend `AbstractTerminableCommand` (see the [test stub in this repo](https://github.com/facile-it/terminable-loop-command/blob/master/tests/Stub/StubTerminableCommand.php)) 

### Example with a command in a common Symfony app 

```bash
vendor/bin/terminable-loop-command.sh bin/console my:command
```
... where the command is:
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
When running a PHP application, you may encounter the need of running a persistent daemon command. You can try to run and endless command, but it can be prone to memory leaks and other issues.

With this small Shell+PHP wrapper, you can have a simple loop that:

 - starts the command
 - does something 
 - sleeps a custom amount of time

If a SIGTERM is received, the execution is stopped ASAP, but the body of the command is never truncated.
