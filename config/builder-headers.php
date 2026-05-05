<?php

return [
    /*
    | 10 professional header templates for the Visual Page Builder / theme.
    | Each is an array of blocks compatible with BlockRenderer (header_blocks).
    */
    'templates' => [
        'header-1-minimal' => [
            'name' => 'هدر مینیمال',
            'blocks' => [
                ['type' => 'section', 'id' => 'h1', 'props' => ['padding' => 'sm', 'background' => 'surface', 'container' => true], 'blocks' => [
                    ['type' => 'nav_menu', 'id' => 'nav1', 'props' => ['location' => 'header']],
                ]],
            ],
        ],
        'header-2-centered-logo' => [
            'name' => 'لوگو وسط',
            'blocks' => [
                ['type' => 'section', 'id' => 'h2', 'props' => ['padding' => 'md', 'background' => 'background', 'container' => true], 'blocks' => [
                    ['type' => 'nav_menu', 'id' => 'nav2', 'props' => ['location' => 'header']],
                ]],
            ],
        ],
        'header-3-dark-cta' => [
            'name' => 'تیره با دکمه CTA',
            'blocks' => [
                ['type' => 'section', 'id' => 'h3', 'props' => ['padding' => 'md', 'background' => 'text', 'container' => true], 'blocks' => [
                    ['type' => 'nav_menu', 'id' => 'nav3', 'props' => ['location' => 'header']],
                    ['type' => 'button', 'id' => 'cta3', 'props' => ['text' => 'تماس', 'url' => '#contact', 'style' => 'primary']],
                ]],
            ],
        ],
        'header-4-sticky-transparent' => [
            'name' => 'چسبان شفاف',
            'blocks' => [
                ['type' => 'section', 'id' => 'h4', 'props' => ['padding' => 'md', 'background' => 'transparent', 'container' => true], 'blocks' => [
                    ['type' => 'nav_menu', 'id' => 'nav4', 'props' => ['location' => 'header']],
                ]],
            ],
        ],
        'header-5-mega-menu' => [
            'name' => 'مگامنو',
            'blocks' => [
                ['type' => 'section', 'id' => 'h5', 'props' => ['padding' => 'sm', 'background' => 'surface', 'container' => true], 'blocks' => [
                    ['type' => 'nav_menu', 'id' => 'nav5', 'props' => ['location' => 'header']],
                ]],
            ],
        ],
        'header-6-search-cart' => [
            'name' => 'جستجو و سبد',
            'blocks' => [
                ['type' => 'section', 'id' => 'h6', 'props' => ['padding' => 'sm', 'background' => 'background', 'container' => true], 'blocks' => [
                    ['type' => 'nav_menu', 'id' => 'nav6', 'props' => ['location' => 'header']],
                    ['type' => 'search_bar', 'id' => 's6', 'props' => ['placeholder' => 'جستجو...']],
                    ['type' => 'cart_icon', 'id' => 'c6', 'props' => []],
                ]],
            ],
        ],
        'header-7-split' => [
            'name' => 'دو بخش',
            'blocks' => [
                ['type' => 'section', 'id' => 'h7', 'props' => ['padding' => 'md', 'background' => 'primary', 'container' => true], 'blocks' => [
                    ['type' => 'nav_menu', 'id' => 'nav7', 'props' => ['location' => 'header']],
                ]],
            ],
        ],
        'header-8-minimal-border' => [
            'name' => 'مینیمال با خط',
            'blocks' => [
                ['type' => 'section', 'id' => 'h8', 'props' => ['padding' => 'sm', 'background' => 'surface', 'container' => true], 'blocks' => [
                    ['type' => 'nav_menu', 'id' => 'nav8', 'props' => ['location' => 'header']],
                ]],
            ],
        ],
        'header-9-top-bar' => [
            'name' => 'نوار بالا',
            'blocks' => [
                ['type' => 'section', 'id' => 'h9a', 'props' => ['padding' => 'xs', 'background' => 'text', 'container' => true], 'blocks' => [
                    ['type' => 'text', 'id' => 't9', 'props' => ['content' => 'پشتیبانی: ۰۹۱۲۰۰۰۰۰۰۰']],
                ]],
                ['type' => 'section', 'id' => 'h9b', 'props' => ['padding' => 'sm', 'background' => 'surface', 'container' => true], 'blocks' => [
                    ['type' => 'nav_menu', 'id' => 'nav9', 'props' => ['location' => 'header']],
                ]],
            ],
        ],
        'header-10-cta-right' => [
            'name' => 'CTA راست',
            'blocks' => [
                ['type' => 'section', 'id' => 'h10', 'props' => ['padding' => 'md', 'background' => 'surface', 'container' => true], 'blocks' => [
                    ['type' => 'nav_menu', 'id' => 'nav10', 'props' => ['location' => 'header']],
                    ['type' => 'button', 'id' => 'cta10', 'props' => ['text' => 'درخواست تماس', 'url' => '#contact', 'style' => 'primary']],
                ]],
            ],
        ],
    ],
];
