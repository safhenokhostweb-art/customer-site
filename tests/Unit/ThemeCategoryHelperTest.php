<?php

namespace Tests\Unit;

use App\Services\ThemeCategoryHelper;
use Tests\TestCase;

/**
 * Template Builder commercial expansion — 'business' and 'services' were
 * genuinely missing from this taxonomy. 'ecommerce' and 'personal-brand'
 * were intentionally NOT added as new keys — 'store' and 'personal' already
 * cover that ground; adding near-duplicates would fragment the catalog.
 */
class ThemeCategoryHelperTest extends TestCase
{
    public function test_business_and_services_categories_exist(): void
    {
        $this->assertSame('کسب‌وکار', ThemeCategoryHelper::label('business'));
        $this->assertSame('خدمات', ThemeCategoryHelper::label('services'));
        $this->assertArrayHasKey('business', ThemeCategoryHelper::all());
        $this->assertArrayHasKey('services', ThemeCategoryHelper::all());
    }

    public function test_existing_categories_are_unaffected(): void
    {
        $this->assertSame('شرکتی', ThemeCategoryHelper::label('corporate'));
        $this->assertSame('فروشگاه', ThemeCategoryHelper::label('store'));
    }

    public function test_unknown_category_falls_back_to_the_raw_value(): void
    {
        $this->assertSame('not-a-real-category', ThemeCategoryHelper::label('not-a-real-category'));
    }
}
