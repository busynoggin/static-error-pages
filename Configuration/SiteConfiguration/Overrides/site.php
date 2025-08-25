<?php

$GLOBALS['SiteConfiguration']['site_errorhandling']['columns']['error_content_source'] = [
    'label' => 'Error Page Source',
    'description' => 'From where to read the HTML source of the error page, can be a TYPO3 page UID or an absolute URL',
    'config' => [
        'type' => 'input',
        'renderType' => 'inputLink',
    ],
];
$GLOBALS['SiteConfiguration']['site_errorhandling']['types']['PHP']['showitem'] .= ',error_content_source';
