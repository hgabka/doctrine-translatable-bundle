<?php

namespace Hgabka\Doctrine\TranslatableBundle\Tests\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Hgabka\Doctrine\Translatable\Annotation as Hgabka;
use Hgabka\Doctrine\Translatable\Entity\TranslatableTrait;
use Hgabka\Doctrine\Translatable\TranslatableInterface;

class Article implements TranslatableInterface
{
    use TranslatableTrait;

    #[Hgabka\Translations(targetEntity: ArticleTranslation::class)]
    private $translations;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }
}
