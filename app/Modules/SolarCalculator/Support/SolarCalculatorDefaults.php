<?php

namespace App\Modules\SolarCalculator\Support;

use App\Models\CurrencyExchangeRate;
use App\Modules\SolarCalculator\Models\SolarCalculatorConfig;
use App\Modules\SolarCalculator\Models\SolarCalculatorFormula;
use Illuminate\Support\Facades\Schema;

/**
 * The calculator's factory defaults, and the one list of editable UI
 * fields (UI_FIELDS) that both the admin settings page and the canvas
 * widget's inspector schema are generated from — so a text added here is
 * editable in both places with no second definition.
 */
final class SolarCalculatorDefaults
{
    public const UI_GROUPS = [
        'header' => 'سربرگ و تصاویر',
        'steps' => 'عنوان مراحل',
        'system_types' => 'نوع سیستم',
        'inputs' => 'فیلدهای ورودی',
        'results' => 'نتایج محاسبه',
        'contact' => 'فرم دریافت پیش‌فاکتور',
        'success' => 'پیام موفقیت',
        'buttons' => 'دکمه‌ها',
    ];

    /** key => [type (string|textarea|image), label, default, group] */
    public const UI_FIELDS = [
        'eyebrow' => ['string', 'برچسب بالای عنوان', 'ابزار محاسبهٔ هوشمند', 'header'],
        'title' => ['string', 'عنوان', 'محاسبهٔ آنلاین سیستم خورشیدی', 'header'],
        'subtitle' => ['textarea', 'توضیح', 'در چهار گام ساده ظرفیت سیستم، تجهیزات موردنیاز و برآورد هزینه را دریافت کنید.', 'header'],
        'banner_image' => ['image', 'تصویر بنر بالای ابزار', '', 'header'],
        'side_image' => ['image', 'تصویر کناری (چیدمان دوستونه)', '', 'header'],

        'step1_title' => ['string', 'عنوان گام ۱', 'نوع سیستم', 'steps'],
        'step1_hint' => ['string', 'راهنمای گام ۱', 'سیستم متناسب با شبکهٔ برق محل نصب را انتخاب کنید.', 'steps'],
        'step2_title' => ['string', 'عنوان گام ۲', 'مصرف و موقعیت', 'steps'],
        'step2_hint' => ['string', 'راهنمای گام ۲', 'مصرف ماهانه یا مبلغ قبض و استان محل نصب را وارد کنید.', 'steps'],
        'step3_title' => ['string', 'عنوان گام ۳', 'نتیجهٔ محاسبه', 'steps'],
        'step3_hint' => ['string', 'راهنمای گام ۳', 'مشخصات فنی پیشنهادی بر اساس اطلاعات شما.', 'steps'],
        'step4_title' => ['string', 'عنوان گام ۴', 'دریافت پیش‌فاکتور', 'steps'],
        'step4_hint' => ['string', 'راهنمای گام ۴', 'برای دریافت پیش‌فاکتور رسمی اطلاعات تماس خود را ثبت کنید.', 'steps'],

        'ongrid_label' => ['string', 'عنوان سیستم متصل به شبکه', 'متصل به شبکه (On-Grid)', 'system_types'],
        'ongrid_desc' => ['textarea', 'توضیح سیستم متصل به شبکه', 'فروش برق تولیدی به شبکه و کاهش قبض؛ بدون باتری.', 'system_types'],
        'ongrid_image' => ['image', 'تصویر سیستم متصل به شبکه', '', 'system_types'],
        'offgrid_label' => ['string', 'عنوان سیستم مستقل', 'مستقل از شبکه (Off-Grid)', 'system_types'],
        'offgrid_desc' => ['textarea', 'توضیح سیستم مستقل', 'برق پایدار در مناطق بدون شبکه با بانک باتری.', 'system_types'],
        'offgrid_image' => ['image', 'تصویر سیستم مستقل', '', 'system_types'],
        'hybrid_label' => ['string', 'عنوان سیستم هیبرید', 'هیبرید (Hybrid)', 'system_types'],
        'hybrid_desc' => ['textarea', 'توضیح سیستم هیبرید', 'اتصال به شبکه همراه با باتری پشتیبان برای قطعی برق.', 'system_types'],
        'hybrid_image' => ['image', 'تصویر سیستم هیبرید', '', 'system_types'],

        'mode_kwh_label' => ['string', 'گزینهٔ ورود مصرف', 'مصرف (کیلووات‌ساعت)', 'inputs'],
        'mode_bill_label' => ['string', 'گزینهٔ ورود مبلغ قبض', 'مبلغ قبض (تومان)', 'inputs'],
        'monthly_kwh_label' => ['string', 'برچسب مصرف ماهانه', 'میانگین مصرف ماهانه', 'inputs'],
        'monthly_kwh_hint' => ['string', 'راهنمای مصرف ماهانه', 'عدد مصرف را از قبض برق (کیلووات‌ساعت) بخوانید.', 'inputs'],
        'monthly_bill_label' => ['string', 'برچسب مبلغ قبض', 'میانگین مبلغ قبض ماهانه', 'inputs'],
        'monthly_bill_hint' => ['string', 'راهنمای مبلغ قبض', 'مبلغ را به تومان وارد کنید.', 'inputs'],
        'region_label' => ['string', 'برچسب استان', 'استان محل نصب', 'inputs'],
        'roof_area_label' => ['string', 'برچسب مساحت سقف', 'فضای قابل‌استفادهٔ سقف یا زمین (اختیاری)', 'inputs'],
        'roof_area_hint' => ['string', 'راهنمای مساحت', 'به مترمربع؛ برای بررسی کافی بودن فضا.', 'inputs'],
        'backup_hours_label' => ['string', 'برچسب ساعات پشتیبانی', 'ساعات موردنیاز برق اضطراری در قطعی', 'inputs'],

        'results_title' => ['string', 'عنوان نتایج', 'مشخصات سیستم پیشنهادی شما', 'results'],
        'results_note' => ['textarea', 'توضیح زیر نتایج', 'این برآورد اولیه است؛ طراحی نهایی پس از بازدید کارشناس انجام می‌شود.', 'results'],
        'price_title' => ['string', 'عنوان برآورد هزینه', 'برآورد هزینهٔ کل سیستم', 'results'],
        'price_hidden_text' => ['string', 'متن جایگزین قیمت (قیمت مخفی)', 'قیمت دقیق در پیش‌فاکتور اعلام می‌شود.', 'results'],
        'price_unavailable_text' => ['string', 'متن نبود نرخ ارز', 'قیمت به‌روز به‌زودی اعلام می‌شود؛ پیش‌فاکتور را دریافت کنید.', 'results'],
        'roof_warning_text' => ['string', 'هشدار کمبود فضا', 'فضای واردشده برای این تعداد پنل کافی نیست؛ کارشناس ما راهکار جایگزین پیشنهاد می‌دهد.', 'results'],
        'saving_label' => ['string', 'برچسب صرفه‌جویی سالانه', 'صرفه‌جویی سالانهٔ تخمینی', 'results'],
        'payback_label' => ['string', 'برچسب بازگشت سرمایه', 'بازگشت سرمایه', 'results'],

        'contact_title' => ['string', 'عنوان فرم', 'پیش‌فاکتور رسمی را دریافت کنید', 'contact'],
        'contact_text' => ['textarea', 'توضیح فرم', 'پیش‌فاکتور با لیست تجهیزات و قیمت روز برای شما صادر می‌شود و کارشناسان ما برای هماهنگی تماس می‌گیرند.', 'contact'],
        'name_label' => ['string', 'برچسب نام', 'نام و نام خانوادگی', 'contact'],
        'mobile_label' => ['string', 'برچسب موبایل', 'شمارهٔ موبایل', 'contact'],
        'email_label' => ['string', 'برچسب ایمیل', 'ایمیل (اختیاری)', 'contact'],
        'note_label' => ['string', 'برچسب توضیحات', 'توضیحات (اختیاری)', 'contact'],
        'privacy_text' => ['string', 'متن حریم خصوصی', 'اطلاعات شما فقط برای پیگیری همین درخواست استفاده می‌شود.', 'contact'],
        'login_required_text' => ['string', 'متن نیاز به ورود', 'برای ادامه وارد حساب کاربری خود شوید.', 'contact'],

        'success_title' => ['string', 'عنوان موفقیت', 'پیش‌فاکتور شما آماده است', 'success'],
        'success_text' => ['textarea', 'متن موفقیت', 'درخواست شما ثبت شد و کارشناسان ما به‌زودی تماس می‌گیرند.', 'success'],

        'btn_next' => ['string', 'دکمهٔ بعدی', 'مرحلهٔ بعد', 'buttons'],
        'btn_back' => ['string', 'دکمهٔ قبلی', 'بازگشت', 'buttons'],
        'btn_calculate' => ['string', 'دکمهٔ محاسبه', 'محاسبه کن', 'buttons'],
        'btn_quote' => ['string', 'دکمهٔ رفتن به فرم', 'دریافت پیش‌فاکتور', 'buttons'],
        'btn_submit' => ['string', 'دکمهٔ ثبت فرم', 'ثبت و صدور پیش‌فاکتور', 'buttons'],
        'btn_view_quote' => ['string', 'دکمهٔ مشاهدهٔ پیش‌فاکتور', 'مشاهدهٔ پیش‌فاکتور', 'buttons'],
        'btn_print' => ['string', 'دکمهٔ دانلود PDF', 'دانلود PDF', 'buttons'],
        'btn_restart' => ['string', 'دکمهٔ محاسبهٔ دوباره', 'محاسبهٔ دوباره', 'buttons'],
        'btn_login' => ['string', 'دکمهٔ ورود', 'ورود / ثبت‌نام', 'buttons'],
    ];

