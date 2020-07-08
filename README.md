# Aws Transcribe
php implementation for transcribing text from url using aws transcribe

# Approach
1) Downloads audio file to temp folder
2) Uploads the file to s3 bucket
3) Starts transcription job using s3
4) Retuns json data from s3 presigned url

# Note
* Supported file formats are mp3, mp4, wav, flac.
