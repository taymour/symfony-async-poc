<?php

namespace App\Command;

use App\Creator\ArticleCreatorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:create-article',
    description: 'Creates an article asynchronously',
)]
class CreateArticleCommand extends Command
{
    public function __construct(
        private ArticleCreatorInterface $articleCreator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('title', InputArgument::OPTIONAL, 'Title of the article');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $title = $input->getArgument('title') ?? 'Default Title';

        $output->writeln('<info>Starting asynchronous article creation...</info>');

        $start = microtime(true);
        $this->articleCreator->create($title);
        $duration = round((microtime(true) - $start) * 1000);

        $output->writeln("<comment>Command finished after {$duration} ms</comment>");
        $output->writeln('<info>The article will actually be created in the background within a few seconds.</info>');
        $output->writeln('<info>Check the file var/log/article_async.log to confirm when it was created.</info>');

        return Command::SUCCESS;
    }
}
