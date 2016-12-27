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

class Process extends Command {

    var $selectedFields = Array();
    var $identifierFieldNumber = FALSE;
    var $client = FALSE;
    var $dataArrayPreparedForIndexing = Array();
    var $examplePrinted = FALSE;

    const BULK_SIZE = 10000;

    var $benchmark = Array();
    var $startTime = 0;

    var $settingsIndexName = "se-all";


    protected function configure()
    {
        // Default values
        $start = 0;
        $end = 10;
        $fileName = "demo.txt";

        $this->setName("data:process")
             ->setDescription("Process data from a file specified")
             ->setDefinition(array(
                      new InputOption('start', 's', InputOption::VALUE_OPTIONAL, 'Which row to start indexing from', $start),
                      new InputOption('end', 'e', InputOption::VALUE_OPTIONAL, 'Which row to stop indexing to', $end),
                      new InputOption('file', 'f', InputOption::VALUE_OPTIONAL, 'Name of datafile', $fileName)
                ))
             ->setHelp(<<<EOT
Process data from a file specified

Usage:

<info>app/console data:process -s 10 -e 20 -f data.txt</info>
EOT
);
    }

    // ---------------------------------------------------------------------------------
    // Executes the command from terminal
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Setup
        $this->benchmark['skipping'] = 0;
        $this->benchmark['rowHandling'] = 0;
        $this->benchmark['bulkIndexing'] = 0;

        // Styles
        $header_style = new OutputFormatterStyle('white', 'green', array('bold'));
        $output->getFormatter()->setStyle('header', $header_style);

        // Options
        $start = intval($input->getOption('start'));
        $end = intval($input->getOption('end'));
        $totalRows = $end - $start;
        $fileName  = $input->getOption('file');

        // Start processing
        $output->writeln('<header>Processing data from ' . $fileName . ', running to start line on row ' . $start . '</header>');

        // Read file
        $handle = fopen($fileName, 'r');
        if (! $handle)
        {
            throw new Exception('Could not open file ' . $fileName);
        }

        // Picks firlds to process
        $this->selectFields($handle);

        // Connects to elasticsearch
        $hosts = ['http://elastic:changeme@192.168.56.10:9200'];
        $this->client = ClientBuilder::create()->setHosts($hosts)->build();

        $i = 0;
        $skippingDone = FALSE;

        // Goes through the lines
        while ($i < $end)
        {
            $this->startTime = microtime(TRUE); // benchmark

            $i++;
            $DwCrow = fgets($handle);

            // Skip lines
            if ($i < $start)
            {
                continue;
            }
            if (! $skippingDone)
            {
                $output->writeln('<header>skipped ' . $start . ' rows</header>');
                $skippingDone = TRUE;
            }

            $this->benchmark['skipping'] += microtime(TRUE) - $this->startTime; $this->startTime = microtime(TRUE); // benchmark

            // Handle the row
            $this->handleRow($DwCrow);
//            echo "Row $i handled\n";

            $this->benchmark['rowHandling'] += microtime(TRUE) - $this->startTime; $this->startTime = microtime(TRUE); // benchmark

//            echo "/" . $DwCrow . "/\n"; // DEBUG ABBA

            // Bulk threshold
            if ($i % self::BULK_SIZE == 0)
            {
                $this->bulkIndex();
                $output->writeln('<header>' . ( round((($i - $start) / $totalRows * 100), 1) ) . '% done (row ' . ( $i / 1000 ) . 'k)</header>');
            }
            // Row limit (-e) reached
            if ($i == $end)
            {
                $this->bulkIndex();
                break;
            }
            // End of file
            elseif (FALSE === $DwCrow)
            {
                $this->bulkIndex();
                break;
            }
        }
            
        fclose($handle);

