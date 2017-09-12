<?php

namespace Openbuild;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Locations extends Command
{

    use ShowMenu;

    private $fileHandle;

    private function writePre()
    {
        copy('/templates/Locations.postgres.pre.sql', '/export/003.Locations.sql');
        $this->fileHandle = fopen('/export/004.Locations.sql', 'a+');
    }

    private function writeData($string)
    {
        fwrite($this->fileHandle, $string);
    }

    private function writePost()
    {
        fwrite($this->fileHandle, file_get_contents('/templates/Locations.postgres.post.sql'));
        fclose($this->fileHandle);
    }

    protected function configure()
    {
        //// the name of the command (the part after "bin/console")
        $this->setName('app:Locations');

        // the short description shown while running "php bin/console list"
        $this->setDescription('Generates Locations.');

        // the full command description shown when running the command with
        // the "--help" option
        $this->setHelp('Downloads Locations data and creates data files.');

        $this->addOption(
            'autoquit',
            false,
            InputOption::VALUE_OPTIONAL,
            'Should we quit after running?',
            false
        );

    }

    public function execute(InputInterface $input, OutputInterface $output)
    {

        $output->write("\n\n");

        $output->write("Copying Locations templates.\n");

        $this->writePre();

        $output->write("Fetching Locations.\n");

        $bar = new ProgressBar($output, 100);
        $bar->setFormat('[%bar%] Fetching Locations. %percent%% %download%');
        $bar->setMessage('', 'percent');
        $bar->setMessage('', 'download');
        $bar->start();

        $client = new \GuzzleHttp\Client([
            'base_uri' => 'http://api.ratings.food.gov.uk',
            'headers' => ['x-api-version' => 2, 'accept' => 'text/json'],
            'progress' => function($downloadTotal, $downloadedBytes, $uploadTotal, $uploadedBytes) use ($bar) {
                $bar->setMessage("Downloading $downloadedBytes of $downloadTotal bytes", 'download');
            }
        ]);

        $promises = [];
        $promises['Countries'] = $client->getAsync('/Countries');
        $promises['Regions'] = $client->getAsync('/Regions');

        // Wait for the requests to complete, even if some of them fail
        $results = \GuzzleHttp\Promise\settle($promises)->wait();

        $bar->finish();

        $output->write("\n");

        $sqlCountry = "INSERT INTO app_public.fhr_country (uuid, id, name, name_key) VALUES ((SELECT uuid_generate_v5(uuid_ns_url(), ('http://api.ratings.food.gov.uk/Countries/' || '%s'))), %d, '%s', '%s');\n";
        $sqlRegion  = "INSERT INTO app_public.fhr_region (uuid, id, name, name_key, code, country_id) VALUES ((SELECT uuid_generate_v5(uuid_ns_url(), ('http://api.ratings.food.gov.uk/Regions/' || '%s'))), %d, '%s', '%s', '%s', %d);\n";

        $handle = fopen('/export/003.Locations.sql', 'a+');

        foreach($results AS $key => $result){

            if($result['state'] == 'rejected'){

                //FIXME - why was it rejected???
                $output->write("Failed to download $key - rejected.\n");
                exit(1);

            }elseif($result['value']->getStatusCode() === 200){

                $results[$key] = json_decode($result['value']->getBody());

            }else{

                $output->write("Failed to download $key - invalid status.\n");
                exit(1);

            }

        }

        foreach($results['Countries']->countries AS $country){
            $countryIds[$country->name] = $country->id;
            $this->writeData(sprintf($sqlCountry, $country->nameKey, $country->id, $country->name, $country->nameKey));
        }

        $this->writeData("\n");

        foreach($results['Regions']->regions AS $region){
            $countryId = isset($countryIds[$region->nameKey]) ? $countryIds[$region->nameKey] : $countryIds['England'];
            $this->writeData(sprintf($sqlRegion, $region->nameKey, $region->id, $region->name, $region->nameKey, $region->code, $countryId));
        }

        $this->writePost();

        $output->write("\nCompleted Locations\n\n");

        if($input->getOption('autoquit') === false){
            $this->ShowMenu($input, $output);
        }else{
            exit();
        }

    }

}