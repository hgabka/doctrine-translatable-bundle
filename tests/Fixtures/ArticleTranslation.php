<?php

namespace Hgabka\Doctrine\TranslatableBundle\Tests\Fixtures;

use Hgabka\Doctrine\Translatable\Annotation as Hgabka;
use Hgabka\Doctrine\Translatable\Entity\TranslationTrait;
use Hgabka\Doctrine\Translatable\TranslatableInterface;
use Hgabka\Doctrine\Translatable\TranslationInterface;

class ArticleTranslation implements TranslationInterface
{
    use TranslationTrait;

    protected ?string $title = null;

    #[Hgabka\Translatable(targetEntity: Article::class)]
    private ?TranslatableInterface $translatable = null;
}
