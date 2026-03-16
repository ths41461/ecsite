<?php

namespace App\Services;

class FragranceRadarService
{
    /**
     * 6 fragrance dimensions with keyword mappings (Japanese primary + English)
     * Keywords are ordered by importance - more specific notes first
     * Based on analysis of 120+ actual seeded products
     */
    private array $dimensionKeywords = [
        'sweetness' => [
            // Sweet, dessert-like notes
            'バニラ', 'vanilla',
            'キャラメル', 'caramel',
            'チョコレ', 'chocolate',
            'ハニー', 'honey',
            'トンカ', 'tonka',
            'ベンゾイン', 'benzoin',
            'アーモンド', 'almond',
            'ココナツ', 'coconut',
            'フルーツ', 'fruit',
            'ベリー', 'berry',
            'ストロベリー', 'strawberry',
            'ラズベリー', 'raspberry',
            'ピーチ', 'peach',
            'リンゴ', 'apple',
            'りんご', 'apple', // hiragana variant
            '梨', 'pear',
            'ペア', 'pear',
            'メロン', 'melon',
            'マンゴー', 'mango',
            'グレープ', 'grape',
            'チェリー', 'cherry',
            'プラム', 'plum',
            'アプリコット', 'apricot',
            'スイート', 'sweet',
            'シュガー', 'sugar',
            'ブラウンシュガー', 'brown sugar',
            'キャンディ', 'candy',
            'メープル', 'maple',
            'プラリネ', 'praline',
            'カスタード', 'custard',
            'カカオ', 'cacao',
            'ココア', 'cocoa',
            'コーヒー', 'coffee',
            '紅茶', 'tea',
            '甘草', 'licorice',
            'リコリス', 'licorice',
            'フルーティ', 'fruity',
            'ジューシー', 'juicy',
            'パイナップル', 'pineapple',
            '柚子', 'yuzu',
            'ゆず', 'yuzu',
        ],
        'freshness' => [
            // Citrus, fresh, clean notes
            'シトラス', 'citrus',
            'レモン', 'lemon',
            'ベルガモット', 'bergamot',
            'オレンジ', 'orange',
            'グレープフルーツ', 'grapefruit',
            'ライム', 'lime',
            'ユズ', 'yuzu',
            'マンダリン', 'mandarin',
            'タンジェリン', 'tangerine',
            '日本のカンキツ', 'japanese citrus',
            '酢橘', 'sudachi',
            'ポメグラネート', 'pomegranate',
            'マリン', 'marine',
            'オーシャン', 'ocean',
            'シー', 'sea',
            'ウォーター', 'water',
            'アクア', 'aqua',
            'アクアティック', 'aquatic',
            'アクアティックノート', 'aquatic note',
            'ウォータリー', 'watery',
            'ウォータリーノート', 'watery note',
            'グリーン', 'green',
            'グリーンノート', 'green note',
            'グリーンアコード', 'green accord',
            'ミント', 'mint',
            'メントール', 'menthol',
            'ユーカリ', 'eucalyptus',
            'ティー', 'tea',
            '緑茶', 'green tea',
            'ホワイトティー', 'white tea',
            'アールグレイ', 'earl grey',
            'アールグレイティー', 'earl grey tea',
            'フレッシュ', 'fresh',
            'クリーン', 'clean',
            'クール', 'cool',
            'アイス', 'ice',
            'コールド', 'cold',
            'キュウリ', 'cucumber',
            'スイカ', 'watermelon',
            'レイン', 'rain',
            'デュー', 'dew',
            '爽やか', 'refreshing',
            '清涼', 'cooling',
            'ソルティー', 'salty',
            'シーソルト', 'sea salt',
            '塩', 'salt',
            'キングウィリアムペアー', 'king william pear',
        ],
        'floral' => [
            // Flower-based notes
            'ローズ', 'rose',
            'バラ', 'rose',
            'ジャスミン', 'jasmine',
            'リリー', 'lily',
            'ホワイトリリー', 'white lily',
            'チューリップ', 'tulip',
            'ピオニー', 'peony',
            'ピンクピオニー', 'pink peony',
            'フリージア', 'freesia',
            'ラベンダー', 'lavender',
            'アイリス', 'iris',
            'オリス', 'orris',
            'スミレ', 'violet',
            'バイオレット', 'violet',
            'ヴァイオレット', 'violet',
            'ビオラ', 'viola',
            'ラン', 'orchid',
            'オーキッド', 'orchid',
            'ブラックオーキッド', 'black orchid',
            'ガーデンニア', 'gardenia',
            'ガーデニア', 'gardenia',
            'チュベローズ', 'tuberose',
            'イランイラン', 'ylang ylang',
            'ネロリ', 'neroli',
            'オレンジブロッサム', 'orange blossom',
            'オレンジフラワー', 'orange flower',
            'サクラ', 'cherry blossom',
            '桜', 'cherry blossom',
            'キンモクセイ', 'osmanthus',
            '金木犀', 'osmanthus',
            'モクレン', 'magnolia',
            'マグノリア', 'magnolia',
            '蓮', 'lotus',
            'ロータス', 'lotus',
            'スイレン', 'water lily',
            '睡蓮', 'water lily',
            'スズラン', 'lily of the valley',
            'リリーオブザバレー', 'lily of the valley',
            'ミュゲ', 'muguet',
            'ヒヤシンス', 'hyacinth',
            'デイジー', 'daisy',
            'ひまわり', 'sunflower',
            'マリーゴールド', 'marigold',
            'カーネーション', 'carnation',
            'フラワー', 'flower',
            'フローラル', 'floral',
            'フローラルスイート', 'floral sweet',
            '白い花々', 'white flowers',
            '花々', 'flowers',
            '白花', 'white flower',
            'ホワイトフラワー', 'white flower',
            'ブーケ', 'bouquet',
            'ブロッサム', 'blossom',
            '花', 'flower',
            'ポピー', 'poppy',
            'ライラック', 'lilac',
            'ゼラニウム', 'geranium',
            'ダチュラ', 'datura',
            'ダリア', 'dahlia',
            'フランジパニ', 'frangipani',
            'ミモザ', 'mimosa',
            'スイートピー', 'sweet pea',
            'ブルーベル', 'bluebell',
            'キンポウゲ', 'buttercup',
            'ポシドニア', 'posidonia',
            '金香木', 'golden osmanthus',
            'ダマスクローズ', 'damask rose',
            'ホワイトローズ', 'white rose',
        ],
        'woody' => [
            // Wood, earth, forest notes
            'ウッド', 'wood',
            'ウッディ', 'woody',
            'ウッディノート', 'woody note',
            'シダー', 'cedar',
            'シダーウッド', 'cedarwood',
            'サンダルウッド', 'sandalwood',
            '白檀', 'sandalwood',
            'パイン', 'pine',
            'サイプレス', 'cypress',
            'ファー', 'fir',
            'オーク', 'oak',
            'オークモス', 'oakmoss',
            'モス', 'moss',
            'パチュリ', 'patchouli',
            'ベチバー', 'vetiver',
            'グアイアック', 'guaiac',
            'ギアウッド', 'guaiac wood',
            'アガーウッド', 'agarwood',
            'ウード', 'oud',
            'バンブー', 'bamboo',
            '竹', 'bamboo',
            'ドリフトウッド', 'driftwood',
            'フォレスト', 'forest',
            '森', 'forest',
            '木', 'tree',
            'バーク', 'bark',
            'ルート', 'root',
            'アース', 'earth',
            'アーシー', 'earthy',
            'ドライ', 'dry',
            'スモーキー', 'smoky',
            'バーント', 'burnt',
            'チャコール', 'charcoal',
            'タバコ', 'tobacco',
            'レザー', 'leather',
            'ヒノキ', 'cypress',
            '檜', 'cypress',
            'マホガニー', 'mahogany',
            'パロサント', 'palo santo',
            'スエード', 'suede',
            'ブラッシュスエード', 'brushed suede',
            'アンバーウッド', 'amberwood',
            'ホワイトシダー', 'white cedar',
            'ウッドセージ', 'wood sage',
        ],
        'spicy' => [
            // Spice, warmth, exotic notes
            'スパイス', 'spice',
            'スパイシー', 'spicy',
            'ペッパー', 'pepper',
            'ピンクペッパー', 'pink pepper',
            'ブラックペッパー', 'black pepper',
            'シナモン', 'cinnamon',
            'クローブ', 'clove',
            'ナツメグ', 'nutmeg',
            'カルダモン', 'cardamom',
            'ジンジャー', 'ginger',
            '生姜', 'ginger',
            'サフラン', 'saffron',
            'クミン', 'cumin',
            'コリアンダー', 'coriander',
            'アニス', 'anise',
            'スターアニス', 'star anise',
            'オールスパイス', 'allspice',
            'カイエン', 'cayenne',
            'チリ', 'chili',
            'ウォーム', 'warm',
            'ホット', 'hot',
            'インセンス', 'incense',
            'ミルラ', 'myrrh',
            'フランキンセンス', 'frankincense',
            'レジン', 'resin',
            'バルサム', 'balsam',
            'オリエンタル', 'oriental',
            'エキゾチック', 'exotic',
            'アロマティック', 'aromatic',
            'ハーブ', 'herb',
            'ハーバル', 'herbal',
            'ローズマリー', 'rosemary',
            'タイム', 'thyme',
            'バジル', 'basil',
            'セージ', 'sage',
            'ウッドセージ', 'wood sage',
            'タジェット', 'tagetes',
            'ジェノベーゼ', 'genovese',
            'カモミール', 'chamomile',
            'ラズベリー', 'raspberry',
        ],
        'musky' => [
            // Musk, amber, sensual notes
            'ムスク', 'musk',
            'ホワイトムスク', 'white musk',
            'スキンムスク', 'skin musk',
            'アンバー', 'amber',
            'アンバーグリス', 'ambergris',
            'アンブロキサン', 'ambroxan',
            'シベット', 'civet',
            'カストレウム', 'castoreum',
            'アニマリック', 'animalic',
            'センシュアル', 'sensual',
            'インティメイト', 'intimate',
            'スキン', 'skin',
            'パウダー', 'powder',
            'パウダリー', 'powdery',
            'ソフト', 'soft',
            'ウォーム', 'warm',
            'コージー', 'cozy',
            'コンフォート', 'comfort',
            'キャッシュミア', 'cashmere',
            'ベルベット', 'velvet',
            'シルク', 'silk',
            'クリーム', 'cream',
            'ミルキー', 'milky',
            'バター', 'butter',
            'スムーズ', 'smooth',
            'リッチ', 'rich',
            'ディープ', 'deep',
            'ダーク', 'dark',
            'ミステリアス', 'mysterious',
            'ノワール', 'noir',
            'ナイト', 'night',
            'イブニング', 'evening',
            'インテンス', 'intense',
            'ストロング', 'strong',
            'ボールド', 'bold',
            'コンフィデント', 'confident',
            '石鹸', 'soap',
            'シャボン', 'soap',
            '清潔', 'clean',
            'ミステリアス', 'mysterious',
        ],
    ];

