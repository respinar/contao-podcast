<?php

declare(strict_types=1);

/*
 * This file is part of Contao Simple Podcast.
 *
 * (c) Hamid Peywasti 2024 <hamid@respinar.com>
 *
 * @license MIT
 */

namespace Respinar\PodcastBundle\Controller\FrontendModule;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\FilesModel;
use Contao\PageModel;
use Contao\StringUtil;
use Respinar\PodcastBundle\Classes\PodcastUtil;
use Respinar\PodcastBundle\Model\ChannelModel;
use Respinar\PodcastBundle\Model\EpisodeModel;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/podcast/feed/{alias}', name: 'podcast_feed', requirements: ['alias' => '[a-zA-Z0-9_\-]+'])]
class PodcastFeedController
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly ContentUrlGenerator $contentUrlGenerator,
        private readonly PodcastUtil $podcastUtil,
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
    ) {
    }

    public function __invoke(Request $request, string $alias): Response
    {
        $this->framework->initialize();

        $channel = ChannelModel::findOneBy('feedAlias', $alias);

        if (!$channel instanceof ChannelModel || !$channel->feed) {
            throw new NotFoundHttpException('Podcast feed not found');
        }

        $episodes = EpisodeModel::findPublishedByPid(
            $channel->id,
            null,
            (int) $channel->maxItems,
            0,
            ['order' => 'date DESC'],
        );

        $baseUrl = rtrim($channel->feedBase ?: $request->getSchemeAndHttpHost(), '/');

        $xml = new \DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        $rss = $xml->createElement('rss');
        $rss->setAttribute('version', '2.0');
        $rss->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:itunes', 'http://www.itunes.com/dtds/podcast-1.0.dtd');
        $rss->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:content', 'http://purl.org/rss/1.0/modules/content/');

        $channelEl = $xml->createElement('channel');

        $titleEl = $xml->createElement('title', htmlspecialchars($channel->title));
        $channelEl->appendChild($titleEl);

        if ($channel->description) {
            $descEl = $xml->createElement('description', htmlspecialchars(StringUtil::decodeEntities(strip_tags($channel->description))));
            $channelEl->appendChild($descEl);
        }

        $jumpToPage = PageModel::findById($channel->jumpTo);
        if ($jumpToPage instanceof PageModel) {
            $linkEl = $xml->createElement('link', $jumpToPage->getAbsoluteUrl());
            $channelEl->appendChild($linkEl);
        }

        $langEl = $xml->createElement('language', $channel->language ?: 'en');
        $channelEl->appendChild($langEl);

        $generatorEl = $xml->createElement('generator', 'Contao Podcast Bundle');
        $channelEl->appendChild($generatorEl);

        if ($channel->coverSRC) {
            $fileModel = FilesModel::findByUuid($channel->coverSRC);
            if ($fileModel instanceof FilesModel) {
                $imageUrl = $baseUrl.'/'.$fileModel->path;
                $imageEl = $xml->createElement('itunes:image');
                $imageEl->setAttribute('href', $imageUrl);
                $channelEl->appendChild($imageEl);
            }
        }

        if (null !== $episodes) {
            foreach ($episodes as $episode) {
                $itemEl = $xml->createElement('item');

                $itemTitle = $xml->createElement('title', htmlspecialchars($episode->title));
                $itemEl->appendChild($itemTitle);

                try {
                    $episodeUrl = $this->contentUrlGenerator->generate($episode, [], UrlGeneratorInterface::ABSOLUTE_URL);
                } catch (\Throwable $e) {
                    $episodeUrl = '';
                }

                if ('' !== $episodeUrl && '0' !== $episodeUrl) {
                    $itemLink = $xml->createElement('link', $episodeUrl);
                    $itemEl->appendChild($itemLink);
                    $itemGuid = $xml->createElement('guid', $episodeUrl);
                    $itemGuid->setAttribute('isPermaLink', 'true');
                    $itemEl->appendChild($itemGuid);
                }

                if ($episode->description) {
                    $itemDesc = $xml->createElement('description', htmlspecialchars(StringUtil::decodeEntities(strip_tags($episode->description))));
                    $itemEl->appendChild($itemDesc);
                }

                $pubDate = $xml->createElement('pubDate', date('r', (int) $episode->date));
                $itemEl->appendChild($pubDate);

                if ($episode->podcastSRC) {
                    $fileModel = FilesModel::findByUuid($episode->podcastSRC);
                    if ($fileModel instanceof FilesModel) {
                        $filePath = $this->projectDir.'/'.$fileModel->path;

                        if (file_exists($filePath)) {
                            $enclosure = $xml->createElement('enclosure');
                            $enclosure->setAttribute('url', $baseUrl.'/'.$fileModel->path);
                            $enclosure->setAttribute('length', (string) filesize($filePath));

                            $ext = strtolower(pathinfo($fileModel->path, PATHINFO_EXTENSION));
                            $mimeTypes = [
                                'mp3' => 'audio/mpeg',
                                'm4a' => 'audio/mp4',
                                'aac' => 'audio/aac',
                                'ogg' => 'audio/ogg',
                                'oga' => 'audio/ogg',
                                'wav' => 'audio/wav',
                                'weba' => 'audio/webm',
                            ];
                            $enclosure->setAttribute('type', $mimeTypes[$ext] ?? 'audio/mpeg');
                            $itemEl->appendChild($enclosure);
                        }
                    }
                }

                if ($episode->duration) {
                    $duration = $xml->createElement('itunes:duration', $this->podcastUtil->iso8601Duration((int) $episode->duration));
                    $itemEl->appendChild($duration);
                }

                $channelEl->appendChild($itemEl);
            }
        }

        $rss->appendChild($channelEl);
        $xml->appendChild($rss);

        return new Response($xml->saveXML(), Response::HTTP_OK, ['Content-Type' => 'application/rss+xml; charset=utf-8']);
    }
}
