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
    var $single = Array();
    var $examplePrinted = FALSE;

    const BULK_SIZE = 10000;

    var $benchmark = Array();
    var $startTime = 0;

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

    protected function execute(InputInterface $input, OutputInterface $output)
    {

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

/*
        if ($rows < 0)
        {
           throw new \InvalidArgumentException('Row count must be higher than zero.');
        }
*/
        // Start processing
        $output->writeln('<header>Processing data from ' . $fileName . ', running to start line on row ' . $start . '</header>');

//        $output->writeln('<header>' . getcwd() . '</header>'); // DEBUG

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

            // Row limit (-e) reached
            if ($i == $end)
            {
                $this->bulkIndex($output);
                break;
            }
            // Bulk threshold
            if ($i % self::BULK_SIZE == 0)
            {
                $this->bulkIndex($output);
            }
            // End of file
            elseif (FALSE === $DwCrow)
            {
                $this->bulkIndex($output);
                break;
            }
        }
            
        fclose($handle);

        // Summary
        $output->writeln('<header>Finished</header>');
        print_r ($this->benchmark);
    }

    protected function bulkIndex(OutputInterface $output)
    {
        $responses = $this->client->bulk($this->single);
        echo "Responses: \n"; print_r ($responses); // DEBUG
        unset($responses);

        $output->writeln('<header>' . ( round((($i - $start) / $totalRows * 100), 3) ) . '% done (row ' . ( $i / 1000 ) . 'k)</header>');

        echo "Single: \n"; print_r ($this->single); // DEBUG
        unset($this->single);
        $this->single = Array();

        $this->benchmark['bulkIndexing'] += microtime(TRUE) - $this->startTime; // benchmark
    }

    // Make conversions and index the row
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

        $params['index'] = 'gbif5';
        $params['type']  = 'occurrence';

        $DwCrowArray = explode("\t", $DwCrow);

        $params['id'] = $DwCrowArray[$this->identifierFieldNumber];

        // Goes through each selected field
        foreach ($this->selectedFields as $fieldNumber => $fieldName)
        {

            $fieldValue = $DwCrowArray[$fieldNumber];
//            $html .= $fieldName . ": " . $rowArray[$fieldNumber] . "\n";

            // Date
            /*
            if ("eventDate" == $fieldName)
            {
                if (empty($fieldValue))
                {
                    // Don't add empty date
                }
                else
                {
                    $data['eventDate'] = $fieldValue;

                    // Presume format "yyyy-MM-dd HH:mm:ss"
                    $temp = explode(" ", $fieldValue);
                    $dateParts = explode("-", $temp[0]);
                    $timeParts = explode(":", $temp[1]);

                    $data['eventDateYear'] = $dateParts[0];
                    $data['eventDateMonth'] = $dateParts[1];
                    $data['eventDateDay'] = $dateParts[2];
                    $data['eventDateHour'] = $timeParts[0];
                }
            }
            */

            // Analyzed data fields
            if ("species" == $fieldName)
            {
                $data[$fieldName . "_ana"] = $fieldValue;
            }

            // Coordinates
            /*
            if ("decimallatitude" == $fieldName && !empty($fieldValue))
            {
                $lat = $fieldValue;
            }
            elseif ("decimallongitude" == $fieldName && !empty($fieldValue))
            {
                $lon = $fieldValue;
            }
            */

            // All fields, except empty
            if (!empty($fieldValue))
            {
                $data[$fieldName] = $fieldValue;
            }

//            print_r ($rowArray);
        }

        // Combined fields
        // Set coord only if both lat and lon are set
        if (!empty($data["decimallatitude"]) && !empty($data["decimallongitude"]))
        {
            $data['coordinates'] = $data["decimallatitude"] . "," . $data["decimallongitude"];
        }

        // Set eventDate only if full date set
        if (!empty($data["year"]) && !empty($data["month"]) && !empty($data["day"]))
        {
            $data['date'] = $data["year"] . "-" . $data["month"] . "-" . $data["day"];
        }
        /*
        // Try to parse eventDate
        elseif (!empty($data['eventDate']))
        {
//            echo "Parsing date: " . $data['eventDate'] . " -> "; // DEBUG

            $dateBeginAndEnd = explode("/", $data['eventDate']);
            $dateAndTime = explode("T", $dateBeginAndEnd[0]);
            $dateAndTime = explode(" ", $dateAndTime[0]);
            $dateParts = explode("-", $dateAndTime[0]);

            unset($data['eventDate']);

            if (strlen($dateParts[0]) == 4)
            {
                @$data['eventDate'] = $dateParts[0] . "-" . $dateParts[1] . "-" . $dateParts[2];
            }
            else
            {
                @$data['eventDate'] = $dateParts[2] . "-" . $dateParts[1] . "-" . $dateParts[0];
            }
            $data['eventDate'] = trim($data['eventDate'], "-");

//            echo $data['eventDate'] . " \n"; // DEBUG
        }
        */

        $params['body']  = $data;

        // TODO: do this only once
        $this->single['body'][] = array(
            'index' => array(
                '_id' => $params['id'],
                "_index" => $params['index'],
                "_type" => $params['type']
            )
        );

        $this->single['body'][] = $params['body'];

        // Print example data of first line
        if (! $this->examplePrinted)
        {
            echo "Example data prepared:\n";
            print_r ($this->single);
            $this->examplePrinted = TRUE;
        }

//        exit("TEST RUN ENDED"); //DEBUG

        // Save single record into index
//        $ret = $this->client->index($params);
    }

    // Picks selected fields column numbers to a variable
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

}