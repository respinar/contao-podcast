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
     * Find a published episode from one or more podcast channel by its ID or alias.
     */
    public static function findPublishedByParentAndIdOrAlias($varId, $arrPids, array $arrOptions = []): self|null
    {
        if (empty($arrPids) || !\is_array($arrPids)) {
            return null;
        }

        $t = static::$strTable;
        $arrColumns = preg_match('/^[1-9]\d*$/', $varId) ? ["$t.id=?"] : ["CAST($t.alias AS BINARY)=?"];
        $arrColumns[] = "$t.pid IN(".implode(',', array_map('\intval', $arrPids)).')';

        if (!static::isPreviewMode($arrOptions)) {
            $time = Date::floorToMinute();
            $arrColumns[] = "$t.published=1 AND ($t.start='' OR $t.start<=$time) AND ($t.stop='' OR $t.stop>$time)";
        }

        return static::findOneBy($arrColumns, [$varId], $arrOptions);
    }

    /**
     * Find published episodes with the default redirect target by their parent ID.
     */
    public static function findPublishedDefaultByPid(int $intPid, array $arrOptions = []): Collection|self|null
    {
        $t = static::$strTable;
        $arrColumns = ["$t.pid=?"];

        if (!static::isPreviewMode($arrOptions)) {
            $time = Date::floorToMinute();
            $arrColumns[] = "$t.published=1 AND ($t.start='' OR $t.start<=$time) AND ($t.stop='' OR $t.stop>$time)";
        }

        if (!isset($arrOptions['order'])) {
            $arrOptions['order'] = "$t.date DESC";
        }

        return static::findBy($arrColumns, [$intPid], $arrOptions);
    }

    /**
     * Count published episodes by their parent ID.
     */
    public static function countPublishedByPid(int $pid, bool|null $blnFeatured = null, array $arrOptions = []): int
    {
        if (0 === $pid) {
            return 0;
        }

        $t = static::$strTable;
        $arrColumns = ["$t.pid = $pid"];

        if (true === $blnFeatured) {
            $arrColumns[] = "$t.featured=1";
        } elseif (false === $blnFeatured) {
            $arrColumns[] = "$t.featured=''";
        }

        if (!static::isPreviewMode($arrOptions)) {
            $time = Date::floorToMinute();
            $arrColumns[] = "($t.start='' OR $t.start<$time) AND ($t.stop='' OR $t.stop>$time) AND $t.published=1";
        }

        return static::countBy($arrColumns, null, $arrOptions);
    }

    /**
     * Find published episodes by their parent ID.
     */
    public static function findPublishedByPid(int $pid, bool|null $blnFeatured = null, int $intLimit = 0, int $intOffset = 0, array $arrOptions = []): Collection|self|null
    {
        if (0 === $pid) {
            return null;
        }

        $t = static::$strTable;
        $arrColumns = ["$t.pid = $pid"];

        if (true === $blnFeatured) {
            $arrColumns[] = "$t.featured=1";
        } elseif (false === $blnFeatured) {
            $arrColumns[] = "$t.featured=''";
        }

        // Never return unpublished elements in the back end, so they don't end up in the
        // RSS feed
        if (!static::isPreviewMode($arrOptions)) {
            $time = Date::floorToMinute();
            $arrColumns[] = "($t.start='' OR $t.start<$time) AND ($t.stop='' OR $t.stop>$time) AND $t.published=1";
        }

        if (!isset($arrOptions['order'])) {
            $arrOptions['order'] = "$t.date DESC";
        }

        $arrOptions['limit'] = $intLimit;
        $arrOptions['offset'] = $intOffset;

        return static::findBy($arrColumns, null, $arrOptions);
    }
}
