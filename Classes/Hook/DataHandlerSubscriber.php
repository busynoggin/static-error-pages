<?php
declare(strict_types=1);

namespace BusyNoggin\StaticErrorPages\Hook;

use BusyNoggin\StaticErrorPages\Service\StaticVersionFetcher;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\CMS\Scheduler\Task\ExecuteSchedulableCommandTask;

class DataHandlerSubscriber
{
    public function __construct(
        private StaticVersionFetcher $fetcher,
        private ConnectionPool $connectionPool
    ) {
    }

    public function processCmdmap(
        string $command,
        string $table,
        int $id,
        int|string|float|array|bool|null $value,
        bool &$commandIsProcessed,
        DataHandler $dataHandler,
        bool|array $pasteUpdate
    ): void {
        if ($table === 'pages' && ctype_digit($id)) {
            $this->recreateStaticVersionIfNecessary((integer) $id);
        }
    }

    // @phpcs:ignore PSR1.Methods.CamelCapsMethodName
    public function processDatamap_afterDatabaseOperations(
        string $command,
        string $table,
        string $id,
        array $fieldArray,
        DataHandler $reference
    ): void {
        if ($table === 'pages' && ctype_digit($id)) {
            $this->recreateStaticVersionIfNecessary((integer) $id);
        }
    }

    public function postProcessClearCache(array &$params, DataHandler &$pObj): void
    {
        if (($params['cacheCmd'] ?? false) && ctype_digit($params['cacheCmd'])) {
            $this->recreateStaticVersionIfNecessary((integer) $params['cacheCmd']);
        }
    }

    private function recreateStaticVersionIfNecessary(int $pageUid): void
    {
        /** @var DeletedRestriction $deletedRestriction */
        $deletedRestriction = GeneralUtility::makeInstance(DeletedRestriction::class);

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_scheduler_task');
        $queryBuilder->select('uid', 'serialized_task_object')->from('tx_scheduler_task');
        $queryBuilder->getRestrictions()->removeAll()->add($deletedRestriction);

        $result = $queryBuilder->executeQuery();
        while ($row = $result->fetchAssociative()) {
            /** @var AbstractTask|\stdClass $task */
            $task = unserialize($row['serialized_task_object']);
            if (!$task instanceof ExecuteSchedulableCommandTask) {
                continue;
            }

            if ($task->getCommandIdentifier() !== 'notfound:static') {
                continue;
            }

            $options = $task->getOptionValues();
            $arguments = $task->getArguments();

            /** @var int $ttl */
            $ttl = (integer) ($arguments['ttl'] ?? $options['ttl'] ?? 0);

            /** @var bool $noVerifySsl */
            $noVerifySsl = $options['no-verify-ssl'];

            /** @var string|int $identifier */
            $identifier = $arguments['live-identifier'];

            if (ctype_digit($identifier) && ((integer) $identifier) === $pageUid) {
                // Scheduler task "live identifier" is a page UID and matches the page being changed in BE.
                $this->fetcher->fetchAndStoreStaticVersion($identifier, !$noVerifySsl, $ttl);
            }
        }
    }
}
