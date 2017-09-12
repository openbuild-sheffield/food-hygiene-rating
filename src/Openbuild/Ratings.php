<?php

namespace Openbuild;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Ratings extends Command
{

    use ShowMenu;

    private $fileHandle;

    private function writePre()
    {
        copy('/templates/Ratings.postgres.pre.sql', '/export/002.Ratings.sql');
        $this->fileHandle = fopen('/export/002.Ratings.sql', 'a+');
    }

    private function writeData($string)
    {
        fwrite($this->fileHandle, $string);
    }

    private function writePost()
    {
        fwrite($this->fileHandle, file_get_contents('/templates/Ratings.postgres.post.sql'));
        fclose($this->fileHandle);
    }

    protected function configure()
    {
        //// the name of the command (the part after "bin/console")
        $this->setName('app:Ratings');

        // the short description shown while running "php bin/console list"
        $this->setDescription('Generates Ratings.');

        // the full command description shown when running the command with
        // the "--help" option
        $this->setHelp('Downloads Ratings data and creates data files.');

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

        $output->write("Copying Ratings templates.\n");

        $this->writePre();

        $output->write("Fetching Ratings.\n");

        $bar = new ProgressBar($output, 100);
        $bar->setFormat('[%bar%] Fetching Ratings. %percent%% %download%');
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
        $promises['Ratings'] = $client->getAsync('/Ratings');

        // Wait for the requests to complete, even if some of them fail
        $results = \GuzzleHttp\Promise\settle($promises)->wait();

        $bar->finish();

        $output->write("\n");

        $sql = "INSERT INTO app_public.fhr_rating (uuid, rating_id, rating_name, rating_key, rating_key_name, scheme_type_id) VALUES ((SELECT uuid_generate_v5(uuid_ns_url(), ('http://api.ratings.food.gov.uk/SchemeTypes/' || '%s'))), %d, '%s', '%s', '%s', %d);\n";

        $handle = fopen('/export/002.Ratings.sql', 'a+');

        foreach($results AS $key => $result){

            if($result['state'] == 'rejected'){

                //FIXME - why was it rejected???
                $output->write("Failed to download $key - rejected.\n");
                exit(1);

            }elseif($result['value']->getStatusCode() === 200){

                $data = json_decode($result['value']->getBody());

                foreach($data->ratings AS $rating){
                    $this->writeData(sprintf($sql, $rating->ratingId, $rating->ratingId, $rating->ratingName, $rating->ratingKey, $rating->ratingName, $rating->schemeTypeId));
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