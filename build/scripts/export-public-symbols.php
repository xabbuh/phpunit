#!/usr/bin/env php
<?php declare(strict_types=1);
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use SebastianBergmann\FileIterator\Facade as FileIteratorFacade;

require __DIR__ . '/../../vendor/autoload.php';

$result = [
    'classLikes' => [],
    'functions'  => [],
];

foreach ((new FileIteratorFacade)->getFilesAsArray(__DIR__ . '/../../src', '.php') as $file) {
    $fileResult = analyse($file);

    $result['classLikes'] = array_merge($result['classLikes'], $fileResult['classLikes']);
    $result['functions']  = array_merge($result['functions'], $fileResult['functions']);
}

file_put_contents(
    __DIR__ . '/../tmp/public-symbols.json',
    json_encode($result, JSON_PRETTY_PRINT)
);

function analyse(string $file): array
{
    $nodes = parse($file);

    $traverser = new NodeTraverser;

    $traverser->addVisitor(new NameResolver);
    $traverser->addVisitor(new ParentConnectingVisitor);

    $visitor = new class extends NodeVisitorAbstract
    {
        private array $classLikes = [];
        private array $functions = [];

        public function enterNode(Node $node): void
        {
            if ($node instanceof Interface_ ||
                $node instanceof Class_ ||
                $node instanceof Enum_ ||
                $node instanceof Trait_ ||
                $node instanceof Function_) {
                if ($node->getDocComment() !== null &&
                    !str_contains($node->getDocComment()->getText(), '@internal')) {
                    if ($node instanceof Function_) {
                        $this->functions[] = $node->namespacedName->name;

                        return;
                    }

                    $this->classLikes[] = $node->namespacedName->name;
                }
            }
        }

        public function classLikes(): array
        {
            return $this->classLikes;
        }

        public function functions(): array
        {
            return $this->functions;
        }
    };

    $traverser->addVisitor($visitor);
    $traverser->traverse($nodes);

    return [
        'classLikes' => $visitor->classLikes(),
        'functions' => $visitor->functions(),
    ];
}

/**
 * @psalm-return array<Node>
 */
function parse(string $file): array
{
    try {
        $nodes = (new ParserFactory)->createForHostVersion()->parse(file_get_contents($file));

        assert($nodes !== null);

        return $nodes;
    } catch (Throwable $t) {
        print $t->getMessage() . PHP_EOL;

        exit(1);
    }
}
