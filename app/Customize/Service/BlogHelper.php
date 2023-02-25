<?php

namespace Customize\Service;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Product;

class BlogHelper
{
    /**
     * @var eccubeConfig
     */
    protected $eccubeConfig;

    public function __construct(
        EccubeConfig $eccubeConfig
    ) {
        $this->eccubeConfig = $eccubeConfig;
    }

    /**
     * ブログ画像取得
     *
     * @param \Eccube\Entity\Product $Blog
     * @param $type large | middle | small
     *
     * @return boolean
     */
    public function getBlogImage($Blog, $type)
    {
        switch ( $type ) {
            case 'large':
                if ( $Blog->getProductImage()->count() == 1 ) {
                    return $Blog->getProductImage()->first();
                }
                return $Blog->getProductImage()->get(1);

            case 'middle':
                return $Blog->getProductImage()->last();

            case 'small':
                return $Blog->getProductImage()->first();
        }
    }
}
