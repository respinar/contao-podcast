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
use Respinar\PodcastBundle\Controller\ContentElement\PodcastController;

/**
 * Content elements
 */
$GLOBALS['TL_DCA']['tl_content']['palettes'][PodcastController::TYPE] = '
    {type_legend},type,headline;
    {config_legend},podcast_episode;
    {meta_legend:hide},podcast_metaFields;
    {template_legend:hide},customTpl,podcast_template,size;
    {protected_legend:hide},protected;
    {expert_legend:hide},guests,cssID;
    {invisible_legend:hide},invisible,start,stop';

// Add fields to tl_content
$GLOBALS['TL_DCA']['tl_content']['fields']['podcast_episode'] = [
	'exclude' => true,
	'inputType' => 'select',
    'foreignKey' => 'tl_podcast_episode.title',
	'eval' => ['multiple' => false, 'foreignTable' => 'tl_podcast_episode', 'chosen' => true, 'mandatory' => true, 'tl_class' => 'w50'],
	'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['tl_content']['fields']['podcast_metaFields'] = [
	'exclude' => true,
	'inputType' => 'checkbox',
	'options' => ['date', 'author', 'comments'],
	'reference' => &$GLOBALS['TL_LANG']['MSC'],
	'eval' => ['multiple' => true],
	'sql' => "varchar(255) COLLATE ascii_bin NOT NULL default 'a:2:{i:0;s:4:\"date\";i:1;s:6:\"author\";}'"
];

$GLOBALS['TL_DCA']['tl_content']['fields']['podcast_template'] = [
	'exclude' => true,
	'inputType' => 'select',
	'options_callback' => static function () {
		return Controller::getTemplateGroup('podcast_');
	},
	'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
	'sql' => "varchar(64) COLLATE ascii_bin NOT NULL default ''"
];