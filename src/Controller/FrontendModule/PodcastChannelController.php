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

use Contao\Config;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Input;
use Contao\ModuleModel;
use Contao\Pagination;
use Contao\Template;
use Respinar\PodcastBundle\Classes\PodcastParser;
use Respinar\PodcastBundle\Classes\PodcastUtil;
use Respinar\PodcastBundle\Model\ChannelModel;
use Respinar\PodcastBundle\Model\EpisodeModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(category: 'podcasts')]
class PodcastChannelController extends AbstractFrontendModuleController
{
    public const TYPE = 'podcast_channel';

    public function __construct(
        private readonly PodcastParser $podcastParser,
        private readonly PodcastUtil $podcastUtil,
    ) {
    }

    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        if ($this->podcastUtil->isProtected($model->podcast_channel)) {
            $template->message = $GLOBALS['TL_LANG']['MSC']['accessError'];

            return $template->getResponse();
        }

        $objChannel = ChannelModel::findOneBy('id', $model->podcast_channel);

        if (null === $objChannel) {
            $template->message = $GLOBALS['TL_LANG']['MSC']['notExist'];

            return $template->getResponse();
        }

        $pageModel = $this->getPageModel();

        $offset = (int) $model->skipFirst;
        $limit = null;

        // Maximum number of items
        if ($model->numberOfItems > 0) {
            $limit = $model->numberOfItems;
        }

        // Handle featured product
        $blnFeatured = match ($model->podcast_featured) {
            'featured' => true,
            'unfeatured' => false,
            default => null,
        };

        $template->episodes = [];

        $intTotal = EpisodeModel::countPublishedByPid($model->podcast_channel, $blnFeatured);

        if ($intTotal < 1) {
            $template->message = $GLOBALS['TL_LANG']['MSC']['emptyChannel'];

            return $template->getResponse();
        }

        $total = $intTotal - $offset;

        // Split the results
        if ($model->perPage > 0 && (!isset($limit) || $model->numberOfItems > $model->perPage)) {
            // Adjust the overall limit
            if (isset($limit)) {
                $total = min($limit, $total);
            }

            // Get the current page
            $id = 'page_n'.$model->id;
            $pageNumber = (int) (Input::get($id) ?: 1);

            // Do not index or cache the page if the page number is outside the range
            if ($pageNumber < 1 || $pageNumber > max(ceil($total / $model->perPage), 1)) {
                throw new PageNotFoundException('Page not found');
            }

            // Set limit and offset
            $limit = $model->perPage;
            $offset += (max($pageNumber, 1) - 1) * $model->perPage;

            // Overall limit
            if ($offset + $limit > $total + (int) $model->skipFirst) {
                $limit = $total + (int) $model->skipFirst - $offset;
            }

            // Add the pagination menu
            $objPagination = new Pagination($total, $model->perPage, Config::get('maxPaginationLinks'), $id);
            $template->pagination = $objPagination->generate("\n  ");
        }

        $arrOptions = [];
        $orderParts = [];

        // Handle featured_first sorting
        if ('featured_first' === $model->podcast_featured) {
            $orderParts[] = 'featured DESC';
        }

        switch ($model->podcast_sortBy) {
            case 'number_asc':
                $orderParts[] = 'episodeNumber ASC';
                break;
            case 'number_desc':
                $orderParts[] = 'episodeNumber DESC';
                break;
            case 'date_asc':
                $orderParts[] = 'date ASC';
                break;
            case 'date_desc':
            default:
                $orderParts[] = 'date DESC';
                break;
        }

        $arrOptions['order'] = implode(', ', $orderParts);

        // Get the items
        if (isset($limit)) {
            $objEpisodes = EpisodeModel::findPublishedByPid($model->podcast_channel, $blnFeatured, $limit, $offset, $arrOptions);
        } else {
            $objEpisodes = EpisodeModel::findPublishedByPid($model->podcast_channel, $blnFeatured, 0, $offset, $arrOptions);
        }

        $template->episodes = $this->podcastParser->parseEpisodes($objEpisodes, $model, $pageModel);

        return $template->getResponse();
    }
}