    public const SYSTEM_TYPES = ['on_grid', 'off_grid', 'hybrid'];

    public const ACCESS_MODES = [
        'open' => 'محاسبه و نتایج آزاد؛ ثبت اطلاعات تماس برای دریافت پیش‌فاکتور الزامی است',
        'login_results' => 'برای مشاهدهٔ نتایج ورود به حساب کاربری الزامی است',
        'login_quote' => 'نتایج آزاد؛ برای مشاهده و دانلود پیش‌فاکتور (PDF) ورود الزامی است',
    ];

    /** @return array<string, string> ui key => default text */
    public static function uiDefaults(): array
    {
        return array_map(static fn (array $f) => $f[2], self::UI_FIELDS);
    }

    /** @return array<string, mixed> config section => default value */
    public static function config(): array
    {
        return [
            'access' => [
                'mode' => 'open',
                'require_email' => false,
            ],
            'calculator' => [
                'default_system_type' => 'on_grid',
                'default_backup_hours' => 4,
                'show_price' => true,
                'show_saving' => true,
                'max_monthly_kwh' => 100000,
                'max_monthly_bill' => 100000000000,
                'max_roof_area' => 100000,
                'max_backup_hours' => 72,
            ],
            'company' => [
                'name' => '',
                'phone' => '',
                'email' => '',
                'address' => '',
                'website' => '',
                'logo' => '',
                'accent_color' => '',
                'quote_prefix' => 'SQ',
                'validity_days' => 7,
                'terms' => "قیمت‌ها بر اساس نرخ ارز روز صدور محاسبه شده و تا پایان مدت اعتبار معتبر است.\n"
                    . "طراحی نهایی و قیمت قطعی پس از بازدید فنی محل نصب اعلام می‌شود.\n"
                    . "هزینهٔ اخذ مجوزها و انشعاب در این پیش‌فاکتور منظور نشده است.",
            ],
            'ui' => self::uiDefaults(),
            'regions' => self::regions(),
            'result_cards' => [
                ['key' => 'system_kw', 'label' => 'ظرفیت پیشنهادی سیستم', 'unit' => 'کیلووات', 'decimals' => 1, 'icon' => 'zap', 'hide_zero' => false],
                ['key' => 'panel_count', 'label' => 'تعداد پنل خورشیدی', 'unit' => 'عدد', 'decimals' => 0, 'icon' => 'grid-3x3', 'hide_zero' => false],
                ['key' => 'inverter_kw', 'label' => 'توان اینورتر', 'unit' => 'کیلووات', 'decimals' => 1, 'icon' => 'cpu', 'hide_zero' => false],
                ['key' => 'battery_bank_kwh', 'label' => 'ظرفیت بانک باتری', 'unit' => 'کیلووات‌ساعت', 'decimals' => 1, 'icon' => 'battery-charging', 'hide_zero' => true],
                ['key' => 'annual_kwh', 'label' => 'تولید سالانهٔ تخمینی', 'unit' => 'کیلووات‌ساعت', 'decimals' => 0, 'icon' => 'sun', 'hide_zero' => false],
                ['key' => 'area_m2', 'label' => 'فضای موردنیاز نصب', 'unit' => 'مترمربع', 'decimals' => 0, 'icon' => 'layout-grid', 'hide_zero' => false],
            ],
            'quote_lines' => [
                ['label' => 'پنل خورشیدی مونوکریستال', 'detail' => 'توان هر پنل {panel_watt} وات', 'qty_key' => 'panel_count', 'qty_unit' => 'عدد', 'amount_key' => 'price_panels', 'hide_zero' => false],
                ['label' => 'اینورتر خورشیدی', 'detail' => 'توان {inverter_kw} کیلووات', 'qty_key' => '', 'qty_unit' => 'دستگاه', 'amount_key' => 'price_inverter', 'hide_zero' => false],
                ['label' => 'بانک باتری لیتیومی', 'detail' => 'ظرفیت کل {battery_bank_kwh} کیلووات‌ساعت', 'qty_key' => 'battery_count', 'qty_unit' => 'ماژول', 'amount_key' => 'price_batteries', 'hide_zero' => true],
                ['label' => 'سازه، کابل و تجهیزات حفاظتی', 'detail' => 'سازهٔ گالوانیزه، کابل DC/AC، تابلو و حفاظت', 'qty_key' => '', 'qty_unit' => 'سری', 'amount_key' => 'price_bos', 'hide_zero' => true],
                ['label' => 'نصب، راه‌اندازی و تست', 'detail' => 'اجرا توسط تیم فنی و تحویل سیستم', 'qty_key' => '', 'qty_unit' => 'خدمت', 'amount_key' => 'price_installation', 'hide_zero' => true],
            ],
            'quote_totals' => [
                'subtotal_key' => 'subtotal',
                'tax_key' => 'tax_amount',
                'tax_label' => 'مالیات بر ارزش افزوده',
                'total_key' => 'total_price',
                'saving_key' => 'annual_saving',
                'payback_key' => 'payback_years',
            ],
        ];
    }

