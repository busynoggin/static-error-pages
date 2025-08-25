<?php
declare(strict_types=1);

namespace BusyNoggin\StaticErrorPages\Command;

use BusyNoggin\StaticErrorPages\Service\StaticVersionFetcher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NotFoundCommand extends Command
{
    public function __construct(private StaticVersionFetcher $fetcher)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Interact with static version of 404 page');
        $this->addArgument(
            'live-identifier',
            InputArgument::REQUIRED,
            'Identifier (page UID or URL) of live 404 page. If the identifier is a page UID and this command is ' .
            'added as a scheduled task through EXT:scheduler then the static version is recreated any time the page ' .
            'is edited in the TYPO3 backend. If the identifier is a URL it is only recreated when the TTL expires.'
        );
        $this->addOption(
            'force',
            'f',
            InputArgument::OPTIONAL | InputOption::VALUE_NONE,
            'Force creation of a new static version'
        );
        $this->addOption(
            'ttl',
            't',
            InputArgument::OPTIONAL,
            'TTL of static version - number of seconds before a new static version is fetched and stored',
            3600
        );
        $this->addOption(
            'fetch-url',
            'u',
            InputArgument::OPTIONAL,
            'If provided, reads the error page source from this URL (absolute URL required) instead of attempting ' .
            'to read it from the URL resolved from Site configuration. This option can be necessary if your web site ' .
            'is running inside a container or behind a reverse proxy with URL rewriting - for example, within DDEV.'
        );
        $this->addOption(
            'no-verify-ssl',
            's',
            InputArgument::OPTIONAL | InputOption::VALUE_NONE,
            'Disable verification of SSL certificate of grabbed URL. Disable if you use a self-signed cert or the ' .
            'cert isn\'t added to known certificates on this host.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var int|string $identifier */
        $identifier = $input->getArgument('live-identifier');
        $noVerifySsl = (bool) $input->getOption('no-verify-ssl');
        $force = (bool) $input->getOption('force');
        $ttl = (integer) $input->getOption('ttl');
        $url = $input->getOption('fetch-url');

        if ($force || $this->fetcher->isExpired($identifier)) {
            $this->fetcher->fetchAndStoreStaticVersion($identifier, $noVerifySsl, $ttl, $url);
        }

        return 0;
    }
}
