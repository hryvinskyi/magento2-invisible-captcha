<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Command;

use Hryvinskyi\InvisibleCaptcha\Model\Area;
use Magento\Framework\App\Cache\Manager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Captcha
 */
class Captcha extends Command
{
    /**
     * @var Manager
     */
    private $cacheManager;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Area
     */
    private $area;

    /**
     * @var array
     */
    private $areaConfigList;

    /**
     * ReCaptcha constructor.
     *
     * @param Manager $cacheManager
     * @param StoreManagerInterface $storeManager
     * @param Area $area
     * @param Area\ConfigList $areaConfigList
     */
    public function __construct(
        Manager $cacheManager,
        StoreManagerInterface $storeManager,
        Area $area,
        Area\ConfigList $areaConfigList
    ) {
        $this->cacheManager = $cacheManager;
        $this->storeManager = $storeManager;
        $this->area = $area;
        $this->areaConfigList = $areaConfigList;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('hryvinskyi:invisible-captcha:disable')
            ->setDescription('Disable invisible captcha')
            ->addArgument(
                'area',
                InputArgument::OPTIONAL,
                'Area (' . implode('/', $this->area->getAllowedList()) . ')',
                Area::GLOBAL
            )
            ->addOption(
                'website_id',
                'website_id',
                InputOption::VALUE_OPTIONAL,
                'Website ID',
                null
            );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     * @throws LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $area = $input->getArgument('area');
        $website = $input->getOption('website_id');

        if ($website !== null && !isset($this->allWebsites()[$website])) {
            throw new LocalizedException(__('Website not found'));
        }

        // disable captcha
        $this->areaConfigList->getConfig($area)->disableCaptcha($area === Area::BACKEND ? null : $website);

        // flush cache
        $this->cacheManager->flush(['config']);
    }

    /**
     * Return all websites
     *
     * @return array
     */
    private function allWebsites(): array
    {
        return $this->storeManager->getWebsites();
    }
}