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
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Respinar\PodcastBundle\Model\EpisodeModel;



#[AsEventListener('contao.preview_url_convert')]
class PreviewUrlConvertListener
{
    private ContaoFramework  $framework;

    public function __construct(
        ContaoFramework $framework,
        private ContentUrlGenerator $contentUrlGenerator,
    ) {
        $this->framework = $framework;
    }

    public function __invoke(PreviewUrlConvertEvent $event): void
    {
        // Do something
        if (!$this->framework->isInitialized()) {
            return;
        }

        $podcast = $this->getPodcastModel($event->getRequest());

        if (!$podcast instanceof EpisodeModel) {
            return;
        }

        $event->setUrl($this->contentUrlGenerator->generate($podcast));
    }

    private function getPodcastModel(Request $request): ?EpisodeModel
    {
        if (!$request->query->has('podcast')) {
            return null;
        }

        return $this->framework->getAdapter(EpisodeModel::class)->findByPk($request->query->get('podcast'));
    }
}