    /**
     * Calculate all 6 radar dimensions from product notes.
     *
     * @param array $notes Array with 'top', 'middle', 'base' keys
     * @return array<string, int> Scores 0-100 for each dimension
     */
    public function calculateRadarData(array $notes): array
    {
        $dimensions = [];

        foreach (array_keys($this->dimensionKeywords) as $dimension) {
            $dimensions[$dimension] = $this->calculateDimensionScore($notes, $dimension);
        }

        return $dimensions;
    }

    /**
     * Calculate score for a single dimension.
     * Uses presence-based scoring: each matched keyword adds points.
     * More matches = higher score, capped at 100.
     *
     * @param array $notes Array with 'top', 'middle', 'base' keys
     * @param string $dimension Dimension name
     * @return int Score 0-100
     */
    private function calculateDimensionScore(array $notes, string $dimension): int
    {
        $keywords = $this->dimensionKeywords[$dimension];
        $totalScore = 0;
        
        // Points per match, different weights for different layers
        $pointsPerMatch = [
            'top' => 12,      // Top notes are most noticeable
            'middle' => 15,   // Middle notes define the character
            'base' => 10,     // Base notes are subtle but lasting
        ];
        
        foreach (['top', 'middle', 'base'] as $layer) {
            $layerNotes = $notes[$layer] ?? '';
            if (empty($layerNotes)) {
                continue;
            }

            $matchCount = 0;
            
            // Check each keyword pair (Japanese is primary)
            for ($i = 0; $i < count($keywords); $i += 2) {
                $jpKeyword = $keywords[$i];
                $enKeyword = $keywords[$i + 1] ?? '';
                
                // Check if keyword is in the notes
                if (mb_strpos($layerNotes, $jpKeyword) !== false) {
                    $matchCount++;
                } elseif (!empty($enKeyword) && mb_stripos($layerNotes, $enKeyword) !== false) {
                    $matchCount++;
                }
            }
            
            // Add points for this layer (cap at 3 matches per layer to avoid runaway scores)
            $cappedMatches = min($matchCount, 4);
            $totalScore += $cappedMatches * $pointsPerMatch[$layer];
        }

        // Cap at 100
        return min(100, $totalScore);
    }

    /**
     * Get dimension labels in Japanese.
     *
     * @return array<string, string>
     */
    public function getDimensionLabels(): array
    {
        return [
            'sweetness' => '甘さ',
            'freshness' => '爽やかさ',
            'floral' => '花',
            'woody' => '木',
            'spicy' => 'スパイス',
            'musky' => 'ムスク',
        ];
    }

    /**
     * Get dimension colors for visualization.
     *
     * @return array<string, string>
     */
    public function getDimensionColors(): array
    {
        return [
            'sweetness' => '#F472B6', // Pink
            'freshness' => '#22D3EE', // Cyan
            'floral' => '#EC4899',    // Rose
            'woody' => '#A16207',     // Brown
            'spicy' => '#F97316',     // Orange
            'musky' => '#7C3AED',     // Purple
        ];
    }
}
