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
        // Process
        $output->writeln('<header>Processing data from ' . $fileName . ', running to start line on row ' . $start . '</header>');

//        $output->writeln('<header>' . getcwd() . '</header>');

        $handle = fopen($fileName, 'r');

        if (! $handle)
        {
            throw new Exception('Could not open file ' . $fileName);
        }

        $this->selectFields($handle);

        $this->client = new Elasticsearch\Client();

        $i = 0;
        $skippingDone = FALSE;

        while ($i < $end)
        {
            $i++;
            $DwCrow = fgets($handle);
            if ($i < $start)
            {
                continue;
            }
            if (! $skippingDone)
            {
                $output->writeln('<header>skipped ' . $start . ' rows</header>');
                $skippingDone = TRUE;
            }

            $this->handleRow($DwCrow);

//            $output->writeln('<header>' . $response . '</header>');
            if ($i % 10000 == 0)
            {
                $output->writeln('<header>' . ( round((($i - $start) / $totalRows * 100), 2) ) . '% done (row ' . ( $i / 1000 ) . 'k)</header>');
            }
        }
            
        fclose($handle);

        // Summary
        $output->writeln('<header>Finished on row ' . $end . '</header>');
    }

    protected function handleRow($DwCrow)
    {
        $data = Array();
        $params = Array();
        $missingDates = 0;
        $lat = FALSE;
        $lon = FALSE;

        $params['index'] = 'gbif2';
        $params['type']  = 'occurrence';

        $DwCrowArray = explode("\t", $DwCrow);

        $params['id'] = $DwCrowArray[$this->catalogNumberFieldNumber];

        // Goes through each selected field
        foreach ($this->selectedFields as $fieldNumber => $fieldName)
        {
            $fieldValue = $DwCrowArray[$fieldNumber];
//            $html .= $fieldName . ": " . $rowArray[$fieldNumber] . "\n";

            // Date
            if ("eventDate" == $fieldName)
            {
                if (empty($fieldValue))
                {
                    // Add nothing
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
            // Coordinates
            elseif ("decimalLatitude" == $fieldName && !empty($fieldValue))
            {
                $lat = $fieldValue;
            }
            elseif ("decimalLongitude" == $fieldName && !empty($fieldValue))
            {
                $lon = $fieldValue;
            }
            // All other fields
            else
            {
                $data[$fieldName] = $fieldValue;
            }

            // Duplicate data fields
            if ("scientificName" == $fieldName)
            {
                $data["scientificName_exact"] = $fieldValue;
            }
//            print_r ($rowArray);
        }

        // Set coord only if both lat and lon are set
        if ($lat && $lon)
        {
            $data['coordinates'] = $lat . ", " . $lon;
        }

        $params['body']  = $data;

        // Save into index
        $ret = $this->client->index($params);

//        print_r($params);
//        print_r($ret);
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