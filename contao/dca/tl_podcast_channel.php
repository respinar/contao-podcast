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
use Contao\StringUtil;
use Contao\BackendUser;
use Contao\CoreBundle\Util\LocaleUtil;

/**
 * Table tl_podcast_channel
 */
$GLOBALS['TL_DCA']['tl_podcast_channel'] = [
	'config' => [
		'dataContainer' => DC_Table::class,
		'ctable' => ['tl_podcast_episode'],
		'switchToEdit'                => true,
		'enableVersioning'            => true,
		'markAsCopy' => 'title',
		'sql' => [
			'keys' => [
				'id' => 'primary'
			]
		],
	],
	// List
	'list' => [
		'sorting' => [
			'mode'                    => DataContainer::MODE_SORTED,
			'fields'                  => array('title'),
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
			'panelLayout'             => 'search,filter,limit',
			'defaultSearchField'      => 'title'
		],
		'label' => [
			'fields'                  => array('title'),
			'format'                  => '%s'
		]
	],

	// Palettes
	'palettes' => [
		'__selector__' => ['feed', 'protected'],
		'default' => '{title_legend},title;{config_legend},overviewPage,jumpTo;{author_legend},owner,author;{feed_legend},feed;{detail_legend},coverSRC,description;{protected_legend:hide},protected;'
	],

	// Subpalettes
	'subpalettes' => [
		'protected' => 'groups',
		'feed' => 'feedAlias,format,language,feedBase,maxItems,imgSize',
		//'allowComments' => 'notify,sortOrder,perPage,moderate,bbcode,requireLogin,disableCaptcha'
	],

	// Fields
	'fields' => [
		'id' => [
			'sql' => ['type' => 'integer', 'unsigned' => true, 'autoincrement' => true]
		],
		'tstamp' => [
			'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0]
		],
		'title' => [
			'search' => true,
			'inputType' => 'text',
			'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
			'sql' => "varchar(255) NOT NULL default ''"
		],
		'feed' => [
			'toggle' => true,
			'filter' => true,
			'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
			'inputType' => 'checkbox',
			'eval' => ['doNotCopy' => true, 'submitOnChange' => true, 'tl_class' => 'w50 m12'],
			'sql' => ['type' => 'boolean', 'default' => false]
		],
		'feedAlias' => [
			'search' => true,
			'inputType' => 'text',
			'eval' => ['mandatory' => true, 'rgxp' => 'alias', 'doNotCopy' => true, 'unique' => true, 'maxlength' => 255, 'tl_class' => 'w50 clr'],
			// 'save_callback' => array
			// (
			// 	['tl_podcast_episode', 'generateAlias')
			// ),
			'sql' => "varchar(255) BINARY NOT NULL default ''"
		],
		'feedBase' => [
			'search' => true,
			'inputType' => 'text',
			'eval' => ['rgxp' => 'url', 'decodeEntities' => true, 'maxlength' => 2048, 'dcaPicker' => true, 'tl_class' => 'w50'],
			'sql' => "varchar(2048) NOT NULL default ''"
		],
		'format' => [
			'filter' => true,
			'inputType' => 'select',
			'options' => ['rss' => 'RSS 2.0', 'atom' => 'Atom'],
			'eval' => ['tl_class' => 'w50'],
			'sql' => "varchar(32) NOT NULL default 'rss'"
		],
		'maxItems' => [
			'inputType' => 'text',
			'eval' => ['rgxp' => 'natural', 'tl_class' => 'w50'],
			'sql' => "smallint(5) unsigned NOT NULL default 25"
		],
		'imgSize' => [
			'inputType' => 'imageSize',
			'reference' => &$GLOBALS['TL_LANG']['MSC'],
			'eval' => ['rgxp' => 'natural', 'includeBlankOption' => true, 'nospace' => true, 'helpwizard' => true, 'tl_class' => 'w50'],
			'options_callback' => ['contao.listener.image_size_options', '__invoke'],
			'sql' => "varchar(255) NOT NULL default ''"
		],
		'owner' => [
			'default' => BackendUser::getInstance()->id,
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
		'author' => [
			'default' => BackendUser::getInstance()->id,
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
		'language' => [
			'search' => true,
			'inputType' => 'text',
			'eval' => ['mandatory' => true, 'maxlength' => 64, 'nospace' => true, 'decodeEntities' => true, 'doNotCopy' => true, 'tl_class' => 'w50'],
			'sql' => "varchar(64) NOT NULL default ''",
			'save_callback' => [
				static function ($value): string {
					// Make sure there is at least a basic language
					if (!preg_match('/^[a-z]{2,}/i', $value)) {
						throw new RuntimeException($GLOBALS['TL_LANG']['ERR']['language']);
					}

					return LocaleUtil::canonicalize($value);
				}
			]
		],
		'description' => [
			'search' => true,
			'inputType' => 'textarea',
			'eval' => ['style' => 'height:60px', 'decodeEntities' => true, 'tl_class' => 'clr'],
			'sql' => "text NULL"
		],
		'coverSRC' => [
			'inputType' => 'fileTree',
			'eval' => ['fieldType' => 'radio', 'filesOnly' => true, 'extensions' => '%contao.image.valid_extensions%', 'mandatory' => true],
			'sql' => "binary(16) NULL"
		],
		'overviewPage' => [
			'inputType' => 'pageTree',
			'foreignKey' => 'tl_page.title',
			'eval' => ['mandatory' => true, 'fieldType' => 'radio', 'tl_class' => 'clr'],
			'sql' => "int(10) unsigned NOT NULL default 0",
			'relation' => ['type' => 'hasOne', 'load' => 'lazy']
		],
		'jumpTo' => [
			'inputType' => 'pageTree',
			'foreignKey' => 'tl_page.title',
			'eval' => ['mandatory' => true, 'fieldType' => 'radio', 'tl_class' => 'clr'],
			'sql' => "int(10) unsigned NOT NULL default 0",
			'relation' => ['type' => 'hasOne', 'load' => 'lazy']
		],
		'protected' => [
			'filter' => true,
			'inputType' => 'checkbox',
			'eval' => ['submitOnChange' => true],
			'sql' => ['type' => 'boolean', 'default' => false]
		],
		'groups' => [
			'inputType' => 'checkbox',
			'foreignKey' => 'tl_member_group.name',
			'eval' => ['mandatory' => true, 'multiple' => true],
			'sql' => "blob NULL",
			'relation' => ['type' => 'hasMany', 'load' => 'lazy']
		],
		'allowComments' => [
			'filter' => true,
			'inputType' => 'checkbox',
			'eval' => ['submitOnChange' => true],
			'sql' => ['type' => 'boolean', 'default' => false]
		],
		'notify' => [
			'inputType' => 'select',
			'options' => ['notify_admin', 'notify_author', 'notify_both'],
			'eval' => ['tl_class' => 'w50'],
			'reference' => &$GLOBALS['TL_LANG']['tl_podcast_channel'],
			'sql' => "varchar(16) NOT NULL default 'notify_admin'"
		],
		'sortOrder' => [
			'inputType' => 'select',
			'options' => ['ascending', 'descending'],
			'reference' => &$GLOBALS['TL_LANG']['MSC'],
			'eval' => ['tl_class' => 'w50 clr'],
			'sql' => "varchar(32) NOT NULL default 'ascending'"
		],
		'perPage' => [
			'inputType' => 'text',
			'eval' => ['rgxp' => 'natural', 'tl_class' => 'w50'],
			'sql' => "smallint(5) unsigned NOT NULL default 0"
		],
		'moderate' => [
			'inputType' => 'checkbox',
			'eval' => ['tl_class' => 'w50'],
			'sql' => ['type' => 'boolean', 'default' => false]
		],
		'bbcode' => [
			'inputType' => 'checkbox',
			'eval' => ['tl_class' => 'w50'],
			'sql' => ['type' => 'boolean', 'default' => false]
		],
		'requireLogin' => [
			'inputType' => 'checkbox',
			'eval' => ['tl_class' => 'w50'],
			'sql' => ['type' => 'boolean', 'default' => false]
		],
		'disableCaptcha' => [
			'inputType' => 'checkbox',
			'eval' => ['tl_class' => 'w50'],
			'sql' => ['type' => 'boolean', 'default' => false]
		]
	]
];

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 */
class tl_podcast_channel extends Backend
{
	/**
	 * Import the back end user object
	 */
	public function __construct()
	{
		parent::__construct();
		$this->import(BackendUser::class, 'User');
	}

	public function manageFeeds($href, $label, $title, $class, $attributes): string
	{
		return '<a href="' . $this->addToUrl($href) . '" class="' . $class . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . $label . '</a> ';
	}
}
