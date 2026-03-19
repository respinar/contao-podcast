<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

// Extend the default palette
PaletteManipulator::create()
    ->addLegend('podcast_legend', 'amg_legend', PaletteManipulator::POSITION_BEFORE)
    ->addField('podcasts', 'podcast_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_user_group')
;

// Add fields to tl_user_group
$GLOBALS['TL_DCA']['tl_user_group']['fields']['podcasts'] =
[
    'label' => &$GLOBALS['TL_LANG']['tl_user']['podcasts'],
    'inputType' => 'checkbox',
    'foreignKey' => 'tl_podcast_channel.title',
    'eval' => ['multiple' => true],
    'sql' => ['type' => 'blob', 'notnull' => false],
    'relation' => ['type' => 'hasMany', 'load' => 'lazy'],
];
