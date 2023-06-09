<?php

declare(strict_types=1);

/*
 * This file is part of Contao Simple Podcast.
 *
 * (c) Hamid Abbaszadeh 2023 <abbaszadeh.h@gmail.com>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/respinar/contao-podcast-bundle
 */

use Contao\Controller;

use Respinar\PodcastBundle\Controller\ContentElement\PodcastEpisodeController;

/**
 * Content elements
 */
$GLOBALS['TL_DCA']['tl_content']['palettes']['podcast_episode'] = '
    {type_legend},type,headline;
    {config_legend},podcast_episode;
    {template_legend:hide},podcast_metaFields,podcast_template,customTpl;
    {image_legend:hide},size;
    {protected_legend:hide},protected;
    {expert_legend:hide},guests,cssID;
    {invisible_legend:hide},invisible,start,stop';

// Add fields to tl_content
$GLOBALS['TL_DCA']['tl_content']['fields']['podcast_episode'] = array
(
	'exclude'                 => true,
	'inputType'               => 'select',
    'foreignKey'              => 'tl_podcast_episode.title',
	//'options_callback'      => array('tl_module_news', 'getNewsArchives'),
	'eval'                    => array('multiple'=>false, 'foreignTable' => 'tl_podcast_episode', 'chosen' => true, 'mandatory'=>true, 'tl_class'=>'w50'),
	'sql'                     => "blob NULL"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['podcast_metaFields'] = array
(
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'options'                 => array('date', 'author', 'comments'),
	'reference'               => &$GLOBALS['TL_LANG']['MSC'],
	'eval'                    => array('multiple'=>true),
	'sql'                     => "varchar(255) COLLATE ascii_bin NOT NULL default 'a:2:{i:0;s:4:\"date\";i:1;s:6:\"author\";}'"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['podcast_template'] = array
(
	'exclude'                 => true,
	'inputType'               => 'select',
	'options_callback' => static function ()
	{
		return Controller::getTemplateGroup('podcast_');
	},
	'eval'                    => array('includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'),
	'sql'                     => "varchar(64) COLLATE ascii_bin NOT NULL default ''"
);