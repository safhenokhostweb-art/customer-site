<?php

return [
    /*
    | Ready-made section presets for the Visual Page Builder.
    | Each preset is a block tree (section with inner blocks) compatible with BlockRenderer.
    */
    'presets' => [
        'hero-center' => [
            'name' => 'هیرو وسط',
            'blocks' => [
                [
                    'type' => 'section',
                    'id' => 'preset_hero_1',
                    'props' => ['padding' => 'xl', 'background' => 'primary', 'container' => true],
                    'blocks' => [
                        ['type' => 'heading', 'id' => 'h1', 'props' => ['level' => 1, 'text' => 'عنوان اصلی شما']],
                        ['type' => 'text', 'id' => 't1', 'props' => ['content' => '<p>توضیح کوتاه درباره خدمات یا محصول شما.</p>']],
                        ['type' => 'button', 'id' => 'b1', 'props' => ['text' => 'شروع کنید', 'url' => '#', 'style' => 'primary']],
                    ],
                ],
            ],
        ],
        'hero-split' => [
            'name' => 'هیرو دو ستونه',
            'blocks' => [
                [
                    'type' => 'section',
                    'id' => 'preset_hero_2',
                    'props' => ['padding' => 'xl', 'background' => 'surface', 'container' => true],
                    'blocks' => [
                        ['type' => 'row', 'id' => 'row1', 'props' => ['gap' => 'lg'], 'blocks' => [
                            ['type' => 'column', 'id' => 'c1', 'props' => ['width' => '1/2'], 'blocks' => [
                                ['type' => 'heading', 'id' => 'h2', 'props' => ['level' => 1, 'text' => 'عنوان']],
                                ['type' => 'text', 'id' => 't2', 'props' => ['content' => '<p>متن توضیحات.</p>']],
                                ['type' => 'button', 'id' => 'b2', 'props' => ['text' => 'بیشتر', 'url' => '#', 'style' => 'primary']],
                            ]],
                            ['type' => 'column', 'id' => 'c2', 'props' => ['width' => '1/2'], 'blocks' => [
                                ['type' => 'image', 'id' => 'img1', 'props' => ['url' => '', 'alt' => 'تصویر']],
                            ]],
                        ]],
                    ],
                ],
            ],
        ],
        'services-grid' => [
            'name' => 'گرید خدمات',
            'blocks' => [
                [
                    'type' => 'section',
                    'id' => 'preset_services',
                    'props' => ['padding' => 'xl', 'background' => 'background', 'container' => true],
                    'blocks' => [
                        ['type' => 'heading', 'id' => 'sh', 'props' => ['level' => 2, 'text' => 'خدمات ما']],
                        ['type' => 'text', 'id' => 'st', 'props' => ['content' => '<p>توضیح کوتاه درباره خدمات.</p>']],
                        ['type' => 'row', 'id' => 'sr', 'props' => ['gap' => 'lg'], 'blocks' => [
                            ['type' => 'column', 'id' => 'sc1', 'props' => ['width' => '1/3'], 'blocks' => [
                                ['type' => 'heading', 'id' => 's1h', 'props' => ['level' => 3, 'text' => 'خدمت ۱']],
                                ['type' => 'text', 'id' => 's1t', 'props' => ['content' => '<p>توضیح.</p>']],
                            ]],
                            ['type' => 'column', 'id' => 'sc2', 'props' => ['width' => '1/3'], 'blocks' => [
                                ['type' => 'heading', 'id' => 's2h', 'props' => ['level' => 3, 'text' => 'خدمت ۲']],
                                ['type' => 'text', 'id' => 's2t', 'props' => ['content' => '<p>توضیح.</p>']],
                            ]],
                            ['type' => 'column', 'id' => 'sc3', 'props' => ['width' => '1/3'], 'blocks' => [
                                ['type' => 'heading', 'id' => 's3h', 'props' => ['level' => 3, 'text' => 'خدمت ۳']],
                                ['type' => 'text', 'id' => 's3t', 'props' => ['content' => '<p>توضیح.</p>']],
                            ]],
                        ]],
                    ],
                ],
            ],
        ],
        'features-4' => [
            'name' => 'ویژگی‌ها (۴ ستون)',
            'blocks' => [
                [
                    'type' => 'section',
                    'id' => 'preset_features',
                    'props' => ['padding' => 'xl', 'background' => 'surface', 'container' => true],
                    'blocks' => [
                        ['type' => 'heading', 'id' => 'fh', 'props' => ['level' => 2, 'text' => 'چرا ما؟']],
                        ['type' => 'row', 'id' => 'fr', 'props' => ['gap' => 'md'], 'blocks' => [
                            ['type' => 'column', 'id' => 'fc1', 'props' => ['width' => '1/4'], 'blocks' => [
                                ['type' => 'text', 'id' => 'f1', 'props' => ['content' => '<p><strong>ویژگی ۱</strong><br>توضیح کوتاه.</p>']],
                            ]],
                            ['type' => 'column', 'id' => 'fc2', 'props' => ['width' => '1/4'], 'blocks' => [
                                ['type' => 'text', 'id' => 'f2', 'props' => ['content' => '<p><strong>ویژگی ۲</strong><br>توضیح کوتاه.</p>']],
                            ]],
                            ['type' => 'column', 'id' => 'fc3', 'props' => ['width' => '1/4'], 'blocks' => [
                                ['type' => 'text', 'id' => 'f3', 'props' => ['content' => '<p><strong>ویژگی ۳</strong><br>توضیح کوتاه.</p>']],
                            ]],
                            ['type' => 'column', 'id' => 'fc4', 'props' => ['width' => '1/4'], 'blocks' => [
                                ['type' => 'text', 'id' => 'f4', 'props' => ['content' => '<p><strong>ویژگی ۴</strong><br>توضیح کوتاه.</p>']],
                            ]],
                        ]],
                    ],
                ],
            ],
        ],
        'cta-block' => [
            'name' => 'بلوک CTA',
            'blocks' => [
                [
                    'type' => 'section',
                    'id' => 'preset_cta',
                    'props' => ['padding' => 'lg', 'background' => 'primary', 'container' => true],
                    'blocks' => [
                        ['type' => 'cta', 'id' => 'cta1', 'props' => [
                            'title' => 'همین الان شروع کنید',
                            'subtitle' => 'با ما در تماس باشید.',
                            'button_text' => 'تماس',
                            'button_url' => '#contact',
                            'background' => 'primary',
                        ]],
                    ],
                ],
            ],
        ],
        'contact-section' => [
            'name' => 'بخش تماس',
            'blocks' => [
                [
                    'type' => 'section',
                    'id' => 'preset_contact',
                    'props' => ['padding' => 'xl', 'background' => 'background', 'container' => true],
                    'blocks' => [
                        ['type' => 'heading', 'id' => 'ch', 'props' => ['level' => 2, 'text' => 'تماس با ما']],
                        ['type' => 'text', 'id' => 'ct', 'props' => ['content' => '<p>آدرس، تلفن، ایمیل.</p>']],
                        ['type' => 'button', 'id' => 'cb', 'props' => ['text' => 'ارسال پیام', 'url' => '/contact', 'style' => 'primary']],
                    ],
                ],
            ],
        ],
        'faq-section' => [
            'name' => 'سوالات متداول',
            'blocks' => [
                [
                    'type' => 'section',
                    'id' => 'preset_faq',
                    'props' => ['padding' => 'xl', 'background' => 'surface', 'container' => true],
                    'blocks' => [
                        ['type' => 'faq', 'id' => 'faq1', 'props' => [
                            'title' => 'سوالات متداول',
                            'items' => [
                                ['question' => 'سوال اول؟', 'answer' => 'پاسخ اول.'],
                                ['question' => 'سوال دوم؟', 'answer' => 'پاسخ دوم.'],
                            ],
                        ]],
                    ],
                ],
            ],
        ],
        'testimonials-section' => [
            'name' => 'نظرات مشتریان',
            'blocks' => [
                [
                    'type' => 'section',
                    'id' => 'preset_testimonials',
                    'props' => ['padding' => 'xl', 'background' => 'background', 'container' => true],
                    'blocks' => [
                        ['type' => 'testimonials', 'id' => 'test1', 'props' => ['title' => 'نظرات مشتریان', 'limit' => 6]],
                    ],
                ],
            ],
        ],
        'blog-preview' => [
            'name' => 'پیش‌نمایش وبلاگ',
            'blocks' => [
                [
                    'type' => 'section',
                    'id' => 'preset_blog',
                    'props' => ['padding' => 'xl', 'background' => 'surface', 'container' => true],
                    'blocks' => [
                        ['type' => 'blog_posts', 'id' => 'blog1', 'props' => ['title' => 'آخرین مطالب', 'limit' => 6]],
                    ],
                ],
            ],
        ],
        'products-showcase' => [
            'name' => 'ویترین محصولات',
            'blocks' => [
                [
                    'type' => 'section',
                    'id' => 'preset_products',
                    'props' => ['padding' => 'xl', 'background' => 'background', 'container' => true],
                    'blocks' => [
                        ['type' => 'heading', 'id' => 'ph', 'props' => ['level' => 2, 'text' => 'محصولات پرفروش']],
                        ['type' => 'product_grid', 'id' => 'pg1', 'props' => ['columns' => 4, 'limit' => 8]],
                    ],
                ],
            ],
        ],
    ],
];
