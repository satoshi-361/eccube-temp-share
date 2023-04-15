<?php

namespace Plugin\EccubePaymentLite4\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;

/**
 * @ORM\Table(name="plg_eccube_payment_lite4_config")
 * @ORM\Entity(repositoryClass="Plugin\EccubePaymentLite4\Repository\ConfigRepository")
 */
class Config extends AbstractEntity
{
    const ENVIRONMENTAL_SETTING_DEVELOPMENT = 1;
    const ENVIRONMENTAL_SETTING_PRODUCTION = 2;
    const LINK_PAYMENT = 1;
    const TOKEN_PAYMENT = 2;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="contract_code", type="string", length=255, nullable=true)
     */
    private $contract_code;

    /**
     * @var integer
     *
     * @ORM\Column(name="environmental_setting", type="integer")
     */
    private $environmental_setting;

    /**
     * @var integer
     *
     * @ORM\Column(name="credit_payment_setting", type="integer", options={"default":1})
     */
    private $credit_payment_setting;

    /**
     * @var string
     *
     * @deprecated
     * @ORM\Column(name="use_payment", type="string", length=1024, nullable=true)
     */
    private $use_payment;

    /**
     * @var string
     *
     * @deprecated
     * @ORM\Column(name="use_convenience", type="string", length=1024, nullable=true)
     */
    private $use_convenience;

    /**
     * @var integer
     *
     * @ORM\Column(name="card_expiration_notification_days", type="integer")
     */
    private $card_expiration_notification_days;

    /**
     * @var boolean
     *
     * @ORM\Column(name="regular", type="boolean")
     */
    private $regular;

    /**
     * @var integer
     *
     * @ORM\Column(name="block_mode", type="integer", nullable=true)
     */
    private $block_mode;

    /**
     * @var integer
     *
     * @ORM\Column(name="access_frequency_time", type="integer", nullable=true)
     */
    private $access_frequency_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="access_frequency", type="integer", nullable=true)
     */
    private $access_frequency;

    /**
     * @var integer
     *
     * @ORM\Column(name="block_time", type="integer", nullable=true)
     */
    private $block_time;

    /**
     * @var string
     *
     * @ORM\Column(name="white_list", type="string", nullable=true)
     */
    private $white_list;

    /**
     * @ORM\ManyToMany(targetEntity="Plugin\EccubePaymentLite4\Entity\GmoEpsilonPayment", inversedBy="gmoEpsilonConfigs", cascade={"persist","remove"})
     * @ORM\JoinTable(name="plg_eccube_payment_lite4_configs_payments")
     */
    private $gmoEpsilonPayments;

    /**
     * @ORM\ManyToMany(targetEntity="ConvenienceStore", inversedBy="gmoEpsilonConfigs", cascade={"persist","remove"})
     * @ORM\JoinTable(name="plg_eccube_payment_lite4_configs_convenience_stores")
     */
    private $ConvenienceStores;

    /**
     * @ORM\ManyToMany(targetEntity="Plugin\EccubePaymentLite4\Entity\IpBlackList", inversedBy="gmoEpsilonConfigs", cascade={"persist","remove"})
     * @ORM\JoinTable(name="plg_eccube_payment_lite4_configs_ip_black_list")
     */
    private $ipBlackList;

    /**
     * @ORM\Column(name="regular_order_notification_email", type="string", length=255, nullable=true)
     */
    private $regular_order_notification_email;

    /**
     * @ORM\ManyToMany(targetEntity="Plugin\EccubePaymentLite4\Entity\MyPageRegularSetting", inversedBy="gmoEpsilonConfigs", cascade={"persist","remove"})
     * @ORM\JoinTable(name="plg_eccube_payment_lite4_configs_my_page_regular_settings")
     */
    private $myPageRegularSettings;

    /**
     * @ORM\Column(name="next_delivery_date_changeable_range_days", type="integer")
     */
    private $next_delivery_date_changeable_range_days;
    /**
     * @ORM\Column(name="first_delivery_days", type="integer")
     */
    private $first_delivery_days;
    /**
     * @ORM\Column(name="next_delivery_days_at_regular_resumption", type="integer")
     */
    private $next_delivery_days_at_regular_resumption;
    /**
     * @ORM\Column(name="next_delivery_days_after_re_payment", type="integer")
     */
    private $next_delivery_days_after_re_payment;
    /**
     * @ORM\Column(name="regular_order_deadline", type="integer")
     */
    private $regular_order_deadline;
    /**
     * @ORM\Column(name="regular_delivery_notification_email_days", type="integer", nullable=true)
     */
    private $regular_delivery_notification_email_days;

    /**
     * @ORM\Column(name="regular_stoppable_count", type="integer")
     */
    private $regular_stoppable_count;

    /**
     * @ORM\Column(name="regular_cancelable_count", type="integer")
     */
    private $regular_cancelable_count;
    /**
     * @ORM\Column(name="regular_resumable_period", type="integer", nullable=true)
     */
    private $regular_resumable_period;

    /**
     * @ORM\Column(name="regular_specified_count_notification_mail", type="integer", nullable=true)
     */
    private $regular_specified_count_notification_mail;

    /** @ORM\Column(name="regular_point_magnification", type="integer", nullable=true)
     */
    private $regular_point_magnification;

    public function __construct()
    {
        $this->gmoEpsilonPayments = new ArrayCollection();
        $this->ConvenienceStores = new ArrayCollection();
        $this->myPageRegularSettings = new ArrayCollection();
        $this->ipBlackList = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getContractCode()
    {
        return $this->contract_code;
    }

    public function setContractCode($contract_code)
    {
        $this->contract_code = $contract_code;

        return $this;
    }

    public function getEnvironmentalSetting()
    {
        return $this->environmental_setting;
    }

    public function setEnvironmentalSetting(int $environmental_setting)
    {
        $this->environmental_setting = $environmental_setting;

        return $this;
    }

    public function getCreditPaymentSetting()
    {
        return $this->credit_payment_setting;
    }

    public function setCreditPaymentSetting($credit_payment_setting)
    {
        $this->credit_payment_setting = $credit_payment_setting;

        return $this;
    }

    public function getUsePayment()
    {
        return $this->use_payment;
    }

    public function setUsePayment($use_payment)
    {
        $this->use_payment = $use_payment;

        return $this;
    }

    public function getUseConvenience()
    {
        return $this->use_convenience;
    }

    public function setUseConvenience($use_convenience)
    {
        $this->use_convenience = $use_convenience;

        return $this;
    }

    public function getRegular()
    {
        return $this->regular;
    }

    public function setRegular($regular)
    {
        $this->regular = $regular;

        return $this;
    }

    public function getBlockMode()
    {
        return $this->block_mode;
    }

    public function setBlockMode($block_mode)
    {
        $this->block_mode = $block_mode;

        return $this;
    }

    public function getAccessFrequencyTime()
    {
        return $this->access_frequency_time;
    }

    public function setAccessFrequencyTime($access_frequency_time)
    {
        $this->access_frequency_time = $access_frequency_time;

        return $this;
    }

    public function getAccessFrequency()
    {
        return $this->access_frequency;
    }

    public function setAccessFrequency($access_frequency)
    {
        $this->access_frequency = $access_frequency;

        return $this;
    }

    public function getBlockTime()
    {
        return $this->block_time;
    }

    public function setBlockTime($block_time)
    {
        $this->block_time = $block_time;

        return $this;
    }

    public function getWhiteList()
    {
        return $this->white_list;
    }

    public function setWhiteList($white_list)
    {
        $this->white_list = $white_list;

        return $this;
    }

    public function getGmoEpsilonPayments()
    {
        return $this->gmoEpsilonPayments;
    }

    public function getConvenienceStores()
    {
        return $this->ConvenienceStores;
    }

    public function getMypageRegularSettings()
    {
        return $this->myPageRegularSettings;
    }

    public function getIpBlackList()
    {
        return $this->ipBlackList;
    }

    public function addGmoEpsilonPayment(GmoEpsilonPayment $gmoEpsilonPayment)
    {
        $gmoEpsilonPayment->addGmoEpsilonConfig($this);
        $this->gmoEpsilonPayments[] = $gmoEpsilonPayment;

        return $this;
    }

    public function addConvenienceStores(ConvenienceStore $ConvenienceStore)
    {
        $ConvenienceStore->addGmoEpsilonConfig($this);
        $this->ConvenienceStores[] = $ConvenienceStore;

        return $this;
    }

    public function addMyPageRegularSetting(MyPageRegularSetting $myPageRegularSetting)
    {
        $myPageRegularSetting->addGmoEpsilonConfig($this);
        $this->myPageRegularSettings[] = $myPageRegularSetting;

        return $this;
    }

    public function addIpBlackList(IpBlackList $ipBlackList)
    {
        $ipBlackList->addGmoEpsilonConfig($this);
        $this->ipBlackList[] = $ipBlackList;

        return $this;
    }

    public function getRegularOrderNotificationEmail()
    {
        return $this->regular_order_notification_email;
    }

    public function setRegularOrderNotificationEmail($regular_order_notification_email)
    {
        $this->regular_order_notification_email = $regular_order_notification_email;

        return $this;
    }

    public function getNextDeliveryDateChangeableRangeDays()
    {
        return $this->next_delivery_date_changeable_range_days;
    }

    public function setNextDeliveryDateChangeableRangeDays($next_delivery_date_changeable_range_days)
    {
        $this->next_delivery_date_changeable_range_days = $next_delivery_date_changeable_range_days;

        return $this;
    }

    public function getFirstDeliveryDays()
    {
        return $this->first_delivery_days;
    }

    public function setFirstDeliveryDays($first_delivery_days)
    {
        $this->first_delivery_days = $first_delivery_days;

        return $this;
    }

    public function getNextDeliveryDaysAtRegularResumption()
    {
        return $this->next_delivery_days_at_regular_resumption;
    }

    public function setNextDeliveryDaysAtRegularResumption($next_delivery_days_at_regular_resumption)
    {
        $this->next_delivery_days_at_regular_resumption = $next_delivery_days_at_regular_resumption;

        return $this;
    }

    public function getNextDeliveryDaysAfterRePayment()
    {
        return $this->next_delivery_days_after_re_payment;
    }

    public function setNextDeliveryDaysAfterRePayment($next_delivery_days_after_re_payment)
    {
        $this->next_delivery_days_after_re_payment = $next_delivery_days_after_re_payment;

        return $this;
    }

    public function getRegularOrderDeadline()
    {
        return $this->regular_order_deadline;
    }

    public function setRegularOrderDeadline($regular_order_deadline)
    {
        $this->regular_order_deadline = $regular_order_deadline;

        return $this;
    }

    public function getRegularDeliveryNotificationEmailDays()
    {
        return $this->regular_delivery_notification_email_days;
    }

    public function setRegularDeliveryNotificationEmailDays($regular_delivery_notification_email_days)
    {
        $this->regular_delivery_notification_email_days = $regular_delivery_notification_email_days;

        return $this;
    }

    public function getCardExpirationNotificationDays()
    {
        return $this->card_expiration_notification_days;
    }

    public function setCardExpirationNotificationDays(int $card_expiration_notification_days)
    {
        $this->card_expiration_notification_days = $card_expiration_notification_days;

        return $this;
    }

    public function getRegularStoppableCount()
    {
        return $this->regular_stoppable_count;
    }

    public function setRegularStoppableCount($regular_stoppable_count): self
    {
        $this->regular_stoppable_count = $regular_stoppable_count;

        return $this;
    }

    public function getRegularCancelableCount()
    {
        return $this->regular_cancelable_count;
    }

    public function setRegularCancelableCount($regular_cancelable_count): self
    {
        $this->regular_cancelable_count = $regular_cancelable_count;

        return $this;
    }

    public function getRegularResumablePeriod()
    {
        return $this->regular_resumable_period;
    }

    public function setRegularResumablePeriod($regular_resumable_period): self
    {
        $this->regular_resumable_period = $regular_resumable_period;

        return $this;
    }

    public function getRegularSpecifiedCountNotificationMail()
    {
        return $this->regular_specified_count_notification_mail;
    }

    public function setRegularSpecifiedCountNotificationMail($regular_specified_count_notification_mail): self
    {
        $this->regular_specified_count_notification_mail = $regular_specified_count_notification_mail;

        return $this;
    }

    public function getRegularPointMagnification()
    {
        return $this->regular_point_magnification;
    }

    public function setRegularPointMagnification($regular_point_magnification): self
    {
        $this->regular_point_magnification = $regular_point_magnification;

        return $this;
    }
}
