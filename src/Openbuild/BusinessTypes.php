<?php

namespace Openbuild;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BusinessTypes extends Command
{

    use ShowMenu;

    private $fileHandle;

    private function writePre()
    {
        copy('/templates/BusinessTypes.postgres.pre.sql', '/export/006.BusinessTypes.sql');
        $this->fileHandle = fopen('/export/006.BusinessTypes.sql', 'a+');
    }

    private function writeData($string)
    {
        fwrite($this->fileHandle, $string);
    }

    private function writePost()
    {
        fwrite($this->fileHandle, file_get_contents('/templates/BusinessTypes.postgres.post.sql'));
        fclose($this->fileHandle);
    }

    protected function configure()
    {
        //// the name of the command (the part after "bin/console")
        $this->setName('app:BusinessTypes');

        // the short description shown while running "php bin/console list"
        $this->setDescription('Generates Business Types.');

        // the full command description shown when running the command with
        // the "--help" option
        $this->setHelp('Downloads Business Types data and creates data files.');

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

        $output->write("Copying BusinessTypes templates.\n");

        $this->writePre();

        $output->write("Fetching BusinessTypes.\n");

        $bar = new ProgressBar($output, 100);
        $bar->setFormat('[%bar%] Fetching Business Types. %percent%% %download%');
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
        $promises['BusinessTypes'] = $client->getAsync('/BusinessTypes');

        // Wait for the requests to complete, even if some of them fail
        $results = \GuzzleHttp\Promise\settle($promises)->wait();

        $bar->finish();

        $output->write("\n");

        $sql = "INSERT INTO app_public.fhr_business_type (uuid, business_type_id, business_type_name) VALUES ((SELECT uuid_generate_v5(uuid_ns_url(), ('http://api.ratings.food.gov.uk/BusinessTypes/' || '%s'))), %d, '%s');\n";

        $handle = fopen('/export/006.BusinessTypes.sql', 'a+');

        foreach($results AS $key => $result){

            if($result['state'] == 'rejected'){

                //FIXME - why was it rejected???
                $output->write("Failed to download $key - rejected.\n");
                exit(1);

            }elseif($result['value']->getStatusCode() === 200){

                $data = json_decode($result['value']->getBody());

                foreach($data->businessTypes AS $businessType){
                    $insert = sprintf($sql, $businessType->BusinessTypeName, $businessType->BusinessTypeId, $businessType->BusinessTypeName);
                    $this->writeData($insert);
                }

            }else{
                $output->write("Failed to download $key - invalid status.\n");
                exit(1);
            }

        }

        $this->writePost();

        $output->write("\nCompleted BusinessTypes\n\n");

        if($input->getOption('autoquit') === false){
            $this->ShowMenu($input, $output);
        }else{
            exit();
        }

    }

}