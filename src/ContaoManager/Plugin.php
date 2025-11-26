<?php

declare(strict_types=1);

/*
 * This file is part of Contao Simple Podcast.
 *
 * (c) Hamid Peywasti 2024 <hamid@respinar.com>
 *
 * @license MIT
 */

namespace Respinar\PodcastBundle\ContaoManager;

use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Contao\CoreBundle\ContaoCoreBundle;
use Respinar\PodcastBundle\RespinarPodcastBundle;
use Respinar\PodcastBundle\Controller\FrontendModule\PodcastFeedController;

class Plugin implements BundlePluginInterface, RoutingPluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(RespinarPodcastBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }

    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel): ?RouteCollection
    {
        $collection = new RouteCollection();

        $route = new Route(
            '/podcast/feed/{alias}',
            ['_controller' => PodcastFeedController::class . '::__invoke'],
            ['alias' => '[a-zA-Z0-9_\-]+']
        );
        $route->setMethods(['GET']);

        $collection->add('podcast_feed', $route);

        return $collection;
    }
}
