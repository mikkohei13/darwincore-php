<?php

namespace Data\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Exception;
use Elasticsearch\ClientBuilder;



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
        $settingsIndexName = 'se-all';

        // Styles
        $header_style = new OutputFormatterStyle('white', 'green', array('bold'));
        $output->getFormatter()->setStyle('header', $header_style);

        // Connects to elasticsearch
        require_once "../darwincore-php.php";
        $client = ClientBuilder::create()->setHosts($hosts)->build();

        // Example Index Mapping
        $typeMapping = array(
            '_source' => array(
                'enabled' => true
            ),
            'properties' => array(
                'gbifid' => array(
                    'type' => 'long'
                ),
                'institutioncode' => array(
                    'type' => 'keyword',
                ),
                'class' => array(
                    'type' => 'keyword',
                ),
                'order' => array(
                    'type' => 'keyword',
                ),
                'family' => array(
                    'type' => 'keyword',
                ),
                'species' => array(
                    'type' => 'keyword',
                ),
                'species_ana' => array(
                    'type' => 'text',
                ),
/*                'countrycode' => array(
                    'type' => 'keyword',
                ),
*/                'decimallatitude' => array(
                    'type' => 'double'
                ),
                'decimallongitude' => array(
                    'type' => 'double'
                ),
                'coordinates' => array(
                    'type' => 'geo_point'
                ),
                'year' => array(
                    'type' => 'short'
                ),
                'month' => array(
                    'type' => 'short'
                ),
                'day' => array(
                    'type' => 'short'
                ),
                'date' => array(
                    'type' => 'date'
                ),
            )
        );

        $indexParams['body']['settings']['number_of_shards']   = 3;
        $indexParams['body']['settings']['number_of_replicas'] = 0;
        $indexParams['index'] = $settingsIndexName;

        $indexParams['body']['mappings']['occurrence'] = $typeMapping;

        print_r($indexParams); // debug

        $client->indices()->create($indexParams);

        // Summary
        $output->writeln('<header>Index created to host(s):</header>');
        print_r ($hosts);
    }

}