<?php


use Akeeba\Engine\Postproc\Connector\S3v4\Configuration;
use Akeeba\Engine\Postproc\Connector\S3v4\Connector;
use Akeeba\Engine\Postproc\Connector\S3v4\Input;

require_once '../config.php';
require_once '../helper.php';


class S3Upload
{
    /**
     * @var Configuration
     */
    private $config;
    /**
     * @var Connector
     */
    private $connector;

    public function __construct()
    {
        $this->config = new Configuration("acces_id", "secret");

        $this->connector = new Connector($this->config);
    }

    public function multipartUpload($sourceFile)
    {
        $input = Input::createFromFile($sourceFile);
        $uploadId = $this->connector->startMultipart($input, 'mybucket', basename($sourceFile));

        $eTags = array();
        $eTag = null;
        $partNumber = 0;

        do {
            // IMPORTANT: You MUST create the input afresh before each uploadMultipart call
            $input = Input::createFromFile($sourceFile);
            $input->setUploadID($uploadId);
            $input->setPartNumber(++$partNumber);

            $eTag = $this->connector->uploadMultipart($input, 'mybucket', basename($sourceFile));

            if (!is_null($eTag)) {
                $eTags[] = $eTag;
            }
        } while (!is_null($eTag));

// IMPORTANT: You MUST create the input afresh before finalising the multipart upload
        $input = Input::createFromFile($sourceFile);
        $input->setUploadID($uploadId);
        $input->setEtags($eTags);

        $this->connector->finalizeMultipart($input, 'mybucket', basename($sourceFile));
    }
}