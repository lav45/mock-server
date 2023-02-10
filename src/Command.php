<?php

namespace lav45\MockServer;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Command
 * @package lav45\MockServer
 */
class Command extends \Symfony\Component\Console\Command\Command
{
    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this
            ->setName('start')
            ->addOption('host', null, InputOption::VALUE_OPTIONAL, "Host", '0.0.0.0')
            ->addOption('port', null, InputOption::VALUE_OPTIONAL, "Port", 8080)
            ->addOption('mocks', null, InputOption::VALUE_OPTIONAL, "Mocks path", '/mocks');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $scanner = new Scanner($input->getOption('mocks'));

        (new Server())
            ->setHost($input->getOption('host'))
            ->setPort($input->getOption('port'))
            ->setMocks($scanner->get())
            ->start();

        return 0;
    }
}
