<?php

declare(strict_types=1);

/*
 * This file is part of Contao Simple Podcast.
 *
 * (c) Hamid Peywasti 2024 <hamid@respinar.com>
 *
 * @license MIT
 */

use Contao\Controller;
use Respinar\PodcastBundle\Controller\FrontendModule\PodcastChannelController;
use Respinar\PodcastBundle\Controller\FrontendModule\PodcastEpisodeController;

/**
 * Frontend modules
 */
$GLOBALS['TL_DCA']['tl_module']['palettes'][PodcastChannelController::TYPE] = '
    {title_legend},name,headline,type;
    {config_legend},podcast_channel,numberOfItems,podcast_featured,podcast_sortBy,skipFirst,perPage;
    {template_legend:hide},podcast_metaFields,;
    {image_legend:hide},customTpl,podcast_template,podcast_listClass,imgSize;
    {protected_legend:hide},protected;
    {expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes'][PodcastEpisodeController::TYPE] = '
    {title_legend},name,headline,type;
    {config_legend},podcast_channels,overviewPage,customLabel;
    {meta_legend:hide},podcast_metaFields;
    {template_legend:hide},podcast_template,customTpl,imgSize;
    {protected_legend:hide},protected;
    {expert_legend:hide},guests,cssID';



// Add fields to tl_module
$GLOBALS['TL_DCA']['tl_module']['fields']['podcast_channel'] = [
	'exclude' => true,
	'inputType' => 'radio',
    'foreignKey' => 'tl_podcast_channel.title',
	'eval' => ['multiple'=>false, 'foreignTable' => 'tl_podcast_channel', 'mandatory' => true],
	'sql' => "int(10) unsigned NULL"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['podcast_channels'] = [
	'exclude' => true,
	'inputType' => 'checkbox',
    'foreignKey' => 'tl_podcast_channel.title',
	'eval' => ['multiple' => true, 'foreignTable' => 'tl_podcast_channel', 'mandatory' => true],
	'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['podcast_featured'] = [
	'exclude' => true,
	'inputType' => 'select',
	'options' => ['all_items', 'featured', 'unfeatured', 'featured_first'],
	'reference' => &$GLOBALS['TL_LANG']['tl_module'],
	'eval' => ['tl_class' => 'w50 clr'],
	'sql' => "varchar(16) COLLATE ascii_bin NOT NULL default 'all_items'"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['podcast_metaFields'] = [
	'exclude' => true,
	'inputType' => 'checkbox',
	'options' => ['date', 'author', 'comments'],
	'reference' => &$GLOBALS['TL_LANG']['MSC'],
	'eval' => ['multiple' => true],
	'sql' => "varchar(255) COLLATE ascii_bin NOT NULL default 'a:2:{i:0;s:4:\"date\";i:1;s:6:\"author\";}'"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['podcast_template'] = [
	'exclude' => true,
	'inputType' => 'select',
	'options_callback' => static function () {
		return Controller::getTemplateGroup('podcast_');
	},
	'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
	'sql' => "varchar(64) COLLATE ascii_bin NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['podcast_listClass'] = [
	'exclude' => true,
	'inputType' => 'text',
	'eval' => ['maxlength'=>128, 'tl_class' => 'w50'],
	'sql' => "varchar(255) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['podcast_sortBy'] = [
	'exclude' => true,
	'inputType' => 'select',
    'options' => ['number_asc','number_desc','date_asc', 'date_desc'],
	'reference' => &$GLOBALS['TL_LANG']['tl_module'],
	'eval' => ['tl_class' => 'w50'],
	'sql' => "varchar(32) COLLATE ascii_bin NOT NULL default 'number_desc'"
];