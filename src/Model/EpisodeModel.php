<?php

declare(strict_types=1);

/*
 * This file is part of Contao Simple Podcast.
 *
 * (c) Hamid Peywasti 2024 <hamid@respinar.com>
 *
 * @license MIT
 */

namespace Respinar\PodcastBundle\Model;

use Contao\Date;
use Contao\Model;
use Contao\Model\Collection;

class EpisodeModel extends Model
{
    protected static $strTable = 'tl_podcast_episode';

	/**
	 * Find a published episode from one or more podcast channel by its ID or alias
	 */
	public static function findPublishedByParentAndIdOrAlias($varId, $arrPids, array $arrOptions=array()): EpisodeModel|null
	{
		if (empty($arrPids) || !\is_array($arrPids))
		{
			return null;
		}

		$t = static::$strTable;
		$arrColumns = !preg_match('/^[1-9]\d*$/', $varId) ? array("CAST($t.alias AS BINARY)=?") : array("$t.id=?");
		$arrColumns[] = "$t.pid IN(" . implode(',', array_map('\intval', $arrPids)) . ")";

		if (!static::isPreviewMode($arrOptions))
		{
			$time = Date::floorToMinute();
			$arrColumns[] = "$t.published=1 AND ($t.start='' OR $t.start<=$time) AND ($t.stop='' OR $t.stop>$time)";
		}

		return static::findOneBy($arrColumns, array($varId), $arrOptions);
	}

	/**
	 * Find published episodes with the default redirect target by their parent ID	 
	 */
	public static function findPublishedDefaultByPid(int $intPid, array $arrOptions=array()): Collection|EpisodeModel|null
	{
		$t = static::$strTable;
		$arrColumns = array("$t.pid=?");

		if (!static::isPreviewMode($arrOptions))
		{
			$time = Date::floorToMinute();
			$arrColumns[] = "$t.published=1 AND ($t.start='' OR $t.start<=$time) AND ($t.stop='' OR $t.stop>$time)";
		}

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order'] = "$t.date DESC";
		}

		return static::findBy($arrColumns, array($intPid), $arrOptions);
	}

	/**
	 * Count published product items by their parent ID
	 */
	public static function countPublishedByPid(int $pid, bool $blnFeatured=null, array $arrOptions=array()): int
	{
		if (empty($pid))
		{
			return 0;
		}

		$t = static::$strTable;
		$arrColumns = array("$t.pid = $pid");

		if ($blnFeatured === true)
		{
			$arrColumns[] = "$t.featured=1";
		}
		elseif ($blnFeatured === false)
		{
			$arrColumns[] = "$t.featured=''";
		}

		if (!static::isPreviewMode($arrOptions))
		{
			$time = time();
			$arrColumns[] = "($t.start='' OR $t.start<$time) AND ($t.stop='' OR $t.stop>$time) AND $t.published=1";
		}

		return static::countBy($arrColumns, null, $arrOptions);
	}

	/**
	 * Find published product items by their parent ID
	 */
	public static function findPublishedByPid(int $pid, $blnFeatured=null, int $intLimit=0, int $intOffset=0, array $arrOptions=array()): Collection|EpisodeModel|null
	{
		if (empty($pid))
		{
			return null;
		}

		$t = static::$strTable;
		$arrColumns = array("$t.pid = $pid");

		if ($blnFeatured === true)
		{
			$arrColumns[] = "$t.featured=1";
		}
		elseif ($blnFeatured === false)
		{
			$arrColumns[] = "$t.featured=''";
		}

		// Never return unpublished elements in the back end, so they don't end up in the RSS feed
		if (!static::isPreviewMode($arrOptions))
		{
			$time = time();
			$arrColumns[] = "($t.start='' OR $t.start<$time) AND ($t.stop='' OR $t.stop>$time) AND $t.published=1";
		}

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order']  = "$t.date DESC";
		}

		$arrOptions['limit']  = $intLimit;
		$arrOptions['offset'] = $intOffset;

		return static::findBy($arrColumns, null, $arrOptions);
	}

}
