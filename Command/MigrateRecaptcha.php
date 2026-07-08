<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Command;

use Hryvinskyi\InvisibleCaptcha\Api\Migration\RecaptchaMigratorInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Migration\ChangeRecord;
use Magento\Framework\App\Cache\Manager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI: migrate configuration from Magento's native Google reCAPTCHA into this
 * module (`bin/magento hryvinskyi:invisible-captcha:migrate-recaptcha [--dry-run] [--force]`).
 *
 * Existing values in the target tree are preserved unless `--force` is given.
 * After a successful (non-dry) run, remove the native modules — this module's
 * composer.json already declares `replace` entries for them.
 */
class MigrateRecaptcha extends Command
{
    private const OPTION_DRY_RUN = 'dry-run';
    private const OPTION_FORCE = 'force';

    /**
     * @param RecaptchaMigratorInterface $migrator
     * @param Manager $cacheManager
     */
    public function __construct(
        private readonly RecaptchaMigratorInterface $migrator,
        private readonly Manager $cacheManager
    ) {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName('hryvinskyi:invisible-captcha:migrate-recaptcha')
            ->setDescription('Migrate configuration from Magento\'s native Google reCAPTCHA into Invisible Captcha')
            ->addOption(self::OPTION_DRY_RUN, null, InputOption::VALUE_NONE, 'Preview the changes without writing anything')
            ->addOption(self::OPTION_FORCE, null, InputOption::VALUE_NONE, 'Overwrite values already present in the target tree');

        parent::configure();
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dryRun = (bool)$input->getOption(self::OPTION_DRY_RUN);
        $force = (bool)$input->getOption(self::OPTION_FORCE);

        if ($dryRun) {
            $output->writeln('<comment>Dry run — no changes will be written.</comment>');
        }

        $records = $this->migrator->migrate($dryRun, $force);

        if ($records === []) {
            $output->writeln('<info>No native Google reCAPTCHA configuration found — nothing to migrate.</info>');

            return Command::SUCCESS;
        }

        $this->renderTable($records, $output);
        $counts = $this->summarize($records);

        $output->writeln(sprintf(
            '<info>%d written, %d overwritten, %d skipped (already set).</info>',
            $counts[RecaptchaMigratorInterface::STATUS_MIGRATED],
            $counts[RecaptchaMigratorInterface::STATUS_OVERWRITTEN],
            $counts[RecaptchaMigratorInterface::STATUS_SKIPPED_EXISTS]
        ));

        $written = $counts[RecaptchaMigratorInterface::STATUS_MIGRATED]
            + $counts[RecaptchaMigratorInterface::STATUS_OVERWRITTEN];

        if ($dryRun) {
            $output->writeln('<comment>Re-run without --dry-run to apply.</comment>');

            return Command::SUCCESS;
        }

        if ($written > 0) {
            $this->cacheManager->flush(['config']);
            $output->writeln('<info>Config cache flushed. Review the settings, then remove the native reCAPTCHA modules.</info>');
        }

        return Command::SUCCESS;
    }

    /**
     * @param ChangeRecord[] $records
     */
    private function renderTable(array $records, OutputInterface $output): void
    {
        $table = new Table($output);
        $table->setHeaders(['Status', 'Scope', 'ID', 'Target path', 'Value']);
        foreach ($records as $record) {
            $table->addRow([
                $record->status,
                $record->scope,
                (string)$record->scopeId,
                $record->target,
                $record->value,
            ]);
        }
        $table->render();
    }

    /**
     * @param ChangeRecord[] $records
     * @return array<string, int>
     */
    private function summarize(array $records): array
    {
        $counts = [
            RecaptchaMigratorInterface::STATUS_MIGRATED => 0,
            RecaptchaMigratorInterface::STATUS_OVERWRITTEN => 0,
            RecaptchaMigratorInterface::STATUS_SKIPPED_EXISTS => 0,
        ];
        foreach ($records as $record) {
            if (isset($counts[$record->status])) {
                $counts[$record->status]++;
            }
        }

        return $counts;
    }
}
