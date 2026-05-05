<?php

return [
    /*
    | 10 professional footer templates for the Visual Page Builder / theme.
    | Each is an array of blocks compatible with BlockRenderer (footer_blocks).
    */
    'templates' => [
        'footer-1-simple' => [
            'name' => 'فوتر ساده',
            'blocks' => [
                ['type' => 'section', 'id' => 'f1', 'props' => ['padding' => 'lg', 'background' => 'surface', 'container' => true], 'blocks' => [
                    ['type' => 'text', 'id' => 'f1t', 'props' => ['content' => '© تمامی حقوق محفوظ است.']],
                ]],
            ],
        ],
        'footer-2-two-columns' => [
            'name' => 'دو ستون',
            'blocks' => [
                ['type' => 'section', 'id' => 'f2', 'props' => ['padding' => 'lg', 'background' => 'text', 'container' => true], 'blocks' => [
                    ['type' => 'columns', 'id' => 'f2c', 'props' => ['ratios' => '1/2,1/2', 'gap' => 'lg'], 'blocks' => [
                        ['type' => 'heading', 'id' => 'f2h1', 'props' => ['level' => 4, 'text' => 'درباره ما']],
                        ['type' => 'heading', 'id' => 'f2h2', 'props' => ['level' => 4, 'text' => 'لینک‌ها']],
                    ]],
                    ['type' => 'divider', 'id' => 'f2d', 'props' => []],
                    ['type' => 'text', 'id' => 'f2t', 'props' => ['content' => '© تمامی حقوق محفوظ است.']],
                ]],
            ],
        ],
        'footer-3-three-columns' => [
            'name' => 'سه ستون',
            'blocks' => [
                ['type' => 'section', 'id' => 'f3', 'props' => ['padding' => 'lg', 'background' => 'surface', 'container' => true], 'blocks' => [
                    ['type' => 'columns', 'id' => 'f3c', 'props' => ['ratios' => '1/3,1/3,1/3', 'gap' => 'lg'], 'blocks' => [
                        ['type' => 'heading', 'id' => 'f3h1', 'props' => ['level' => 4, 'text' => 'شرکت']],
                        ['type' => 'heading', 'id' => 'f3h2', 'props' => ['level' => 4, 'text' => 'پشتیبانی']],
                        ['type' => 'heading', 'id' => 'f3h3', 'props' => ['level' => 4, 'text' => 'تماس']],
                    ]],
                    ['type' => 'divider', 'id' => 'f3d', 'props' => []],
                    ['type' => 'text', 'id' => 'f3t', 'props' => ['content' => '© تمامی حقوق محفوظ است.']],
                ]],
            ],
        ],
        'footer-4-newsletter' => [
            'name' => 'با خبرنامه',
            'blocks' => [
                ['type' => 'section', 'id' => 'f4', 'props' => ['padding' => 'lg', 'background' => 'primary', 'container' => true], 'blocks' => [
                    ['type' => 'cta', 'id' => 'f4c', 'props' => [
                        'title' => 'در خبرنامه عضو شوید',
                        'subtitle' => 'تخفیف‌ها و اخبار را از دست ندهید.',
                        'button_text' => 'ثبت نام',
                        'button_url' => '#newsletter',
                        'background' => 'primary',
                    ]],
                ]],
                ['type' => 'section', 'id' => 'f4b', 'props' => ['padding' => 'lg', 'background' => 'text', 'container' => true], 'blocks' => [
                    ['type' => 'text', 'id' => 'f4t', 'props' => ['content' => '© تمامی حقوق محفوظ است.']],
                ]],
            ],
        ],
        'footer-5-social' => [
            'name' => 'با شبکه‌های اجتماعی',
            'blocks' => [
                ['type' => 'section', 'id' => 'f5', 'props' => ['padding' => 'lg', 'background' => 'surface', 'container' => true], 'blocks' => [
                    ['type' => 'heading', 'id' => 'f5h', 'props' => ['level' => 3, 'text' => 'تماس با ما']],
                    ['type' => 'social_icons', 'id' => 'f5s', 'props' => ['links' => []]],
                    ['type' => 'divider', 'id' => 'f5d', 'props' => []],
                    ['type' => 'text', 'id' => 'f5t', 'props' => ['content' => '© تمامی حقوق محفوظ است.']],
                ]],
            ],
        ],
        'footer-6-trust-badges' => [
            'name' => 'با نشان اعتماد',
            'blocks' => [
                ['type' => 'section', 'id' => 'f6', 'props' => ['padding' => 'lg', 'background' => 'background', 'container' => true], 'blocks' => [
                    ['type' => 'grid', 'id' => 'f6g', 'props' => ['columns' => 4, 'gap' => 'md'], 'blocks' => [
                        ['type' => 'text', 'id' => 'f6a', 'props' => ['content' => 'پرداخت امن']],
                        ['type' => 'text', 'id' => 'f6b', 'props' => ['content' => 'ارسال سریع']],
                        ['type' => 'text', 'id' => 'f6c', 'props' => ['content' => 'ضمانت بازگشت']],
                        ['type' => 'text', 'id' => 'f6d', 'props' => ['content' => 'پشتیبانی ۲۴/۷']],
                    ]],
                    ['type' => 'divider', 'id' => 'f6d', 'props' => []],
                    ['type' => 'text', 'id' => 'f6t', 'props' => ['content' => '© تمامی حقوق محفوظ است.']],
                ]],
            ],
        ],
        'footer-7-four-columns' => [
            'name' => 'چهار ستون',
            'blocks' => [
                ['type' => 'section', 'id' => 'f7', 'props' => ['padding' => 'lg', 'background' => 'text', 'container' => true], 'blocks' => [
                    ['type' => 'columns', 'id' => 'f7c', 'props' => ['ratios' => '1/4,1/4,1/4,1/4', 'gap' => 'lg'], 'blocks' => [
                        ['type' => 'heading', 'id' => 'f7h1', 'props' => ['level' => 4, 'text' => 'خرید']],
                        ['type' => 'heading', 'id' => 'f7h2', 'props' => ['level' => 4, 'text' => 'پشتیبانی']],
                        ['type' => 'heading', 'id' => 'f7h3', 'props' => ['level' => 4, 'text' => 'شرکت']],
                        ['type' => 'heading', 'id' => 'f7h4', 'props' => ['level' => 4, 'text' => 'تماس']],
                    ]],
                    ['type' => 'divider', 'id' => 'f7d', 'props' => []],
                    ['type' => 'social_icons', 'id' => 'f7s', 'props' => ['links' => []]],
                    ['type' => 'text', 'id' => 'f7t', 'props' => ['content' => '© تمامی حقوق محفوظ است.']],
                ]],
            ],
        ],
        'footer-8-minimal-dark' => [
            'name' => 'مینیمال تیره',
            'blocks' => [
                ['type' => 'section', 'id' => 'f8', 'props' => ['padding' => 'lg', 'background' => 'text', 'container' => true], 'blocks' => [
                    ['type' => 'text', 'id' => 'f8t', 'props' => ['content' => '© تمامی حقوق محفوظ است.']],
                ]],
            ],
        ],
        'footer-9-newsletter-links' => [
            'name' => 'خبرنامه و لینک‌ها',
            'blocks' => [
                ['type' => 'section', 'id' => 'f9', 'props' => ['padding' => 'lg', 'background' => 'surface', 'container' => true], 'blocks' => [
                    ['type' => 'cta', 'id' => 'f9c', 'props' => [
                        'title' => 'عضویت در خبرنامه',
                        'subtitle' => 'کدهای تخفیف و اخبار.',
                        'button_text' => 'ثبت',
                        'button_url' => '#',
                        'background' => 'primary',
                    ]],
                    ['type' => 'divider', 'id' => 'f9d', 'props' => []],
                    ['type' => 'columns', 'id' => 'f9c2', 'props' => ['ratios' => '1/2,1/2', 'gap' => 'lg'], 'blocks' => [
                        ['type' => 'heading', 'id' => 'f9h1', 'props' => ['level' => 4, 'text' => 'لینک‌های سریع']],
                        ['type' => 'heading', 'id' => 'f9h2', 'props' => ['level' => 4, 'text' => 'تماس']],
                    ]],
                    ['type' => 'text', 'id' => 'f9t', 'props' => ['content' => '© تمامی حقوق محفوظ است.']],
                ]],
            ],
        ],
        'footer-10-full' => [
            'name' => 'فوتر کامل',
            'blocks' => [
                ['type' => 'section', 'id' => 'f10', 'props' => ['padding' => 'lg', 'background' => 'text', 'container' => true], 'blocks' => [
                    ['type' => 'columns', 'id' => 'f10c', 'props' => ['ratios' => '1/4,1/4,1/4,1/4', 'gap' => 'lg'], 'blocks' => [
                        ['type' => 'heading', 'id' => 'f10h1', 'props' => ['level' => 4, 'text' => 'درباره']],
                        ['type' => 'heading', 'id' => 'f10h2', 'props' => ['level' => 4, 'text' => 'خدمات']],
                        ['type' => 'heading', 'id' => 'f10h3', 'props' => ['level' => 4, 'text' => 'قوانین']],
                        ['type' => 'heading', 'id' => 'f10h4', 'props' => ['level' => 4, 'text' => 'تماس']],
                    ]],
                    ['type' => 'divider', 'id' => 'f10d', 'props' => []],
                    ['type' => 'social_icons', 'id' => 'f10s', 'props' => ['links' => []]],
                    ['type' => 'text', 'id' => 'f10t', 'props' => ['content' => '© تمامی حقوق محفوظ است. پرداخت امن. ارسال سریع.']],
                ]],
            ],
        ],
    ],
];
