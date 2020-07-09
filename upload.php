<?php
    //uploads selected file directly to S3 bucket
    //input : file
    //output : s3 url
	if(isset($_FILES['audio'])){
		$file_name = $_FILES['audio']['name'];   
        $temp_file_location = $_FILES['audio']['tmp_name']; 

        require 'aws.phar';
        
        $bucket = "ocrscanner2";
        $region = "ap-southeast-1";
        $access_key_id = "AKIAXO4Z5UT4HIRCHUP7";
        $secret_access_key = "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";

		$s3 = new Aws\S3\S3Client([
			'region'  => $region,
			'version' => 'latest',
			'credentials' => [
				'key'    => $access_key_id,
				'secret' => $secret_access_key,
			]
		]);		

		$result = $s3->putObject([
			'Bucket' => $bucket,
			'Key'    => $file_name,
			'SourceFile' => $temp_file_location			
		]);
        $s3Url = $result['ObjectURL'];
        echo json_encode(array('url' => $s3Url));
        exit;
	}
?>