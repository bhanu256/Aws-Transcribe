<?php
ini_set('max_execution_time', 300);
ini_set("allow_url_fopen", 1);
//required package
require 'aws.phar';

//https://www2.cs.uic.edu/~i101/SoundFiles/gettysburg10.wav
//required modules
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Aws\Exception\AwsException;
use Aws\TranscribeService\TranscribeServiceClient;

//aws credentials
$bucket = "";
$region = "";
$access_key_id = "";
$secret_access_key = "";

//S3 Constructor Object
$s3 = S3Client::factory(array(
    'region' => $region,
    'version' => 'latest',
    'credentials' => [
    'key' => $access_key_id,
    'secret' => $secret_access_key,
    ]
));

//aws transcribe client obj
$transcribeClient = new TranscribeServiceClient([
    'region' => $region,
    'version' => 'latest',
    'credentials' => array(
        'key' => $access_key_id,
        'secret' => $secret_access_key,
    ),
]);

if($_REQUEST['audioUrl']){
    $url = $_REQUEST['audioUrl'];

    try{
        //downloads audio file to server
        $tempFilePath = basename($url);
        $tempFile = fopen($tempFilePath, "w") or die("Error: Unable to open file.");
        $fileContents = file_get_contents($url);
        $tempFile = file_put_contents($tempFilePath, $fileContents);
        
        //temp file info
        $path_info = pathinfo($tempFilePath);
        $ext = $path_info['extension'];
        $filename = $path_info['filename'];

        //uploads temp file to s3 bucket
        $upload = $s3->putObject([
            'Bucket' => $bucket,
            'Key' => $tempFilePath,
            'SourceFile' => $tempFilePath,
            'ACL' => 'public-read',
        ]);
        $s3Url = $upload['ObjectURL'];

        //starts transcription job in english lang
        $transcribeJob = $transcribeClient->startTranscriptionJob([
            'LanguageCode' => 'en-US',
            'Media' => [
                'MediaFileUri' => $s3Url,
            ],
            'MediaFormat' => $ext,
            'TranscriptionJobName' => $filename,
        ]);

        //checks whether the job is completed/not every 3 sec
        while(1){
            //job status
            $result = $transcribeClient->getTranscriptionJob([
                'TranscriptionJobName' => $filename,
            ]);

            //JOB STATUS STATES : QUEUED|IN_PROGRESS|FAILED|COMPLETED
            //job status success(COMPLETED)
            if($result['TranscriptionJob']['TranscriptionJobStatus'] == "COMPLETED"){
                $resultUrl = $result['TranscriptionJob']['Transcript']['TranscriptFileUri']; //s3 presigned url
                $json = file_get_contents($resultUrl); //data from url

                //deletes s3 object 
                $s3del =$s3->deleteObject([
                    'Bucket' => $bucket,
                    'Key' => $tempFilePath
                ]);

                //deleted transcribe job
                $jobdel = $transcribeClient->deleteTranscriptionJob([
                    'TranscriptionJobName' => $filename,
                ]);

                //deletes temp file
                gc_collect_cycles();
                try {unlink($tempFilePath);} catch(Exception $ex) {}

                header('Content-Type: application/json');
                echo $json;
                exit;
            }
            else if($result['TranscriptionJob']['TranscriptionJobStatus'] == "FAILED"){
                $s3del =$s3->deleteObject([
                    'Bucket' => $bucket,
                    'Key' => $tempFilePath
                ]);

                $jobdel = $client->deleteTranscriptionJob([
                    'TranscriptionJobName' => $filename,
                ]);

                gc_collect_cycles();
                try {unlink($tempFilePath);} catch(Exception $ex) {}

                header('Content-Type: application/json');
                echo json_encode(array('result' => "Unable to process file"));
                exit;
            }
            sleep(3);
        }

    } catch(AwsException $exp){
        exit($exp);
    }

}
?>
