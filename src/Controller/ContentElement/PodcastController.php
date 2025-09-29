<?php

declare(strict_types=1);

/*
 * This file is part of Contao Simple Podcast.
 *
 * (c) Hamid Peywasti 2024 <hamid@respinar.com>
 *
 * @license MIT
 */

namespace Respinar\PodcastBundle\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\Template;
use Respinar\PodcastBundle\Classes\PodcastParser;
use Respinar\PodcastBundle\Model\EpisodeModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(category: 'media', template: 'ce_podcast')]
class PodcastController extends AbstractContentElementController
{
    public const TYPE = 'podcast';

    public function __construct(
        private readonly PodcastParser $podcastParser,
        private readonly ScopeMatcher $scopeMatcher,
    ) {
    }

    protected function getResponse(
        Template $template,
        ContentModel $model,
        Request $request,
    ): Response {
        if ($this->scopeMatcher->isBackendRequest($request)) {
            return $template->getResponse();
        }

        $episode = EpisodeModel::findByPk($model->podcast_episode);

        if (!$episode instanceof EpisodeModel) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $model->imgSize = $model->size;

        $template->episode = $this->podcastParser->parseEpisode(
            $episode,
            $model,
            $this->getPageModel(),
        );

        return $template->getResponse();
    }
}