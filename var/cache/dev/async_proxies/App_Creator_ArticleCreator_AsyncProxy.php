<?php
namespace App\Creator;

class ArticleCreator_AsyncProxy implements \App\Creator\ArticleCreatorInterface {
    private \App\Go\AsyncRunner $asyncRunner;
    private \App\Creator\ArticleCreator $inner;

    public function __construct(\App\Go\AsyncRunner $asyncRunner, \App\Creator\ArticleCreator $inner) {
        $this->asyncRunner = $asyncRunner;
        $this->inner = $inner;
    }

    public function create(string $title): void {
    $this->asyncRunner->execute(\get_class($this->inner), 'create', [$title]);
}
}