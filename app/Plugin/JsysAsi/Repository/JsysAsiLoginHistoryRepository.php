<?php
namespace Plugin\JsysAsi\Repository;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Component\HttpFoundation\RequestStack;
use Eccube\Repository\AbstractRepository;
use Eccube\Util\StringUtil;
use Plugin\JsysAsi\Entity\JsysAsiLoginHistory;
use Plugin\JsysAsi\Entity\JsysAsiLoginHistoryStatus;
use Plugin\JsysAsi\Util\JsysAsiUserAgentUtil;

/**
 * ログイン履歴Repository
 * @author manabe
 *
 */
class JsysAsiLoginHistoryRepository extends AbstractRepository
{
    /** Form Text */
    const TEXT = 1;
    /** Form Date */
    const DATE = 2;
    /** Form CheckBox */
    const CHECK = 3;


    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var JsysAsiLoginHistoryStatusRepository
     */
    private $statusRepo;

    /**
     * @var JsysAsiLoginHistoryTfaStatusRepository
     */
    private $tfaStatusRepo;

    /**
     * @var JsysAsiLoginHistoryLockStatusRepository
     */
    private $lockStatusRepo;


    /**
     * JsysAsiLoginHistoryRepository constructor.
     * @param JsysAsiLoginHistoryStatusRepository $statusRepo
     * @param JsysAsiLoginHistoryTfaStatusRepository $tfaStatusRepo
     * @param JsysAsiLoginHistoryLockStatusRepository $lockStatusRepo
     * @param RegistryInterface $registry
     */
    public function __construct(
        RequestStack $requestStack,
        JsysAsiLoginHistoryStatusRepository $statusRepo,
        JsysAsiLoginHistoryTfaStatusRepository $tfaStatusRepo,
        JsysAsiLoginHistoryLockStatusRepository $lockStatusRepo,
        RegistryInterface $registry
    ) {
        parent::__construct($registry, JsysAsiLoginHistory::class);

        $this->requestStack   = $requestStack;
        $this->statusRepo     = $statusRepo;
        $this->tfaStatusRepo  = $tfaStatusRepo;
        $this->lockStatusRepo = $lockStatusRepo;
    }

