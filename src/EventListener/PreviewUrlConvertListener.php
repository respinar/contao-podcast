<?php

declare(strict_types=1);

/*
 * This file is part of Contao Podcast Bundle.
 *
 * (c) Hamid Peywasti 2024 <hamid@respinar.com>
 *
 * @license MIT
 */

namespace Respinar\PodcastBundle\EventListener;

use Contao\CoreBundle\Event\PreviewUrlConvertEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Respinar\PodcastBundle\Model\EpisodeModel;

use Respinar\PodcastBundle\Podcast;
use Spatie\SchemaOrg\Episode;

#[AsEventListener('contao.preview_url_convert')]
class PreviewUrlConvertListener
{
    private ContaoFramework  $framework;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    public function __invoke(PreviewUrlConvertEvent $event): void
    {
        // Do something
        if (!$this->framework->isInitialized()) {
            return;
        }

        if (null === ($podcast = $this->getPodcastModel($event->getRequest()))) {
            return;
        }

        $event->setUrl($this->framework->getAdapter(Podcast::class)->generateEpisodeUrl($podcast, false, true));

    }

    private function getPodcastModel(Request $request): ?EpisodeModel
    {
        if (!$request->query->has('podcast')) {
            return null;
        }

        return $this->framework->getAdapter(EpisodeModel::class)->findByPk($request->query->get('podcast'));
    }
}
