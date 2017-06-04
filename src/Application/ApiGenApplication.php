<?php declare(strict_types=1);

namespace ApiGen\Application;

use ApiGen\Application\Command\RunCommand;
use ApiGen\Configuration\Configuration;
use ApiGen\Contract\Generator\GeneratorQueueInterface;
use ApiGen\ModularConfiguration\Option\DestinationOption;
use ApiGen\ModularConfiguration\Option\OverwriteOption;
use ApiGen\ModularConfiguration\Option\SourceOption;
use ApiGen\Reflection\Contract\ParserInterface;
use ApiGen\Utils\FileSystem;

final class ApiGenApplication
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var ParserInterface
     */
    private $parser;

    /**
     * @var GeneratorQueueInterface
     */
    private $generatorQueue;

    /**
     * @var FileSystem
     */
    private $fileSystem;

    public function __construct(
        Configuration $configuration,
        ParserInterface $parser,
        GeneratorQueueInterface $generatorQueue,
        FileSystem $fileSystem
    ) {
        $this->configuration = $configuration;
        $this->parser = $parser;
        $this->generatorQueue = $generatorQueue;
        $this->fileSystem = $fileSystem;
    }

    public function runCommand(RunCommand $runCommand): void
    {
        $options = $this->configuration->resolveOptions([
            SourceOption::NAME => $runCommand->getSource(),
            DestinationOption::NAME => $runCommand->getDestination()
        ]);

        $this->parser->parseDirectories($options[SourceOption::NAME]);
        $this->prepareDestination($options[DestinationOption::NAME], (bool) $options[OverwriteOption::NAME]);
        $this->generatorQueue->run();
    }

    private function prepareDestination(string $destination, bool $shouldOverwrite): void
    {
        if ($shouldOverwrite) {
            $this->fileSystem->purgeDir($destination);
        }

        $this->copyThemeResourcesToDestination($destination);
    }

    private function copyThemeResourcesToDestination(string $destination): void
    {
        $this->fileSystem->copyDirectory(
            $this->configuration->getTemplatesDirectory() . '/resources',
            $destination . '/resources'
        );
    }
}
