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
                'id' => array(
                    'type' => 'string',
                    'index' => 'not_analyzed'
                ),
                'gbifID' => array(
                    'type' => 'long'
                ),
                'institutionCode' => array(
                    'type' => 'string',
                    'index' => 'not_analyzed'
                ),
                'collectionCode' => array(
                    'type' => 'string',
                    'index' => 'not_analyzed'
                ),
                'publishingCountry' => array(
                    'type' => 'string',
                    'index' => 'not_analyzed'
                ),
                'kingdom' => array(
                    'type' => 'string',
                    'index' => 'not_analyzed'
                ),
                'phylum' => array(
                    'type' => 'string',
                    'index' => 'not_analyzed'
                ),
                'class' => array(
                    'type' => 'string',
                    'index' => 'not_analyzed'
                ),
                'order' => array(
                    'type' => 'string',
                    'index' => 'not_analyzed'
                ),
                'family' => array(
                    'type' => 'string',
                    'index' => 'not_analyzed'
                ),
                'genus' => array(
                    'type' => 'string',
                    'index' => 'not_analyzed'
                ),
                'species_ana' => array(
                    'type' => 'string',
                    'analyzer' => 'standard'
                ),
                'species' => array(
                    'type' => 'string',
                    'index' => 'not_analyzed'
                ),
                'scientificNameAuthorship' => array(
                    'type' => 'string',
                    'index' => 'not_analyzed'
                ),
                'taxonRank' => array(
                    'type' => 'string',
                    'index' => 'not_analyzed'
                ),
                'continent' => array(
                    'type' => 'string',
                    'index' => 'not_analyzed'
                ),
                'countryCode' => array(
                    'type' => 'string',
                    'index' => 'not_analyzed'
                ),
                'stateProvince' => array(
                    'type' => 'string',
                    'index' => 'not_analyzed'
                ),
                'county' => array(
                    'type' => 'string',
                    'index' => 'not_analyzed'
                ),
                'municipality' => array(
                    'type' => 'string',
                    'index' => 'not_analyzed'
                ),
                'locality_ana' => array(
                    'type' => 'string',
                    'analyzer' => 'standard'
                ),
                'locality' => array(
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
                 'coordinateUncertaintyInMeters' => array(
                    'type' => 'integer'
                ),
                'geodeticDatum' => array(
                    'type' => 'string',
                    'index' => 'not_analyzed'
                ),
               'eventDate' => array(
                    'type' => 'date',
                    "format" => "yyyy-MM-dd HH:mm:ss"
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
                'identifiedBy' => array(
                    'type' => 'string',
                    'index' => 'not_analyzed'
                ),
                'hasCoordinate' => array(
                    'type' => 'string',
                    'index' => 'not_analyzed'
                ),
                'hasGeospatialIssues' => array(
                    'type' => 'string',
                    'index' => 'not_analyzed'
                ),
                'issue_ana' => array(
                    'type' => 'string',
                    'analyzer' => 'standard'
                ),
            )
        );

        $indexParams['body']['settings']['number_of_shards']   = 3;
        $indexParams['body']['settings']['number_of_replicas'] = 0;
        $indexParams['index']  = 'gbif5';

        $indexParams['body']['mappings']['occurrence'] = $typeMapping;

        print_r($indexParams); // debug

        $client->indices()->create($indexParams);

        // Summary
        $output->writeln('<header>Index created</header>');
    }

}