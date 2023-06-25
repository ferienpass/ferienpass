<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\AnnotationToAttributeRector;
use Rector\Php80\ValueObject\AnnotationToAttribute;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/core-bundle/src',
        __DIR__ . '/admin-bundle/src',
    ]);

    $rectorConfig->sets([
        SetList::PHP_81
    ]);

    $rectorConfig->ruleWithConfiguration(
        AnnotationToAttributeRector::class,
        [
            new AnnotationToAttribute('Symfony\Component\Serializer\Annotation'),
            new AnnotationToAttribute('Symfony\Component\Routing\Annotation\Route'),
        ]
    );
};
