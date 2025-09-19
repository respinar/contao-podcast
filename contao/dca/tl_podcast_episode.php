<?php

declare(strict_types=1);

/*
 * This file is part of Contao Simple Podcast.
 *
 * (c) Hamid Peywasti 2024 <hamid@respinar.com>
 *
 * @license MIT
 */

use Contao\Backend;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Input;
use Contao\BackendUser;
use Contao\Config;
use Contao\System;

use Respinar\PodcastBundle\Model\ChannelModel;

System::loadLanguageFile('tl_content');

/**
 * Table tl_podcast_episode
 */
$GLOBALS['TL_DCA']['tl_podcast_episode'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_podcast_channel',
        'enableVersioning' => true,
        'switchToEdit' => true,
        'markAsCopy' => 'headline',
        'sql' => [
            'keys' => [
                'id' => 'primary',
				'alias' => 'index',
				'pid,published,featured,start,stop' => 'index'
			]
		],
	],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_PARENT,
            'fields' => ['date'],
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'headerFields'=> ['title', 'jumpTo', 'author', 'feed', 'feedAlias'],

            'panelLayout' => 'filter;sort,search,limit'
		],
        'label' => [
            'fields' => ['date', 'episodeNumber', 'title'],
            'format' => '<span style="color:#999;padding-left:3px">[%s]</span> episode: %s - %s',
		],
        'global_operations' => [
            'all' => [
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"'
			]
		],
        'operations' => [
            'edit' => [
                'href' => 'act=edit',
                'icon' => 'edit.svg'
			],
            'copy' => [
                'href' => 'act=copy',
                'icon' => 'copy.svg'
			],
            'delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\'))return false;Backend.getScrollOffset()"'
			],
			'toggle' => [
				'href' => 'act=toggle&amp;field=published',
				'icon' => 'visible.svg',
				'showInHeader' => true
			],
			'feature' => [
				'href' => 'act=toggle&amp;field=featured',
				'icon' => 'featured.svg',
			],
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.svg',
                'attributes' => 'style="margin-right:3px"'
			],
		]
	],

    // Palettes
    'palettes' => [
		//'__selector__' => ['addImage'],
		'default' => '
			{title_legend},title,featured,alias,episodeNumber;
			{date_legend},date,author;
			{podcast_legend},podcastSRC;
			{image_legend},coverSRC;
			{meta_legend},pageTitle,duration,description;
			{teaser_legend},subheadline,teaser;
			{expert_legend:hide},cssClass;
			{publish_legend},published,start,stop'
	],

	// Subpalettes
	'subpalettes' => [
		//'addImage' => 'singleSRC',
	],
    'fields' => [
        'id'  => [
            'sql' => "int(10) unsigned NOT NULL auto_increment"
		],
        'pid' => [
			'foreignKey' => 'tl_podcast_channel.title',
			'sql' => "int(10) unsigned NOT NULL default 0",
			'relation' => ['type' => 'belongsTo', 'load' => 'lazy']
		],
        'tstamp'  => [
            'sql' => "int(10) unsigned NOT NULL default '0'"
		],
        'title' => [
            'exclude' => true,
			'search' => true,
			'inputType' => 'text',
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
			'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
			'sql' => "varchar(255) NOT NULL default ''"
		],
        'featured' => [
			'exclude' => true,
			'toggle' => true,
			'filter' => true,
			'inputType' => 'checkbox',
			'eval' => ['tl_class' => 'w50 m12'],
			'sql' => "char(1) NOT NULL default ''"
		],
		'alias' => [
			'exclude' => true,
			'search' => true,
			'inputType' => 'text',
			'eval' => ['rgxp' => 'alias', 'doNotCopy' => true, 'unique' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
			'save_callback' => [
				['tl_podcast_episode', 'generateAlias']
			],
			'sql' => "varchar(255) BINARY NOT NULL default ''"
		],
		'author' => [
			'default' => BackendUser::getInstance()->id,
			'exclude' => true,
			'search' => true,
			'filter' => true,
			'sorting' => true,
			'flag' => DataContainer::SORT_ASC,
			'inputType' => 'select',
			'foreignKey' => 'tl_user.name',
			'eval' => ['doNotCopy' => true, 'chosen' => true, 'mandatory' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
			'sql' => "int(10) unsigned NOT NULL default 0",
			'relation' => ['type' => 'hasOne', 'load' => 'lazy']
		],
		'date' => [
			'default' => time(),
			'exclude' => true,
			'filter' => true,
			'sorting' => true,
			'flag' => DataContainer::SORT_MONTH_DESC,
			'inputType' => 'text',
			'eval' => ['rgxp' => 'date', 'mandatory' => true, 'doNotCopy' => true, 'datepicker' => true, 'tl_class' => 'w50 wizard'],
			// 'load_callback' => array
			// (
			// 	['tl_news', 'loadDate')
			// ),
			'sql' => "int(10) unsigned NOT NULL default 0"
		],
        'episodeNumber' => [
			'exclude' => true,
			'sorting' => true,
			'inputType' => 'text',
			'eval' => ['rgxp' => 'number', 'mandatory' => true, 'doNotCopy' => true, 'tl_class' => 'w50'],
			'sql' => "int(10) unsigned NULL"
		],
		'pageTitle' => [
			'exclude' => true,
			'search' => true,
			'inputType' => 'text',
			'eval' => ['maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'w50'],
			'sql' => "varchar(255) NOT NULL default ''"
		],
		'description' => [
			'exclude' => true,
			'search' => true,
			'inputType' => 'textarea',
			'eval' => ['style' => 'height:60px', 'decodeEntities' => true, 'tl_class' => 'clr'],
			'sql' => "text NULL"
		],
		'duration' => [
			'exclude' => true,
			'inputType' => 'text',
			'eval' => ['rgxp' => 'number', 'mandatory' => true, 'doNotCopy' => true, 'tl_class' => 'w50'],
			'sql' => "int(5) unsigned NULL"
		],
		'subheadline' => [
			'exclude' => true,
			'search' => true,
			'inputType' => 'text',
			'eval' => ['maxlength' => 255, 'tl_class' => 'long'],
			'sql' => "varchar(255) NOT NULL default ''"
		],
		'teaser' => [
			'exclude' => true,
			'search' => true,
			'inputType' => 'textarea',
			'eval' => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
			'sql' => "text NULL"
		],
		'coverSRC' => [
			'exclude' => true,
			'inputType' => 'fileTree',
			'eval' => ['fieldType' => 'radio', 'filesOnly' => true, 'extensions' => '%contao.image.valid_extensions%', 'mandatory' => true],
			'sql' => "binary(16) NULL"
		],
		'podcastSRC' => [
			'exclude' => true,
			'inputType' => 'fileTree',
			'eval' => ['multiple'=>false, 'fieldType' => 'radio', 'filesOnly' => true, 'isDownloads' => true, 'extensions' => 'mp3, m4a, ogg', 'mandatory' => true],
			'sql' => "binary(16) NULL"
		],
		'cssClass' => [
			'exclude' => true,
			'inputType' => 'text',
			'eval' => ['tl_class' => 'w50'],
			'sql' => "varchar(255) NOT NULL default ''"
		],
		'noComments' => [
			'exclude' => true,
			'filter' => true,
			'inputType' => 'checkbox',
			'eval' => ['tl_class' => 'w50 m12'],
			'sql' => "char(1) NOT NULL default ''"
		],
		'published' => [
			'exclude' => true,
			'toggle' => true,
			'filter' => true,
			'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
			'inputType' => 'checkbox',
			'eval' => ['doNotCopy' => true],
			'sql' => "char(1) NOT NULL default ''"
		],
		'start' => [
			'exclude' => true,
			'inputType' => 'text',
			'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
			'sql' => "varchar(10) NOT NULL default ''"
		],
		'stop' => [
			'exclude' => true,
			'inputType' => 'text',
			'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
			'sql' => "varchar(10) NOT NULL default ''"
		]
	]
];

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @property News $News
 */
class tl_podcast_episode extends Backend
{
	/**
	 * Import the back end user object
	 */
	public function __construct()
	{
		parent::__construct();
		$this->import(BackendUser::class, 'User');
	}

	/**
	 * Auto-generate the news alias if it has not been set yet
	 *
	 * @param mixed         $varValue
	 * @param DataContainer $dc
	 *
	 * @return string
	 *
	 * @throws Exception
	 */
	public function generateAlias($varValue, DataContainer $dc)
	{
		$aliasExists = function (string $alias) use ($dc): bool
		{
			return $this->Database->prepare("SELECT id FROM tl_podcast_episode WHERE alias=? AND id!=?")->execute($alias, $dc->id)->numRows > 0;
		};

		// Generate alias if there is none
		if (!$varValue)
		{
			$varValue = System::getContainer()->get('contao.slug')->generate($dc->activeRecord->title, ChannelModel::findByPk($dc->activeRecord->pid)->jumpTo, $aliasExists);
		}
		elseif (preg_match('/^[1-9]\d*$/', $varValue))
		{
			throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasNumeric'], $varValue));
		}
		elseif ($aliasExists($varValue))
		{
			throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
		}

		return $varValue;
	}
}