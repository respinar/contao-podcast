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
use Contao\PageModel;
use Contao\StringUtil;
use Respinar\PodcastBundle\Model\ChannelModel;
use Respinar\PodcastBundle\Model\EpisodeModel;
use Symfony\Bundle\SecurityBundle\Security;

final class PodcastUtil
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    /**
     * Convert seconds to an ISO 8601 duration.
     */
    public function iso8601Duration(int $seconds): string
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
    public function getDuration(int $seconds): string
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
    public function sortOutProtected(array $channels): array
    {
        if ([] === $channels) {
            return [];
        }

        $collection = ChannelModel::findMultipleByIds($channels);

        if (null === $collection) {
            return [];
        }

        $allowed = [];

        while ($collection->next()) {
            if (
                $collection->protected
                && !$this->security->isGranted(
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
    public function isProtected(int $channelId): bool
    {
        $channel = ChannelModel::findByPk($channelId);

        if (!$channel instanceof ChannelModel) {
            return false;
        }

        if (!$channel->protected) {
            return false;
        }

        return !$this->security->isGranted(
            ContaoCorePermissions::MEMBER_IN_GROUPS,
            StringUtil::deserialize($channel->groups, true),
        );
    }
}
