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

use Contao\CoreBundle\Event\PreviewUrlCreateEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

use Contao\CoreBundle\Framework\ContaoFramework;
use Respinar\PodcastBundle\Model\EpisodeModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsEventListener('contao.preview_url_create')]
class PreviewUrlCreateListener
{
    private RequestStack $requestStack;
    private ContaoFramework $framework;

    public function __construct(RequestStack $requestStack, ContaoFramework $framework)
    {
        $this->requestStack = $requestStack;
        $this->framework = $framework;
    }

    public function __invoke(PreviewUrlCreateEvent $event): void
    {
        // Do something
        if (!$this->framework->isInitialized() || 'podcasts' !== $event->getKey()) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            throw new \RuntimeException('The request stack did not contain a request');
        }

        // Return on the product category list page
        if ('tl_podcast_episode' === $request->query->get('table') && !$request->query->has('act')) {
            return;
        }

        if ((!$id = $this->getId($event, $request)) || (!$podcastModel = $this->getPodcastModel($id))) {
            return;
        }

        $event->setQuery('podcast='.$podcastModel->id);
    }

    /**
     * @return int|string
     */
    private function getId(PreviewUrlCreateEvent $event, Request $request)
    {
        // Overwrite the ID if the podcast settings are edited
        if ('tl_podcast_episode' === $request->query->get('table') && 'edit' === $request->query->get('act')) {
            return $request->query->get('id');
        }

        return $event->getId();
    }

    /**
     * @param int|string $id
     */
    private function getPodcastModel($id): ?EpisodeModel
    {
        return $this->framework->getAdapter(EpisodeModel::class)->findByPk($id);
    }
}
