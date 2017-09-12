<?php

namespace Openbuild;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Authorities extends Command
{

    use ShowMenu;

    private $fileHandle;

    private function writePre()
    {
        copy('/templates/Authorities.postgres.pre.sql', '/export/004.Authorities.sql');
        $this->fileHandle = fopen('/export/004.Authorities.sql', 'a+');
    }

    private function writeData($string)
    {
        fwrite($this->fileHandle, $string);
    }

    private function writePost()
    {
        fwrite($this->fileHandle, file_get_contents('/templates/Authorities.postgres.post.sql'));
        fclose($this->fileHandle);
    }

    protected function configure()
    {
        //// the name of the command (the part after "bin/console")
        $this->setName('app:Authorities');

        // the short description shown while running "php bin/console list"
        $this->setDescription('Generates Authorities.');

        // the full command description shown when running the command with
        // the "--help" option
        $this->setHelp('Downloads Authorities data and creates data files.');

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

        $output->write("Copying Authorities templates.\n");

        $this->writePre();

        $output->write("Fetching Authorities.\n");

        $bar = new ProgressBar($output, 100);
        $bar->setFormat('[%bar%] Fetching Authorities. %percent%% %download%');
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
        $promises['Authorities'] = $client->getAsync('/Authorities');

        // Wait for the requests to complete, even if some of them fail
        $results = \GuzzleHttp\Promise\settle($promises)->wait();

        $bar->finish();

        $output->write("\n");

        $sql = "INSERT INTO app_public.fhr_authority (uuid, local_authority_id, local_authority_id_code, name, friendly_name, url, email, region_id, scheme_type_id) VALUES ((SELECT uuid_generate_v5(uuid_ns_url(), ('http://api.ratings.food.gov.uk/Authorities/' || %d))), %d, '%s', '%s', '%s', '%s', '%s', (SELECT id FROM app_public.fhr_region WHERE name_key = '%s'), %d);\n";

        foreach($results AS $key => $result){

            if($result['state'] == 'rejected'){

                //FIXME - why was it rejected???
                $output->write("Failed to download $key - rejected.\n");
                exit(1);

            }elseif($result['value']->getStatusCode() === 200){

                $data = json_decode($result['value']->getBody());

                foreach($data->authorities AS $authority){

                    $authority->LocalAuthorityId = str_replace("'", "''", $authority->LocalAuthorityId);
                    $authority->LocalAuthorityIdCode = str_replace("'", "''", $authority->LocalAuthorityIdCode);
                    $authority->Name = str_replace("'", "''", $authority->Name);
                    $authority->FriendlyName = str_replace("'", "''", $authority->FriendlyName);
                    $authority->Url = str_replace("'", "''", $authority->Url);
                    $authority->Email = str_replace("'", "''", $authority->Email);
                    $authority->RegionName = str_replace("'", "''", $authority->RegionName);
                    $authority->SchemeType = str_replace("'", "''", $authority->SchemeType);

                    $insert = sprintf($sql, $authority->LocalAuthorityId, $authority->LocalAuthorityId, $authority->LocalAuthorityIdCode, $authority->Name, $authority->FriendlyName, $authority->Url, $authority->Email, $authority->RegionName, $authority->SchemeType);
                    $this->writeData($insert);

                }

            }else{
                $output->write("Failed to download $key - invalid status.\n");
                exit(1);
            }

        }

        $this->writePost();

        $output->write("\nCompleted Authorities\n\n");

        if($input->getOption('autoquit') === false){
            $this->ShowMenu($input, $output);
        }else{
            exit();
        }

    }

}