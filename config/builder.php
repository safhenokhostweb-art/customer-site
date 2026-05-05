<?php

return [
    /*
    | SDK element classes. All must implement App\Builder\Contracts\ElementInterface.
    | Used by ElementRegistry for registration and editor metadata.
    */
    'elements' => [
        \App\Builder\Elements\HeadingElement::class,
        \App\Builder\Elements\TextElement::class,
        \App\Builder\Elements\ImageElement::class,
        \App\Builder\Elements\ButtonElement::class,
        \App\Builder\Elements\DividerElement::class,
        \App\Builder\Elements\SpacerElement::class,
        \App\Builder\Elements\SectionElement::class,
        \App\Builder\Elements\RowElement::class,
        \App\Builder\Elements\ColumnElement::class,
        \App\Builder\Elements\ContainerElement::class,
        \App\Builder\Elements\GridElement::class,
        \App\Builder\Elements\ColumnsElement::class,
        \App\Builder\Elements\HeroElement::class,
    ],
];