    /**
     * Approximate average daily peak sun hours by province. Starting
     * values for the admin to tune — they are data, not engineering truth.
     *
     * @return list<array{key:string,label:string,psh:float}>
     */
    public static function regions(): array
    {
        $rows = [
            ['tehran', 'تهران', 5.0], ['alborz', 'البرز', 5.0], ['isfahan', 'اصفهان', 5.4],
            ['yazd', 'یزد', 5.8], ['kerman', 'کرمان', 5.7], ['fars', 'فارس', 5.6],
            ['khorasan_razavi', 'خراسان رضوی', 5.1], ['khorasan_south', 'خراسان جنوبی', 5.6],
            ['khorasan_north', 'خراسان شمالی', 4.8], ['sistan', 'سیستان و بلوچستان', 6.0],
            ['hormozgan', 'هرمزگان', 5.6], ['bushehr', 'بوشهر', 5.4], ['khuzestan', 'خوزستان', 5.2],
            ['semnan', 'سمنان', 5.4], ['qom', 'قم', 5.3], ['markazi', 'مرکزی', 5.1],
            ['qazvin', 'قزوین', 4.9], ['zanjan', 'زنجان', 4.8], ['hamedan', 'همدان', 4.9],
            ['kermanshah', 'کرمانشاه', 5.0], ['kurdistan', 'کردستان', 4.9], ['lorestan', 'لرستان', 5.0],
            ['ilam', 'ایلام', 5.2], ['chaharmahal', 'چهارمحال و بختیاری', 5.3],
            ['kohgiluyeh', 'کهگیلویه و بویراحمد', 5.3], ['azarbaijan_east', 'آذربایجان شرقی', 4.7],
            ['azarbaijan_west', 'آذربایجان غربی', 4.7], ['ardabil', 'اردبیل', 4.4],
            ['gilan', 'گیلان', 3.9], ['mazandaran', 'مازندران', 4.0], ['golestan', 'گلستان', 4.4],
        ];

        return array_map(static fn (array $r) => ['key' => $r[0], 'label' => $r[1], 'psh' => $r[2]], $rows);
    }

