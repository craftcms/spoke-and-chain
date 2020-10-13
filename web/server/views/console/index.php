<?php
/* @var $this RequirementsChecker */
/* @var $summary array */
/* @var $requirements array[] */

echo "\nCraft CMS Requirement Checker\n\n";

echo "This script checks if your web server configuration meets the requirements for running a Craft CMS installation.\n";
echo "It checks if the server is running the right version of PHP, if appropriate PHP extensions have been loaded,\n";
echo "and if php.ini file settings are correct.\n\n";

$header = 'Results:';

echo "\n{$header}\n";
echo str_pad('', strlen($header), '-')."\n\n";

foreach ($requirements as $key => $requirement)
{
    if ($requirement['condition']) {
        echo $requirement['name'].": OK\n";
        echo "\n";
    } else {
        echo $requirement['name'].': '.($requirement['mandatory'] ? 'FAILED!!!' : 'WARNING!!!')."\n";

        $memo = strip_tags($requirement['memo']);

        if (!empty($memo)) {
            echo 'Memo: '.strip_tags($requirement['memo'])."\n";
        }

        echo "\n";
    }
}

$summaryString = 'Errors: '.$summary['errors'].'   Warnings: '.$summary['warnings'].'   Total checks: '.$summary['total'];
echo str_pad('', strlen($summaryString), '-')."\n";
echo $summaryString;

echo "\n\n";
