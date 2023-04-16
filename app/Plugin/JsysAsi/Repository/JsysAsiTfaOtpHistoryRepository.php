<?php
namespace Plugin\JsysAsi\Repository;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Eccube\Repository\AbstractRepository;
use Plugin\JsysAsi\Entity\JsysAsiTfaOtpHistory;
use Plugin\JsysAsi\Entity\JsysAsiTfaUser;

/**
 * 2要素認証OTP履歴Repository
 * @author manabe
 *
 */
class JsysAsiTfaOtpHistoryRepository extends AbstractRepository
{
    /**
     * JsysAsiTfaOtpHistoryRepository constructor.
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, JsysAsiTfaOtpHistory::class);
    }

    /**
     * 使用済みのOTPか調べます。
     * @param JsysAsiTfaUser $jsysAsiTfaUser
     * @param string $otp
     * @param int $banned_min
     * @throws \Exception
     * @return boolean
     */
    public function isUsedOtp(JsysAsiTfaUser $jsysAsiTfaUser, $otp, $banned_min)
    {
        if (empty($banned_min)) {
            throw new \Exception('JsysAsiTfaOtpHistory isUsedOtp empty minutes.');
        }

        $date = new \DateTime();
        $date->modify("-{$banned_min} minutes");

        $record = $this->createQueryBuilder('h')
            ->select('h')
            ->where('h.tfa_user_id = :tfaUserId')
            ->andWhere('h.otp = :otp')
            ->andWhere('h.create_date >= :create_date')
            ->setParameter('tfaUserId', $jsysAsiTfaUser->getId())
            ->setParameter('otp', $otp)
            ->setParameter('create_date', $date)
            ->getQuery()
            ->getResult();

        return !$record ? false : true;
    }

    /**
     * 指定日数より古いレコードを削除します。
     * @param int $days
     * @return boolean
     */
    public function deleteOldRecordsByDays($days = 2)
    {
        $em = $this->getEntityManager();
        try {
            $em->beginTransaction();

            $days = empty($days) ? 2 : $days;
            $date = new \DateTime();
            $date->modify("-{$days} day");

            $qb = $this->createQueryBuilder('h')
                ->delete()
                ->where('h.create_date < :create_date')
                ->setParameter('create_date', $date);

            $cnt = $qb->getQuery()->getResult();

            $em->flush();
            $em->commit();

            log_info("JsysAsi OTP履歴 削除 {$cnt}件", [
                'date' => $date->format('Y-m-d H:i:s'),
            ]);
            return true;

        } catch (\Exception $ex) {
            log_info("JsysAsi OTP履歴 削除 失敗", ['error' => $ex->getMessage()]);
            $em->rollback();
            return false;
        }
    }

    /**
     * 2要素認証ユーザーの履歴をすべて削除します。
     * @param JsysAsiTfaUser $jsysAsiTfaUser
     * @throws \Exception
     * @return boolean
     */
    public function deleteByTfaUser(JsysAsiTfaUser $jsysAsiTfaUser)
    {
        $em = $this->getEntityManager();
        try {
            $em->beginTransaction();

            $qb = $this->createQueryBuilder('h')
                ->delete()
                ->where('h.tfa_user_id = :tfaUserId')
                ->setParameter('tfaUserId', $jsysAsiTfaUser->getId());

            $cnt = $qb->getQuery()->getResult();

            $em->flush();
            $em->commit();

            log_info("JsysAsi OTP履歴 一括削除 {$cnt}件", [
                'JsysAsiTfaUser' => $jsysAsiTfaUser,
            ]);
            return true;

        } catch (\Exception $ex) {
            $em->rollback();
            $msg = $ex->getMessage();
            log_info("JsysAsi OTP履歴 一括削除 失敗", ['error' => $msg]);
            throw new \Exception("JsysAsi otp history delete failed. {$msg}");
        }
    }

}