    /**
     * key => [label, group, expression, unit, is_system, description]
     *
     * @return array<string, array{0:string,1:string,2:string,3:string,4:bool,5:string}>
     */
    public static function formulas(): array
    {
        return [
            // Technical constants.
            'tariff_per_kwh' => ['میانگین تعرفهٔ برق هر کیلووات‌ساعت', 'constants', '2000', 'تومان', false, 'برای تبدیل مبلغ قبض به مصرف و محاسبهٔ صرفه‌جویی.'],
            'performance_ratio' => ['ضریب عملکرد سیستم (PR)', 'constants', '0.78', '', false, 'تلفات دما، گردوغبار، کابل و اینورتر.'],
            'panel_watt' => ['توان هر پنل', 'constants', '550', 'وات', false, ''],
            'panel_area_m2' => ['مساحت هر پنل', 'constants', '2.58', 'مترمربع', false, ''],
            'area_spacing_factor' => ['ضریب فاصله‌گذاری و راهرو سرویس', 'constants', '1.3', '', false, ''],
            'inverter_margin' => ['ضریب اطمینان توان اینورتر', 'constants', '1.15', '', false, ''],
            'battery_dod' => ['عمق دشارژ مجاز باتری', 'constants', '0.9', '', false, 'برای باتری لیتیومی حدود ۰٫۹.'],
            'battery_unit_kwh' => ['ظرفیت هر ماژول باتری', 'constants', '5.12', 'کیلووات‌ساعت', false, ''],
            'offgrid_autonomy_days' => ['روزهای خودکفایی سیستم مستقل', 'constants', '1.5', 'روز', false, ''],
            'min_system_kw' => ['حداقل ظرفیت قابل‌نصب', 'constants', '1', 'کیلووات', false, ''],

            // Equipment base prices (foreign currency) and local costs.
            'panel_price_usd_per_w' => ['قیمت پنل به ازای هر وات', 'prices', '0.2', 'دلار', false, 'با نرخ دلار (rate_usd) به تومان تبدیل می‌شود.'],
            'inverter_ongrid_usd_per_kw' => ['قیمت اینورتر متصل به شبکه به ازای هر کیلووات', 'prices', '110', 'دلار', false, ''],
            'inverter_hybrid_usd_per_kw' => ['قیمت اینورتر هیبرید/مستقل به ازای هر کیلووات', 'prices', '190', 'دلار', false, ''],
            'battery_unit_price_usd' => ['قیمت هر ماژول باتری', 'prices', '1150', 'دلار', false, ''],
            'bos_percent' => ['سهم تجهیزات جانبی از قیمت تجهیزات اصلی', 'prices', '20', 'درصد', false, 'سازه، کابل، تابلو و حفاظت.'],
            'installation_toman_per_kw' => ['هزینهٔ نصب و راه‌اندازی هر کیلووات', 'prices', '3500000', 'تومان', false, ''],
            'vat_percent' => ['نرخ مالیات بر ارزش افزوده', 'prices', '10', 'درصد', false, ''],

            // Sizing.
            'monthly_kwh' => ['مصرف ماهانه', 'sizing', 'if(consumption_by_bill, input_monthly_bill / tariff_per_kwh, input_monthly_kwh)', 'کیلووات‌ساعت', true, ''],
            'daily_kwh' => ['مصرف روزانه', 'sizing', 'monthly_kwh / 30', 'کیلووات‌ساعت', true, ''],
            'required_kw' => ['ظرفیت موردنیاز', 'sizing', 'max(min_system_kw, daily_kwh / (psh * performance_ratio))', 'کیلووات', true, ''],
            'panel_count' => ['تعداد پنل', 'sizing', 'ceil(required_kw * 1000 / panel_watt)', 'عدد', true, ''],
            'system_kw' => ['ظرفیت نصب‌شده', 'sizing', 'panel_count * panel_watt / 1000', 'کیلووات', true, ''],
            'inverter_kw' => ['توان اینورتر', 'sizing', 'roundup(system_kw * inverter_margin, 0.5)', 'کیلووات', true, ''],
            'battery_kwh' => ['انرژی موردنیاز باتری', 'sizing', 'if(is_on_grid, 0, if(is_off_grid, daily_kwh * offgrid_autonomy_days, daily_kwh / 24 * backup_hours) / battery_dod)', 'کیلووات‌ساعت', true, ''],
            'battery_count' => ['تعداد ماژول باتری', 'sizing', 'if(battery_kwh > 0, ceil(battery_kwh / battery_unit_kwh), 0)', 'عدد', true, ''],
            'battery_bank_kwh' => ['ظرفیت بانک باتری', 'sizing', 'battery_count * battery_unit_kwh', 'کیلووات‌ساعت', true, ''],
            'annual_kwh' => ['تولید سالانه', 'sizing', 'system_kw * psh * performance_ratio * 365', 'کیلووات‌ساعت', true, ''],
            'area_m2' => ['فضای موردنیاز', 'sizing', 'panel_count * panel_area_m2 * area_spacing_factor', 'مترمربع', true, ''],
            'roof_fits' => ['کافی بودن فضا', 'sizing', 'if(roof_area > 0, area_m2 <= roof_area, 1)', '', true, '۱ یعنی کافی، ۰ یعنی ناکافی.'],

            // Pricing (Toman).
            'price_panels' => ['قیمت پنل‌ها', 'pricing', 'system_kw * 1000 * panel_price_usd_per_w * rate_usd', 'تومان', true, ''],
            'price_inverter' => ['قیمت اینورتر', 'pricing', 'inverter_kw * if(is_on_grid, inverter_ongrid_usd_per_kw, inverter_hybrid_usd_per_kw) * rate_usd', 'تومان', true, ''],
            'price_batteries' => ['قیمت باتری‌ها', 'pricing', 'battery_count * battery_unit_price_usd * rate_usd', 'تومان', true, ''],
            'price_bos' => ['قیمت تجهیزات جانبی', 'pricing', '(price_panels + price_inverter + price_batteries) * bos_percent / 100', 'تومان', true, ''],
            'price_installation' => ['هزینهٔ نصب', 'pricing', 'system_kw * installation_toman_per_kw', 'تومان', true, ''],
            'subtotal' => ['جمع کل قبل از مالیات', 'pricing', 'price_panels + price_inverter + price_batteries + price_bos + price_installation', 'تومان', true, ''],
            'tax_amount' => ['مالیات', 'pricing', 'subtotal * vat_percent / 100', 'تومان', true, ''],
            'total_price' => ['مبلغ نهایی', 'pricing', 'subtotal + tax_amount', 'تومان', true, ''],
            'annual_saving' => ['صرفه‌جویی سالانه', 'pricing', 'annual_kwh * tariff_per_kwh', 'تومان', true, ''],
            'payback_years' => ['دورهٔ بازگشت سرمایه', 'pricing', 'if(annual_saving > 0, total_price / annual_saving, 0)', 'سال', true, ''],
        ];
    }

