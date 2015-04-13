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

class Process extends Command {

    var $selectedFields = Array();
    var $catalogNumberFieldNumber = FALSE;
    var $client = FALSE;

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

//        $output->writeln('<header>' . getcwd() . '</header>');

        $handle = fopen($fileName, 'r');

        if (! $handle)
        {
            throw new Exception('Could not open file ' . $fileName);
        }

        $this->selectFields($handle);

        $this->client = new Elasticsearch\Client();

        $i = 0;
        while ($i < $rows)
        {
            $DwCrow = fgets($handle);
            $response = $this->handleRow($DwCrow);

//            $output->writeln('<header>' . $response . '</header>');
            $i++;
        }
            
        fclose($handle);

        // Summary
        $output->writeln('<header>Total rows = '.$i.' </header>');
    }

    protected function handleRow($DwCrow)
    {
        $html = "";
        $data = Array();
        $params = Array();
        $params['index'] = 'gbif';
        $params['type']  = 'occurrence';

        $DwCrowArray = explode("\t", $DwCrow);

        $params['id'] = $DwCrowArray[$this->catalogNumberFieldNumber];

        foreach ($this->selectedFields as $fieldNumber => $fieldName)
        {
//            $html .= $fieldName . ": " . $rowArray[$fieldNumber] . "\n";
            
            $data[$fieldName] = $DwCrowArray[$fieldNumber];

            // Duplicate data fields
            if ("scientificName" == $fieldName)
            {
                $data["scientificName_exact"] = $DwCrowArray[$fieldNumber];
            }
//            print_r ($rowArray);
        }
        $params['body']  = $data;

        $ret = $this->client->index($params);

//        print_r($params);
//        print_r($ret);

        return $html . "\n------------------------------------\n";
    }

    protected function selectFields($handle)
    {
        include_once "settings.php";

        $fileFieldsRow = fgets($handle);
        $fileFieldsArray = explode("\t", $fileFieldsRow);
//        print_r ($fileFieldsArray);

        foreach ($fileFieldsArray as $fieldNumber => $fieldName)
        {
            if (@$settingsSelectedFields[$fieldName] == TRUE)
            {
                $this->selectedFields[$fieldNumber] = $fieldName;
                if ("catalogNumberField" == $fieldName)
                {
                    $this->catalogNumberFieldNumber = $fieldNumber;
                }
            }
        }

        print_r ($this->selectedFields);
    }

}