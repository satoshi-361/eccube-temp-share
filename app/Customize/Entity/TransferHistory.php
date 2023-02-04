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

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation as Eccube;

if (!class_exists(TransferHistory::class, false)) {
    /**
     * TransferHistory
     *
     * @ORM\Table(name="dtb_transfer_history")
     * @ORM\InheritanceType("SINGLE_TABLE")
     * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
     * @ORM\HasLifecycleCallbacks()
     * @ORM\Entity(repositoryClass="Customize\Repository\TransferHistoryRepository")
     * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
     */
    class TransferHistory extends \Eccube\Entity\AbstractEntity
    {
        /**
         * @var integer
         *
         * @ORM\Column(name="id", type="integer", options={"unsigned":true})
         * @ORM\Id
         * @ORM\GeneratedValue(strategy="IDENTITY")
         */
        private $id;

        /**
         * @var integer
         *
         * @ORM\Column(name="customer_id", type="integer")
         */
        private $customer_id;

        /**
         * @var string|null
         * 残高
         * 
         * @ORM\Column(name="balance", type="decimal", precision=12, scale=2, nullable=true)
         */
        private $balance = 0;

        /**
         * @var string|null
         * 振込確定額
         * 
         * @ORM\Column(name="transferable", type="decimal", precision=12, scale=2, nullable=true)
         */
        private $transferable;

        /**
         * @var string|null
         * 振込額
         * 
         * @ORM\Column(name="transfered", type="decimal", precision=12, scale=2, nullable=true)
         */
        private $transfered;

        /**
         * @var boolean
         * 振込状態
         *
         * @ORM\Column(name="transfered_status", type="boolean", options={"default":false})
         */
        private $transfered_status = false;

        /**
         * @var string
         * 振込失敗時のエラーメッセージ
         * 
         * @ORM\Column(name="message", type="string", length=255)
         */
        private $message;

        /**
         * @var \DateTime
         * 振込日時
         * 
         * @ORM\Column(name="transfered_date", type="datetimetz")
         */
        private $transfered_date;

        /**
         * @var \DateTime
         *
         * @ORM\Column(name="create_date", type="datetimetz")
         */
        private $create_date;

        /**
         * @var \DateTime
         *
         * @ORM\Column(name="update_date", type="datetimetz")
         */
        private $update_date;

        /**
         * Get id.
         *
         * @return int
         */
        public function getId()
        {
            return $this->id;
        }

        /**
         * Set customer_id.
         *
         * @param integer $customer_id
         *
         * @return this
         */
        public function setCustomerId($customer_id = null)
        {
            $this->customer_id = $customer_id;

            return $this;
        }

        /**
         * Get customer_id.
         *
         * @return integer
         */
        public function getCustomerId()
        {
            return $this->customer_id;
        }

        /**
         * Set balance.
         *
         * @param string|null $balance
         *
         * @return this
         */
        public function setBalance($balance = null)
        {
            $this->balance = $balance;

            return $this;
        }

        /**
         * Get balance.
         *
         * @return string|null
         */
        public function getBalance()
        {
            return $this->balance;
        }

        /**
         * Set transferable.
         *
         * @param string|null $transferable
         *
         * @return this
         */
        public function setTransferable($transferable = null)
        {
            $this->transferable = $transferable;

            return $this;
        }

        /**
         * Get transferable.
         *
         * @return string|null
         */
        public function getTransferable()
        {
            return $this->transferable;
        }

        /**
         * Set transfered.
         *
         * @param string|null $transfered
         *
         * @return this
         */
        public function setTransfered($transfered = null)
        {
            $this->transfered = $transfered;

            return $this;
        }

        /**
         * Get transfered.
         *
         * @return string|null
         */
        public function getTransfered()
        {
            return $this->transfered;
        }

        /**
         * Set transferedStatus.
         *
         * @param boolean $transferedStatus
         *
         * @return this
         */
        public function setTransferedStatus($transferedStatus)
        {
            $this->transfered_status = $transferedStatus;

            return $this;
        }

        /**
         * Get transferedStatus.
         *
         * @return boolean
         */
        public function getTransferedStatus()
        {
            return $this->transfered_status;
        }

        /**
         * Set message.
         *
         * @param string|null $message
         *
         * @return this
         */
        public function setMessage($message = null)
        {
            $this->message = $message;

            return $this;
        }

        /**
         * Get message.
         *
         * @return string|null
         */
        public function getMessage()
        {
            return $this->message;
        }

        /**
         * Set transfered_date.
         *
         * @param \DateTime $transferedDate
         *
         * @return this
         */
        public function setTransferedDate($transferedDate)
        {
            $this->transfered_date = $transferedDate;

            return $this;
        }

        /**
         * Get transferedDate.
         *
         * @return \DateTime
         */
        public function getTransferedDate()
        {
            return $this->transfered_date;
        }

        /**
         * Set createDate.
         *
         * @param \DateTime $createDate
         *
         * @return this
         */
        public function setCreateDate($createDate)
        {
            $this->create_date = $createDate;

            return $this;
        }

        /**
         * Get createDate.
         *
         * @return \DateTime
         */
        public function getCreateDate()
        {
            return $this->create_date;
        }

        /**
         * Set updateDate.
         *
         * @param \DateTime $updateDate
         *
         * @return this
         */
        public function setUpdateDate($updateDate)
        {
            $this->update_date = $updateDate;

            return $this;
        }

        /**
         * Get updateDate.
         *
         * @return \DateTime
         */
        public function getUpdateDate()
        {
            return $this->update_date;
        }
    }
}