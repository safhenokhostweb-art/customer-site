<?php

require __DIR__.'/../../../vendor/autoload.php';
$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

if (config('database.connections.mysql.database') !== 'customer_site_browser') {
    throw new RuntimeException('Default homepage fixture requires the isolated browser database.');
}
$document = SaaSPlatform\ThemeRuntime\DefaultHomepage::document();
$document['pages']['home']['is_home'] = true;
app(App\Services\ThemeCanvas\ThemeInstanceRevisionService::class)->record(
    $document,
    App\Models\ThemeInstanceRevision::TYPE_INSTALL,
    ['theme_identifier' => 'default-homepage-acceptance', 'theme_name' => 'صفحه اصلی پیش‌فرض'],
);
App\Models\Setting::set('canvas_draft_data', '', 'theme');
