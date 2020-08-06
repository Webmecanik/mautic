<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEcrBundle\Command;

use Mautic\CoreBundle\Command\ModeratedCommand;
use MauticPlugin\MauticDolistBundle\Dolist\Callback\CallbackEmail;
use MauticPlugin\MauticDolistBundle\Dolist\Callback\CallbackFactory;
use MauticPlugin\MauticDolistBundle\Dolist\Callback\CallbackService;
use MauticPlugin\MauticEcrBundle\Integration\EcrSettings;
use MauticPlugin\MauticEcrBundle\Sync\DAO\InputDAO;
use MauticPlugin\MauticEcrBundle\Sync\EcrSync;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EcrSyncCommand extends ModeratedCommand
{
    /**
     * @var EcrSync
     */
    private $ecrSync;

    /**
     * EcrSyncCommand constructor.
     */
    public function __construct(EcrSync $ecrSync)
    {
        parent::__construct();
        $this->ecrSync = $ecrSync;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('mautic:ecr:sync')
            ->setDescription('Sync for ecr')
            ->setHelp('This command recive info about message deliveribelity')
            ->addOption(
                '--start-datetime',
                null,
                InputOption::VALUE_OPTIONAL,
                'Set start date/time for updated values in UTC timezone.',
                '-1 day'
            )
            ->addOption(
                '--end-datetime',
                null,
                InputOption::VALUE_OPTIONAL,
                'Set start date/time for updated values in UTC timezone.',
                'now'
            );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $key = __CLASS__;
        if (!$this->checkRunStatus($input, $output, $key)) {
            return 0;
        }

        $inputDAO = new InputDAO($input->getOptions());
        $synced   = $this->ecrSync->syncOrders($inputDAO);
        $output->writeln('Sync contacts: '.$synced);

        return 0;
    }
}
