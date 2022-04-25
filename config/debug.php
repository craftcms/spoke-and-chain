<?php

use craft\helpers\App;

$fsHandle = App::env('CRAFT_DEBUG_FS');

return $fsHandle ? [
    'fs' => Craft::$app->getFs()->getFilesystemByHandle($fsHandle),
    'dataPath' => 'debug',
] : [];
