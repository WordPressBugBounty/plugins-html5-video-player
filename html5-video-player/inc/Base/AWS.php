<?php

namespace H5VP\Base;

class AWS
{
    private $secrets;
    public function __construct()
    {
        $this->secrets = $this->get_secrets();
        add_action('wp_ajax_h5vp_aws_picker', [$this, 'h5vp_aws_picker']);
    }

    public function get_aws_s3_url($url)
    {
        $aws = $this->parseS3Url($url);
        if (!$aws) {
            return $url;
        }
        $secrets = $this->get_secrets();
        if ($secrets && class_exists('\Aws\S3\S3Client')) {

            $s3Client = new \Aws\S3\S3Client([
                'version' => 'latest',
                'region' => $secrets['region'],
                'credentials' => [
                    'key'    => $secrets['key'],
                    'secret' => $secrets['secret'],
                ]
            ]);

            try {
                // Generate a pre-signed URL
                $cmd = $s3Client->getCommand('GetObject', [
                    'Bucket' => $secrets['bucket'],
                    'Key' => $aws['file_location'],
                ]);
                return $s3Client->createPresignedRequest($cmd, '+60 minutes')->getUri()->__toString();
            } catch (\Exception $e) {
                return null;
            }
        }
        return $url;
    }

    public function parseS3Url($s3Url)
    {
        // Parse the URL using parse_url
        $urlParts = wp_parse_url($s3Url);

        // Check if it's an S3 URL
        if ($urlParts && isset($urlParts['host']) && preg_match('/\.amazonaws\.com$/', $urlParts['host'])) {
            // Extract bucket, server, file location, and region
            $hostParts = explode('.', $urlParts['host']);
            $bucket = $hostParts[0];
            $server = $urlParts['host'];
            $fileLocation = ltrim($urlParts['path'], '/');
            $region = $hostParts[2]; // Assuming the region is the third part of the host

            return [
                'bucket' => trim($bucket),
                'server' => trim($server),
                'file_location' => trim($fileLocation),
                'region' => trim($region),
            ];
        } else {
            // Not a valid S3 URL
            return null;
        }
    }

    public function get_secrets()
    {
        $options = get_option('h5vp_option', []);
        if (isset($options['h5vp_aws_key_id']) && $options['h5vp_aws_access_key']) {
            return [
                'bucket' => $options['h5vp_aws_bucket'] ?? '',
                'region' => $options['h5vp_aws_region'] ?? '',
                'key' => $options['h5vp_aws_key_id'],
                'secret' => $options['h5vp_aws_access_key'],
            ];
        }
        return null;
    }

    // get s3 list objects
    public function get_s3_list_objects()
    {
        $secrets = $this->get_secrets();
        if ($secrets && class_exists('\Aws\S3\S3Client')) {
            try {
                $s3Client = new \Aws\S3\S3Client([
                    'version' => 'latest',
                    'region' => $secrets['region'],
                    'credentials' => [
                        'key'    => $secrets['key'],
                        'secret' => $secrets['secret'],
                    ]
                ]);
                $list = $s3Client->listObjectsV2([
                    'Bucket' => $secrets['bucket'],
                ]);

                return $this->format_list_objects($list);
            } catch (\Throwable $th) {
                return $th->getMessage();
            }
        }
        return 'AWS S3 Client not found';
    }

    public function format_list_objects($list)
    {
        $formatted = [];
        foreach ($list['Contents'] as $item) {
            $formatted[] = $item['Key'];
        }

        return ['bucketName' => $list['Name'], 'region' => $this->secrets['region'], 'lists' => $formatted];
    }

    //Clicked AWS File Picker (ajax call)
    function h5vp_aws_picker()
    {
        wp_send_json_success($this->get_s3_list_objects());
    }
}
