<?php

namespace Openbuild;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SchemeTypes extends Command
{

    use ShowMenu;

    private $fileHandle;

    private function writePre()
    {
        copy('/templates/SchemeTypes.postgres.pre.sql', '/export/001.SchemeTypes.sql');
        $this->fileHandle = fopen('/export/001.SchemeTypes.sql', 'a+');
    }

    private function writeData($string)
    {
        fwrite($this->fileHandle, $string);
    }

    private function writePost()
    {
        fwrite($this->fileHandle, file_get_contents('/templates/SchemeTypes.postgres.post.sql'));
        fclose($this->fileHandle);
    }

    protected function configure()
    {
        //// the name of the command (the part after "bin/console")
        $this->setName('app:SchemeTypes');

        // the short description shown while running "php bin/console list"
        $this->setDescription('Generates Scheme Types.');

        // the full command description shown when running the command with
        // the "--help" option
        $this->setHelp('Downloads Scheme Types data and creates data files.');

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

        $output->write("Copying SchemeTypes templates.\n");

        $this->writePre();

        $output->write("Fetching SchemeTypes.\n");

        $bar = new ProgressBar($output, 100);
        $bar->setFormat('[%bar%] Fetching Scheme Types. %percent%% %download%');
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
        $promises['SchemeTypes'] = $client->getAsync('/SchemeTypes');

        // Wait for the requests to complete, even if some of them fail
        $results = \GuzzleHttp\Promise\settle($promises)->wait();

        $bar->finish();

        $output->write("\n");

        $sql = "INSERT INTO app_public.fhr_scheme_type (uuid, scheme_type_id, scheme_type_name, scheme_type_key) VALUES ((SELECT uuid_generate_v5(uuid_ns_url(), ('http://api.ratings.food.gov.uk/SchemeTypes/' || '%s'))), %d, '%s', '%s');\n";

        $handle = fopen('/export/001.SchemeTypes.sql', 'a+');

        foreach($results AS $key => $result){

            if($result['state'] == 'rejected'){

                //FIXME - why was it rejected???
                $output->write("Failed to download $key - rejected.\n");
                exit(1);

            }elseif($result['value']->getStatusCode() === 200){

                $data = json_decode($result['value']->getBody());

                foreach($data->schemeTypes AS $schemeType){
                    $this->writeData(sprintf($sql, $schemeType->schemeTypeKey, $schemeType->schemeTypeid, $schemeType->schemeTypeName, $schemeType->schemeTypeKey));
                }

            }else{
                $output->write("Failed to download $key - invalid status.\n");
                exit(1);
            }

        }

        $this->writePost();

        $output->write("\nCompleted SchemeTypes\n\n");

        if($input->getOption('autoquit') === false){
            $this->ShowMenu($input, $output);
        }else{
            exit();
        }

    }

}