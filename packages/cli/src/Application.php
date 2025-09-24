<?php

declare(strict_types=1);

namespace Lacus\Changesets\Cli;

use Lacus\Changesets\Cli\Commands\AddCommand;
use Lacus\Changesets\Cli\Commands\InitCommand;
use Lacus\Changesets\Cli\Commands\StatusCommand;
use Lacus\Changesets\Cli\Commands\VersionCommand;
use Symfony\Component\Console\Application as BaseApplication;

final class Application extends BaseApplication
{
    public function __construct()
    {
        parent::__construct('Changesets', '1.0.0');

        $this->addCommands([
            new InitCommand(),
            new AddCommand(),
            new StatusCommand(),
            new VersionCommand(),
        ]);
    }
}
