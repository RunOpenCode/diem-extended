<?php
/*
 * This file is part of the dmCorePlugin package.
 * (c) 2011 Diem project
 * 
 *  http://www.diem-project.org
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * 
 * @see sfSessionStorage
 *
 */
class dmSessionStorage extends sfSessionStorage
{
	public function initialize($options = null)
    {
        if (session_id() != '') {
            self::$sessionStarted = true;
        }
        parent::initialize($options);
    }
}