    /**
     * 抽出条件を適用したQueryBuilderを取得します。
     * @param array $searchData
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getQueryBuilderBySearchData($searchData)
    {
        $qb = $this->createQueryBuilder('l')->select('l');

        // ログインID・IPアドレス
        if ($this->isSetInSearchData($searchData, 'multi', self::TEXT)) {
            $where = 'l.login_id LIKE :login_id OR l.ip_address LIKE :ip_address';
            $value = '%' . str_replace('%', '\\%', $searchData['multi']) . '%';
            $qb
                ->andWhere($where)
                ->setParameter('login_id', $value)
                ->setParameter('ip_address', $value);
        }

        // ログイン日時(開始)
        if ($this->isSetInSearchData($searchData, 'login_date_start', self::DATE)) {
            $qb
                ->andWhere('l.login_date >= :login_start')
                ->setParameter('login_start', $searchData['login_date_start']);
        }

        // ログイン日時(終了)
        if ($this->isSetInSearchData($searchData, 'login_date_end', self::DATE)) {
            $qb
                ->andWhere('l.login_date <= :login_end')
                ->setParameter('login_end', $searchData['login_date_end']);
        }

        // ステータス
        if ($this->isSetInSearchData($searchData, 'status', self::CHECK)) {
            $qb
                ->andWhere($qb->expr()->in('l.Status', ':Status'))
                ->setParameter('Status', $searchData['status']);
        }

        // 2要素認証ステータス
        if ($this->isSetInSearchData($searchData, 'tfa_status', self::CHECK)) {
            $qb
                ->andWhere($qb->expr()->in('l.TfaStatus', ':TfaStatus'))
                ->setParameter('TfaStatus', $searchData['tfa_status']);
        }

        // ロックステータス
        if ($this->isSetInSearchData($searchData, 'lock_status', self::CHECK)) {
            $qb
                ->andWhere($qb->expr()->in('l.LockStatus', ':LockStatus'))
                ->setParameter('LockStatus', $searchData['lock_status']);
        }

        // ソート順
        $qb->addOrderBy('l.id', 'DESC');

        return $qb;
    }

    /**
     * ログイン履歴へ保存を行います。
     * @param string $loginId
     * @param \DateTime $loginDate
     * @param int $status
     * @param int $tfaStatus
     * @param int $lockStatus
     * @throws \Exception
     * @return boolean
     */
    public function saveHistory(
        $loginId,
        \DateTime $loginDate,
        int $status,
        int $tfaStatus,
        int $lockStatus
    ) {
        // ログインIDが空の場合は「未入力」を代入
        if (empty($loginId)) {
            $loginId = trans('jsys_asi.empty.login_id');
        }
        // ステータス系項目のエンティティを取得
        $entityStatus = $this->statusRepo->find($status);
        if (!$entityStatus) {
            throw new \Exception(trans('jsys_asi.not_found.status'));
        }
        $entityTfaStatus = $this->tfaStatusRepo->find($tfaStatus);
        if (!$entityTfaStatus) {
            throw new \Exception(trans('jsys_asi.not_found.tfa_status'));
        }
        $entityLockStatus = $this->lockStatusRepo->find($lockStatus);
        if (!$entityLockStatus) {
            throw new \Exception(trans('jsys_asi.not_found.lock_status'));
        }

        // ログイン履歴へ新規登録
        $loginHistory = new JsysAsiLoginHistory();
        $loginHistory
            ->setLoginDate($loginDate)
            ->setLoginId($loginId)
            ->setIpAddress($this->requestStack->getMasterRequest()->getClientIp())
            ->setStatus($entityStatus)
            ->setTfaStatus($entityTfaStatus)
            ->setLockStatus($entityLockStatus)
            ->setUserAgent(JsysAsiUserAgentUtil::getUserAgent($this->requestStack));

        $this->getEntityManager()->persist($loginHistory);
        $this->getEntityManager()->flush($loginHistory);

        return true;
    }

    /**
     * 指定件数内の存在するレコード数とログイン成功件数を取得します。
     * @param string $ipAddress
     * @param int $limit
     * @return array
     */
    public function getAllAndSuccessCounts($ipAddress, $limit)
    {
        if (empty($ipAddress) || empty($limit)) {
            return ['cnt_success' => 0, 'cnt_all' => 0];
        }

        $sql = 'SELECT '
             . '  SUM(CASE WHEN status = :status THEN 1 ELSE 0 END) AS cnt_success, '
             . '  count(*) AS cnt_all '
             . 'FROM ( '
             . '  SELECT status '
             . '  FROM plg_jsys_asi_login_history '
             . '  WHERE ip_address = :ip_address '
             . '  ORDER BY id DESC '
             . '  LIMIT :limit '
             . ') AS h ';

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('cnt_success', 'cnt_success');
        $rsm->addScalarResult('cnt_all', 'cnt_all');

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $query->setParameters([
            ':ip_address' => $ipAddress,
            ':limit'      => $limit,
            ':status'     => JsysAsiLoginHistoryStatus::SUCCESS,
        ]);
        $result = $query->getOneOrNullResult();

        // 全件数が指定件数に満たない場合はロックしないような値を返す
        if (!$result) {
            return ['cnt_success' => 0, 'cnt_all' => 0];
        }
        return $result;
    }


    /**
     * 抽出条件配列に有効な値が設定されているか調べます。
     * @param array $datas
     * @param string $key
     * @param int $formType
     * @return boolean
     */
    private function isSetInSearchData($datas, $key, $formType)
    {
        switch ($formType) {
            case self::TEXT:
                return isset($datas[$key]) && StringUtil::isNotBlank($datas[$key]);
            case self::DATE:
                return isset($datas[$key]) && !is_null($datas[$key]);
            case self::CHECK:
                return !empty($datas[$key]) && count($datas[$key]) > 0;
            default:
                return isset($datas[$key]) && !is_null($datas[$key]);
        }
    }

}
