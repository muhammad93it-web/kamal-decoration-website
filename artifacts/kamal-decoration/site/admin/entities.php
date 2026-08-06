<?php
/**
 * Config for the generic CRUD engine (crud.php).
 * Field types: text, textarea, rich, number, select, select_query, image, date, datetime, check, slug, code
 */

function kd_entities(): array
{
    return [

        'categories' => [
            'table' => 'categories',
            'title' => t('a_categories', 'بەشەکان'),
            'label_col' => 'name',
            'order' => 'sort_order, id',
            'searchable' => ['name', 'slug'],
            'list' => [
                ['key' => 'image', 'label' => '', 'type' => 'img'],
                ['key' => 'name', 'label' => t('a_f_name', 'ناو'), 'type' => 'text'],
                ['key' => 'type', 'label' => t('a_f_type', 'جۆر'), 'type' => 'map', 'map' => [
                    'product' => t('a_type_product', 'بەرهەم'), 'project' => t('a_type_project', 'پرۆژە'), 'post' => t('a_type_post', 'بابەت')]],
                ['key' => 'sort_order', 'label' => t('a_f_sort', 'ڕیز'), 'type' => 'num'],
                ['key' => 'is_active', 'label' => t('a_f_active', 'چالاک'), 'type' => 'bool'],
            ],
            'fields' => [
                'name' => ['type' => 'text', 'label' => t('a_f_name', 'ناو'), 'required' => true, 'max' => 150],
                'slug' => ['type' => 'slug', 'label' => t('a_f_slug', 'سڵەگ (لینک)'), 'from' => 'name', 'max' => 180],
                'type' => ['type' => 'select', 'label' => t('a_f_type', 'جۆر'), 'options' => [
                    'product' => t('a_type_product', 'بەرهەم'), 'project' => t('a_type_project', 'پرۆژە'), 'post' => t('a_type_post', 'بابەت')], 'default' => 'product'],
                'description' => ['type' => 'textarea', 'label' => t('a_f_desc', 'وەسف'), 'rows' => 3],
                'image' => ['type' => 'image', 'label' => t('a_f_image', 'وێنە'), 'subdir' => 'media'],
                'sort_order' => ['type' => 'number', 'label' => t('a_f_sort', 'ڕیز'), 'default' => 0],
                'is_featured' => ['type' => 'check', 'label' => t('a_f_featured', 'دیارکراو (لە پەڕەی سەرەکی)')],
                'is_active' => ['type' => 'check', 'label' => t('a_f_active', 'چالاک'), 'default' => 1],
            ],
        ],

        'palettes' => [
            'table' => 'palettes',
            'title' => t('a_palettes', 'پاڵێتەکان'),
            'label_col' => 'name',
            'order' => 'sort_order, id',
            'searchable' => ['name', 'slug', 'code'],
            'list' => [
                ['key' => 'cover_image', 'label' => '', 'type' => 'img'],
                ['key' => 'name', 'label' => t('a_f_name', 'ناو'), 'type' => 'text'],
                ['key' => 'code', 'label' => t('a_f_code', 'کۆد'), 'type' => 'code'],
                ['key' => 'family', 'label' => t('shade_family', 'خێزانی ڕەنگ'), 'type' => 'text'],
                ['key' => 'is_active', 'label' => t('a_f_active', 'چالاک'), 'type' => 'bool'],
            ],
            'fields' => [
                'name' => ['type' => 'text', 'label' => t('a_f_name', 'ناو'), 'required' => true, 'max' => 150],
                'slug' => ['type' => 'slug', 'label' => t('a_f_slug', 'سڵەگ (لینک)'), 'from' => 'name', 'max' => 180],
                'code' => ['type' => 'code', 'label' => t('a_f_code', 'کۆد'), 'prefix' => 'KD-P'],
                'family' => ['type' => 'text', 'label' => t('shade_family', 'خێزانی ڕەنگ'), 'max' => 80, 'hint' => t('a_family_hint', 'نموونە: قاوەیی، بێج، خۆڵەمێشی')],
                'description' => ['type' => 'textarea', 'label' => t('a_f_desc', 'وەسف'), 'rows' => 3],
                'cover_image' => ['type' => 'image', 'label' => t('a_f_cover', 'وێنەی سەرەکی'), 'subdir' => 'palettes'],
                'sort_order' => ['type' => 'number', 'label' => t('a_f_sort', 'ڕیز'), 'default' => 0],
                'is_featured' => ['type' => 'check', 'label' => t('a_f_featured', 'دیارکراو (لە پەڕەی سەرەکی)')],
                'is_active' => ['type' => 'check', 'label' => t('a_f_active', 'چالاک'), 'default' => 1],
            ],
            'row_actions' => fn(array $r) => [
                ['label' => '🌈 ' . t('a_shades', 'ڕەنگەکان'), 'url' => admin_url('shades.php?palette=' . (int)$r['id'])],
            ],
        ],

        'products' => [
            'table' => 'products',
            'title' => t('a_products', 'بەرهەمەکان'),
            'label_col' => 'name',
            'order' => 'sort_order, id DESC',
            'searchable' => ['name', 'slug', 'code'],
            'list_join' => 'LEFT JOIN categories c ON c.id = t.category_id',
            'list_select' => 't.*, c.name AS _cat',
            'list' => [
                ['key' => 'main_image', 'label' => '', 'type' => 'img'],
                ['key' => 'name', 'label' => t('a_f_name', 'ناو'), 'type' => 'text'],
                ['key' => 'code', 'label' => t('a_f_code', 'کۆد'), 'type' => 'code'],
                ['key' => '_cat', 'label' => t('a_f_category', 'بەش'), 'type' => 'text'],
                ['key' => 'price', 'label' => t('a_f_price', 'نرخ'), 'type' => 'money'],
                ['key' => 'is_active', 'label' => t('a_f_active', 'چالاک'), 'type' => 'bool'],
            ],
            'fields' => [
                'name' => ['type' => 'text', 'label' => t('a_f_name', 'ناو'), 'required' => true, 'max' => 200],
                'slug' => ['type' => 'slug', 'label' => t('a_f_slug', 'سڵەگ (لینک)'), 'from' => 'name', 'max' => 220],
                'code' => ['type' => 'code', 'label' => t('a_f_code', 'کۆد'), 'prefix' => 'KD-PR'],
                'category_id' => ['type' => 'select_query', 'label' => t('a_f_category', 'بەش'),
                    'options_sql' => "SELECT id, name FROM categories WHERE type = 'product' ORDER BY sort_order", 'nullable' => true],
                'short_desc' => ['type' => 'textarea', 'label' => t('a_f_short_desc', 'کورتە وەسف'), 'rows' => 2, 'max' => 500],
                'description' => ['type' => 'textarea', 'label' => t('a_f_desc', 'وەسفی تەواو'), 'rows' => 6],
                'specifications' => ['type' => 'textarea', 'label' => t('a_f_specs', 'تایبەتمەندییەکان'), 'rows' => 4,
                    'hint' => t('a_specs_hint', 'هەر دێڕێک: ناو: بەها — نموونە: پانی: ١٦٠ سم')],
                'price' => ['type' => 'number', 'label' => t('a_f_price', 'نرخ (دینار)'), 'nullable' => true, 'min' => 0, 'step' => 250,
                    'hint' => t('a_price_hint', 'بەتاڵی بهێڵەوە بۆ «پرسیار بکە»')],
                'unit' => ['type' => 'text', 'label' => t('a_f_unit', 'یەکە'), 'max' => 60, 'hint' => t('a_unit_hint', 'نموونە: بۆ مەترێک، بۆ دانەیەک')],
                'main_image' => ['type' => 'image', 'label' => t('a_f_main_image', 'وێنەی سەرەکی'), 'subdir' => 'products'],
                'is_available' => ['type' => 'check', 'label' => t('product_available', 'بەردەستە'), 'default' => 1],
                'is_featured' => ['type' => 'check', 'label' => t('a_f_featured', 'دیارکراو (لە پەڕەی سەرەکی)')],
                'is_active' => ['type' => 'check', 'label' => t('a_f_active', 'چالاک'), 'default' => 1],
                'sort_order' => ['type' => 'number', 'label' => t('a_f_sort', 'ڕیز'), 'default' => 0],
            ],
            'gallery' => ['table' => 'product_images', 'fk' => 'product_id', 'image_col' => 'image', 'subdir' => 'products'],
            'm2m' => ['palettes' => [
                'label' => t('a_palettes', 'پاڵێتەکان'),
                'table' => 'product_palettes', 'own_col' => 'product_id', 'other_col' => 'palette_id',
                'options_sql' => 'SELECT id, name FROM palettes ORDER BY sort_order',
            ]],
            'after_save' => 'ensure_product_codes',
        ],

        'projects' => [
            'table' => 'projects',
            'title' => t('a_projects', 'پرۆژەکان'),
            'label_col' => 'title',
            'order' => 'sort_order, id DESC',
            'searchable' => ['title', 'slug', 'location', 'client_name'],
            'list' => [
                ['key' => 'main_image', 'label' => '', 'type' => 'img'],
                ['key' => 'title', 'label' => t('a_f_title', 'ناونیشان'), 'type' => 'text'],
                ['key' => 'location', 'label' => t('a_f_location', 'شوێن'), 'type' => 'text'],
                ['key' => 'completed_at', 'label' => t('a_f_completed', 'کۆتایی هاتن'), 'type' => 'date'],
                ['key' => 'is_active', 'label' => t('a_f_active', 'چالاک'), 'type' => 'bool'],
            ],
            'fields' => [
                'title' => ['type' => 'text', 'label' => t('a_f_title', 'ناونیشان'), 'required' => true, 'max' => 200],
                'slug' => ['type' => 'slug', 'label' => t('a_f_slug', 'سڵەگ (لینک)'), 'from' => 'title', 'max' => 220],
                'category_id' => ['type' => 'select_query', 'label' => t('a_f_category', 'بەش'),
                    'options_sql' => "SELECT id, name FROM categories WHERE type = 'project' ORDER BY sort_order", 'nullable' => true],
                'client_name' => ['type' => 'text', 'label' => t('a_f_client', 'ناوی کڕیار'), 'max' => 150],
                'location' => ['type' => 'text', 'label' => t('a_f_location', 'شوێن'), 'max' => 150],
                'completed_at' => ['type' => 'date', 'label' => t('a_f_completed', 'بەرواری کۆتایی هاتن'), 'nullable' => true],
                'description' => ['type' => 'textarea', 'label' => t('a_f_desc', 'وەسف'), 'rows' => 6],
                'main_image' => ['type' => 'image', 'label' => t('a_f_main_image', 'وێنەی سەرەکی'), 'subdir' => 'projects'],
                'before_image' => ['type' => 'image', 'label' => t('project_before', 'پێش'), 'subdir' => 'projects', 'hint' => t('a_ba_hint', 'بۆ سلایدەری پێش/پاش هەردوو وێنە دابنێ')],
                'after_image' => ['type' => 'image', 'label' => t('project_after', 'پاش'), 'subdir' => 'projects'],
                'is_featured' => ['type' => 'check', 'label' => t('a_f_featured', 'دیارکراو (لە پەڕەی سەرەکی)')],
                'is_active' => ['type' => 'check', 'label' => t('a_f_active', 'چالاک'), 'default' => 1],
                'sort_order' => ['type' => 'number', 'label' => t('a_f_sort', 'ڕیز'), 'default' => 0],
            ],
            'gallery' => ['table' => 'project_images', 'fk' => 'project_id', 'image_col' => 'image', 'subdir' => 'projects', 'caption_col' => 'caption'],
        ],

        'posts' => [
            'table' => 'posts',
            'title' => t('a_posts', 'بابەتەکان'),
            'label_col' => 'title',
            'order' => 'id DESC',
            'searchable' => ['title', 'slug'],
            'list' => [
                ['key' => 'cover_image', 'label' => '', 'type' => 'img'],
                ['key' => 'title', 'label' => t('a_f_title', 'ناونیشان'), 'type' => 'text'],
                ['key' => 'is_published', 'label' => t('a_f_published', 'بڵاوکراوە'), 'type' => 'bool'],
                ['key' => 'published_at', 'label' => t('a_date', 'بەروار'), 'type' => 'date'],
                ['key' => 'views', 'label' => t('a_f_views', 'بینین'), 'type' => 'num'],
            ],
            'fields' => [
                'title' => ['type' => 'text', 'label' => t('a_f_title', 'ناونیشان'), 'required' => true, 'max' => 200],
                'slug' => ['type' => 'slug', 'label' => t('a_f_slug', 'سڵەگ (لینک)'), 'from' => 'title', 'max' => 220],
                'category_id' => ['type' => 'select_query', 'label' => t('a_f_category', 'بەش'),
                    'options_sql' => "SELECT id, name FROM categories WHERE type = 'post' ORDER BY sort_order", 'nullable' => true],
                'excerpt' => ['type' => 'textarea', 'label' => t('a_f_excerpt', 'کورتە'), 'rows' => 2, 'max' => 500],
                'body' => ['type' => 'rich', 'label' => t('a_f_body', 'ناوەڕۆک')],
                'cover_image' => ['type' => 'image', 'label' => t('a_f_cover', 'وێنەی سەرەکی'), 'subdir' => 'posts'],
                'is_published' => ['type' => 'check', 'label' => t('a_f_published', 'بڵاوکراوە'), 'default' => 1],
                'published_at' => ['type' => 'datetime', 'label' => t('a_f_published_at', 'کاتی بڵاوکردنەوە'), 'nullable' => true,
                    'hint' => t('a_pubat_hint', 'بەتاڵی بهێڵەوە = ئێستا')],
            ],
            'before_save' => function (array &$data, ?int $id): void {
                if (!empty($data['is_published']) && empty($data['published_at'])) {
                    $data['published_at'] = date('Y-m-d H:i:s');
                }
                if ($id === null && current_user()) {
                    $data['author_id'] = current_user()['id'];
                }
            },
        ],

        'sliders' => [
            'table' => 'sliders',
            'title' => t('a_sliders', 'سلایدشۆ'),
            'label_col' => 'title',
            'order' => 'sort_order, id',
            'searchable' => ['title'],
            'list' => [
                ['key' => 'image', 'label' => '', 'type' => 'img'],
                ['key' => 'title', 'label' => t('a_f_title', 'ناونیشان'), 'type' => 'text'],
                ['key' => 'sort_order', 'label' => t('a_f_sort', 'ڕیز'), 'type' => 'num'],
                ['key' => 'is_active', 'label' => t('a_f_active', 'چالاک'), 'type' => 'bool'],
            ],
            'fields' => [
                'title' => ['type' => 'text', 'label' => t('a_f_title', 'ناونیشان'), 'max' => 200],
                'subtitle' => ['type' => 'textarea', 'label' => t('a_f_subtitle', 'ژێرنووس'), 'rows' => 2, 'max' => 300],
                'image' => ['type' => 'image', 'label' => t('a_f_image', 'وێنە'), 'subdir' => 'sliders', 'required' => true,
                    'hint' => t('a_slider_img_hint', 'باشترین قەبارە: 1920×900')],
                'button_text' => ['type' => 'text', 'label' => t('a_f_btn_text', 'نووسینی دوگمە'), 'max' => 100],
                'button_url' => ['type' => 'text', 'label' => t('a_f_btn_url', 'لینکی دوگمە'), 'max' => 300, 'dir' => 'ltr'],
                'starts_at' => ['type' => 'datetime', 'label' => t('a_f_starts', 'دەستپێک (ئارەزوومەندانە)'), 'nullable' => true],
                'ends_at' => ['type' => 'datetime', 'label' => t('a_f_ends', 'کۆتایی (ئارەزوومەندانە)'), 'nullable' => true],
                'sort_order' => ['type' => 'number', 'label' => t('a_f_sort', 'ڕیز'), 'default' => 0],
                'is_active' => ['type' => 'check', 'label' => t('a_f_active', 'چالاک'), 'default' => 1],
            ],
        ],

        'testimonials' => [
            'table' => 'testimonials',
            'title' => t('a_testimonials', 'ڕای کڕیاران'),
            'label_col' => 'name',
            'order' => 'sort_order, id',
            'searchable' => ['name', 'location'],
            'list' => [
                ['key' => 'name', 'label' => t('a_f_name', 'ناو'), 'type' => 'text'],
                ['key' => 'location', 'label' => t('a_f_location', 'شوێن'), 'type' => 'text'],
                ['key' => 'rating', 'label' => t('a_f_rating', 'هەڵسەنگاندن'), 'type' => 'stars'],
                ['key' => 'is_active', 'label' => t('a_f_active', 'چالاک'), 'type' => 'bool'],
            ],
            'fields' => [
                'name' => ['type' => 'text', 'label' => t('a_f_name', 'ناو'), 'required' => true, 'max' => 120],
                'location' => ['type' => 'text', 'label' => t('a_f_location', 'شوێن'), 'max' => 120],
                'quote' => ['type' => 'textarea', 'label' => t('a_f_quote', 'بۆچوون'), 'rows' => 4, 'required' => true],
                'rating' => ['type' => 'select', 'label' => t('a_f_rating', 'هەڵسەنگاندن'), 'default' => '5',
                    'options' => ['5' => '★★★★★', '4' => '★★★★', '3' => '★★★', '2' => '★★', '1' => '★']],
                'sort_order' => ['type' => 'number', 'label' => t('a_f_sort', 'ڕیز'), 'default' => 0],
                'is_active' => ['type' => 'check', 'label' => t('a_f_active', 'چالاک'), 'default' => 1],
            ],
        ],
    ];
}
