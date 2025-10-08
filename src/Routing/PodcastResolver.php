<?php

declare(strict_types=1);

namespace Respinar\PodcastBundle\Routing;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Content\ContentUrlResolverInterface;
use Contao\CoreBundle\Routing\Content\ContentUrlResult;
use Contao\PageModel;
use Respinar\PodcastBundle\Model\ChannelModel;
use Respinar\PodcastBundle\Model\EpisodeModel;

class PodcastResolver implements ContentUrlResolverInterface
{
    public function __construct(
        private readonly ContaoFramework $framework,
    ) {
    }

    public function resolve(object $content): ?ContentUrlResult
    {
        if (!$content instanceof EpisodeModel) {
            return null;
        }

        $channelAdapter = $this->framework->getAdapter(ChannelModel::class);
        $pageAdapter = $this->framework->getAdapter(PageModel::class);

        $channel = $channelAdapter->findById($content->pid);

        if (null === $channel || !$channel->jumpTo) {
            return null;
        }

        return ContentUrlResult::resolve(
            $pageAdapter->findPublishedById((int) $channel->jumpTo)
        );
    }

    public function getParametersForContent(object $content, PageModel $pageModel): array
    {
        if (!$content instanceof EpisodeModel) {
            return [];
        }

        return [
            'parameters' => '/'.($content->alias ?: $content->id),
        ];
    }
}