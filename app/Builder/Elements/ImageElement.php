<?php

namespace App\Builder\Elements;

class ImageElement extends BaseElement
{
    public static function getType(): string { return 'image'; }
    public static function getName(): string { return 'تصویر'; }
    public static function getIcon(): string { return '🖼️'; }
    public static function getCategory(): string { return 'content'; }
    public static function getDefaultProps(): array { return ['url' => '', 'alt' => '', 'caption' => '']; }
    public static function getSchema(): array
    {
        return [
            'url' => ['type' => 'string', 'required' => false, 'default' => '', 'max' => 2048],
            'alt' => ['type' => 'string', 'required' => false, 'default' => '', 'max' => 500],
            'caption' => ['type' => 'string', 'required' => false, 'default' => '', 'max' => 500],
        ];
    }
    public static function render(array $props, array $context = []): string
    {
        $url = (string) ($props['url'] ?? '');
        $alt = self::escapeAttr((string) ($props['alt'] ?? ''));
        $caption = self::escape((string) ($props['caption'] ?? ''));
        $id = $context['id'] ?? '';
        if ($url === '' || !self::isValidUrl($url)) {
            return "<figure id=\"tb-{$id}\" class=\"tb-image tb-image-placeholder\"><span>تصویر</span></figure>";
        }
        $url = self::escapeAttr($url);
        $img = "<img src=\"{$url}\" alt=\"{$alt}\" loading=\"lazy\" class=\"tb-img\">";
        $cap = $caption ? "<figcaption class=\"tb-image-caption\">{$caption}</figcaption>" : '';
        return "<figure id=\"tb-{$id}\" class=\"tb-image\">{$img}{$cap}</figure>";
    }
}
