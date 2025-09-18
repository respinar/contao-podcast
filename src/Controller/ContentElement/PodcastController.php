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
use Contao\Template;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Respinar\PodcastBundle\Classes\Podcast;

use Respinar\PodcastBundle\Model\ChannelModel;
use Respinar\PodcastBundle\Model\EpisodeModel;

#[AsContentElement(category: 'media', template: 'ce_podcast')]
class PodcastController extends AbstractContentElementController
{

    public const TYPE = 'podcast';


    protected function getResponse(Template $template, ContentModel $model, Request $request): Response
    {
        $page = $this->getPageModel();

        if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
		{
			return $template->getResponse();
		}

        $objEpisode = EpisodeModel::findOneByID($model->podcast_episode);

        $model->imgSize = $model->size;

        $template->episode = Podcast::parseEpisode($objEpisode, $model, $page);

        return $template->getResponse();
    }
}