    /** code => Persian name; installed with rate 0 (not set). */
    public const CURRENCIES = [
        'USD' => 'دلار آمریکا',
        'EUR' => 'یورو',
        'AED' => 'درهم امارات',
        'CNY' => 'یوان چین',
    ];

    /** Idempotent: inserts only what is missing, never overwrites an edit. */
    public static function install(): void
    {
        if (Schema::hasTable('solar_calculator_formulas')) {
            $existing = SolarCalculatorFormula::query()->pluck('key')->all();
            $order = 0;
            foreach (self::formulas() as $key => [$label, $group, $expression, $unit, $system, $description]) {
                $order += 10;
                if (in_array($key, $existing, true)) {
                    continue;
                }
                SolarCalculatorFormula::query()->create([
                    'key' => $key, 'label' => $label, 'group' => $group, 'expression' => $expression,
                    'unit' => $unit !== '' ? $unit : null, 'description' => $description !== '' ? $description : null,
                    'is_system' => $system, 'sort_order' => $order,
                ]);
            }
        }

        if (Schema::hasTable('solar_calculator_configs')) {
            $existing = SolarCalculatorConfig::query()->pluck('key')->all();
            foreach (self::config() as $key => $value) {
                if (!in_array($key, $existing, true)) {
                    SolarCalculatorConfig::query()->create(['key' => $key, 'value' => $value]);
                }
            }
        }

        if (Schema::hasTable('currency_exchange_rates')) {
            foreach (self::CURRENCIES as $code => $name) {
                CurrencyExchangeRate::query()->firstOrCreate(['code' => $code], ['name' => $name, 'rate_toman' => 0]);
            }
        }
    }

    /** Restores one formula's factory expression; false when it has none. */
    public static function restoreFormula(SolarCalculatorFormula $formula): bool
    {
        $default = self::formulas()[$formula->key] ?? null;
        if ($default === null) {
            return false;
        }
        $formula->update(['expression' => $default[2]]);

        return true;
    }
}
