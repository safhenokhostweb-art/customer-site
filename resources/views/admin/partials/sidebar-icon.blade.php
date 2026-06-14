@php $key = $key ?? ''; @endphp
@if($key === 'dashboard')
<x-core-ui::icon name="dashboard" class="w-5 h-5 ml-3 shrink-0 text-indigo-500" />
@elseif($key === 'pages')
<x-core-ui::icon name="file-text" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'categories')
<x-core-ui::icon name="tag" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'posts')
<x-core-ui::icon name="file-text" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'post_series')
<x-core-ui::icon name="folder" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'products' || $key === 'product_attributes' || $key === 'shop_product_reviews')
<x-core-ui::icon name="package" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'product_categories')
<x-core-ui::icon name="grid" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'orders')
<x-core-ui::icon name="order" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'coupon')
<x-core-ui::icon name="coupon" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'reviews')
<x-core-ui::icon name="star" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'referral')
<x-core-ui::icon name="referral" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'subscription')
<x-core-ui::icon name="subscription" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'whatsapp')
<x-core-ui::icon name="message-circle" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'sms')
<x-core-ui::icon name="sms" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'courses')
<x-core-ui::icon name="courses" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'invoice')
<x-core-ui::icon name="invoice" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'business_card')
<x-core-ui::icon name="profile" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'social_links')
<x-core-ui::icon name="share" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'settings')
<x-core-ui::icon name="settings" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'license')
<x-core-ui::icon name="key" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'modules')
<x-core-ui::icon name="puzzle" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'modules_changelog')
<x-core-ui::icon name="clock" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'module_store')
<x-core-ui::icon name="store" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'theme_design')
<x-core-ui::icon name="sun" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'builder_health')
<x-core-ui::icon name="check-circle" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'currency_reform')
<x-core-ui::icon name="wallet" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'advertisements')
<x-core-ui::icon name="image" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'forms')
<x-core-ui::icon name="file-text" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'forms_submissions')
<x-core-ui::icon name="download" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'captcha')
<x-core-ui::icon name="shield-check" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'users_panel')
<x-core-ui::icon name="users" class="w-5 h-5 ml-3 shrink-0 text-indigo-400" />
@elseif($key === 'support')
<x-core-ui::icon name="message" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'support_history')
<x-core-ui::icon name="clock" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'notifications')
<x-core-ui::icon name="bell" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'advanced_search')
<x-core-ui::icon name="search" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'menu')
<x-core-ui::icon name="menu" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'bank_gateway')
<x-core-ui::icon name="credit-card" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'accounting')
<x-core-ui::icon name="receipt" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'seo_advanced')
<x-core-ui::icon name="search" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'analytics' || $key === 'analytics_local')
<x-core-ui::icon name="analytics" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'announcements')
<x-core-ui::icon name="announcement" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'backup')
<x-core-ui::icon name="backup" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'brand')
<x-core-ui::icon name="store" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'cloud')
<x-core-ui::icon name="cloud" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'contract')
<x-core-ui::icon name="file-text" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'crm')
<x-core-ui::icon name="users" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'discount_advanced')
<x-core-ui::icon name="price-tag" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'gallery')
<x-core-ui::icon name="image" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'i18n')
<x-core-ui::icon name="globe" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'inventory')
<x-core-ui::icon name="package" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'marketing_automation')
<x-core-ui::icon name="zap" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'marketplace_sync')
<x-core-ui::icon name="refresh" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'multi_currency')
<x-core-ui::icon name="wallet" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'omnichannel')
<x-core-ui::icon name="broadcast" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'podcast')
<x-core-ui::icon name="music" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'portfolio')
<x-core-ui::icon name="folder-open" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'product_bundling')
<x-core-ui::icon name="shopping-bag" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'projects')
<x-core-ui::icon name="list" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'qa')
<x-core-ui::icon name="help" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'recruitment')
<x-core-ui::icon name="user-plus" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'services')
<x-core-ui::icon name="file" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'shipping')
<x-core-ui::icon name="package" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'tasks')
<x-core-ui::icon name="check" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'video')
<x-core-ui::icon name="video" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'wallet')
<x-core-ui::icon name="wallet" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'installments')
<x-core-ui::icon name="credit-card" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'access_lists')
<x-core-ui::icon name="lock" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'homepage_videos')
<x-core-ui::icon name="video" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'translations')
<x-core-ui::icon name="globe" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'activity_log')
<x-core-ui::icon name="activity" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'education_center')
<x-core-ui::icon name="courses" class="w-5 h-5 ml-3 shrink-0" />
@elseif($key === 'site_content')
<x-core-ui::icon name="assignment" class="w-5 h-5 ml-3 shrink-0" />
@else
<x-core-ui::icon name="menu" class="w-5 h-5 ml-3 shrink-0" />
@endif
