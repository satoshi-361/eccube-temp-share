<?php
namespace Plugin\JsysAsi\Service;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Common\EccubeConfig;
use Plugin\JsysAsi\Entity\Config;
use Plugin\JsysAsi\Entity\JsysAsiLockedIpAddress;
use Plugin\JsysAsi\Repository\ConfigRepository;
use Plugin\JsysAsi\Repository\JsysAsiLockedIpAddressRepository;
use Plugin\JsysAsi\Repository\JsysAsiLoginHistoryRepository;

/**
 * ロックサービス
 * @author manabe
 *
 */
class JsysAsiLockService
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EccubeConfig
     */
    private $eccubeConfig;

    /**
     * @var JsysAsiLoginHistoryRepository
     */
    private $historyRepo;

    /**
     * @var JsysAsiLockedIpAddressRepository
     */
    private $lockedIpRepo;

    /**
     * @var Config
     */
    private $Config;


    /**
     * JsysAsiLockService constructor.
     * @param EntityManagerInterface $entityManager
     * @param EccubeConfig $eccubeConfig
     * @param ConfigRepository $configRepo
     * @param JsysAsiLoginHistoryRepository $historyRepo
     * @param JsysAsiLockedIpAddressRepository $lockedIpRepo
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        EccubeConfig $eccubeConfig,
        ConfigRepository $configRepo,
        JsysAsiLoginHistoryRepository $historyRepo,
        JsysAsiLockedIpAddressRepository $lockedIpRepo
    ) {
        $this->entityManager = $entityManager;
        $this->eccubeConfig  = $eccubeConfig;
        $this->historyRepo   = $historyRepo;
        $this->lockedIpRepo  = $lockedIpRepo;
        $this->Config        = $configRepo->get();
    }

    /**
     * スーパーバイザーか調べます。
     * @return boolean
     */
    public function checkSupervisor()
    {
        if (!$this->eccubeConfig->has('jsys_asi_lock_supervisor')) {
            return false;
        }
        if (true === $this->eccubeConfig['jsys_asi_lock_supervisor']) {
            return true;
        }
        return false;
    }

    /**
     * ロック済みのIPアドレスか調べます。
     * @param string $ipAddress
     * @return boolean
     */
    public function isLockedIpAddress(string $ipAddress)
    {
        // スーパーバイザーの場合は未ロック判定
        if ($this->checkSupervisor()) {
            return false;
        }

        $lockedIp = $this->lockedIpRepo->findOneBy(['ip_address' => $ipAddress]);
        return !$lockedIp ? false : true;
    }

    /**
     * ロックすべきIPアドレスか調べます。
     * @param string $ipAddress
     * @return boolean
     */
    public function shouldLockIpAddress(string $ipAddress)
    {
        if (!$this->Config->getOptionIpAddressLock()) {
            return false;
        }
        if (!$this->Config->getOptionIpAddressLockCount()) {
            return false;
        }

        // ログイン履歴から指定IPアドレスの直近の件数を取得
        $limit  = $this->Config->getOptionIpAddressLockCount();
        $counts = $this->historyRepo->getAllAndSuccessCounts($ipAddress, $limit);

        // 指定の件数に満たない場合はロックしない
        if ($counts['cnt_all'] < $limit) {
            return false;
        }
        // 直近で1度でもログイン成功している場合はロックしない
        if ($counts['cnt_success'] > 0) {
            return false;
        }
        // 直近で1度もログイン成功しない場合はロックすべき
        return true;
    }

    /**
     * IPアドレスをロックします。
     * @param string $ipAddress
     * @return boolean
     */
    public function lockIpAddress(string $ipAddress)
    {
        // すでにロック済みの場合は正常終了
        $LockedIp = $this->lockedIpRepo->findOneBy(['ip_address' => $ipAddress]);
        if ($LockedIp) {
            return true;
        }

        $LockedIp = new JsysAsiLockedIpAddress();
        $LockedIp->setIpAddress($ipAddress);

        $this->entityManager->persist($LockedIp);
        $this->entityManager->flush($LockedIp);

        return true;
    }

    /**
     * IPアドレスのロックを解除します。
     * @param string $ipAddress
     * @return boolean
     */
    public function unlockIpAddress(string $ipAddress)
    {
        // ロックされていない場合は正常終了
        $LockedIp = $this->lockedIpRepo->findOneBy(['ip_address' => $ipAddress]);
        if (!$LockedIp) {
            return true;
        }

        $this->entityManager->remove($LockedIp);
        $this->entityManager->flush($LockedIp);

        return true;
    }

}
