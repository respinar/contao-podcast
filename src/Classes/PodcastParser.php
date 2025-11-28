<?php

declare(strict_types=1);

/*
 * This file is part of Contao Simple Podcast.
 *
 * (c) Hamid Peywasti 2024 <hamid@respinar.com>
 *
 * @license MIT
 */

namespace Respinar\PodcastBundle\Classes;

use Contao\ContentModel;
use Contao\CoreBundle\Image\Studio\Figure;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Util\LocaleUtil;
use Contao\Date;
use Contao\File;
use Contao\FilesModel;
use Contao\FrontendTemplate;
use Contao\Model\Collection;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\UserModel;
use Respinar\PodcastBundle\Model\EpisodeModel;

final readonly class PodcastParser
{
    public function __construct(
        private Studio $studio,
        private PodcastSchema $schema,
        private ContentUrlGenerator $contentUrlGenerator,
        private PodcastUtil $podcastUtil,
    ) {
    }

    public function parseEpisode(EpisodeModel $episode, ContentModel|ModuleModel $model, PageModel $page): string
    {
        $template = new FrontendTemplate($model->podcast_template);

        $template->setData($episode->row());

        $template->link = $this->contentUrlGenerator->generate($episode);

        $template->date = Date::parse($page->dateFormat, $episode->date);

        $template->duration = $this->podcastUtil->getDuration((int) $episode->duration);

        if (($author = $episode->getRelated('author')) instanceof UserModel) {
            $template->author = $GLOBALS['TL_LANG']['MSC']['by'].' '.$author->name;
            $template->authorModel = $author;
        }

        $this->addFigure($template, $episode, $model);

        $mediaUrl = $this->addAudio($template, $episode, $page);

        $schema = $this->schema->generate($episode, $mediaUrl);

        $template->schemaOrgData = $schema;

        return $template->parse();
    }

    public function parseEpisodes(Collection $episodes, ContentModel|ModuleModel $model, PageModel $page,): array
    {
        $items = [];

        foreach ($episodes as $episode) {
            $items[] = $this->parseEpisode($episode, $model, $page);
        }

        return $items;
    }

    private function addFigure(FrontendTemplate $template, EpisodeModel $episode, ContentModel|ModuleModel $model,): void
    {
        if (!$episode->coverSRC) {
            return;
        }

        $size = null;

        if ($model->imgSize) {
            $imgSize = StringUtil::deserialize($model->imgSize);

            if (
                $imgSize[0] > 0
                || $imgSize[1] > 0
                || is_numeric($imgSize[2])
                || ($imgSize[2][0] ?? null) === '_'
            ) {
                $size = $model->imgSize;
            }
        }

        $figure = $this->studio
            ->createFigureBuilder()
            ->from($episode->coverSRC)
            ->setSize($size)
            ->buildIfResourceExists()
        ;

        if ($figure instanceof Figure) {
            $template->figure = $figure;
        }
    }

    private function addAudio(FrontendTemplate $template, EpisodeModel $episode, PageModel $page): string|null
    {
        if (!$episode->podcastSRC) {
            return null;
        }

        $fileModel = FilesModel::findByUuid($episode->podcastSRC);

        if (!$fileModel instanceof FilesModel) {
            return null;
        }

        $locale = LocaleUtil::formatAsLocale($page->language ?: 'en');

        $meta = $fileModel->getMetadata($locale);

        $file = new File($fileModel->path);

        $file->title = StringUtil::specialchars(
            $meta?->getTitle() ?: $file->name,
        );

        $template->file = [
            'name' => $file->name,
            'path' => $file->path,
            'mime' => $file->mime,
            'title' => $file->title,
            'extension' => $file->extension,
        ];

        if (null !== $meta) {
            $template->caption = $meta->getCaption();
        }

        return '/'.$file->path;
    }
}
