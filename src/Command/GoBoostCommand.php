<?php

namespace App\Command;

use App\Go\AsyncRunner;
use App\Go\Attribute\Async;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'go:boost',
    description: 'Add a short description for your command',
)]
class GoBoostCommand extends Command
{
    public function __construct(private AsyncRunner $async)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('service', null, InputOption::VALUE_REQUIRED)
            ->addOption('method',  null, InputOption::VALUE_REQUIRED)
            ->addOption('params',  null, InputOption::VALUE_REQUIRED)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $service = $input->getOption('service');
        $method = $input->getOption('method');
        $params = explode(',', $input->getOption('params'));

        $this->async->run(
            service: $service,
            method: $method,
            params: $params,
        );

        return Command::SUCCESS;
    }
}
