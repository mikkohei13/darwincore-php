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
    var $identifierFieldNumber = FALSE;
    var $client = FALSE;
    var $single = Array();
    const BULK_SIZE = 10000;

    var $benchmark = Array();

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

//        $output->writeln('<header>' . getcwd() . '</header>'); // debug

        $handle = fopen($fileName, 'r');

        if (! $handle)
        {
            throw new Exception('Could not open file ' . $fileName);
        }

        $this->selectFields($handle);

        $this->client = new Elasticsearch\Client();

        $i = 0;
        $skippingDone = FALSE;

        // Go through the lines
        while ($i < $end)
        {
            $startTime = microtime(TRUE);
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

            $this->benchmark['skipping'] += microtime(TRUE) - $startTime; $startTime = microtime(TRUE);

            // Handle the row
            $this->handleRow($DwCrow);

            $this->benchmark['rowHandling'] += microtime(TRUE) - $startTime; $startTime = microtime(TRUE);

//            $output->writeln('<header>' . $response . '</header>'); // debug

            // Intermediate report or end of file
            if ($i % self::BULK_SIZE == 0 || FALSE === $DwCrow)
            {
                $responses = $this->client->bulk($this->single);
                $output->writeln('<header>' . ( round((($i - $start) / $totalRows * 100), 3) ) . '% done (row ' . ( $i / 1000 ) . 'k)</header>');

//                print_r ($this->single); // debug
                unset($this->single);
                $this->single = Array();

                $this->benchmark['bulkIndexing'] += microtime(TRUE) - $startTime;
            }
            // End of file
            if (FALSE === $DwCrow)
            {
                break;
            }
        }
            
        fclose($handle);

        // Summary
        $output->writeln('<header>Finished</header>');
        print_r ($this->benchmark);
    }

    // Make conversions and index the row
    protected function handleRow($DwCrow)
    {
        // Stop if no data
        if (FALSE === $DwCrow)
        {
            return;
        }

        $startTime = microtime(TRUE);

        $data = Array();
        $params = Array();
        $missingDates = 0;

        $params['index'] = 'gbif4';
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
            if ("species" == $fieldName || "locality" == $fieldName || "issue_" == $fieldName)
            {
                $data[$fieldName . "_ana"] = $fieldValue;
            }

            // Coordinates
            if ("decimalLatitude" == $fieldName && !empty($fieldValue))
            {
                $lat = $fieldValue;
            }
            elseif ("decimalLongitude" == $fieldName && !empty($fieldValue))
            {
                $lon = $fieldValue;
            }

            // All fields, except empty
            if (!empty($fieldValue))
            {
                $data[$fieldName] = $fieldValue;
            }
//            print_r ($rowArray);
        }

        // Combined fields
        // Set coord only if both lat and lon are set
        if (!empty($data["decimalLatitude"]) && !empty($data["decimalLongitude"]))
        {
            $data['coordinates'] = $data["decimalLatitude"] . ", " . $data["decimalLongitude"];
        }
        // Set eventDate only if full date set
        if (!empty($data["year"]) && !empty($data["month"]) && !empty($data["day"]))
        {
            $data['eventDate'] = $data["year"] . "-" . $data["month"] . "-" . $data["day"] . " 00:00:00";
        }

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

        // Save into index
//        $ret = $this->client->index($params);
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

                // pick either id or gbifID as identifier
                if ("id" == $fieldName || "gbifID" == $fieldName)
                {
                    $this->identifierFieldNumber = $fieldNumber;
                }
            }
        }

        print_r ($this->selectedFields);
    }

}