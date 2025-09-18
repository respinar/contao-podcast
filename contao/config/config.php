<?php

/*
 * This file is part of Contao Simple Podcast.
 *
 * (c) Hamid Peywasti 2024 <hamid@respinar.com>
 *
 * @license MIT
 */

use Respinar\PodcastBundle\Model\ChannelModel;
use Respinar\PodcastBundle\Model\EpisodeModel;

/**
 * Backend modules
 */
$GLOBALS['BE_MOD']['content']['podcasts'] = array(
    'tables' => array('tl_podcast_channel', 'tl_podcast_episode')
);

/**
 * Models
 */
$GLOBALS['TL_MODELS']['tl_podcast_channel'] = ChannelModel::class;
$GLOBALS['TL_MODELS']['tl_podcast_episode'] = EpisodeModel::class;
