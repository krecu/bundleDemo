<?php

namespace ZakupkiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportCommand extends ContainerAwareCommand
{
    /** @var \Symfony\Component\DependencyInjection\ContainerInterface */
    private $container = null;

    protected function configure()
    {
        $this->setName('zakupki:export');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

    }
}
