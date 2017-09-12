<?php

namespace Openbuild;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class Menu extends Command
{

    use ShowMenu;

    protected function configure()
    {
        //// the name of the command (the part after "bin/console")
        $this->setName('app:menu');

        // the short description shown while running "php bin/console list"
        $this->setDescription('Shows the menu.');

        // the full command description shown when running the command with
        // the "--help" option
        $this->setHelp('Shows the main menu');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->ShowMenu($input, $output);
    }

}