<?php

namespace Data\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Exception;
use Elasticsearch;


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

        $client = new Elasticsearch\Client();

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
                    'index' => 'not_analyzed'
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
                    'index' => 'not_analyzed'
                ),
                'decimalLatitude' => array(
                    'type' => 'double'
                ),
                'decimalLongitude' => array(
                    'type' => 'double'
                ),
                'coordinates' => array(
                    "type" => 'geo_point'
                ),
                'eventDate' => array(
                    'type' => 'date',
                    "format" => "yyyy-MM-dd HH:mm:ss"
                ),
                'eventDateYear' => array(
                    'type' => 'short'
                ),
                'eventDateMonth' => array(
                    'type' => 'short'
                ),
                'eventDateDay' => array(
                    'type' => 'short'
                ),
                'eventDateHour' => array(
                    'type' => 'short'
                ),
                'identifiedBy' => array(
                    'type' => 'string',
                    'index' => 'not_analyzed'
                )
            )
        );

        $indexParams['body']['settings']['number_of_shards']   = 3;
        $indexParams['body']['settings']['number_of_replicas'] = 0;
        $indexParams['index']  = 'gbif2';

        $indexParams['body']['mappings']['occurrence'] = $typeMapping;

        print_r($indexParams); // debug

        $client->indices()->create($indexParams);

        // Summary
        $output->writeln('<header>Index created</header>');
    }

}