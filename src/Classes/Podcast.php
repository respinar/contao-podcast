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
use Contao\System;
use Contao\FrontendTemplate;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\Environment;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\FilesModel;
use Contao\UserModel;
use Contao\CoreBundle\Util\LocaleUtil;
use Contao\Date;
use Contao\File;
use Contao\Model\Collection;
use Contao\ModuleModel;
use Respinar\PodcastBundle\Model\ChannelModel;
use Respinar\PodcastBundle\Model\EpisodeModel;

class Podcast {

    /**
	 * URL cache array
	 * @var array
	 */
	private static $arrUrlCache = array();

    static public function parseEpisode (EpisodeModel $objEpisode, ModuleModel|ContentModel $model, PageModel $page, bool $blnAddArchive=false) {

		global $objPage;

        $objTemplate = new FrontendTemplate($model->podcast_template);

		$objTemplate->setData($objEpisode->row());

		$objTemplate->link = self::generateEpisodeUrl($objEpisode, $blnAddArchive);

		$objTemplate->date = Date::parse($objPage->dateFormat, $objEpisode->date);

		$objTemplate->duration = self::getDuration($objEpisode->duration);

		/** @var UserModel $objAuthor */
		if (($objAuthor = $objEpisode->getRelated('author')) instanceof UserModel)
		{
			$objTemplate->author = $GLOBALS['TL_LANG']['MSC']['by'] . ' ' . $objAuthor->name;
			$objTemplate->authorModel = $objAuthor;
		}

		if ($objEpisode->coverSRC)
		{
			//$imgSize = $objArticle->size ?: null;

			// Override the default image size
			if ($model->imgSize)
			{
				$size = StringUtil::deserialize($model->imgSize);

				if ($size[0] > 0 || $size[1] > 0 || is_numeric($size[2]) || ($size[2][0] ?? null) === '_')
				{
					$imgSize = $model->imgSize;
				}
			}

			$figure = System::getContainer()
				->get('contao.image.studio')
				->createFigureBuilder()
				->setSize($imgSize)
				->from($objEpisode->coverSRC)
                ->buildIfResourceExists();
		}

        if (null !== $figure)
		{
			$figure->applyLegacyTemplateData($objTemplate);
		}

		if ($objEpisode->podcastSRC)
		{
			$source = StringUtil::deserialize($objEpisode->podcastSRC);

			$objFileModel = FilesModel::findByUuid($objEpisode->podcastSRC);

			// Convert the language to a locale (see #5678)
			$strLanguage = LocaleUtil::formatAsLocale($page->language ?: 'en');
			//$strCaption = $this->playerCaption;

			/** @var FilesModel $objFileModel */
			$objMeta = $objFileModel->getMetadata($strLanguage);
			$strTitle = null;

			if (null !== $objMeta)
			{
				$strTitle = $objMeta->getTitle();

				if (empty($strCaption))
				{
					$strCaption = $objMeta->getCaption();
				}
			}

			$objFile = new File($objFileModel->path);
			$objFile->title = StringUtil::specialchars($strTitle ?: $objFile->name);
			$objFile->title = StringUtil::specialchars($objFile->name);

			$objTemplate->file = $objFile;
		}

		// schema.org information
		$objTemplate->getSchemaOrgData = static function () use ($objTemplate, $objEpisode, $model): array {
			$jsonLd = self::getSchemaOrgData($objEpisode, $model);

			if ($objTemplate->file)
			{
				$jsonLd['associatedMedia']['contentUrl'] = '/'.$objTemplate->file->path;
			}

			return $jsonLd;
		};

        return $objTemplate->parse();
    }


    static public function parseEpisodes (Collection $objEpisodes, ModuleModel|ContentModel $model, PageModel $page, $blnAddArchive=false) {

        $arrEpisodes = array();

        foreach($objEpisodes as $objEpisode){
            $arrEpisodes[] = self::parseEpisode($objEpisode, $model, $page, $blnAddArchive);
        }
        return $arrEpisodes;
    }


    static public function generateEpisodeUrl (EpisodeModel $objItem, bool $blnAddArchive=false, bool $blnAbsolute=false) {
        $strCacheKey = 'id_' . $objItem->id . ($blnAbsolute ? '_absolute' : '');

		// Load the URL from cache
		if (isset(self::$arrUrlCache[$strCacheKey]))
		{
			return self::$arrUrlCache[$strCacheKey];
		}

		// Initialize the cache
		self::$arrUrlCache[$strCacheKey] = null;

		// Link to the default page
		if (self::$arrUrlCache[$strCacheKey] === null)
		{
			$objPage = PageModel::findByPk($objItem->getRelated('pid')->jumpTo);

			if (!$objPage instanceof PageModel)
			{
				self::$arrUrlCache[$strCacheKey] = StringUtil::ampersand(Environment::get('requestUri'));
			}
			else
			{
				$params = '/' . ($objItem->alias ?: $objItem->id);

				self::$arrUrlCache[$strCacheKey] = StringUtil::ampersand($blnAbsolute ? $objPage->getAbsoluteUrl($params) : $objPage->getFrontendUrl($params));
			}
		}

		return self::$arrUrlCache[$strCacheKey];
    }

