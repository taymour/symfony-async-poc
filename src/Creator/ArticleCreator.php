<?php

declare(strict_types=1);

namespace App\Creator;

use App\Entity\Article;
use App\Go\Attribute\Async;
use Doctrine\ORM\EntityManagerInterface;

class ArticleCreator implements ArticleCreatorInterface
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    #[Async]
    public function create(string $title): void
    {
        sleep(5);

        $article = new Article();
        $article->setTitle($title);
        $this->entityManager->persist($article);
        $this->entityManager->flush();

        file_put_contents('var/log/article_async.log', date('H:i:s') . " - Article created : {$title}\n", FILE_APPEND);
    }
}
