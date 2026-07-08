<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Command;

use Hryvinskyi\InvisibleCaptcha\Model\Area;
use Magento\Config\Model\ResourceModel\Config as ConfigResource;
use Magento\Framework\App\Cache\Manager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI: disable captcha globally or for a single area
 * (e.g. `bin/magento hryvinskyi:invisible-captcha:disable frontend --website_id=1`).
 */
class Captcha extends Command
{
    /**
     * Area => [config path, whether it is scoped per website].
     *
     * @var array<string, array{0:string,1:bool}>
     */
    private const AREA_PATHS = [
        Area::GLOBAL => ['hryvinskyi_invisible_captcha/general/enabled', true],
        Area::FRONTEND => ['hryvinskyi_invisible_captcha/form_protection/frontend/enabled', true],
        Area::BACKEND => ['hryvinskyi_invisible_captcha/form_protection/backend/enabled', false],
    ];

    /**
     * @param Manager $cacheManager
     * @param StoreManagerInterface $storeManager
     * @param Area $area
     * @param ConfigResource $configResource
     */
    public function __construct(
        private readonly Manager $cacheManager,
        private readonly StoreManagerInterface $storeManager,
        private readonly Area $area,
        private readonly ConfigResource $configResource
    ) {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName('hryvinskyi:invisible-captcha:disable')
            ->setDescription('Disable invisible captcha')
            ->addArgument(
                'area',
                InputArgument::OPTIONAL,
                'Area (' . implode('/', $this->area->getAllowedList()) . ')',
                Area::GLOBAL
            )
            ->addOption('website_id', 'website_id', InputOption::VALUE_OPTIONAL, 'Website ID', null);

        parent::configure();
    }

    /**
     * @inheritDoc
     * @throws LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $area = (string)$input->getArgument('area');
        $website = $input->getOption('website_id');

        if (!isset(self::AREA_PATHS[$area])) {
            throw new LocalizedException(__('Area must be one of %1', implode('/', $this->area->getAllowedList())));
        }

        if ($website !== null && !isset($this->storeManager->getWebsites()[$website])) {
            throw new LocalizedException(__('Website not found'));
        }

        [$path, $scoped] = self::AREA_PATHS[$area];

        if ($scoped && $website !== null) {
            $this->configResource->saveConfig($path, '0', ScopeInterface::SCOPE_WEBSITES, (int)$website);
        } else {
            $this->configResource->saveConfig($path, '0', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
        }

        $this->cacheManager->flush(['config']);
        $output->writeln(sprintf('<info>Invisible captcha disabled for area "%s".</info>', $area));

        return Command::SUCCESS;
    }
}
