<?php
namespace Plugin\JsysAsi;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Plugin\AbstractPluginManager;
use Plugin\JsysAsi\Entity\Config;
use Plugin\JsysAsi\Entity\JsysAsiLoginHistoryLockStatus;
use Plugin\JsysAsi\Entity\JsysAsiLoginHistoryStatus;
use Plugin\JsysAsi\Entity\JsysAsiLoginHistoryTfaStatus;

class PluginManager extends AbstractPluginManager
{
    /**
     * {@inheritDoc}
     * @see \Eccube\Plugin\AbstractPluginManager::enable()
     */
    public function enable(array $meta, ContainerInterface $container)
    {
        $em = $container->get('doctrine.orm.entity_manager');

        // プラグイン設定を作成
        $this->createConfig($em);
        // ログイン履歴ステータスを作成
        $this->createLoginHistoryStatus($em);
        // ログイン履歴2要素認証ステータスを作成
        $this->createLoginHistoryTfaStatus($em);
        // ログイン履歴ロックステータスを作成
        $this->createLoginHistoryLockStatus($em);
    }

    /**
     * プラグイン設定を作成します。
     * @param EntityManagerInterface $em
     */
    protected function createConfig(EntityManagerInterface $em)
    {
        if ($this->existsRecord($em, Config::class, 1)) {
            return;
        }

        $Config = new Config();
        $Config
            ->setOptionTfa(false)
            ->setOptionLoginSuccessMail(false)
            ->setOptionLoginFailureMail(false)
            ->setOptionIpAddressLock(false)
            ->setCreateDate(new \DateTime())
            ->setUpdateDate(new \DateTime());

        $em->persist($Config);
        $em->flush($Config);
    }

    /**
     * ログイン履歴ステータスを作成します。
     * @param EntityManagerInterface $em
     */
    protected function createLoginHistoryStatus(EntityManagerInterface $em)
    {
        $class = JsysAsiLoginHistoryStatus::class;
        $datas = [
            ['id' => 1, 'name' => '成功', 'sort' => 1],
            ['id' => 2, 'name' => '拒否', 'sort' => 2],
            ['id' => 9, 'name' => '失敗', 'sort' => 9],
        ];
        foreach ($datas as $data) {
            if ($this->existsRecord($em, $class, $data['id'])) {
                continue;
            }

            $Status = new JsysAsiLoginHistoryStatus();
            $Status
                ->setId($data['id'])
                ->setName($data['name'])
                ->setSortNo($data['sort']);
            $em->persist($Status);
            $em->flush($Status);
        }
    }

    /**
     * ログイン履歴2要素認証ステータスを作成します。
     * @param EntityManagerInterface $em
     */
    protected function createLoginHistoryTfaStatus(EntityManagerInterface $em)
    {
        $class = JsysAsiLoginHistoryTfaStatus::class;
        $datas = [
            ['id' => 1, 'name' => '機能無効', 'sort' => 1],
            ['id' => 2, 'name' => '有効OTP', 'sort' => 2],
            ['id' => 3, 'name' => '無効OTP', 'sort' => 3],
            ['id' => 4, 'name' => '使用済OTP', 'sort' => 4],
        ];
        foreach ($datas as $data) {
            if ($this->existsRecord($em, $class, $data['id'])) {
                continue;
            }

            $Status = new JsysAsiLoginHistoryTfaStatus();
            $Status
                ->setId($data['id'])
                ->setName($data['name'])
                ->setSortNo($data['sort']);
            $em->persist($Status);
            $em->flush($Status);
        }
    }

    /**
     * ログイン履歴ロックステータスを作成します。
     * @param EntityManagerInterface $em
     */
    protected function createLoginHistoryLockStatus(EntityManagerInterface $em)
    {
        $class = JsysAsiLoginHistoryLockStatus::class;
        $datas = [
            ['id' => 1, 'name' => '機能無効', 'sort' => 1],
            ['id' => 2, 'name' => '未ロック', 'sort' => 2],
            ['id' => 3, 'name' => 'ロック済み', 'sort' => 3],
        ];
        foreach ($datas as $data) {
            if ($this->existsRecord($em, $class, $data['id'])) {
                continue;
            }

            $Status = new JsysAsiLoginHistoryLockStatus();
            $Status
                ->setId($data['id'])
                ->setName($data['name'])
                ->setSortNo($data['sort']);
            $em->persist($Status);
            $em->flush($Status);
        }
    }

    /**
     * レコードが存在するか調べます。
     * @param EntityManagerInterface $em
     * @param string $class
     * @param int $id
     * @return boolean
     */
    private function existsRecord(EntityManagerInterface $em, $class, $id)
    {
        $Record = $em->find($class, $id);
        if (!$Record) {
            return false;
        }
        return true;
    }

}
