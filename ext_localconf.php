<?php

if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('scheduler')) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] =
        \BusyNoggin\StaticNotFound\Hook\DataHandlerSubscriber::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] =
        \BusyNoggin\StaticNotFound\Hook\DataHandlerSubscriber::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][] =
        \BusyNoggin\StaticNotFound\Hook\DataHandlerSubscriber::class . '->postProcessClearCache';
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] =
        \BusyNoggin\StaticNotFound\Hook\DataHandlerSubscriber::class;
}

if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['static_error_pages'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['static_error_pages'] = [
        'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
        'backend' => \BusyNoggin\StaticErrorPages\Cache\RawFileBackend::class,
        'options' => [
            'defaultLifetime' => 804600
        ],
        'groups' => []
    ];
}
