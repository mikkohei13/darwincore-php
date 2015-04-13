<?php

namespace Data\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Exception;

class Createindex extends Command {

    protected function configure()
    {   
        $this->setName("data:createindex")
             ->setDescription("Create an index")
             ->setHelp(<<<EOT
Help text

Usage:

<info>app/console data:createindex</info>
EOT
);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Styles
        $header_style = new OutputFormatterStyle('white', 'green', array('bold'));
        $output->getFormatter()->setStyle('header', $header_style);

        // Example Index Mapping
        $typeMapping = array(
            '_source' => array(
                'enabled' => true
            ),
            'properties' => array(
                'catalogNumber' => array(
                    'type' => 'integer'
                ),
                'scientificName' => array(
                    'type' => 'string',
                    'analyzer' => 'standard'
                ),
                'scientificName_exact' => array(
                    'type' => 'string',
                    'analyzer' => 'not_analyzed'
                ),
                'county' => array(
                    'type' => 'string',
                    'analyzer' => 'standard'
                ),
                'locality' => array(
                    'type' => 'string',
                    'analyzer' => 'standard'
                ),
                'locality_exact' => array(
                    'type' => 'string',
                    'analyzer' => 'not_analyzed'
                ),
                'decimalLatitude' => array(
                    'type' => 'double'
                ),
                'decimalLongitude' => array(
                    'type' => 'double'
                ),
                'eventDate' => array(
                    'type' => 'date'
                ),
                'identifiedBy' => array(
                    'type' => 'string',
                    'analyzer' => 'standard'
                ),
            )
        );

        print_r($typeMapping);

        // Summary
        $output->writeln('<header>End</header>');
    }

}