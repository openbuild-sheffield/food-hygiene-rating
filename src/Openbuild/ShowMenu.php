<?php

namespace Openbuild;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

trait ShowMenu {

    public function ShowMenu(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');

        $question = new ChoiceQuestion(
            'Please select an option (defaults to download)',
            array('Quit', 'Scheme Types', 'Ratings', 'Locations', 'Authorities', 'Score Descriptors', 'Business Types', 'Establishments'),
            0
        );

        $question->setErrorMessage('Option %s is invalid.');

        $option = $helper->ask($input, $output, $question);
        $output->writeln('You have selected: '.$option);

        if($option === 'Quit')
        {
            exit();
        }

        $option = str_replace(' ', '', $option);

        $command = $this->getApplication()->find('app:' . $option);

        $arguments = array(
            'command' => 'app:' . $option
        );

        $commandInput = new ArrayInput($arguments);
        $returnCode = $command->run($commandInput, $output);

    }

}