        // Summary
        $output->writeln('<header>Finished</header>');
        print_r ($this->benchmark);
    }

    // ---------------------------------------------------------------------------------
    // Bulk indexes several rows of data
    protected function bulkIndex()
    {
        $responses = $this->client->bulk($this->dataArrayPreparedForIndexing);
//        echo "Responses: \n"; print_r ($responses); // DEBUG
        unset($responses);

//        echo "dataArrayPreparedForIndexing: \n"; print_r ($this->dataArrayPreparedForIndexing); // DEBUG
        unset($this->dataArrayPreparedForIndexing);
        $this->dataArrayPreparedForIndexing = Array();

        $this->benchmark['bulkIndexing'] += microtime(TRUE) - $this->startTime; // benchmark
    }

    // ---------------------------------------------------------------------------------
    // Prepare a row to be indexed
    protected function handleRow($DwCrow)
    {
        // Stop if no data
        if (FALSE === $DwCrow)
        {
            return;
        }

        $this->startTime = microtime(TRUE);

        $data = Array();
        $params = Array();
        $missingDates = 0;

        $params['index'] = $this->settingsIndexName;
        $params['type']  = "occurrence";

        $DwCrowArray = explode("\t", $DwCrow);

        $params['id'] = $DwCrowArray[$this->identifierFieldNumber];

        // Goes through each selected field. Fields are selected in the settings.php file.
        foreach ($this->selectedFields as $fieldNumber => $fieldName)
        {
            $fieldValue = $DwCrowArray[$fieldNumber];

            // Analyzed data fields
            if ("species" == $fieldName)
            {
                $data[$fieldName . "_ana"] = $fieldValue;
            }

            // All selected fields, except empty
            if (!empty($fieldValue))
            {
                $data[$fieldName] = $fieldValue;
            }
        }

        // Additional fields, which are combined from several other fields

        // Set coord only if both lat and lon are set
        if (!empty($data["decimallatitude"]) && !empty($data["decimallongitude"]))
        {
            $data['coordinates'] = $data["decimallatitude"] . "," . $data["decimallongitude"];
        }

        // Set eventDate only if full date set
        if (!empty($data["year"]) && !empty($data["month"]) && !empty($data["day"]))
        {
            $fullMonth = substr(("0" . $data["month"]), -2, 2);
            $fullDay = substr(("0" . $data["day"]), -2, 2);

            // Just in case (DEBUG)
            if (strlen($fullMonth) != 2 || strlen($fullDay) != 2)
            {
                exit("Invalid month/day: $fullMonth $fullDay");
            }

            $data['date'] = $data["year"] . "-" . $fullMonth . "-" . $fullDay;
        }

        // Setup indexing array
        // First metadata...
        $this->dataArrayPreparedForIndexing['body'][] = array(
            'index' => array(
                '_id' => $params['id'],
                "_index" => $params['index'],
                "_type" => $params['type']
            )
        );

        // ...then the data
        $this->dataArrayPreparedForIndexing['body'][] = $data;

        // Print example data if not printed yet
        if (! $this->examplePrinted)
        {
            echo "Example data prepared (first row):\n";
            print_r ($this->dataArrayPreparedForIndexing);
            $this->examplePrinted = TRUE;
        }

//        exit("TEST RUN ENDED"); //DEBUG
    }

    // ---------------------------------------------------------------------------------
    // Picks selected fields' column numbers to a variable
    protected function selectFields($handle)
    {
        require_once "settings.php";

        $fileFieldsRow = fgets($handle);
        $fileFieldsArray = explode("\t", $fileFieldsRow);
        print_r ($fileFieldsArray); // DEBUG

        foreach ($fileFieldsArray as $fieldNumber => $fieldName)
        {
            if (@$settingsSelectedFields[$fieldName] == TRUE)
            {
                $this->selectedFields[$fieldNumber] = $fieldName;

                // Pick identifier column number also to a separate variable
                if ("gbifid" == $fieldName)
                {
                    $this->identifierFieldNumber = $fieldNumber;
                }
            }
        }

        echo "Selected fields in this dataset:\n";
        print_r ($this->selectedFields);
    }

    // ---------------------------------------------------------------------------------

}