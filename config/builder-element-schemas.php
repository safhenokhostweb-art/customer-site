<?php

/**
 * Element contract schemas for the Visual Page Builder.
 * Each element type defines: type, required/optional props, optional style shape, allowed children.
 * Used by ElementSchemaRegistry to validate block JSON before render.
 */

return [
    /*
    | Schema per element type.
    | Keys: type (string), props (array of prop => ['required' => bool, 'type' => string]),
    |       styles (optional array of allowed keys), children (optional: 'blocks' | 'none' | list of types).
    */
    'elements' => [
        'heading' => [
            'type' => 'heading',
            'props' => [
                'level' => ['required' => false, 'type' => 'integer'],
                'text' => ['required' => false, 'type' => 'string'],
            ],
            'styles' => ['layout', 'typography', 'background', 'effects'],
            'children' => 'none',
        ],
        'text' => [
            'type' => 'text',
            'props' => [
                'content' => ['required' => false, 'type' => 'string'],
            ],
            'styles' => ['layout', 'typography', 'background', 'effects'],
            'children' => 'none',
        ],
        'image' => [
            'type' => 'image',
            'props' => [
                'url' => ['required' => false, 'type' => 'string'],
                'alt' => ['required' => false, 'type' => 'string'],
                'caption' => ['required' => false, 'type' => 'string'],
            ],
            'styles' => ['layout', 'typography', 'background', 'effects'],
            'children' => 'none',
        ],
        'button' => [
            'type' => 'button',
            'props' => [
                'text' => ['required' => false, 'type' => 'string'],
                'url' => ['required' => false, 'type' => 'string'],
                'style' => ['required' => false, 'type' => 'string'],
            ],
            'styles' => ['layout', 'typography', 'background', 'effects'],
            'children' => 'none',
        ],
        'icon' => [
            'type' => 'icon',
            'props' => [
                'name' => ['required' => false, 'type' => 'string'],
                'size' => ['required' => false, 'type' => 'string'],
            ],
            'styles' => ['layout', 'typography', 'background', 'effects'],
            'children' => 'none',
        ],
        'divider' => [
            'type' => 'divider',
            'props' => [],
            'styles' => ['layout', 'typography', 'background', 'effects'],
            'children' => 'none',
        ],
        'spacer' => [
            'type' => 'spacer',
            'props' => [
                'height' => ['required' => false, 'type' => 'string'],
            ],
            'styles' => ['layout', 'typography', 'background', 'effects'],
            'children' => 'none',
        ],
        'video' => [
            'type' => 'video',
            'props' => [
                'url' => ['required' => false, 'type' => 'string'],
                'caption' => ['required' => false, 'type' => 'string'],
            ],
            'styles' => ['layout', 'typography', 'background', 'effects'],
            'children' => 'none',
        ],
        'hero' => [
            'type' => 'hero',
            'props' => [
                'title' => ['required' => false, 'type' => 'string'],
                'subtitle' => ['required' => false, 'type' => 'string'],
                'button_text' => ['required' => false, 'type' => 'string'],
                'button_url' => ['required' => false, 'type' => 'string'],
                'background' => ['required' => false, 'type' => 'string'],
                'align' => ['required' => false, 'type' => 'string'],
            ],
            'styles' => ['layout', 'typography', 'background', 'effects'],
            'children' => 'none',
        ],
        'section' => [
            'type' => 'section',
            'props' => [
                'padding' => ['required' => false, 'type' => 'string'],
                'background' => ['required' => false, 'type' => 'string'],
                'container' => ['required' => false, 'type' => 'boolean'],
                'background_image' => ['required' => false, 'type' => 'string'],
            ],
            'styles' => ['layout', 'typography', 'background', 'effects'],
            'children' => 'blocks',
        ],
        'row' => [
            'type' => 'row',
            'props' => [
                'gap' => ['required' => false, 'type' => 'string'],
            ],
            'styles' => ['layout', 'typography', 'background', 'effects'],
            'children' => 'blocks',
        ],
        'column' => [
            'type' => 'column',
            'props' => [
                'width' => ['required' => false, 'type' => 'string'],
            ],
            'styles' => ['layout', 'typography', 'background', 'effects'],
            'children' => 'blocks',
        ],
        'container' => [
            'type' => 'container',
            'props' => [],
            'styles' => ['layout', 'typography', 'background', 'effects'],
            'children' => 'blocks',
        ],
        'grid' => [
            'type' => 'grid',
            'props' => [
                'columns' => ['required' => false, 'type' => 'integer'],
                'gap' => ['required' => false, 'type' => 'string'],
            ],
            'styles' => ['layout', 'typography', 'background', 'effects'],
            'children' => 'blocks',
        ],
        'columns' => [
            'type' => 'columns',
            'props' => [
                'ratios' => ['required' => false, 'type' => 'string'],
                'gap' => ['required' => false, 'type' => 'string'],
            ],
            'styles' => ['layout', 'typography', 'background', 'effects'],
            'children' => 'blocks',
        ],
        'flex_container' => [
            'type' => 'flex_container',
            'props' => [
                'label'          => ['required' => false, 'type' => 'string'],
                'direction'      => ['required' => false, 'type' => 'string'],
                'gap'            => ['required' => false, 'type' => 'string'],
                'alignItems'     => ['required' => false, 'type' => 'string'],
                'justifyContent' => ['required' => false, 'type' => 'string'],
                'wrap'           => ['required' => false, 'type' => 'boolean'],
                'minHeight'      => ['required' => false, 'type' => 'integer'],
            ],
            'styles' => ['layout', 'typography', 'background', 'effects'],
            'children' => 'blocks',
        ],
        'tabs' => [
            'type' => 'tabs',
            'props' => [
                'tabs' => ['required' => false, 'type' => 'array'],
            ],
            'styles' => ['layout', 'typography', 'background', 'effects'],
            'children' => 'none',
        ],
        'accordion' => [
            'type' => 'accordion',
            'props' => [
                'items' => ['required' => false, 'type' => 'array'],
            ],
            'styles' => ['layout', 'typography', 'background', 'effects'],
            'children' => 'none',
        ],
        'slider' => [
            'type' => 'slider',
            'props' => [
                'autoplay' => ['required' => false, 'type' => 'boolean'],
                'interval' => ['required' => false, 'type' => 'integer'],
                'slides' => ['required' => false, 'type' => 'array'],
            ],
            'styles' => ['layout', 'typography', 'background', 'effects'],
            'children' => 'none',
        ],
        'cta' => [
            'type' => 'cta',
            'props' => [
                'title' => ['required' => false, 'type' => 'string'],
                'subtitle' => ['required' => false, 'type' => 'string'],
                'button_text' => ['required' => false, 'type' => 'string'],
                'button_url' => ['required' => false, 'type' => 'string'],
                'background' => ['required' => false, 'type' => 'string'],
            ],
            'styles' => ['layout', 'typography', 'background', 'effects'],
            'children' => 'none',
        ],
        'testimonials' => [
            'type' => 'testimonials',
            'props' => [
                'title' => ['required' => false, 'type' => 'string'],
                'limit' => ['required' => false, 'type' => 'integer'],
            ],
            'styles' => ['layout', 'typography', 'background', 'effects'],
            'children' => 'none',
        ],
        'blog_posts' => [
            'type' => 'blog_posts',
            'props' => [
                'title' => ['required' => false, 'type' => 'string'],
                'limit' => ['required' => false, 'type' => 'integer'],
            ],
            'styles' => ['layout', 'typography', 'background', 'effects'],
            'children' => 'none',
        ],
        'product_grid' => [
            'type' => 'product_grid',
            'props' => [
                'columns' => ['required' => false, 'type' => 'integer'],
                'limit' => ['required' => false, 'type' => 'integer'],
            ],
            'styles' => ['layout', 'typography', 'background', 'effects'],
            'children' => 'none',
        ],
        'faq' => [
            'type' => 'faq',
            'props' => [
                'title' => ['required' => false, 'type' => 'string'],
                'items' => ['required' => false, 'type' => 'array'],
            ],
            'styles' => ['layout', 'typography', 'background', 'effects'],
            'children' => 'none',
        ],
        'gallery' => [
            'type' => 'gallery',
            'props' => [
                'title' => ['required' => false, 'type' => 'string'],
                'items' => ['required' => false, 'type' => 'array'],
            ],
            'styles' => ['layout', 'typography', 'background', 'effects'],
            'children' => 'none',
        ],
        'countdown' => [
            'type' => 'countdown',
            'props' => [
                'end_date' => ['required' => false, 'type' => 'string'],
                'label' => ['required' => false, 'type' => 'string'],
            ],
            'styles' => ['layout', 'typography', 'background', 'effects'],
            'children' => 'none',
        ],
        'social_icons' => [
            'type' => 'social_icons',
            'props' => [
                'links' => ['required' => false, 'type' => 'array'],
            ],
            'styles' => ['layout', 'typography', 'background', 'effects'],
            'children' => 'none',
        ],
        'promo_banner' => [
            'type' => 'promo_banner',
            'props' => [
                'text' => ['required' => false, 'type' => 'string'],
                'link' => ['required' => false, 'type' => 'string'],
                'style' => ['required' => false, 'type' => 'string'],
            ],
            'styles' => ['layout', 'typography', 'background', 'effects'],
            'children' => 'none',
        ],
        'custom_html' => [
            'type' => 'custom_html',
            'props' => [
                'content' => ['required' => false, 'type' => 'string'],
            ],
            'styles' => ['layout', 'typography', 'background', 'effects'],
            'children' => 'none',
        ],
        'nav_menu' => [
            'type' => 'nav_menu',
            'props' => [
                'location' => ['required' => false, 'type' => 'string'],
            ],
            'styles' => ['layout', 'typography', 'background', 'effects'],
            'children' => 'none',
        ],
        'search_bar' => [
            'type' => 'search_bar',
            'props' => [
                'placeholder' => ['required' => false, 'type' => 'string'],
            ],
            'styles' => ['layout', 'typography', 'background', 'effects'],
            'children' => 'none',
        ],
        'cart_icon' => [
            'type' => 'cart_icon',
            'props' => [],
            'styles' => ['layout', 'typography', 'background', 'effects'],
            'children' => 'none',
        ],

        // ── HVAC / Industry-specific blocks ──────────────────────────────
        'nav_header' => [
            'type' => 'nav_header',
            'props' => [
                'logo_text'  => ['required' => false, 'type' => 'string'],
                'phone'      => ['required' => false, 'type' => 'string'],
                'cta_text'   => ['required' => false, 'type' => 'string'],
                'cta_url'    => ['required' => false, 'type' => 'string'],
                'links'      => ['required' => false, 'type' => 'array'],
                'sticky'     => ['required' => false, 'type' => 'boolean'],
            ],
            'styles'   => ['layout', 'typography', 'background', 'effects'],
            'children' => 'none',
        ],
        'hvac_hero' => [
            'type' => 'hvac_hero',
            'props' => [
                'badge'              => ['required' => false, 'type' => 'string'],
                'title'              => ['required' => false, 'type' => 'string'],
                'subtitle'           => ['required' => false, 'type' => 'string'],
                'btn_primary_text'   => ['required' => false, 'type' => 'string'],
                'btn_primary_url'    => ['required' => false, 'type' => 'string'],
                'btn_secondary_text' => ['required' => false, 'type' => 'string'],
                'btn_secondary_url'  => ['required' => false, 'type' => 'string'],
                'phone'              => ['required' => false, 'type' => 'string'],
                'rating'             => ['required' => false, 'type' => 'string'],
                'rating_label'       => ['required' => false, 'type' => 'string'],
            ],
            'styles'   => ['layout', 'typography', 'background', 'effects'],
            'children' => 'none',
        ],
        'client_logos' => [
            'type' => 'client_logos',
            'props' => [
                'title' => ['required' => false, 'type' => 'string'],
                'logos' => ['required' => false, 'type' => 'array'],
            ],
            'styles'   => ['layout', 'typography', 'background', 'effects'],
            'children' => 'none',
        ],
        'about_section' => [
            'type' => 'about_section',
            'props' => [
                'badge'       => ['required' => false, 'type' => 'string'],
                'title'       => ['required' => false, 'type' => 'string'],
                'description' => ['required' => false, 'type' => 'string'],
                'features'    => ['required' => false, 'type' => 'array'],
                'btn_text'    => ['required' => false, 'type' => 'string'],
                'btn_url'     => ['required' => false, 'type' => 'string'],
                'stats'       => ['required' => false, 'type' => 'array'],
            ],
            'styles'   => ['layout', 'typography', 'background', 'effects'],
            'children' => 'none',
        ],
        'service_cards' => [
            'type' => 'service_cards',
            'props' => [
                'title'    => ['required' => false, 'type' => 'string'],
                'subtitle' => ['required' => false, 'type' => 'string'],
                'columns'  => ['required' => false, 'type' => 'integer'],
                'cards'    => ['required' => false, 'type' => 'array'],
            ],
            'styles'   => ['layout', 'typography', 'background', 'effects'],
            'children' => 'none',
        ],
        'stats_row' => [
            'type' => 'stats_row',
            'props' => [
                'title'      => ['required' => false, 'type' => 'string'],
                'subtitle'   => ['required' => false, 'type' => 'string'],
                'background' => ['required' => false, 'type' => 'string'],
                'stats'      => ['required' => false, 'type' => 'array'],
            ],
            'styles'   => ['layout', 'typography', 'background', 'effects'],
            'children' => 'none',
        ],
        'trust_badges' => [
            'type' => 'trust_badges',
            'props' => [
                'title'    => ['required' => false, 'type' => 'string'],
                'subtitle' => ['required' => false, 'type' => 'string'],
                'columns'  => ['required' => false, 'type' => 'integer'],
                'items'    => ['required' => false, 'type' => 'array'],
            ],
            'styles'   => ['layout', 'typography', 'background', 'effects'],
            'children' => 'none',
        ],
        'project_cards' => [
            'type' => 'project_cards',
            'props' => [
                'title'    => ['required' => false, 'type' => 'string'],
                'subtitle' => ['required' => false, 'type' => 'string'],
                'columns'  => ['required' => false, 'type' => 'integer'],
                'cards'    => ['required' => false, 'type' => 'array'],
            ],
            'styles'   => ['layout', 'typography', 'background', 'effects'],
            'children' => 'none',
        ],
        'contact_info' => [
            'type' => 'contact_info',
            'props' => [
                'title'      => ['required' => false, 'type' => 'string'],
                'subtitle'   => ['required' => false, 'type' => 'string'],
                'background' => ['required' => false, 'type' => 'string'],
                'items'      => ['required' => false, 'type' => 'array'],
            ],
            'styles'   => ['layout', 'typography', 'background', 'effects'],
            'children' => 'none',
        ],
        'site_footer' => [
            'type' => 'site_footer',
            'props' => [
                'logo_text'    => ['required' => false, 'type' => 'string'],
                'tagline'      => ['required' => false, 'type' => 'string'],
                'copyright'    => ['required' => false, 'type' => 'string'],
                'background'   => ['required' => false, 'type' => 'string'],
                'links'        => ['required' => false, 'type' => 'array'],
                'social_links' => ['required' => false, 'type' => 'array'],
                'columns'      => ['required' => false, 'type' => 'array'],
            ],
            'styles'   => ['layout', 'typography', 'background', 'effects'],
            'children' => 'none',
        ],
    ],

    /*
    | Max lengths for stress-test / validation (optional).
    */
    'limits' => [
        'string_prop_max_length' => 65535,
        'url_max_length' => 2048,
        'children_max_depth' => 20,
    ],
];
