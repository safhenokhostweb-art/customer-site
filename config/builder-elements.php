<?php

return [
    'elements' => [

        // ── HEADING ────────────────────────────────────────────────────────────
        'heading' => [
            'name' => 'عنوان',
            'icon' => '🔠',
            'category' => 'content',
            'required_module' => null,
            'defaultProps' => ['level' => 2, 'text' => 'عنوان صفحه', 'align' => 'right', 'color' => ''],
            'schema' => [
                'text'  => ['label' => 'متن عنوان', 'type' => 'string', 'default' => 'عنوان صفحه'],
                'level' => ['label' => 'سطح (H1–H6)', 'type' => 'select', 'default' => 2, 'options' => ['1' => 'H1 — بزرگ‌ترین', '2' => 'H2', '3' => 'H3', '4' => 'H4', '5' => 'H5', '6' => 'H6']],
                'align' => ['label' => 'تراز', 'type' => 'select', 'default' => 'right', 'options' => ['right' => 'راست', 'center' => 'وسط', 'left' => 'چپ']],
                'color' => ['label' => 'رنگ متن', 'type' => 'color', 'default' => ''],
            ],
        ],

        // ── TEXT (rich text) ──────────────────────────────────────────────────
        'text' => [
            'name' => 'متن',
            'icon' => '📄',
            'category' => 'content',
            'required_module' => null,
            'defaultProps' => ['content' => '<p>متن خود را اینجا بنویسید.</p>', 'align' => 'right'],
            'schema' => [
                'content' => ['label' => 'محتوا', 'type' => 'richtext', 'default' => '<p>متن خود را اینجا بنویسید.</p>'],
                'align'   => ['label' => 'تراز', 'type' => 'select', 'default' => 'right', 'options' => ['right' => 'راست', 'center' => 'وسط', 'left' => 'چپ']],
            ],
        ],

        // ── IMAGE (with upload) ───────────────────────────────────────────────
        'image' => [
            'name' => 'تصویر',
            'icon' => '🖼️',
            'category' => 'content',
            'required_module' => null,
            'defaultProps' => ['url' => '', 'alt' => '', 'caption' => '', 'width' => 'full', 'rounded' => ''],
            'schema' => [
                'url'     => ['label' => 'تصویر', 'type' => 'image', 'default' => ''],
                'alt'     => ['label' => 'متن جایگزین (alt)', 'type' => 'string', 'default' => ''],
                'caption' => ['label' => 'زیرنویس تصویر', 'type' => 'string', 'default' => ''],
                'width'   => ['label' => 'عرض', 'type' => 'select', 'default' => 'full', 'options' => ['full' => 'تمام عرض', 'xl' => 'خیلی بزرگ', 'lg' => 'بزرگ', 'md' => 'متوسط', 'sm' => 'کوچک']],
                'rounded' => ['label' => 'گوشه گرد', 'type' => 'select', 'default' => '', 'options' => ['' => 'بدون', 'sm' => 'کم', 'md' => 'متوسط', 'lg' => 'زیاد', 'full' => 'کاملاً دایره']],
            ],
        ],

        // ── CARD ──────────────────────────────────────────────────────────────
        'card' => [
            'name' => 'کارت',
            'icon' => '🃏',
            'category' => 'content',
            'required_module' => null,
            'defaultProps' => [
                'image' => '', 'badge' => '', 'title' => 'عنوان کارت',
                'content' => '<p>توضیحات کارت را اینجا بنویسید.</p>',
                'button_text' => '', 'button_url' => '#', 'button_style' => 'primary',
                'shadow' => 'md', 'rounded' => 'md',
            ],
            'schema' => [
                'image'        => ['label' => 'تصویر کارت', 'type' => 'image', 'default' => ''],
                'badge'        => ['label' => 'برچسب (اختیاری)', 'type' => 'string', 'default' => ''],
                'title'        => ['label' => 'عنوان', 'type' => 'string', 'default' => 'عنوان کارت'],
                'content'      => ['label' => 'محتوا', 'type' => 'richtext', 'default' => ''],
                'button_text'  => ['label' => 'متن دکمه (اختیاری)', 'type' => 'string', 'default' => ''],
                'button_url'   => ['label' => 'لینک دکمه', 'type' => 'string', 'default' => '#'],
                'button_style' => ['label' => 'نوع دکمه', 'type' => 'select', 'default' => 'primary', 'options' => ['primary' => 'اصلی', 'outline' => 'خطی', 'ghost' => 'شفاف']],
                'shadow'       => ['label' => 'سایه', 'type' => 'select', 'default' => 'md', 'options' => ['none' => 'بدون', 'sm' => 'کم', 'md' => 'متوسط', 'lg' => 'زیاد']],
                'rounded'      => ['label' => 'گوشه گرد', 'type' => 'select', 'default' => 'md', 'options' => ['none' => 'بدون', 'sm' => 'کم', 'md' => 'متوسط', 'lg' => 'زیاد', 'xl' => 'خیلی زیاد']],
            ],
        ],

        // ── FEATURES GRID ─────────────────────────────────────────────────────
        'features_grid' => [
            'name' => 'گرید ویژگی‌ها',
            'icon' => '✨',
            'category' => 'content',
            'required_module' => null,
            'defaultProps' => [
                'title' => 'ویژگی‌های ما', 'columns' => '3',
                'items' => [
                    ['icon' => '⚡', 'title' => 'سریع', 'text' => 'توضیحات ویژگی اول'],
                    ['icon' => '🔒', 'title' => 'امن', 'text' => 'توضیحات ویژگی دوم'],
                    ['icon' => '💎', 'title' => 'حرفه‌ای', 'text' => 'توضیحات ویژگی سوم'],
                ],
            ],
            'schema' => [
                'title'   => ['label' => 'عنوان بخش', 'type' => 'string', 'default' => 'ویژگی‌های ما'],
                'columns' => ['label' => 'ستون‌ها', 'type' => 'select', 'default' => '3', 'options' => ['2' => '۲ ستون', '3' => '۳ ستون', '4' => '۴ ستون']],
                'items'   => [
                    'label' => 'ویژگی‌ها',
                    'type'  => 'repeater',
                    'default' => [],
                    'item_schema' => [
                        'icon'  => ['label' => 'آیکون (ایموجی)', 'type' => 'string', 'default' => '⭐'],
                        'title' => ['label' => 'عنوان', 'type' => 'string', 'default' => 'ویژگی'],
                        'text'  => ['label' => 'توضیحات', 'type' => 'textarea', 'default' => ''],
                    ],
                ],
            ],
        ],

        // ── COUNTER GRID ──────────────────────────────────────────────────────
        'counter_grid' => [
            'name' => 'شمارنده آماری',
            'icon' => '🔢',
            'category' => 'marketing',
            'required_module' => null,
            'defaultProps' => [
                'title' => '',
                'items' => [
                    ['number' => '۲۵۰', 'suffix' => '+', 'label' => 'مشتری راضی', 'color' => ''],
                    ['number' => '۵۰', 'suffix' => 'K', 'label' => 'محصول فروخته‌شده', 'color' => ''],
                    ['number' => '۱۰', 'suffix' => '+', 'label' => 'سال تجربه', 'color' => ''],
                ],
            ],
            'schema' => [
                'title' => ['label' => 'عنوان بخش (اختیاری)', 'type' => 'string', 'default' => ''],
                'items' => [
                    'label' => 'آمارها',
                    'type'  => 'repeater',
                    'default' => [],
                    'item_schema' => [
                        'number' => ['label' => 'عدد', 'type' => 'string', 'default' => '۱۰۰'],
                        'suffix' => ['label' => 'پسوند (مثلاً +)', 'type' => 'string', 'default' => '+'],
                        'label'  => ['label' => 'برچسب', 'type' => 'string', 'default' => 'آمار'],
                        'color'  => ['label' => 'رنگ عدد', 'type' => 'color', 'default' => ''],
                    ],
                ],
            ],
        ],

        // ── PRICING CARD ──────────────────────────────────────────────────────
        'pricing_card' => [
            'name' => 'کارت قیمت',
            'icon' => '💳',
            'category' => 'marketing',
            'required_module' => null,
            'defaultProps' => [
                'name' => 'پلن طلایی', 'price' => '۱۵۰,۰۰۰', 'period' => 'ماهانه',
                'badge' => 'پرطرفدار', 'highlighted' => false,
                'button_text' => 'شروع کنید', 'button_url' => '#',
                'features' => [
                    ['text' => 'دسترسی کامل', 'included' => true],
                    ['text' => 'پشتیبانی ۲۴/۷', 'included' => true],
                    ['text' => 'فضای نامحدود', 'included' => false],
                ],
            ],
            'schema' => [
                'name'        => ['label' => 'نام پلن', 'type' => 'string', 'default' => 'پلن طلایی'],
                'price'       => ['label' => 'قیمت (تومان)', 'type' => 'string', 'default' => '۱۵۰,۰۰۰'],
                'period'      => ['label' => 'دوره (مثلاً ماهانه)', 'type' => 'string', 'default' => 'ماهانه'],
                'badge'       => ['label' => 'برچسب ویژه (اختیاری)', 'type' => 'string', 'default' => ''],
                'highlighted' => ['label' => 'پلن برجسته', 'type' => 'boolean', 'default' => false],
                'features'    => [
                    'label' => 'ویژگی‌های پلن',
                    'type'  => 'repeater',
                    'default' => [],
                    'item_schema' => [
                        'text'     => ['label' => 'متن ویژگی', 'type' => 'string', 'default' => 'ویژگی'],
                        'included' => ['label' => 'موجود است؟', 'type' => 'boolean', 'default' => true],
                    ],
                ],
                'button_text' => ['label' => 'متن دکمه', 'type' => 'string', 'default' => 'شروع کنید'],
                'button_url'  => ['label' => 'لینک دکمه', 'type' => 'string', 'default' => '#'],
            ],
        ],

        // ── TEAM GRID ─────────────────────────────────────────────────────────
        'team_grid' => [
            'name' => 'تیم ما',
            'icon' => '👥',
            'category' => 'content',
            'required_module' => null,
            'defaultProps' => [
                'title' => 'تیم ما', 'columns' => '3',
                'items' => [
                    ['photo' => '', 'name' => 'نام نام‌خانوادگی', 'role' => 'مدیرعامل', 'bio' => ''],
                    ['photo' => '', 'name' => 'نام نام‌خانوادگی', 'role' => 'مدیر فنی', 'bio' => ''],
                ],
            ],
            'schema' => [
                'title'   => ['label' => 'عنوان بخش', 'type' => 'string', 'default' => 'تیم ما'],
                'columns' => ['label' => 'ستون‌ها', 'type' => 'select', 'default' => '3', 'options' => ['2' => '۲ ستون', '3' => '۳ ستون', '4' => '۴ ستون']],
                'items'   => [
                    'label' => 'اعضای تیم',
                    'type'  => 'repeater',
                    'default' => [],
                    'item_schema' => [
                        'photo' => ['label' => 'عکس', 'type' => 'image', 'default' => ''],
                        'name'  => ['label' => 'نام', 'type' => 'string', 'default' => 'نام'],
                        'role'  => ['label' => 'سمت', 'type' => 'string', 'default' => 'سمت'],
                        'bio'   => ['label' => 'بیوگرافی کوتاه', 'type' => 'textarea', 'default' => ''],
                    ],
                ],
            ],
        ],

        // ── TESTIMONIAL CARD ──────────────────────────────────────────────────
        'testimonial_card' => [
            'name' => 'نظر مشتری (تکی)',
            'icon' => '💬',
            'category' => 'marketing',
            'required_module' => null,
            'defaultProps' => [
                'text' => 'این محصول واقعاً عالی بود و کاملاً انتظاراتم را برآورده کرد.',
                'author' => 'علی محمدی', 'role' => 'مدیرعامل شرکت X',
                'avatar' => '', 'rating' => '5',
            ],
            'schema' => [
                'text'   => ['label' => 'متن نظر', 'type' => 'textarea', 'default' => ''],
                'author' => ['label' => 'نام نویسنده', 'type' => 'string', 'default' => ''],
                'role'   => ['label' => 'سمت / شرکت', 'type' => 'string', 'default' => ''],
                'avatar' => ['label' => 'عکس نویسنده', 'type' => 'image', 'default' => ''],
                'rating' => ['label' => 'امتیاز (۱–۵)', 'type' => 'select', 'default' => '5', 'options' => ['1'=>'⭐','2'=>'⭐⭐','3'=>'⭐⭐⭐','4'=>'⭐⭐⭐⭐','5'=>'⭐⭐⭐⭐⭐']],
            ],
        ],

        // ── ALERT BOX ─────────────────────────────────────────────────────────
        'alert_box' => [
            'name' => 'جعبه هشدار',
            'icon' => '⚠️',
            'category' => 'content',
            'required_module' => null,
            'defaultProps' => ['type' => 'info', 'icon' => 'ℹ️', 'title' => 'توجه', 'text' => 'پیام مهمی برای کاربران.', 'dismissible' => false],
            'schema' => [
                'type'        => ['label' => 'نوع', 'type' => 'select', 'default' => 'info', 'options' => ['info' => 'اطلاعات (آبی)', 'success' => 'موفقیت (سبز)', 'warning' => 'هشدار (زرد)', 'danger' => 'خطر (قرمز)', 'primary' => 'اصلی']],
                'icon'        => ['label' => 'آیکون (ایموجی)', 'type' => 'string', 'default' => 'ℹ️'],
                'title'       => ['label' => 'عنوان', 'type' => 'string', 'default' => 'توجه'],
                'text'        => ['label' => 'متن', 'type' => 'textarea', 'default' => ''],
                'dismissible' => ['label' => 'قابل بستن', 'type' => 'boolean', 'default' => false],
            ],
        ],

        // ── MAP EMBED ─────────────────────────────────────────────────────────
        'map_embed' => [
            'name' => 'نقشه',
            'icon' => '🗺️',
            'category' => 'utility',
            'required_module' => null,
            'defaultProps' => ['url' => '', 'height' => 'md', 'title' => 'موقعیت ما', 'rounded' => true],
            'schema' => [
                'url'     => ['label' => 'آدرس embed نقشه (Google Maps/Neshan)', 'type' => 'string', 'default' => ''],
                'height'  => ['label' => 'ارتفاع', 'type' => 'select', 'default' => 'md', 'options' => ['sm' => 'کوچک (250px)', 'md' => 'متوسط (400px)', 'lg' => 'بزرگ (550px)', 'xl' => 'خیلی بزرگ (700px)']],
                'title'   => ['label' => 'عنوان (اختیاری)', 'type' => 'string', 'default' => ''],
                'rounded' => ['label' => 'گوشه گرد', 'type' => 'boolean', 'default' => true],
            ],
        ],

        // ── CONTACT FORM ──────────────────────────────────────────────────────
        'contact_form' => [
            'name' => 'فرم تماس',
            'icon' => '📬',
            'category' => 'interactive',
            'required_module' => null,
            'defaultProps' => [
                'title' => 'با ما در تماس باشید',
                'show_name' => true, 'show_phone' => true, 'show_subject' => false,
                'button_text' => 'ارسال پیام',
                'success_msg' => 'پیام شما با موفقیت ارسال شد. به زودی با شما تماس می‌گیریم.',
            ],
            'schema' => [
                'title'       => ['label' => 'عنوان فرم', 'type' => 'string', 'default' => 'با ما در تماس باشید'],
                'show_name'   => ['label' => 'نمایش فیلد نام', 'type' => 'boolean', 'default' => true],
                'show_phone'  => ['label' => 'نمایش فیلد تلفن', 'type' => 'boolean', 'default' => true],
                'show_subject' => ['label' => 'نمایش فیلد موضوع', 'type' => 'boolean', 'default' => false],
                'button_text' => ['label' => 'متن دکمه ارسال', 'type' => 'string', 'default' => 'ارسال پیام'],
                'success_msg' => ['label' => 'پیام موفقیت', 'type' => 'textarea', 'default' => 'پیام شما ارسال شد.'],
            ],
        ],

        // ── TIMELINE ──────────────────────────────────────────────────────────
        'timeline' => [
            'name' => 'تایم‌لاین',
            'icon' => '📅',
            'category' => 'content',
            'required_module' => null,
            'defaultProps' => [
                'title' => 'مراحل کار',
                'items' => [
                    ['date' => '۱۴۰۰', 'title' => 'تأسیس شرکت', 'content' => 'شروع فعالیت ما.', 'color' => ''],
                    ['date' => '۱۴۰۱', 'title' => 'اولین محصول', 'content' => 'اولین محصول را ارائه دادیم.', 'color' => ''],
                    ['date' => '۱۴۰۲', 'title' => 'رشد تیم', 'content' => 'تیم ما به ۲۰ نفر رسید.', 'color' => ''],
                ],
            ],
            'schema' => [
                'title' => ['label' => 'عنوان', 'type' => 'string', 'default' => 'مراحل کار'],
                'items' => [
                    'label' => 'رویدادها',
                    'type'  => 'repeater',
                    'default' => [],
                    'item_schema' => [
                        'date'    => ['label' => 'تاریخ / سال', 'type' => 'string', 'default' => ''],
                        'title'   => ['label' => 'عنوان رویداد', 'type' => 'string', 'default' => ''],
                        'content' => ['label' => 'توضیحات', 'type' => 'textarea', 'default' => ''],
                        'color'   => ['label' => 'رنگ نقطه', 'type' => 'color', 'default' => ''],
                    ],
                ],
            ],
        ],

        // ── BUTTON ────────────────────────────────────────────────────────────
        'button' => [
            'name' => 'دکمه',
            'icon' => '🔘',
            'category' => 'content',
            'required_module' => null,
            'defaultProps' => ['text' => 'کلیک کنید', 'url' => '#', 'style' => 'primary', 'size' => 'md', 'align' => 'right', 'icon' => ''],
            'schema' => [
                'text'  => ['label' => 'متن دکمه', 'type' => 'string', 'default' => 'کلیک کنید'],
                'url'   => ['label' => 'لینک (URL)', 'type' => 'string', 'default' => '#'],
                'style' => ['label' => 'نوع', 'type' => 'select', 'default' => 'primary', 'options' => ['primary' => 'اصلی', 'secondary' => 'ثانویه', 'outline' => 'خطی', 'ghost' => 'شفاف', 'danger' => 'خطر']],
                'size'  => ['label' => 'اندازه', 'type' => 'select', 'default' => 'md', 'options' => ['sm' => 'کوچک', 'md' => 'متوسط', 'lg' => 'بزرگ', 'xl' => 'خیلی بزرگ']],
                'align' => ['label' => 'تراز', 'type' => 'select', 'default' => 'right', 'options' => ['right' => 'راست', 'center' => 'وسط', 'left' => 'چپ']],
                'icon'  => ['label' => 'آیکون (ایموجی، اختیاری)', 'type' => 'string', 'default' => ''],
            ],
        ],

        // ── ICON ──────────────────────────────────────────────────────────────
        'icon' => [
            'name' => 'آیکون',
            'icon' => '⭐',
            'category' => 'content',
            'required_module' => null,
            'defaultProps' => ['name' => '⭐', 'size' => 'md', 'color' => '', 'align' => 'right'],
            'schema' => [
                'name'  => ['label' => 'آیکون (ایموجی)', 'type' => 'string', 'default' => '⭐'],
                'size'  => ['label' => 'اندازه', 'type' => 'select', 'default' => 'md', 'options' => ['sm' => 'کوچک', 'md' => 'متوسط', 'lg' => 'بزرگ', 'xl' => 'خیلی بزرگ', 'xxl' => 'غول‌آسا']],
                'color' => ['label' => 'رنگ', 'type' => 'color', 'default' => ''],
                'align' => ['label' => 'تراز', 'type' => 'select', 'default' => 'right', 'options' => ['right' => 'راست', 'center' => 'وسط', 'left' => 'چپ']],
            ],
        ],

        // ── DIVIDER ───────────────────────────────────────────────────────────
        'divider' => [
            'name' => 'جداکننده',
            'icon' => '➖',
            'category' => 'content',
            'required_module' => null,
            'defaultProps' => ['style' => 'solid', 'color' => '', 'width' => 'full', 'thickness' => '1'],
            'schema' => [
                'style'     => ['label' => 'نوع خط', 'type' => 'select', 'default' => 'solid', 'options' => ['solid' => 'توپر', 'dashed' => 'خطچین', 'dotted' => 'نقطه‌چین', 'double' => 'دوخط']],
                'color'     => ['label' => 'رنگ', 'type' => 'color', 'default' => ''],
                'width'     => ['label' => 'عرض', 'type' => 'select', 'default' => 'full', 'options' => ['full' => 'تمام عرض', 'xl' => 'خیلی عریض', 'lg' => 'عریض', 'md' => 'متوسط', 'sm' => 'کوچک']],
                'thickness' => ['label' => 'ضخامت (px)', 'type' => 'select', 'default' => '1', 'options' => ['1' => '۱px', '2' => '۲px', '3' => '۳px', '4' => '۴px']],
            ],
        ],

        // ── SPACER ────────────────────────────────────────────────────────────
        'spacer' => [
            'name' => 'فاصله',
            'icon' => '↕️',
            'category' => 'content',
            'required_module' => null,
            'defaultProps' => ['height' => 'md', 'customHeight' => '', 'heightUnit' => 'px'],
            'schema' => [
                'height' => ['label' => 'ارتفاع پیش‌فرض', 'type' => 'select', 'default' => 'md', 'options' => ['xs' => 'خیلی کوچک (8px)', 'sm' => 'کوچک (16px)', 'md' => 'متوسط (32px)', 'lg' => 'بزرگ (64px)', 'xl' => 'خیلی بزرگ (96px)', 'xxl' => 'غول‌آسا (128px)', 'custom' => 'سفارشی']],
                'customHeight' => ['label' => 'ارتفاع سفارشی', 'type' => 'number', 'default' => '', 'min' => 0, 'max' => 2000, 'step' => 1, 'placeholder' => 'مقدار را وارد کنید'],
                'heightUnit' => ['label' => 'واحد', 'type' => 'select', 'default' => 'px', 'options' => ['px' => 'پیکسل (px)', 'vh' => 'ارتفاع ویو (vh)']],
            ],
        ],

        // ── VIDEO ─────────────────────────────────────────────────────────────
        'video' => [
            'name' => 'ویدیو',
            'icon' => '🎬',
            'category' => 'content',
            'required_module' => null,
            'defaultProps' => ['url' => '', 'caption' => '', 'autoplay' => false, 'rounded' => true],
            'schema' => [
                'url'      => ['label' => 'آدرس ویدیو (YouTube/Aparat embed URL)', 'type' => 'string', 'default' => ''],
                'caption'  => ['label' => 'زیرنویس', 'type' => 'string', 'default' => ''],
                'autoplay' => ['label' => 'پخش خودکار', 'type' => 'boolean', 'default' => false],
                'rounded'  => ['label' => 'گوشه گرد', 'type' => 'boolean', 'default' => true],
            ],
        ],

        // ── HERO ──────────────────────────────────────────────────────────────
        'hero' => [
            'name' => 'بخش قهرمان',
            'icon' => '🌟',
            'category' => 'layout',
            'required_module' => null,
            'defaultProps' => [
                'title' => 'عنوان اصلی سایت', 'subtitle' => '<p>توضیحات کوتاه و جذاب درباره کسب‌وکار شما.</p>',
                'button_text' => 'شروع کنید', 'button_url' => '#',
                'button2_text' => '', 'button2_url' => '#',
                'background' => 'primary', 'bg_image' => '', 'align' => 'center',
                'min_height' => 'md',
            ],
            'schema' => [
                'title'        => ['label' => 'عنوان اصلی', 'type' => 'string', 'default' => 'عنوان اصلی'],
                'subtitle'     => ['label' => 'زیرعنوان', 'type' => 'richtext', 'default' => ''],
                'button_text'  => ['label' => 'متن دکمه اول', 'type' => 'string', 'default' => 'شروع کنید'],
                'button_url'   => ['label' => 'لینک دکمه اول', 'type' => 'string', 'default' => '#'],
                'button2_text' => ['label' => 'متن دکمه دوم (اختیاری)', 'type' => 'string', 'default' => ''],
                'button2_url'  => ['label' => 'لینک دکمه دوم', 'type' => 'string', 'default' => '#'],
                'background'   => ['label' => 'پس‌زمینه', 'type' => 'select', 'default' => 'primary', 'options' => ['primary' => 'رنگ اصلی', 'gradient' => 'گرادیانت', 'dark' => 'تیره', 'light' => 'روشن', 'image' => 'تصویر']],
                'bg_image'     => ['label' => 'تصویر پس‌زمینه (وقتی background=image)', 'type' => 'image', 'default' => ''],
                'align'        => ['label' => 'تراز محتوا', 'type' => 'select', 'default' => 'center', 'options' => ['right' => 'راست', 'center' => 'وسط', 'left' => 'چپ']],
                'min_height'   => ['label' => 'حداقل ارتفاع', 'type' => 'select', 'default' => 'md', 'options' => ['sm' => 'کوچک', 'md' => 'متوسط', 'lg' => 'بزرگ', 'screen' => 'تمام صفحه']],
            ],
        ],

        // ── SECTION ───────────────────────────────────────────────────────────
        'section' => [
            'name' => 'بخش',
            'icon' => '📦',
            'category' => 'layout',
            'required_module' => null,
            'defaultProps' => ['padding' => 'none', 'background' => 'transparent', 'container' => true, 'radius' => ''],
            'schema' => [
                'padding'    => ['label' => 'فاصله داخلی', 'type' => 'select', 'default' => 'none', 'options' => ['none' => 'بدون', 'xs' => 'بسیار کوچک', 'sm' => 'کوچک', 'md' => 'متوسط', 'lg' => 'بزرگ', 'xl' => 'خیلی بزرگ']],
                'background' => ['label' => 'پس‌زمینه', 'type' => 'select', 'default' => 'transparent', 'options' => ['transparent' => 'شفاف', 'surface' => 'سفید', 'muted' => 'خاکستری', 'primary' => 'رنگ اصلی', 'dark' => 'تیره']],
                'container'  => ['label' => 'محدود به container', 'type' => 'boolean', 'default' => true],
                'radius'     => ['label' => 'گوشه گرد', 'type' => 'select', 'default' => '', 'options' => ['' => 'بدون', 'sm' => 'کم', 'md' => 'متوسط', 'lg' => 'زیاد']],
            ],
        ],

        // ── ROW ───────────────────────────────────────────────────────────────
        'row' => [
            'name' => 'ردیف',
            'icon' => '⇄',
            'category' => 'layout',
            'required_module' => null,
            'defaultProps' => ['gap' => 'none', 'columns' => 2, 'align' => 'start'],
            'schema' => [
                'columns' => ['label' => 'تعداد ستون', 'type' => 'select', 'default' => '2', 'options' => ['1' => '۱ ستون', '2' => '۲ ستون', '3' => '۳ ستون', '4' => '۴ ستون']],
                'gap'     => ['label' => 'فاصله', 'type' => 'select', 'default' => 'none', 'options' => ['none' => 'بدون', 'xs' => 'بسیار کوچک', 'sm' => 'کوچک', 'md' => 'متوسط', 'lg' => 'بزرگ']],
                'align'   => ['label' => 'تراز عمودی', 'type' => 'select', 'default' => 'start', 'options' => ['start' => 'بالا', 'center' => '중앙', 'end' => 'پایین', 'stretch' => 'کشیده']],
            ],
        ],

        // ── COLUMN ────────────────────────────────────────────────────────────
        'column' => [
            'name' => 'ستون',
            'icon' => '▤',
            'category' => 'layout',
            'required_module' => null,
            'defaultProps' => ['width' => '1/2'],
            'schema' => [
                'width' => ['label' => 'عرض', 'type' => 'select', 'default' => '1/2', 'options' => ['1/1' => 'تمام عرض', '1/2' => '۵۰٪', '1/3' => '۳۳٪', '2/3' => '۶۶٪', '1/4' => '۲۵٪', '3/4' => '۷۵٪']],
            ],
        ],

        // ── GRID ──────────────────────────────────────────────────────────────
        'grid' => [
            'name' => 'گرید',
            'icon' => '🔲',
            'category' => 'layout',
            'required_module' => null,
            'defaultProps' => ['columns' => 3, 'gap' => 'none'],
            'schema' => [
                'columns' => ['label' => 'ستون‌ها', 'type' => 'select', 'default' => '3', 'options' => ['2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶']],
                'gap'     => ['label' => 'فاصله', 'type' => 'select', 'default' => 'none', 'options' => ['none' => 'بدون', 'xs' => 'بسیار کوچک', 'sm' => 'کوچک', 'md' => 'متوسط', 'lg' => 'بزرگ']],
            ],
        ],

        // ── COLUMNS ───────────────────────────────────────────────────────────
        'columns' => [
            'name' => 'ستون‌های نسبی',
            'icon' => '▥',
            'category' => 'layout',
            'required_module' => null,
            'defaultProps' => ['ratios' => '1/2,1/2', 'gap' => 'none'],
            'schema' => [
                'ratios' => ['label' => 'نسبت ستون‌ها', 'type' => 'select', 'default' => '1/2,1/2', 'options' => ['1/2,1/2' => '۵۰٪ — ۵۰٪', '1/3,2/3' => '۳۳٪ — ۶۶٪', '2/3,1/3' => '۶۶٪ — ۳۳٪', '1/4,3/4' => '۲۵٪ — ۷۵٪', '1/3,1/3,1/3' => '۳۳٪ — ۳۳٪ — ۳۳٪']],
                'gap'    => ['label' => 'فاصله', 'type' => 'select', 'default' => 'none', 'options' => ['none' => 'بدون', 'xs' => 'بسیار کوچک', 'sm' => 'کوچک', 'md' => 'متوسط', 'lg' => 'بزرگ']],
            ],
        ],

        // ── TABS ──────────────────────────────────────────────────────────────
        'tabs' => [
            'name' => 'تب‌ها',
            'icon' => '📑',
            'category' => 'layout',
            'required_module' => null,
            'defaultProps' => ['tabs' => [['title' => 'تب ۱', 'blocks' => []], ['title' => 'تب ۲', 'blocks' => []]]],
            'schema' => [],
        ],

        // ── ACCORDION ────────────────────────────────────────────────────────
        'accordion' => [
            'name' => 'آکوردئون',
            'icon' => '🪗',
            'category' => 'layout',
            'required_module' => null,
            'defaultProps' => [
                'title' => '',
                'items' => [
                    ['title' => 'سوال اول چیست؟', 'content' => 'پاسخ سوال اول اینجا.'],
                    ['title' => 'سوال دوم چیست؟', 'content' => 'پاسخ سوال دوم اینجا.'],
                ],
            ],
            'schema' => [
                'title' => ['label' => 'عنوان بخش (اختیاری)', 'type' => 'string', 'default' => ''],
                'items' => [
                    'label' => 'آیتم‌ها',
                    'type'  => 'repeater',
                    'default' => [],
                    'item_schema' => [
                        'title'   => ['label' => 'عنوان / سوال', 'type' => 'string', 'default' => 'سوال'],
                        'content' => ['label' => 'محتوا / پاسخ', 'type' => 'textarea', 'default' => ''],
                    ],
                ],
            ],
        ],

        // ── SLIDER ───────────────────────────────────────────────────────────
        'slider' => [
            'name' => 'اسلایدر',
            'icon' => '🎠',
            'category' => 'layout',
            'required_module' => null,
            'defaultProps' => [
                'autoplay' => true, 'interval' => 5000,
                'slides' => [
                    ['image' => '', 'title' => 'عنوان اسلاید ۱', 'subtitle' => '', 'link' => ''],
                    ['image' => '', 'title' => 'عنوان اسلاید ۲', 'subtitle' => '', 'link' => ''],
                ],
            ],
            'schema' => [
                'autoplay' => ['label' => 'پخش خودکار', 'type' => 'boolean', 'default' => true],
                'interval' => ['label' => 'فاصله (میلی‌ثانیه)', 'type' => 'number', 'default' => 5000],
                'slides'   => [
                    'label' => 'اسلایدها',
                    'type'  => 'repeater',
                    'default' => [],
                    'item_schema' => [
                        'image'    => ['label' => 'تصویر اسلاید', 'type' => 'image', 'default' => ''],
                        'title'    => ['label' => 'عنوان', 'type' => 'string', 'default' => ''],
                        'subtitle' => ['label' => 'زیرعنوان', 'type' => 'string', 'default' => ''],
                        'link'     => ['label' => 'لینک (اختیاری)', 'type' => 'string', 'default' => ''],
                    ],
                ],
            ],
        ],

        // ── CTA ───────────────────────────────────────────────────────────────
        'cta' => [
            'name' => 'دعوت به اقدام',
            'icon' => '📢',
            'category' => 'marketing',
            'required_module' => null,
            'defaultProps' => [
                'title' => 'همین الان شروع کنید', 'subtitle' => 'با ما در تماس باشید',
                'button_text' => 'اقدام کنید', 'button_url' => '#',
                'button2_text' => '', 'button2_url' => '#',
                'background' => 'primary',
            ],
            'schema' => [
                'title'        => ['label' => 'عنوان', 'type' => 'string', 'default' => 'همین الان شروع کنید'],
                'subtitle'     => ['label' => 'زیرعنوان', 'type' => 'textarea', 'default' => ''],
                'button_text'  => ['label' => 'متن دکمه اول', 'type' => 'string', 'default' => 'اقدام کنید'],
                'button_url'   => ['label' => 'لینک دکمه اول', 'type' => 'string', 'default' => '#'],
                'button2_text' => ['label' => 'متن دکمه دوم (اختیاری)', 'type' => 'string', 'default' => ''],
                'button2_url'  => ['label' => 'لینک دکمه دوم', 'type' => 'string', 'default' => '#'],
                'background'   => ['label' => 'پس‌زمینه', 'type' => 'select', 'default' => 'primary', 'options' => ['primary' => 'رنگ اصلی', 'dark' => 'تیره', 'gradient' => 'گرادیانت', 'light' => 'روشن']],
            ],
        ],

        // ── TESTIMONIALS ──────────────────────────────────────────────────────
        'testimonials' => [
            'name' => 'نظرات مشتریان',
            'icon' => '⭐',
            'category' => 'dynamic',
            'required_module' => 'reviews',
            'defaultProps' => ['title' => 'نظرات مشتریان', 'limit' => 3],
            'schema' => [
                'title' => ['label' => 'عنوان', 'type' => 'string', 'default' => 'نظرات مشتریان'],
                'limit' => ['label' => 'تعداد نمایش', 'type' => 'number', 'default' => 3],
            ],
        ],

        // ── BLOG POSTS ────────────────────────────────────────────────────────
        'blog_posts' => [
            'name' => 'پست‌های وبلاگ',
            'icon' => '📰',
            'category' => 'dynamic',
            'required_module' => 'blog',
            'defaultProps' => ['title' => 'آخرین مطالب', 'limit' => 6, 'layout' => 'grid'],
            'schema' => [
                'title'  => ['label' => 'عنوان بخش', 'type' => 'string', 'default' => 'آخرین مطالب'],
                'limit'  => ['label' => 'تعداد پست', 'type' => 'number', 'default' => 6],
                'layout' => ['label' => 'نوع نمایش', 'type' => 'select', 'default' => 'grid', 'options' => ['grid' => 'گرید', 'list' => 'لیست', 'slider' => 'اسلایدر']],
            ],
        ],

        // ── PRODUCT GRID ──────────────────────────────────────────────────────
        'product_grid' => [
            'name' => 'گرید محصولات',
            'icon' => '🛒',
            'category' => 'ecommerce',
            'required_module' => 'shop',
            'defaultProps' => ['columns' => 4, 'limit' => 8, 'title' => ''],
            'schema' => [
                'title'   => ['label' => 'عنوان بخش', 'type' => 'string', 'default' => ''],
                'columns' => ['label' => 'ستون‌ها', 'type' => 'select', 'default' => '4', 'options' => ['2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵']],
                'limit'   => ['label' => 'تعداد محصول', 'type' => 'number', 'default' => 8],
            ],
        ],

        // ── FAQ ───────────────────────────────────────────────────────────────
        'faq' => [
            'name' => 'سوالات متداول',
            'icon' => '❓',
            'category' => 'dynamic',
            'required_module' => null,
            'defaultProps' => [
                'title' => 'سوالات متداول',
                'items' => [
                    ['title' => 'چگونه شروع کنیم؟', 'answer' => 'ابتدا ثبت‌نام کنید و سپس...'],
                    ['title' => 'هزینه خدمات چقدر است؟', 'answer' => 'قیمت‌های ما رقابتی است...'],
                ],
            ],
            'schema' => [
                'title' => ['label' => 'عنوان بخش', 'type' => 'string', 'default' => 'سوالات متداول'],
                'items' => [
                    'label' => 'سوالات',
                    'type'  => 'repeater',
                    'default' => [],
                    'item_schema' => [
                        'title'  => ['label' => 'سوال', 'type' => 'string', 'default' => 'سوال'],
                        'answer' => ['label' => 'پاسخ', 'type' => 'textarea', 'default' => ''],
                    ],
                ],
            ],
        ],

        // ── GALLERY ───────────────────────────────────────────────────────────
        'gallery' => [
            'name' => 'گالری تصاویر',
            'icon' => '🗂️',
            'category' => 'content',
            'required_module' => null,
            'defaultProps' => ['title' => '', 'columns' => '3', 'items' => []],
            'schema' => [
                'title'   => ['label' => 'عنوان گالری', 'type' => 'string', 'default' => ''],
                'columns' => ['label' => 'ستون‌ها', 'type' => 'select', 'default' => '3', 'options' => ['2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵']],
                'items'   => [
                    'label' => 'تصاویر',
                    'type'  => 'repeater',
                    'default' => [],
                    'item_schema' => [
                        'url'     => ['label' => 'تصویر', 'type' => 'image', 'default' => ''],
                        'caption' => ['label' => 'زیرنویس', 'type' => 'string', 'default' => ''],
                        'alt'     => ['label' => 'توضیح (alt)', 'type' => 'string', 'default' => ''],
                    ],
                ],
            ],
        ],

        // ── COUNTDOWN ────────────────────────────────────────────────────────
        'countdown' => [
            'name' => 'شمارش معکوس',
            'icon' => '⏱️',
            'category' => 'marketing',
            'required_module' => null,
            'defaultProps' => ['end_date' => '', 'label' => 'پایان پیشنهاد', 'style' => 'default'],
            'schema' => [
                'end_date' => ['label' => 'تاریخ پایان (YYYY-MM-DD HH:MM)', 'type' => 'string', 'default' => ''],
                'label'    => ['label' => 'برچسب', 'type' => 'string', 'default' => 'پایان پیشنهاد'],
                'style'    => ['label' => 'استایل', 'type' => 'select', 'default' => 'default', 'options' => ['default' => 'پیش‌فرض', 'dark' => 'تیره', 'primary' => 'رنگ اصلی']],
            ],
        ],

        // ── SOCIAL ICONS ──────────────────────────────────────────────────────
        'social_icons' => [
            'name' => 'شبکه‌های اجتماعی',
            'icon' => '🔗',
            'category' => 'utility',
            'required_module' => null,
            'defaultProps' => [
                'align' => 'right',
                'links' => [
                    ['platform' => 'instagram', 'url' => '#', 'label' => 'اینستاگرام'],
                    ['platform' => 'telegram', 'url' => '#', 'label' => 'تلگرام'],
                ],
            ],
            'schema' => [
                'align' => ['label' => 'تراز', 'type' => 'select', 'default' => 'right', 'options' => ['right' => 'راست', 'center' => 'وسط', 'left' => 'چپ']],
                'links' => [
                    'label' => 'شبکه‌های اجتماعی',
                    'type'  => 'repeater',
                    'default' => [],
                    'item_schema' => [
                        'platform' => ['label' => 'پلتفرم', 'type' => 'select', 'default' => 'instagram', 'options' => ['instagram' => 'اینستاگرام', 'telegram' => 'تلگرام', 'twitter' => 'توییتر/X', 'youtube' => 'یوتیوب', 'linkedin' => 'لینکدین', 'whatsapp' => 'واتساپ', 'aparat' => 'آپارات', 'website' => 'وبسایت', 'other' => 'سایر']],
                        'url'      => ['label' => 'لینک', 'type' => 'string', 'default' => '#'],
                        'label'    => ['label' => 'برچسب (اختیاری)', 'type' => 'string', 'default' => ''],
                    ],
                ],
            ],
        ],

        // ── PROMO BANNER ──────────────────────────────────────────────────────
        'promo_banner' => [
            'name' => 'بنر تبلیغاتی',
            'icon' => '📣',
            'category' => 'marketing',
            'required_module' => null,
            'defaultProps' => ['text' => 'پیشنهاد ویژه! همین الان خرید کنید', 'link' => '', 'link_text' => 'بیشتر بدانید', 'style' => 'info'],
            'schema' => [
                'text'      => ['label' => 'متن بنر', 'type' => 'string', 'default' => ''],
                'link'      => ['label' => 'لینک', 'type' => 'string', 'default' => ''],
                'link_text' => ['label' => 'متن لینک', 'type' => 'string', 'default' => 'بیشتر بدانید'],
                'style'     => ['label' => 'نوع', 'type' => 'select', 'default' => 'info', 'options' => ['info' => 'اطلاعات', 'success' => 'موفقیت', 'warning' => 'هشدار', 'danger' => 'خطر', 'promo' => 'تبلیغ ویژه']],
            ],
        ],

        // ── NEWSLETTER ────────────────────────────────────────────────────────
        'newsletter' => [
            'name' => 'خبرنامه',
            'icon' => '📧',
            'category' => 'interactive',
            'required_module' => null,
            'defaultProps' => [
                'title' => 'عضو خبرنامه شوید',
                'subtitle' => 'آخرین اخبار و تخفیف‌ها را دریافت کنید',
                'button_text' => 'عضویت',
                'placeholder' => 'شماره موبایل یا ایمیل...',
                'background' => 'muted',
            ],
            'schema' => [
                'title'       => ['label' => 'عنوان', 'type' => 'string', 'default' => 'عضو خبرنامه شوید'],
                'subtitle'    => ['label' => 'زیرعنوان', 'type' => 'string', 'default' => ''],
                'placeholder' => ['label' => 'متن placeholder', 'type' => 'string', 'default' => 'شماره موبایل...'],
                'button_text' => ['label' => 'متن دکمه', 'type' => 'string', 'default' => 'عضویت'],
                'background'  => ['label' => 'پس‌زمینه', 'type' => 'select', 'default' => 'muted', 'options' => ['muted' => 'خاکستری', 'primary' => 'رنگ اصلی', 'dark' => 'تیره', 'white' => 'سفید']],
            ],
        ],

        // ── CUSTOM HTML ───────────────────────────────────────────────────────
        'custom_html' => [
            'name' => 'HTML سفارشی',
            'icon' => '⌨️',
            'category' => 'utility',
            'required_module' => null,
            'defaultProps' => ['content' => ''],
            'schema' => [
                'content' => ['label' => 'کد HTML', 'type' => 'textarea', 'default' => ''],
            ],
        ],

        // ── SCROLL TO TOP ──────────────────────────────────────────────────────
        'scroll_to_top' => [
            'name' => 'دکمه هدایت به بالا (Scroll to Top)',
            'icon' => '🔝',
            'category' => 'utility',
            'required_module' => null,
            'description' => 'دکمه شناور هدایت سریع به بالای صفحه با افکت نورانی و تنظیمات سفارشی',
            'defaultProps' => [
                'icon_type' => 'svg',
                'icon' => 'arrow-up',
                'custom_icon_class' => '',
                'custom_icon_image' => '',
                'show_label' => false,
                'label' => 'برو به بالا',
                'orientation' => 'horizontal',
                'position' => 'bottom-right',
                'offset_x' => 24,
                'offset_y' => 24,
                'size' => 'md',
                'border_radius' => 9999,
                'scroll_threshold' => 300,
                'z_index' => 999,
                'bg_color' => '#1e293b',
                'text_color' => '#ffffff',
                'hover_bg_color' => '#3b82f6',
                'hover_text_color' => '#ffffff',
                'glow_effect' => true,
                'glow_color' => 'rgba(59, 130, 246, 0.5)',
                'hover_lift' => true,
            ],
            'schema' => [
                'icon_type' => ['label' => 'نوع آیکون', 'type' => 'select', 'default' => 'svg', 'options' => ['svg' => 'آیکون SVG', 'custom_class' => 'فونت (FontAwesome)', 'image' => 'تصویر سفارشی']],
                'icon' => ['label' => 'آیکون SVG', 'type' => 'icon', 'default' => 'arrow-up'],
                'custom_icon_class' => ['label' => 'کلاس فونت سفارشی', 'type' => 'string', 'default' => ''],
                'custom_icon_image' => ['label' => 'تصویر سفارشی', 'type' => 'image', 'default' => ''],
                'show_label' => ['label' => 'نمایش متن کنار دکمه', 'type' => 'boolean', 'default' => false],
                'label' => ['label' => 'متن دکمه', 'type' => 'string', 'default' => 'برو به بالا'],
                'orientation' => ['label' => 'جهت چیدمان متن و آیکون', 'type' => 'select', 'default' => 'horizontal', 'options' => ['horizontal' => 'افقی (کنار هم)', 'vertical' => 'عمودی (زیر هم)']],
                'position' => ['label' => 'موقعیت قرارگیری', 'type' => 'select', 'default' => 'bottom-right', 'options' => ['bottom-right' => 'پایین راست', 'bottom-left' => 'پایین چپ']],
                'offset_x' => ['label' => 'فاصله افقی از لبه (px)', 'type' => 'number', 'default' => 24],
                'offset_y' => ['label' => 'فاصله عمودی از پایین (px)', 'type' => 'number', 'default' => 24],
                'size' => ['label' => 'اندازه دکمه', 'type' => 'select', 'default' => 'md', 'options' => ['sm' => 'کوچک', 'md' => 'متوسط', 'lg' => 'بزرگ']],
                'border_radius' => ['label' => 'گوشه‌های دکمه (px)', 'type' => 'number', 'default' => 9999],
                'scroll_threshold' => ['label' => 'آستانه اسکرول برای نمایش (px)', 'type' => 'number', 'default' => 300],
                'z_index' => ['label' => 'ترتیب لایه (z-index)', 'type' => 'number', 'default' => 999],
                'bg_color' => ['label' => 'رنگ پس‌زمینه', 'type' => 'color', 'default' => '#1e293b'],
                'text_color' => ['label' => 'رنگ متن و آیکون', 'type' => 'color', 'default' => '#ffffff'],
                'hover_bg_color' => ['label' => 'رنگ پس‌زمینه در حالت هاور', 'type' => 'color', 'default' => '#3b82f6'],
                'hover_text_color' => ['label' => 'رنگ متن در حالت هاور', 'type' => 'color', 'default' => '#ffffff'],
                'glow_effect' => ['label' => 'هاله نورانی (Glow Pulse Aura)', 'type' => 'boolean', 'default' => true],
                'glow_color' => ['label' => 'رنگ هاله نورانی', 'type' => 'color', 'default' => 'rgba(59, 130, 246, 0.5)'],
                'hover_lift' => ['label' => 'حرکت رو به بالا در هاور (Lift Effect)', 'type' => 'boolean', 'default' => true],
            ],
        ],

    ],

    'categories' => [
        'layout'      => 'چیدمان',
        'content'     => 'محتوا',
        'marketing'   => 'بازاریابی',
        'interactive' => 'فرم‌ها و تعاملی',
        'dynamic'     => 'محتوای پویا',
        'ecommerce'   => 'فروشگاه',
        'utility'     => 'ابزار',
    ],
    'category_icons' => [
        'layout'      => '🏗️',
        'content'     => '✍️',
        'marketing'   => '📣',
        'interactive' => '🖱️',
        'dynamic'     => '⚡',
        'ecommerce'   => '🛒',
        'utility'     => '🔧',
    ],
    'category_colors' => [
        'layout'      => 'text-blue-600 bg-blue-50',
        'content'     => 'text-green-700 bg-green-50',
        'marketing'   => 'text-orange-600 bg-orange-50',
        'interactive' => 'text-violet-600 bg-violet-50',
        'dynamic'     => 'text-purple-600 bg-purple-50',
        'ecommerce'   => 'text-emerald-700 bg-emerald-50',
        'utility'     => 'text-slate-600 bg-slate-100',
    ],
];
