<?php

return [
  '*' => [
  ],
  'bref-local' => \craft\cloud\Config::create()->s3ClientOptions([
      'endpoint' => 'http://minio:9000',
      'use_path_style_endpoint' => true,
  ]),
];
