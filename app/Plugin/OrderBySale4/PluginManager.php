<?php

/*
 * This file is part of OrderBySale4
 *
 * Copyright(c) U-Mebius Inc. All Rights Reserved.
 *
 * https://umebius.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\OrderBySale4;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\Block;
use Eccube\Entity\Master\DeviceType;
use Eccube\Entity\Master\ProductListOrderBy;
use Eccube\Plugin\AbstractPluginManager;
use Plugin\OrderBySale4\Entity\Config;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class PluginManager.
 */
class PluginManager extends AbstractPluginManager
{
    private $blocks = [
        [
            'file_name' => 'order_by_sale',
            'name' => '人気順商品',
            'use_controller' => true,
            'deletable' => false,
        ],
    ];

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * Install the plugin.
     */
    public function install(array $meta, ContainerInterface $container)
    {
    }

    /**
     * Update the plugin.
     */
    public function update(array $meta, ContainerInterface $container)
    {
        $this->createBlocks($container);
        $this->copyTemplateFile($container);
    }

    /**
     * Enable the plugin.
     */
    public function enable(array $meta, ContainerInterface $container)
    {
        $this->createMasterData($container);
        $this->createBlocks($container);
        $this->copyTemplateFile($container);
    }

    /**
     * Disable the plugin.
     */
    public function disable(array $meta, ContainerInterface $container)
    {
        $this->deleteMasterData($container);
        $this->removeBlocks($container);
    }

    /**
     * Uninstall the plugin.
     */
    public function uninstall(array $meta, ContainerInterface $container)
    {
        $this->deleteMasterData($container);
        $this->removeBlocks($container);
    }

    private function createMasterData(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine')->getManager();

        $Configs = $entityManager->getRepository('Plugin\OrderBySale4\Entity\Config')->findAll();

        $qb = $entityManager->createQueryBuilder();
        $maxRank = $qb->select('MAX(m.sort_no)')
            ->from(ProductListOrderBy::class, 'm')
            ->getQuery()
            ->getSingleScalarResult();

        if (!count($Configs)) {
            $qb = $entityManager->createQueryBuilder();
            $maxId = $qb->select('MAX(m.id)')
                ->from(ProductListOrderBy::class, 'm')
                ->getQuery()
                ->getSingleScalarResult();
            $data = [
                [
                    'className' => 'Eccube\Entity\Master\ProductListOrderBy',
                    'id' => ++$maxId,
                    'sort_no' => ++$maxRank,
                    'name' => '売れ筋順',
                ],
            ];
        } else {
            /* @var $Configs Config[] */
            foreach ($Configs as $Config) {
                $data[] =
                    [
                        'className' => 'Eccube\Entity\Master\ProductListOrderBy',
                        'id' => $Config->getProductListOrderById(),
                        'sort_no' => ++$maxRank,
                        'name' => $Config->getName(),
                    ]
                ;
            }
        }

        foreach ($data as $row) {
            $Entity = $entityManager->getRepository($row['className'])
                ->find($row['id']);
            if (!$Entity) {
                $Entity = new $row['className']();
                $Entity
                    ->setName($row['name'])
                    ->setId($row['id'])
                    ->setSortNo($row['sort_no'])
                ;

                $entityManager->persist($Entity);
                $entityManager->flush($Entity);

                $Config = $entityManager->getRepository('Plugin\OrderBySale4\Entity\Config')->get();
                if (!$Config) {
                    $Config = new Config();
                }

                $Config
                    ->setName($row['name'])
                    ->setProductListOrderById($Entity->getId())
                    ->setType(1)
                ;

                $entityManager->persist($Config);
                $entityManager->flush($Config);
            }
        }
    }

    private function deleteMasterData(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine')->getManager();

        $Configs = $entityManager->getRepository('Plugin\OrderBySale4\Entity\Config')->findAll();

        /* @var $Configs Config[] */
        foreach ($Configs as $Config) {
            $ProductListOrderBy = $entityManager->getRepository('Eccube\Entity\Master\ProductListOrderBy')
                ->find($Config->getProductListOrderById());

            if ($ProductListOrderBy) {
                $entityManager->remove($ProductListOrderBy);
            }
        }
    }

    /**
     * @param $container ContainerInterface
     */
    private function createBlocks($container)
    {
        $entityManager = $container->get('doctrine')->getManager();

        $DeviceType = $entityManager->getRepository('Eccube\Entity\Master\DeviceType')
            ->find(DeviceType::DEVICE_TYPE_PC);

        foreach ($this->blocks as $block) {
            if (!$Block = $entityManager->getRepository('Eccube\Entity\Block')->findOneBy([
                'file_name' => $block['file_name'],
            ])) {
                $Block = new Block();
                $Block
                    ->setDeviceType($DeviceType)
                    ->setUseController($block['use_controller'])
                    ->setName($block['name'])
                    ->setDeletable($block['deletable'])
                    ->setFileName($block['file_name'])
                ;
                $entityManager->persist($Block);
                $entityManager->flush($Block);
            }
        }
    }

    /**
     * @param $container ContainerInterface
     */
    private function removeBlocks($container)
    {
        $entityManager = $container->get('doctrine')->getManager();

        $Blocks = $entityManager->getRepository('Eccube\Entity\Block')
            ->findBy([
                'file_name' => array_map(function ($block) {
                    return $block['file_name'];
                }, $this->blocks),
            ]);

        foreach ($Blocks as $Block) {
            $entityManager->remove($Block);
        }

        $entityManager->flush();
    }

    private function copyTemplateFile(ContainerInterface $container)
    {
        $templateDir = $container->getParameter('eccube_theme_front_dir');

        foreach ($this->blocks as $block) {
            $targetPath = $templateDir.'/Block/'.$block['file_name'].'.twig';
            if (!file_exists($targetPath)) {
                $file = new Filesystem();
                $file->copy(
                    dirname(__FILE__).'/Resource/template/default/Block/'.$block['file_name'].'.twig',
                    $targetPath
                );
            }
        }
    }
}
