<?php

defined('AKEEBAENGINE') or define('AKEEBAENGINE', 1);

#require_once '../config.php';

foreach (glob("./**/*.php") as $filename) {
    require_once $filename;
}

use Akeeba\Engine\Postproc\Connector\S3v4\Configuration;
use Akeeba\Engine\Postproc\Connector\S3v4\Connector;
use Akeeba\Engine\Postproc\Connector\S3v4\Input;

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
    /**
     * @var mixed
     */
    private $global;

    public function __construct()
    {
        $this->global = $GLOBALS['config'];
        $this->config = new Configuration($this->global['aws']['access_key'], $this->global['aws']['secret']);

        $this->connector = new Connector($this->config);
    }

    public function multipartUpload($sourceFile)
    {
        $input = Input::createFromFile($sourceFile);
        $uploadId = $this->connector->startMultipart($input, $this->global['aws']['bucket'], basename($sourceFile));

        $eTags = array();
        $eTag = null;
        $partNumber = 0;

        do {
            // IMPORTANT: You MUST create the input afresh before each uploadMultipart call
            $input = Input::createFromFile($sourceFile);
            $input->setUploadID($uploadId);
            $input->setPartNumber(++$partNumber);

            $eTag = $this->connector->uploadMultipart($input, $this->global['aws']['bucket'], basename($sourceFile));

            if (!is_null($eTag)) {
                $eTags[] = $eTag;
            }
        } while (!is_null($eTag));

// IMPORTANT: You MUST create the input afresh before finalising the multipart upload
        $input = Input::createFromFile($sourceFile);
        $input->setUploadID($uploadId);
        $input->setEtags($eTags);

        $this->connector->finalizeMultipart($input, $this->global['aws']['bucket'], basename($sourceFile));
    }
}