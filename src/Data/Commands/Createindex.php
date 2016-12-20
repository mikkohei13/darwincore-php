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
        // Styles
        $header_style = new OutputFormatterStyle('white', 'green', array('bold'));
        $output->getFormatter()->setStyle('header', $header_style);

        $hosts = ['http://elastic:changeme@192.168.56.10:9200'];
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
                'species' => array(
                    'type' => 'keyword',
                ),
                'species_ana' => array(
                    'type' => 'text',
                ),
                'countrycode' => array(
                    'type' => 'keyword',
                ),
                'decimallatitude' => array(
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
        $indexParams['index']  = 'gbif-test';

        $indexParams['body']['mappings']['occurrence'] = $typeMapping;

        print_r($indexParams); // debug

        $client->indices()->create($indexParams);

        // Summary
        $output->writeln('<header>Index created</header>');
    }

}