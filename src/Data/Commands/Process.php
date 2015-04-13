<?php

namespace Data\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Exception;

class Process extends Command {

    protected function configure()
    {   
        $rows = 10;
        $fileName = "demo.txt";

        $this->setName("data:process")
             ->setDescription("Process data from a file specified")
             ->setDefinition(array(
                      new InputOption('rows', 'r', InputOption::VALUE_OPTIONAL, 'Number of rows to handle', $rows),
                      new InputOption('file', 'f', InputOption::VALUE_OPTIONAL, 'Name of datafile', $fileName)
                ))
             ->setHelp(<<<EOT
Process data from a file specified

Usage:

<info>app/console data:process -r 10 -f data.txt</info>
EOT
);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Styles
        $header_style = new OutputFormatterStyle('white', 'green', array('bold'));
        $output->getFormatter()->setStyle('header', $header_style);

        // Options
        $rows = intval($input->getOption('rows'));
        $fileName  = $input->getOption('file');

        if ($rows < 0)
        {
           throw new \InvalidArgumentException('Row count must be higher than zero.');
        }

        // Process
        $output->writeln('<header>Processing data from ' . $fileName . '</header>');

        $handle = fopen($fileName, 'r');

        if (! $handle)
        {
            throw new Exception('Could not open file ' . $fileName);
        }

        $i = 0;
        while ($i < $rows)
        {
            $line = fgets($handle);
            $output->writeln('<header>'.$line.'</header>');
            $i++;
        }
            
        fclose($handle);

        // Summary
        $output->writeln('<header>Total rows = '.$i.' </header>');
    }
}