<?php
/*
 * Copyright (C) SPREAD WORKS Inc. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Plugin\TabaHtmlEditor\Common;

class Constants extends AbstractConstants
{

    /*
     * __call マジックメソッド
     */
    public function __call($name, $args)
    {
        return self::$$name;
    }
}