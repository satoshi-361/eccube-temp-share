<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eccube\Command;

use Customize\Service\PaypalTransferHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PaypalTransferCommand extends Command
{
    protected static $defaultName = 'eccube:paypal:transfer';

    /**
     * @var PaypalTransferHelper
     */
    private $paypalTransferHelper;

    public function __construct(PaypalTransferHelper $paypalTransferHelper)
    {
        parent::__construct();

        $this->paypalTransferHelper = $paypalTransferHelper;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $date = $input->getOption('date');

        if (empty($date)) {
            $io->error('date is required.');

            return 1;
        }

        if ( $date == 15 ) {
            $this->paypalTransferHelper->startPaypalPayout15();
        } else {
            $this->paypalTransferHelper->startPaypalPayout30();
        }


        $io->success('Paypal Transfered');

        return 0;        
    }
}
