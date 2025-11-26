<?php

declare(strict_types=1);

/*
 * This file is part of Contao Simple Podcast.
 *
 * (c) Hamid Peywasti 2024 <hamid@respinar.com>
 *
 * @license MIT
 */

namespace Respinar\PodcastBundle\EventListener;

use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Event\SitemapEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

use Contao\PageModel;
use Contao\Database;
use Contao\CoreBundle\Framework\ContaoFramework;
use Respinar\PodcastBundle\Model\ChannelModel;
use Respinar\PodcastBundle\Model\EpisodeModel;

#[AsEventListener(event: ContaoCoreEvents::SITEMAP)]
class SitemapListener
{
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    public function __invoke(SitemapEvent $event): void
    {
        $arrRoot = $this->framework->createInstance(Database::class)->getChildRecords($event->getRootPageIds(), 'tl_page');

        // Early return here in the unlikely case that there are no pages
        if (empty($arrRoot)) {
            return;
        }

        $arrPages = [];
        $time = time();

        // Get all podcast channels
        $objChannels = $this->framework->getAdapter(ChannelModel::class)->findByProtected('');

        if (null === $objChannels) {
            return;
        }

        // Walk through each channel
        foreach ($objChannels as $objChannel)
        {
            // Skip channel without target page
            if (!$objChannel->jumpTo) {
                continue;
            }

            // Skip channel categories outside the root nodes
            if (!\in_array($objChannel->jumpTo, $arrRoot, false)) {
                continue;
            }

            $objParent = $this->framework->getAdapter(PageModel::class)->findWithDetails($objChannel->jumpTo);

            // The target page does not exist
            if (null === $objParent) {
                continue;
            }

            // The target page has not been published (see #5520)
            if (!$objParent->published || ($objParent->start && $objParent->start > $time) || ($objParent->stop && $objParent->stop <= $time)) {
                continue;
            }

            // The target page is protected (see #8416)
            if ($objParent->protected) {
                continue;
            }

            // The target page is exempt from the sitemap (see #6418)
            if ('noindex,nofollow' === $objParent->robots) {
                continue;
            }

            // Get the items
            $objEpisodes = $this->framework->getAdapter(EpisodeModel::class)->findPublishedDefaultByPid($objChannel->id);

            if (null === $objEpisodes) {
                continue;
            }

            foreach ($objEpisodes as $objEpisode) {
                $arrPages[] = $objParent->getAbsoluteUrl('/'.($objEpisode->alias ?: $objEpisode->id));
            }
        }

        $sitemap = $event->getDocument();

        foreach ($arrPages as $strUrl) {
            $urlSet = $sitemap->childNodes[0];

            $loc = $sitemap->createElement('loc', $strUrl);
            $urlEl = $sitemap->createElement('url');
            $urlEl->appendChild($loc);
            $urlSet->appendChild($urlEl);
        }
    }
}
