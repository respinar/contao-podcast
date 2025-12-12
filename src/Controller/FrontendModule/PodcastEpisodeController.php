<?php

declare(strict_types=1);

/*
 * This file is part of Contao Simple Podcast.
 *
 * (c) Hamid Peywasti 2024 <hamid@respinar.com>
 *
 * @license MIT
 */

namespace Respinar\PodcastBundle\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Contao\CoreBundle\String\HtmlDecoder;
use Contao\Environment;
use Contao\Input;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\Template;
use Respinar\PodcastBundle\Classes\PodcastParser;
use Respinar\PodcastBundle\Model\ChannelModel;
use Respinar\PodcastBundle\Model\EpisodeModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(category: 'podcasts')]
class PodcastEpisodeController extends AbstractFrontendModuleController
{
    public const TYPE = 'podcast_episode';

    public function __construct(
        private readonly PodcastParser $podcastParser,
        private readonly ResponseContextAccessor $responseContextAccessor,
        private readonly HtmlDecoder $htmlDecoder,
    ) {
    }

    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $page = $this->getPageModel();

        $model->podcast_channels = StringUtil::deserialize($model->podcast_channels);
        $objEpisode = EpisodeModel::findPublishedByParentAndIdOrAlias(Input::get('auto_item'), $model->podcast_channels);

        // Throw 404 error if episode not found
        if (!$objEpisode instanceof EpisodeModel) {
            throw new PageNotFoundException('Page not found: '.Environment::get('uri'));
        }

        $template->referer = null;

        $channel = $objEpisode->getRelated('pid');
        if ($channel instanceof ChannelModel) {
            $overviewPage = PageModel::findById($channel->overviewPage);
            if ($overviewPage instanceof PageModel) {
                $template->referer = $overviewPage->getFrontendUrl();
            }
        }

        if ($model->overviewPage) {
            $overviewPage = PageModel::findById($model->overviewPage);
            if ($overviewPage instanceof PageModel) {
                $template->referer = $overviewPage->getFrontendUrl();
            }
        }

        $template->back = $model->customLabel ?: ($GLOBALS['TL_LANG']['MSC']['podcastOverview'] ?? $GLOBALS['TL_LANG']['MSC']['newsOverview']);

        $template->episode = $this->podcastParser->parseEpisode($objEpisode, $model, $page);

        // Page title and Description
        $responseContext = $this->responseContextAccessor->getResponseContext();

        if ($responseContext && $responseContext->has(HtmlHeadBag::class)) {
            /** @var HtmlHeadBag $htmlHeadBag */
            $htmlHeadBag = $responseContext->get(HtmlHeadBag::class);

            if ($objEpisode->pageTitle) {
                $htmlHeadBag->setTitle($objEpisode->pageTitle);
            } elseif ($objEpisode->title) {
                $htmlHeadBag->setTitle($objEpisode->title);
            }

            if ($objEpisode->description) {
                $htmlHeadBag->setMetaDescription($this->htmlDecoder->inputEncodedToPlainText($objEpisode->description));
            }
        }

        return $template->getResponse();
    }
}
