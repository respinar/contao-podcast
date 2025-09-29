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

use Contao\ContentModel;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\UserModel;
use Respinar\PodcastBundle\Model\EpisodeModel;

final readonly class PodcastSchema
{

    public function generate(
        EpisodeModel $episode,
        ModuleModel|ContentModel $model,
        ?string $mediaUrl = null,
    ): array {
        $jsonLd = [
            '@type' => 'PodcastEpisode',
            'identifier' => '#/schema/podcastepisode/' . $episode->id,
            'url' => '/' . PodcastUtil::generateEpisodeUrl($episode),
            'name' => $episode->title,
            'datePublished' => date('Y-m-d', (int) $episode->date),
            'duration' => PodcastUtil::iso8601Duration((int) $episode->duration),
            'episodeNumber' => $episode->episodeNumber,
            'associatedMedia' => [
                '@type' => 'MediaObject',
            ],
        ];

        if ($episode->description) {
            $jsonLd['description'] = StringUtil::decodeEntities(strip_tags($episode->description));
        }

        /** @var UserModel|null $author */
        $author = $episode->getRelated('author');

        if ($author instanceof UserModel) {
            $jsonLd['author'] = [
                '@type' => 'Person',
                'name' => $author->name,
            ];
        }

        if ($mediaUrl !== null) {
            $jsonLd['associatedMedia']['contentUrl'] = $mediaUrl;
        }

        if (($channel = $episode->getRelated('pid')) !== null) {
            $overviewPage = PageModel::findById($channel->overviewPage);

            $jsonLd['partOfSeries'] = [
                '@type' => 'PodcastSeries',
                'name' => $channel->title,
                'url' => $overviewPage?->getFrontendUrl() ?? '',
            ];
        }

        return $jsonLd;
    }
}
