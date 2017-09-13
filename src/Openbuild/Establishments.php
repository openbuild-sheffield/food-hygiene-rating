<?php

namespace Openbuild;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Establishments extends Command
{

    use ShowMenu;

    private $fileHandle;
    private $cache = true;

    private function writePre()
    {
        copy('/templates/Establishments.postgres.pre.sql', '/export/007.Establishments.sql');
        $this->fileHandle = fopen('/export/007.Establishments.sql', 'a+');
    }

    private function writeData($string)
    {
        fwrite($this->fileHandle, $string);
    }

    private function writePost()
    {
        fwrite($this->fileHandle, file_get_contents('/templates/Establishments.postgres.post.sql'));
        fclose($this->fileHandle);
    }

    private function processedLocal($data)
    {

        if($this->cache === false){
            return false;
        }

        if(is_object($data) && isset($data->FHRSID) && isset($data->RatingDate)){

            $localPath = '/downloads/' . implode('/', str_split($data->FHRSID)) . '.json';

            if(file_exists($localPath) === false){
                return false;
            }

            $localData = json_decode(file_get_contents($localPath));

            if(is_object($localData) && isset($localData->FHRSID) && isset($localData->RatingDate)){

                if($localData->RatingDate === $data->RatingDate){

                    $this->processEstablishment($localData, false);

                    return true;

                }else{

                    return false;

                }

            }else{

                return false;

            }

        }else{

            return false;

        }

    }

