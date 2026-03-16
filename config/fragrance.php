<?php

/**
 * AI Fragrance Recommendation Configuration
 *
 * This file contains all mappings needed to match quiz answers to database products
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Vibe to Fragrance Notes Mapping
    |--------------------------------------------------------------------------
    | Maps quiz "vibe" selection to actual fragrance notes in the database
    | Each vibe maps to multiple notes that can appear in top/middle/base
    */
    'vibe_notes' => [
        'floral' => [
            'top' => ['Rose', 'Jasmine', 'Lily', 'Tulip', 'Peony', 'Freesia', 'Cherry Blossom', 'Sakura', 'White Flower', 'Orange Blossom', 'Neroli', 'バラ', 'ジャスミン', 'ユリ', 'チューリップ', 'ピオニー', 'フリージア', 'サクラ', '兰', '茉莉'],
            'middle' => ['Rose', 'Jasmine', 'Lily', 'Tulip', 'Peony', 'Freesia', 'White Flower', 'Orchid', 'Gardenia', 'Tuberose', 'Frangipani', 'バラ', 'ジャスミン', 'ユリ', 'ガーデニア', 'チュベローズ', '花'],
            'base' => ['Rose', 'Jasmine', 'Peony', 'Iris', 'Violet', 'バラ', 'ジャスミン', 'ピオニー', 'アイリス', 'スミレ'],
        ],
        'citrus' => [
            'top' => ['Bergamot', 'Lemon', 'Orange', 'Yuzu', 'Grapefruit', 'Mandarin', 'Tangerine', 'Lime', 'Juniper', 'ベルガamot', 'レモン', 'オレンジ', 'ユズ', 'グレープフルーツ', 'シトラス', 'ライム', 'マンダリン'],
            'middle' => ['Citrus', 'Green', 'Water', 'Mint', 'Herbs', 'シトラス', 'グリーン', 'ミント', 'ハーブ', 'ウォータ'],
            'base' => ['Vetiver', 'Cedar', 'Musk', 'ベチバー', 'シダー', 'ムスク'],
        ],
        'vanilla' => [
            'top' => ['Vanilla', 'Benzoin', 'Tonka Bean', 'Caramel', 'Chocolate', 'Coffee', 'Honey', 'バニラ', 'キャラメル', 'チョコレ', 'コーヒー', 'ハニー', 'トンカ'],
            'middle' => ['Vanilla', 'Sweet', 'Almond', 'Coconut', 'バニラ', 'スイート', 'アーモンド', 'ココナツ'],
            'base' => ['Vanilla', 'Benzoin', 'Tonka Bean', 'Amber', 'Vanilla', 'バニラ', 'アンバー', 'トンカ'],
        ],
        'woody' => [
            'top' => ['Sandalwood', 'Cedar', 'Vetiver', 'Patchouli', 'Oakmoss', 'Rosewood', 'Agarwood', 'シダー', 'ウッディ', 'ベチバー', 'パチュリ', 'オーク', 'ローズウッド'],
            'middle' => ['Woody', 'Spice', 'Leather', 'Smoky', 'Incense', 'ウッディ', 'スパイス', 'レザー', 'スモー', 'インセンス'],
            'base' => ['Sandalwood', 'Cedar', 'Vetiver', 'Patchouli', 'Oakmoss', 'Agarwood', 'シダー', 'ウッディ', 'ベチバー', 'パチュリ', 'オーク'],
        ],
        'ocean' => [
            'top' => ['Marine', 'Sea', 'Water', 'Aqua', 'Salt', 'Sea Salt', 'Ocean', 'Mermaid', 'マリン', 'シー', 'オーシャン', 'ウォータ', '塩', '人魚'],
            'middle' => ['Water', 'Aquatic', 'Sea', 'Marine', 'Ocean', 'Watermelon', '水', 'アクアリ', 'マリン', 'スイカ'],
            'base' => ['Musk', 'Vetiver', 'Sandalwood', 'ムスク', 'ベチバー', 'シダー'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Vibe to Category Mapping
    |--------------------------------------------------------------------------
    | Maps quiz "vibe" to product category names
    */
    'vibe_categories' => [
        'floral' => ['フローラル EDP', 'フローラル EDT', '花香'],
        'citrus' => ['シトラス EDT', 'シトラス EDP', 'シトラス', 'フレッシュ EDT', 'ウォータリー'],
        'vanilla' => ['オリエンタル EDP', 'オリエンタル Parfum', 'スイート'],
        'woody' => ['ウッディ EDP', 'ウッディ EDT', 'ウッド'],
        'ocean' => ['フレッシュ EDT', 'ウォータリー', 'マリン'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Personality to Style Mapping
    |--------------------------------------------------------------------------
    | Maps personality type to recommended styles/brands
    */
    'personality_styles' => [
        'romantic' => [
            'styles' => ['feminine', 'elegant', 'soft'],
            'genders' => ['women'],
            'brands' => ['シャネル', 'ディオール', 'ジョーマローン', 'ジルスチュアート', 'アナスイ'],
            'tiers' => ['ラグジュアリー', 'プレミアム'],
            'keywords' => ['flower', 'rose', 'jasmine', 'sweet', 'love', 'romance'],
        ],
        'energetic' => [
            'styles' => ['casual', 'fresh', 'sporty'],
            'genders' => ['women', 'unisex'],
            'brands' => ['ケンゾー', '資生堂', 'SHIRO', 'アナスイ'],
            'tiers' => ['プチプラ', 'プレミアム'],
            'keywords' => ['citrus', 'fresh', 'water', 'green', 'energy'],
        ],
        'cool' => [
            'styles' => ['chic', 'modern', 'sophisticated'],
            'genders' => ['men', 'unisex'],
            'brands' => ['トムフォード', 'グッチ', 'ヴェルサーチ', 'プラダ', 'アルマーニ'],
            'tiers' => ['ラグジュアリー', 'ウルトララグジュアリー'],
            'keywords' => ['woody', 'spice', 'leather', 'fresh', 'clean'],
        ],
        'natural' => [
            'styles' => ['natural', 'casual', 'organic'],
            'genders' => ['unisex', 'women'],
            'brands' => ['SHIRO', '資生堂', 'ケンゾー', 'ジョーマローン'],
            'tiers' => ['プチプラ', 'プレミアム'],
            'keywords' => ['green', 'natural', 'wood', 'earth', 'minimal'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Style to Gender Mapping
    |--------------------------------------------------------------------------
    */
    'style_genders' => [
        'feminine' => ['women'],
        'casual' => ['women', 'unisex', 'men'],
        'chic' => ['women', 'men'],
        'natural' => ['unisex', 'women'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Occasion to Product Type Mapping
    |--------------------------------------------------------------------------
    */
    'occasion_types' => [
        'daily' => [
            'concentrations' => ['EDT', 'EDC', 'Mist'],
            'price_max' => 8000,
            'keywords' => ['light', 'fresh', 'easy'],
        ],
        'work' => [
            'concentrations' => ['EDT', 'EDP'],
            'price_max' => 15000,
            'keywords' => ['subtle', 'professional', 'clean'],
        ],
        'date' => [
            'concentrations' => ['EDP', 'Parfum'],
            'price_max' => 30000,
            'keywords' => ['romantic', 'sweet', 'intense', 'long-lasting'],
        ],
        'special' => [
            'concentrations' => ['EDP', 'Parfum'],
            'price_max' => 60000,
            'keywords' => ['luxury', 'unique', 'signature'],
        ],
        'casual' => [
            'concentrations' => ['EDT', 'Mist', 'EDC'],
            'price_max' => 10000,
            'keywords' => ['relaxed', 'fresh', 'easy'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Experience Level Recommendations
    |--------------------------------------------------------------------------
    */
    'experience_levels' => [
        'beginner' => [
            'concentrations' => ['Mist', 'EDC', 'EDT'],
            'price_max' => 5000,
            'description' => '軽い香りで扱いやすいアイテムを推奨',
        ],
        'some' => [
            'concentrations' => ['EDT', 'EDP'],
            'price_max' => 15000,
            'description' => 'バランス良くとっつきやすいアイテムを推奨',
        ],
        'experienced' => [
            'concentrations' => ['EDP', 'Parfum'],
            'price_max' => 60000,
            'description' => '本格的な香りを推奨',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Japanese Note Translations (for matching)
    |--------------------------------------------------------------------------
    */
    'note_translations' => [
        // Top notes
        'ベルガモット' => 'Bergamot',
        'レモン' => 'Lemon',
        'オレンジ' => 'Orange',
        'シトラス' => 'Citrus',
        'ミント' => 'Mint',
        'ピンクペッパー' => 'Pink Pepper',
        'りんご' => 'Apple',
        'カシス' => 'Cassis',
        'マンダリン' => 'Mandarin',
        'グレープフルーツ' => 'Grapefruit',
        'ユズ' => 'Yuzu',
        'ライム' => 'Lime',
        'レッドフルーツ' => 'Red Fruit',
        'ストロベリー' => 'Strawberry',
        'ペア' => 'Pear',
        'メロン' => 'Melon',
        'ピーチ' => 'Peach',
        'ラズベリー' => 'Raspberry',
        'ライチ' => 'Lychee',
        'アルデヒド' => 'Aldehyde',
        'イランイラン' => 'Ylang Ylang',

        // Middle notes
        'ジャスミン' => 'Jasmine',
        'ローズ' => 'Rose',
        'ホワイトリリー' => 'White Lily',
        'ピオニー' => 'Peony',
        'フリージア' => 'Freesia',
        'スミレ' => 'Violet',
        'バイオレット' => 'Violet',
        'ラベンダー' => 'Lavender',
        'オレンジブロッサム' => 'Orange Blossom',
        'イランイラン' => 'Ylang Ylang',
        'チュベローズ' => 'Tuberose',
        'ゲッカ' => 'Gardenia',

        // Base notes
        'ムスク' => 'Musk',
        'シダーウッド' => 'Cedarwood',
        'シダー' => 'Cedar',
        'アンバー' => 'Amber',
        'バニラ' => 'Vanilla',
        'サンダルウッド' => 'Sandalwood',
        'パチュリ' => 'Patchouli',
        'ベチバー' => 'Vetiver',
        'ムスク' => 'Musk',
        'トンカ' => 'Tonka Bean',
    ],

    /*
    |--------------------------------------------------------------------------
    | Budget Tiers (for quiz)
    |--------------------------------------------------------------------------
    */
    'budget_tiers' => [
        3000 => ['min' => 0, 'max' => 3000, 'label' => '¥3,000以下', 'tier' => 'petit'],
        5000 => ['min' => 3001, 'max' => 5000, 'label' => '¥3,000-5,000', 'tier' => 'affordable'],
        8000 => ['min' => 5001, 'max' => 8000, 'label' => '¥5,000-8,000', 'tier' => 'mid'],
        10000 => ['min' => 8001, 'max' => 15000, 'label' => '¥8,000以上', 'tier' => 'luxury'],
        15000 => ['min' => 15001, 'max' => 30000, 'label' => '¥15,000以上', 'tier' => 'premium'],
        30000 => ['min' => 30001, 'max' => 60000, 'label' => '¥30,000以上', 'tier' => 'ultra_luxury'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Concentration Full Names
    |--------------------------------------------------------------------------
    */
    'concentrations' => [
        'Parfum' => ['label' => 'パルファム', 'intensity' => 30, 'lasting' => '最强'],
        'EDP' => ['label' => 'オードパルファム', 'intensity' => 20, 'lasting' => '较强'],
        'EDT' => ['label' => 'オードトワレ', 'intensity' => 15, 'lasting' => '中等'],
        'EDC' => ['label' => 'オードコロン', 'intensity' => 10, 'lasting' => '较轻'],
        'Mist' => ['label' => 'ボディミスト', 'intensity' => 5, 'lasting' => '轻盈'],
        'Oil' => ['label' => 'パーソナルケア Oil', 'intensity' => 20, 'lasting' => '较长'],
    ],
];
