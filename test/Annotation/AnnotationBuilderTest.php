<?php

declare(strict_types=1);

namespace LaminasTest\Form\Annotation;

use Laminas\Form\Annotation;

class AnnotationBuilderTest extends AbstractBuilderTestCase
{
    protected function createBuilder(): Annotation\AbstractBuilder
    {
        return new Annotation\AnnotationBuilder();
    }
}