	/**
	 * Return the schema.org data from a news article
	 *
	 * @param NewsModel $objArticle
	 *
	 * @return array
	 */
	public static function getSchemaOrgData(EpisodeModel $objEpisode, ModuleModel|ContentModel $model): array
	{
		$htmlDecoder = System::getContainer()->get('contao.string.html_decoder');

		$jsonLd = array(
			'@type' => 'PodcastEpisode',
			'identifier' => '#/schema/podcastepisode/' . $objEpisode->id,
			'url' => '/'.self::generateEpisodeUrl($objEpisode),
			'name' => $objEpisode->title,
			'datePublished' => date('Y-m-d', intval($objEpisode->date)),
			"duration" => self::iso8601_duration($objEpisode->duration),
			"episodeNumber" => $objEpisode->episodeNumber,
		);

		if ($objEpisode->description)
		{
			$jsonLd['description'] = $htmlDecoder->htmlToPlainText($objEpisode->description);
		}

		/** @var UserModel $objAuthor */
		if (($objAuthor = $objEpisode->getRelated('author')) instanceof UserModel)
		{
			$jsonLd['author'] = array(
				'@type' => 'Person',
				'name' => $objAuthor->name,
			);
		}

		$jsonLd['associatedMedia'] = array(
			'@type' => 'MediaObject',
		);

		if ($objEpisode->getRelated('pid')) {
			$url = '/'.PageModel::findById($objEpisode->getRelated('pid')->overviewPage)->getFrontendUrl();
		}

		// if ($model->overviewPage) {
		// 	$url = '/'.PageModel::findById($model->overviewPage)->getFrontendUrl();
		// }

		$jsonLd['partOfSeries'] = array(
			'@type' => 'PodcastSeries',
			"name" => $objEpisode->getRelated('pid')->title,
			"url"=> $url ?? ''
		);

		return $jsonLd;
	}

	/**
	 * Sort out protected channels
	 *
	 * @param array $arrChannels
	 *
	 * @return array
	 */
	public static function sortOutProtected(array $arrChannels): array
	{
		if (empty($arrChannels) || !\is_array($arrChannels))
		{
			return $arrChannels;
		}

		$objChannel = ChannelModel::findMultipleByIds($arrChannels);
		$arrChannels = array();

		if ($objChannel !== null)
		{
			$security = System::getContainer()->get('security.helper');

			while ($objChannel->next())
			{
				if ($objChannel->protected && !$security->isGranted(ContaoCorePermissions::MEMBER_IN_GROUPS, StringUtil::deserialize($objChannel->groups, true)))
				{
					continue;
				}

				$arrChannels[] = $objChannel->id;
			}
		}

		return $arrChannels;
	}

	/**
	 * cheching protected channel
	 *
	 * @param int $channel
	 *
	 * @return boolean
	 */
	public static function isProtected(int $channel): bool
	{
		if (!isset($channel))
		{
			return false;
		}

		$objChannel = ChannelModel::findById($channel);

		if (empty($objChannel))
		{
			return false;
		}

		$security = System::getContainer()->get('security.helper');

		if ($objChannel->protected && !$security->isGranted(ContaoCorePermissions::MEMBER_IN_GROUPS, StringUtil::deserialize($objChannel->groups, true)))
		{
			return true;
		}

		return false;
	}

	/**
	 * convert seconds to ISO8601 format
	 *
	 * @param int $seconts
	 *
	 * @return string
	 */
	public static function iso8601_duration(int $seconds): string
	{
		$intervals = array('H' => 3600, 'M' => 60, 'S' => 1);

		$pt = 'P';
		$result = '';
		foreach ($intervals as $tag => $divisor)
		{
			$qty = floor($seconds/$divisor);
			if ( !$qty && $result == '' )
			{
			$pt = 'T';
			continue;
			}

			$seconds -= $qty * $divisor;
			$result  .= "$qty$tag";
		}
		if ( $result=='' )
			$result='0S';
		return "$pt$result";
	}

	/**
	 * get duration
	 *
	 * @param int $seconts
	 *
	 * @return string
	 */
	public static function getDuration(int $seconds): string
	{
		$result = "";

		$hours = floor($seconds/3600);

		if ($hours > 0 ) {
			$result .= "$hours ".$GLOBALS['TL_LANG']['MSC']['podcast_hr']." ";
			$seconds %= 3600;
		}

		$mins = round($seconds/60);

		if ($mins > 0) {
			$result .= "$mins ".$GLOBALS['TL_LANG']['MSC']['podcast_min'];
		}

		return $result;
	}

}