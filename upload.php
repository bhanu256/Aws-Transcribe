<?php
    //uploads selected file directly to S3 bucket
    //input : file
    //output : s3 url
	if(isset($_FILES['audio'])){
		$file_name = $_FILES['audio']['name'];   
        $temp_file_location = $_FILES['audio']['tmp_name']; 

        require 'aws.phar';
        
        $bucket = "";
        $region = "";
        $access_key_id = "";
        $secret_access_key = "";

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