<?php
// src/Creator/ArticleCreatorInterface.php
namespace App\Creator;

interface ArticleCreatorInterface
{
    public function create(string $title): void;
}
