<?php

namespace Openbuild;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ScoreDescriptors extends Command
{

    use ShowMenu;

    private $fileHandle;

    private function writePre()
    {
        copy('/templates/ScoreDescriptors.postgres.pre.sql', '/export/005.ScoreDescriptors.sql');
        $this->fileHandle = fopen('/export/005.ScoreDescriptors.sql', 'a+');
    }

    private function writeData($string)
    {
        fwrite($this->fileHandle, $string);
    }

    private function writePost()
    {
        fwrite($this->fileHandle, file_get_contents('/templates/ScoreDescriptors.postgres.post.sql'));
        fclose($this->fileHandle);
    }

    protected function configure()
    {
        //// the name of the command (the part after "bin/console")
        $this->setName('app:ScoreDescriptors');

        // the short description shown while running "php bin/console list"
        $this->setDescription('Generates Score Descriptors.');

        // the full command description shown when running the command with
        // the "--help" option
        $this->setHelp('Downloads Score Descriptors data and creates data files.');

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

        $output->write("Copying ScoreDescriptors templates.\n");

        $this->writePre();

        $output->write("Fetching ScoreDescriptors.\n");

        $bar = new ProgressBar($output, 100);
        $bar->setFormat('[%bar%] Fetching Score Descriptors. %percent%% %download%');
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
        $promises['ScoreDescriptors'] = $client->getAsync('/ScoreDescriptors');

        // Wait for the requests to complete, even if some of them fail
        $results = \GuzzleHttp\Promise\settle($promises)->wait();

        $bar->finish();

        $output->write("\n");

        $sqlCategory = "INSERT INTO app_public.fhr_score_category (uuid, score_category) VALUES ((SELECT uuid_generate_v5(uuid_ns_url(), ('http://api.ratings.food.gov.uk/ScoreDescriptors.category/' || '%s'))), '%s');\n";
        $sqlCategoryScore = "INSERT INTO app_public.fhr_score_category_score (uuid, score_category_uuid, score, description) VALUES ((SELECT uuid_generate_v5(uuid_ns_url(), ('http://api.ratings.food.gov.uk/ScoreDescriptors.score/' || '%s'))), (SELECT uuid_generate_v5(uuid_ns_url(), ('http://api.ratings.food.gov.uk/ScoreDescriptors.category/' || '%s'))), %d, '%s');\n";

        $handle = fopen('/export/005.ScoreDescriptors.sql', 'a+');

        foreach($results AS $key => $result){

            if($result['state'] == 'rejected'){

                //FIXME - why was it rejected???
                $output->write("Failed to download $key - rejected.\n");
                exit(1);

            }elseif($result['value']->getStatusCode() === 200){

                $data = json_decode($result['value']->getBody());

                foreach($data->scoreDescriptors AS $scoreDescriptor){
                    $categories[$scoreDescriptor->ScoreCategory] = $scoreDescriptor->ScoreCategory;
                }

                foreach($categories AS $category){
                    $this->writeData(sprintf($sqlCategory, $category, $category));
                }

                foreach($data->scoreDescriptors AS $scoreDescriptor){
                    $this->writeData(sprintf($sqlCategoryScore, $scoreDescriptor->ScoreCategory . $scoreDescriptor->Score, $scoreDescriptor->ScoreCategory, $scoreDescriptor->Score, $scoreDescriptor->Description));
                }

            }else{
                $output->write("Failed to download $key - invalid status.\n");
                exit(1);
            }

        }

        $this->writePost();

        $output->write("\nCompleted ScoreDescriptors\n\n");

        if($input->getOption('autoquit') === false){
            $this->ShowMenu($input, $output);
        }else{
            exit();
        }

    }

}