    private function processEstablishment($dataEstablishment, $save)
    {
        $sqlEstablishment = "INSERT INTO app_public.fhr_establishment
(uuid, fhrsid, local_authority_business_id, business_name, business_type_id, address_line_1, address_line_2, address_line_3, address_line_4, postcode, phone, rating_key, rating_date, local_authority_code, geocode)
VALUES ((SELECT uuid_generate_v5(uuid_ns_url(), ('http://api.ratings.food.gov.uk/Establishments/' || %d))), %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', ST_GeographyFromText('SRID=4326;POINT(%f %f)'));\n";

        $this->writeData(
            sprintf(
                $sqlEstablishment,
                $dataEstablishment->FHRSID,
                $dataEstablishment->FHRSID,
                str_replace("'","''", $dataEstablishment->LocalAuthorityBusinessID),
                str_replace("'","''", $dataEstablishment->BusinessName),
                $dataEstablishment->BusinessTypeID,
                str_replace("'","''", $dataEstablishment->AddressLine1),
                str_replace("'","''", $dataEstablishment->AddressLine2),
                str_replace("'","''", $dataEstablishment->AddressLine3),
                str_replace("'","''", $dataEstablishment->AddressLine4),
                str_replace("'","''", $dataEstablishment->PostCode),
                str_replace("'","''", $dataEstablishment->Phone),
                str_replace("'","''", $dataEstablishment->RatingKey),
                $dataEstablishment->RatingDate,
                str_replace("'","''", $dataEstablishment->LocalAuthorityCode),
                $dataEstablishment->geocode->longitude,
                $dataEstablishment->geocode->latitude
            )
        );

        if($save && $this->cache){

            $localPath = '/downloads/' . implode('/', str_split($dataEstablishment->FHRSID)) . '.json';

            $dirname = dirname($localPath);

            if(! is_dir($dirname)){
                mkdir($dirname, 0777, true);
            }

            file_put_contents($localPath, json_encode($dataEstablishment));

        }

    }

    protected function configure()
    {
        //// the name of the command (the part after "bin/console")
        $this->setName('app:Establishments');

        // the short description shown while running "php bin/console list"
        $this->setDescription('Generates Establishments.');

        // the full command description shown when running the command with
        // the "--help" option
        $this->setHelp('Downloads Establishments data and creates data files.');

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

        $output->write("Copying Establishments templates.\n");

        $this->writePre();

        $output->write("Fetching Establishments.\n");

        $bar = new ProgressBar($output, 100);
        $bar->setFormat('[%bar%] Fetching Establishments. %percent%% Files: %files% %download%');
        $bar->setMessage('', 'percent');
        $bar->setMessage('?', 'files');
        $bar->setMessage('', 'download');

        $client = new \GuzzleHttp\Client([
            'base_uri' => 'http://api.ratings.food.gov.uk',
            'headers' => ['x-api-version' => 2, 'accept' => 'text/json'],
            'progress' => function($downloadTotal, $downloadedBytes, $uploadTotal, $uploadedBytes) use ($bar) {
                $bar->setMessage("Downloading $downloadedBytes of $downloadTotal bytes", 'download');
            }
        ]);

        $processing = true;
        $page = 0;
        $$totalPages = null;
        $$totalCount = null;
        $processedEstablishmentsCount = 0;

        $bar->start();

        do {

            $promisesBasic = [];

            if($page === 0){

                ++$page;
                //LEWIS
                $promisesBasic["Establishments/basic/$page/5000"] = $client->getAsync("Establishments/basic/$page/5000");

            }else{

                for($i = 1; $i <= 10; $i++){

                    if(is_int($totalPages) && $page < $totalPages){
                        ++$page;
                        $promisesBasic["Establishments/basic/$page/5000"] = $client->getAsync("Establishments/basic/$page/5000");
                    }

                }

            }

            $resultsBasic = \GuzzleHttp\Promise\settle($promisesBasic)->wait();

            foreach($resultsBasic AS $keyBasic => $resultBasic){

                if($resultBasic['state'] == 'rejected'){

                    //FIXME - why was it rejected???
                    $output->write("Failed to download $keyBasic - rejected.\n");
                    exit(1);

                }elseif($resultBasic['value']->getStatusCode() === 200){

                    $dataBasic = json_decode($resultBasic['value']->getBody());
                    $totalPages = $dataBasic->meta->totalPages;
                    $totalCount = $dataBasic->meta->totalCount;

                    $processingData = true;
                    $line = 0;

                    do{

                        $promisesEstablishments = [];

                        for($i = 1; $i <= 100; $i++){

                            if(isset($dataBasic->establishments[$line])){

                                if($this->processedLocal($dataBasic->establishments[$line]) === false){

                                    $promisesEstablishments[] = $client->getAsync('/Establishments/' . $dataBasic->establishments[$line]->FHRSID);

                                }else{

                                    ++$processedEstablishmentsCount;
                                    $bar->setMessage("$processedEstablishmentsCount / $totalCount", 'files');
                                    $bar->setMessage(round((($processedEstablishmentsCount / $totalCount) * 100), 2), 'percent');
                                    $bar->advance();
                                    $bar->setProgress(floor(($processedEstablishmentsCount / $totalCount) * 100));

                                }

                                ++$line;

                            }else{
                                $processingData = false;
                            }

                        }

                        if(count($promisesEstablishments) != 0)
                        {

                            $resultsEstablishments = \GuzzleHttp\Promise\settle($promisesEstablishments)->wait();

                            foreach($resultsEstablishments AS $keyEstablishment => $resultEstablishment){

                                if($resultEstablishment['state'] == 'rejected'){

                                    //Do nothing
                                    ++$processedEstablishmentsCount;
                                    echo "rejected\n";

                                }elseif($resultEstablishment['value']->getStatusCode() === 200){

                                    $dataEstablishment = json_decode($resultEstablishment['value']->getBody());

                                    $this->processEstablishment($dataEstablishment, true);

                                    ++$processedEstablishmentsCount;
                                    $bar->setMessage("$processedEstablishmentsCount / $totalCount", 'files');
                                    $bar->setMessage(round((($processedEstablishmentsCount / $totalCount) * 100), 2), 'percent');
                                    $bar->advance();
                                    $bar->setProgress(floor(($processedEstablishmentsCount / $totalCount) * 100));

                                }else{

                                    ++$processedEstablishmentsCount;

                                }

                            }

                        }


                    }while($processingData);

                }else{

                    //FIXME - why was it rejected???
                    $output->write("Failed to download $keyBasic - invalid status code.\n");
                    exit(1);

                }

            }

            if($page >= $totalPages){
                $processing = false;
            }

        }while($processing);

        $bar->finish();

        $this->writePost();

        $output->write("\nCompleted Establishments\n\n");

        if($input->getOption('autoquit') === false){
            $this->ShowMenu($input, $output);
        }else{
            exit();
        }

    }

}