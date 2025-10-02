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
use Contao\ModuleModel;
use Contao\Template;
use Contao\Input;
use Contao\Pagination;
use Contao\StringUtil;
use Contao\Config;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Respinar\PodcastBundle\Model\EpisodeModel;
use Respinar\PodcastBundle\Model\ChannelModel;

use Respinar\PodcastBundle;
use Respinar\PodcastBundle\Classes\PodcastParser;
use Respinar\PodcastBundle\Classes\PodcastUtil;

#[AsFrontendModule(category: 'podcasts')]
class PodcastChannelController extends AbstractFrontendModuleController
{
	public const TYPE = 'podcast_channel';

	public function __construct(
		private readonly PodcastParser $podcastParser,
	) {}

	protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
	{

		if (PodcastUtil::isProtected($model->podcast_channel)) {
			$template->message = $GLOBALS['TL_LANG']['MSC']['accessError'];
			return $template->getResponse();
		}

		$objChannel = ChannelModel::findOneBy('id', $model->podcast_channel);

		// No Podcast Channel
		if (empty($objChannel)) {
			$template->message = $GLOBALS['TL_LANG']['MSC']['notExist'];
			return  $template->getResponse();
		}

		$page = $this->getPageModel();

		$offset = intval($model->skipFirst);
		$limit = null;

		// Maximum number of items
		if ($model->numberOfItems > 0) {
			$limit = $model->numberOfItems;
		}

		// Handle featured product
		if ($model->podcast_featured == 'featured') {
			$blnFeatured = true;
		} elseif ($model->podcast_featured == 'unfeatured') {
			$blnFeatured = false;
		} else {
			$blnFeatured = null;
		}

		$template->episodes = array();

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
			$id = 'page_n' . $model->id;
			$page = Input::get($id) ?: 1;

			// Do not index or cache the page if the page number is outside the range
			if ($page < 1 || $page > max(ceil($total / $model->perPage), 1)) {
				global $objPage;
				$objPage->noSearch = 1;
				$objPage->cache = 0;

				// Send a 404 header
				header('HTTP/1.1 404 Not Found');
				return $template->getResponse();
			}

			// Set limit and offset
			$limit = $model->perPage;
			$offset += (max($page, 1) - 1) * $model->perPage;
			$skip = intval($model->skipFirst);

			// Overall limit
			if ($offset + $limit > $total + $skip) {
				$limit = $total + $skip - $offset;
			}

			// Add the pagination menu
			$objPagination = new Pagination($total, $model->perPage, Config::get('maxPaginationLinks'), $id);
			$template->pagination = $objPagination->generate("\n  ");
		}

		$arrOptions = array();
		if ($model->podcast_sortBy) {
			switch ($model->podcast_sortBy) {
				case 'number_asc':
					$arrOptions['order'] = "episodeNumber ASC";
					break;
				case 'number_desc':
					$arrOptions['order'] = "episodeNumber DESC";
					break;
				case 'date_asc':
					$arrOptions['order'] = "date ASC";
					break;
				case 'date_desc':
					$arrOptions['order'] = "date DESC";
					break;
			}
		}


		// Get the items
		if (isset($limit)) {
			$objEpisodes = EpisodeModel::findPublishedByPid($model->podcast_channel, $blnFeatured, $limit, $offset, $arrOptions);
		} else {
			$objEpisodes = EpisodeModel::findPublishedByPid($model->podcast_channel, $blnFeatured, 0, $offset, $arrOptions);
		}

		//$objEpisodes = EpisodeModel::findBy('pid', $model->podcast_channel);

		$template->episodes = $this->podcastParser->parseEpisodes($objEpisodes, $model, $page);

		return $template->getResponse();
	}
}
