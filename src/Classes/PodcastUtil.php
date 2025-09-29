<?php

declare(strict_types=1);

/*
 * This file is part of Contao Simple Podcast.
 *
 * (c) Hamid Peywasti 2024 <hamid@respinar.com>
 *
 * @license MIT
 */

namespace Respinar\PodcastBundle\Classes;

use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\Environment;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Respinar\PodcastBundle\Model\ChannelModel;
use Respinar\PodcastBundle\Model\EpisodeModel;

final class PodcastUtil
{
  /**
   * URL cache.
   */
  private static array $urlCache = [];

  /**
   * Generate an episode URL.
   */
  public static function generateEpisodeUrl(
    EpisodeModel $episode,
    bool $absolute = false,
  ): string {
    $cacheKey = sprintf(
      'id_%d%s',
      $episode->id,
      $absolute ? '_absolute' : '',
    );

    if (isset(self::$urlCache[$cacheKey])) {
      return self::$urlCache[$cacheKey];
    }

    $page = PageModel::findByPk($episode->getRelated('pid')->jumpTo);

    if (!$page instanceof PageModel) {
      return self::$urlCache[$cacheKey] = StringUtil::ampersand(Environment::get('requestUri'));
    }

    $parameters = '/' . ($episode->alias ?: $episode->id);

    return self::$urlCache[$cacheKey] = StringUtil::ampersand(
      $absolute
        ? $page->getAbsoluteUrl($parameters)
        : $page->getFrontendUrl($parameters),
    );
  }

  /**
   * Convert seconds to an ISO 8601 duration.
   */
  public static function iso8601Duration(int $seconds): string
  {
    $hours = intdiv($seconds, 3600);
    $seconds %= 3600;

    $minutes = intdiv($seconds, 60);
    $seconds %= 60;

    $duration = 'PT';

    if ($hours > 0) {
      $duration .= $hours . 'H';
    }

    if ($minutes > 0) {
      $duration .= $minutes . 'M';
    }

    if ($seconds > 0 || $duration === 'PT') {
      $duration .= $seconds . 'S';
    }

    return $duration;
  }

  /**
   * Format a duration for display.
   */
  public static function getDuration(int $seconds): string
  {
    $parts = [];

    $hours = intdiv($seconds, 3600);

    if ($hours > 0) {
      $parts[] = sprintf(
        '%d %s',
        $hours,
        $GLOBALS['TL_LANG']['MSC']['podcast_hr'],
      );

      $seconds %= 3600;
    }

    $minutes = intdiv($seconds, 60);

    if ($minutes > 0) {
      $parts[] = sprintf(
        '%d %s',
        $minutes,
        $GLOBALS['TL_LANG']['MSC']['podcast_min'],
      );
    }

    return implode(' ', $parts);
  }

  /**
   * Remove protected channels the current member cannot access.
   */
  public static function sortOutProtected(array $channels): array
  {
    if ([] === $channels) {
      return [];
    }

    $collection = ChannelModel::findMultipleByIds($channels);

    if (null === $collection) {
      return [];
    }

    $security = System::getContainer()->get('security.helper');

    $allowed = [];

    while ($collection->next()) {
      if (
        $collection->protected
        && !$security->isGranted(
          ContaoCorePermissions::MEMBER_IN_GROUPS,
          StringUtil::deserialize($collection->groups, true),
        )
      ) {
        continue;
      }

      $allowed[] = $collection->id;
    }

    return $allowed;
  }

  /**
   * Check whether a channel is protected for the current member.
   */
  public static function isProtected(int $channelId): bool
  {
    $channel = ChannelModel::findByPk($channelId);

    if (!$channel instanceof ChannelModel) {
      return false;
    }

    if (!$channel->protected) {
      return false;
    }

    $security = System::getContainer()->get('security.helper');

    return !$security->isGranted(
      ContaoCorePermissions::MEMBER_IN_GROUPS,
      StringUtil::deserialize($channel->groups, true),
    );
  }
}
