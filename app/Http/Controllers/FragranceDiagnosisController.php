<?php

namespace App\Http\Controllers;

use App\Services\AI\AIRecommendationService;
use App\Services\AI\ContextBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class FragranceDiagnosisController extends Controller
{
    public function __construct(
        private AIRecommendationService $recommendationService,
        private ContextBuilder $contextBuilder
    ) {}

    public function results(Request $request)
    {
        $validated = $request->validate([
            'personality' => 'required|string|in:romantic,energetic,cool,natural',
            'vibe' => 'required|string|in:floral,citrus,vanilla,woody,ocean',
            'occasion' => 'required|array|min:1',
            'occasion.*' => 'string|in:daily,date,special,work,casual',
            'style' => 'required|string|in:feminine,casual,chic,natural',
            'budget' => 'required|integer|min:0|max:100000',
            'experience' => 'required|string|in:beginner,some,experienced',
            'season' => 'nullable|string|in:spring,fall,all',
        ]);

        $quizData = [
            'personality' => $validated['personality'],
            'vibe' => $validated['vibe'],
            'occasion' => $validated['occasion'],
            'style' => $validated['style'],
            'budget' => (int) $validated['budget'],
            'experience' => $validated['experience'],
            'season' => $validated['season'] ?? 'all',
        ];

        $profile = $this->generateScentProfile($quizData);

        $context = $this->contextBuilder->build($quizData);
        $aiResult = $this->recommendationService->recommend($quizData);
        $recommendations = $this->formatRecommendations($aiResult['products'] ?? $context['available_products'], $profile);

        $sessionId = (string) Str::uuid();

        return Inertia::render('FragranceDiagnosisResults', [
            'quizData' => $quizData,
            'profile' => $profile,
            'recommendations' => $recommendations,
            'sessionId' => $sessionId,
        ]);
    }

    private function formatRecommendations(array $products, array $profile): array
    {
        return collect($products)
            ->map(function ($product, $index) use ($profile) {
                return [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'brand' => $product['brand'],
                    'category' => $product['category'],
                    'price' => $product['min_price'],
                    'imageUrl' => $product['image_url'] ?? null,
                    'notes' => $product['notes'] ?? [],
                    'matchScore' => $this->calculateMatchScore($product, $profile),
                    'reason' => $this->generateReason($product, $profile),
                ];
            })
            ->sortByDesc('matchScore')
            ->take(7)
            ->values()
            ->toArray();
    }

    private function calculateMatchScore(array $product, array $profile): int
    {
        $score = 70;

        if (! empty($product['notes'])) {
            $score += rand(5, 20);
        }

        if (isset($product['gender']) && in_array($product['gender'], ['women', 'unisex'])) {
            $score += 5;
        }

        return min(100, $score);
    }

    private function generateReason(array $product, array $profile): string
    {
        $vibeNotes = [
            'floral' => 'フローラル',
            'citrus' => 'シトラス',
            'vanilla' => 'スイート',
            'woody' => 'ウッディ',
            'ocean' => 'フレッシュ',
        ];

        $vibeNote = $vibeNotes[$profile['type'] ?? 'floral'] ?? 'フローラル';

        return "{$product['brand']}の{$product['name']}は、{$vibeNote}なノートが特徴的。{$profile['name']}のあなたにおすすめの一本です。";
    }

    private function generateScentProfile(array $quizData): array
    {
        $profiles = [
            'romantic' => [
                'floral' => [
                    'type' => 'romantic',
                    'name' => 'フレッシュ&ロマンチック',
                    'description' => '華やかで女性らしいあなたにぴったりのフローラルな香り。淡いピンクの花々のような優しく甘いノートが、あなたの魅力を引き立てます。',
                    'color' => '#FFE4E1',
                    'notes' => [
                        'top' => ['ベルガモット', 'ピーチ'],
                        'middle' => ['ローズ', 'ジャスミン'],
                        'base' => ['ムスク', 'バニラ'],
                    ],
                ],
                'citrus' => [
                    'type' => 'romantic',
                    'name' => 'シュガー&スパイス',
                    'description' => '明るく元気なあなたにぴったりのフルーティーな香り。甘さと酸味のバランスが絶妙で、周りの人を笑顔にする魅力があります。',
                    'color' => '#FFFACD',
                    'notes' => [
                        'top' => ['レモン', 'グレープフルーツ'],
                        'middle' => ['フリージア', 'ピオニー'],
                        'base' => ['ホワイトムスク'],
                    ],
                ],
                'vanilla' => [
                    'type' => 'romantic',
                    'name' => 'スイートドリーム',
                    'description' => '優しく包み込むような甘い香りが、あなたの温かい性格を表現。バニラと花々の組み合わせが、夢見がちなあなたにぴったりです。',
                    'color' => '#FFF8DC',
                    'notes' => [
                        'top' => ['アイリス', 'マンダリン'],
                        'middle' => ['オレンジブロッサム', 'イランイラン'],
                        'base' => ['バニラ', 'アンバー'],
                    ],
                ],
                'woody' => [
                    'type' => 'romantic',
                    'name' => 'ミステリアスロマンス',
                    'description' => '上品で深みのある香りが、あなたのミステリアスな魅力を引き出します。ウッディノートとフローラルの絶妙なバランス。',
                    'color' => '#DEB887',
                    'notes' => [
                        'top' => ['ピンクペッパー', 'カラブリアン Bergamot'],
                        'middle' => ['ダマスクローズ', 'サンダルウッド'],
                        'base' => ['パチョリ', 'シダーウッド'],
                    ],
                ],
                'ocean' => [
                    'type' => 'romantic',
                    'name' => 'クリアウォーター',
                    'description' => '爽やかで透明感のある香りが、あなたの純粋な魅力を表現。海風のような清涼感と女性らしさの融合。',
                    'color' => '#E0FFFF',
                    'notes' => [
                        'top' => ['シーノート', 'レモン'],
                        'middle' => ['ローズ', 'リリー'],
                        'base' => ['ムスク', 'アンバー'],
                    ],
                ],
            ],
            'energetic' => [
                'floral' => [
                    'type' => 'energetic',
                    'name' => 'サンシャインガール',
                    'description' => '明るく元気なあなたにぴったりの華やかな香り。太陽のような輝きと花々の爽やかさが、あなたのポジティブなエネルギーを表現。',
                    'color' => '#FFE4B5',
                    'notes' => [
                        'top' => ['オレンジ', 'マンゴー'],
                        'middle' => ['ひまわり', 'マリーゴールド'],
                        'base' => ['シダーウッド'],
                    ],
                ],
                'citrus' => [
                    'type' => 'energetic',
                    'name' => 'フレッシュエナジー',
                    'description' => 'みずみずしく弾けるような柑橘の香りが、あなたの活発な性格を表現。一日中爽やかな気分でいられるフレッシュな香り。',
                    'color' => '#FFFACD',
                    'notes' => [
                        'top' => ['ライム', 'レモン', 'グレープフルーツ'],
                        'middle' => ['グリーンティー', 'ミント'],
                        'base' => ['ホワイトムスク'],
                    ],
                ],
                'vanilla' => [
                    'type' => 'energetic',
                    'name' => 'ハッピースイート',
                    'description' => '甘さの中に元気の詰まった香り。バニラの温かさとフルーツの元気さが絶妙にマッチした、笑顔になれる香り。',
                    'color' => '#FFF8DC',
                    'notes' => [
                        'top' => ['ストロベリー', 'ラズベリー'],
                        'middle' => ['ココナッツ', 'ジャスミン'],
                        'base' => ['バニラ', 'カカオ'],
                    ],
                ],
                'woody' => [
                    'type' => 'energetic',
                    'name' => 'アクティブウッド',
                    'description' => 'アウトドアが好きなあなたにぴったりの自然な香り。ウッディノートとスパイシーなアクセントが、冒険心をくすぐります。',
                    'color' => '#D2B48C',
                    'notes' => [
                        'top' => ['ジンジャー', 'カルダモン'],
                        'middle' => ['ベチバー', 'シダーウッド'],
                        'base' => ['サンダルウッド', 'ムスク'],
                    ],
                ],
                'ocean' => [
                    'type' => 'energetic',
                    'name' => 'オーシャンスプラッシュ',
                    'description' => '海辺のアクティビティが好きなあなたに。潮風とフルーツの爽やかさが融合した、リフレッシュできる香り。',
                    'color' => '#87CEEB',
                    'notes' => [
                        'top' => ['シーノート', 'メロン'],
                        'middle' => ['ロータス', 'キューカンバー'],
                        'base' => ['ドリフトウッド', 'ムスク'],
                    ],
                ],
            ],
            'cool' => [
                'floral' => [
                    'type' => 'cool',
                    'name' => 'シックローズ',
                    'description' => '洗練された都会的なあなたに。エレガントなフローラルノートが、あなたのクールな魅力を上品に引き立てます。',
                    'color' => '#DCDCDC',
                    'notes' => [
                        'top' => ['ベルガモット', 'ブラックカラント'],
                        'middle' => ['バラ', 'アイリス'],
                        'base' => ['パチョリ', 'ベチバー'],
                    ],
                ],
                'citrus' => [
                    'type' => 'cool',
                    'name' => 'クールシトラス',
                    'description' => 'クリアでシャープな柑橘の香りが、あなたの研ぎ澄まされた感性を表現。すっきりとした清潔感のある香り。',
                    'color' => '#F5F5F5',
                    'notes' => [
                        'top' => ['レモン', 'ライム'],
                        'middle' => ['グリーンティー', 'バンブー'],
                        'base' => ['ホワイトシダー'],
                    ],
                ],
                'vanilla' => [
                    'type' => 'cool',
                    'name' => 'モダンスイート',
                    'description' => '甘さを抑えたモダンなバニラ。都会的でトレンドを意識するあなたに、エレガントな甘さをプラス。',
                    'color' => '#F5F5DC',
                    'notes' => [
                        'top' => ['ピンクペッパー', 'オレンジ'],
                        'middle' => ['オーキッド', 'アーモンド'],
                        'base' => ['バニラ', 'サンダルウッド'],
                    ],
                ],
                'woody' => [
                    'type' => 'cool',
                    'name' => 'アーバンウッド',
                    'description' => '大人っぽく洗練されたウッディノート。都会の夜景に似合う、クールでセクシーな香り。',
                    'color' => '#696969',
                    'notes' => [
                        'top' => ['ベルガモット', 'ブラックペッパー'],
                        'middle' => ['シダーウッド', 'インク'],
                        'base' => ['ベチバー', 'アンバー'],
                    ],
                ],
                'ocean' => [
                    'type' => 'cool',
                    'name' => 'クリスタルブルー',
                    'description' => '氷のようにクリアで洗練されたマリンノート。クールでミステリアスなあなたの魅力を引き出します。',
                    'color' => '#B0E0E6',
                    'notes' => [
                        'top' => ['マリンノート', 'ミント'],
                        'middle' => ['ウォーターリリー', 'ローズ'],
                        'base' => ['シダーウッド', 'アンバー'],
                    ],
                ],
            ],
            'natural' => [
                'floral' => [
                    'type' => 'natural',
                    'name' => 'ピュアフラワー',
                    'description' => '飾らない自然体のあなたに。控えめながらも上品なフローラルノートが、あなたの素敵な個性を引き立てます。',
                    'color' => '#F0FFF0',
                    'notes' => [
                        'top' => ['グリーンノート', 'シクラメン'],
                        'middle' => ['スズラン', 'リリー'],
                        'base' => ['ムスク', 'アンバー'],
                    ],
                ],
                'citrus' => [
                    'type' => 'natural',
                    'name' => 'オーガニックシトラス',
                    'description' => '自然の恵みを感じる爽やかなシトラス。無理のない、ありのままのあなたを表現する清潔な香り。',
                    'color' => '#FAFAD2',
                    'notes' => [
                        'top' => ['レモン', 'オレンジ'],
                        'middle' => ['ハーブ', 'グリーンティー'],
                        'base' => ['シダーウッド'],
                    ],
                ],
                'vanilla' => [
                    'type' => 'natural',
                    'name' => 'コットンバニラ',
                    'description' => '優しく包み込むようなナチュラルなバニラ。ふわふわのコットンのような温かみのある香り。',
                    'color' => '#FAEBD7',
                    'notes' => [
                        'top' => ['ホワイトティー', 'アイリス'],
                        'middle' => ['ジャスミン', 'ココナッツ'],
                        'base' => ['バニラ', 'ムスク'],
                    ],
                ],
                'woody' => [
                    'type' => 'natural',
                    'name' => 'フォレストブリーズ',
                    'description' => '森林浴のような心地よいウッディノート。穏やかで落ち着いた雰囲気のあなたにぴったり。',
                    'color' => '#8FBC8F',
                    'notes' => [
                        'top' => ['ユーカリ', 'グリーンリーフ'],
                        'middle' => ['サンダルウッド', 'シダー'],
                        'base' => ['ベチバー', 'ムスク'],
                    ],
                ],
                'ocean' => [
                    'type' => 'natural',
                    'name' => 'ピュアウォーター',
                    'description' => '澄んだ水のような透明感のある香り。穏やかで清らかな雰囲気のあなたに、シンプルな美しさを。',
                    'color' => '#E0FFFF',
                    'notes' => [
                        'top' => ['ウォーターノート', 'カシス'],
                        'middle' => ['ローズ', 'リリー'],
                        'base' => ['シダーウッド', 'ホワイトムスク'],
                    ],
                ],
            ],
        ];

        $personality = $quizData['personality'];
        $vibe = $quizData['vibe'];

        $profile = $profiles[$personality][$vibe] ?? $profiles['natural']['floral'];

        $occasions = [];
        foreach ($quizData['occasion'] as $occasion) {
            $occasions[] = match ($occasion) {
                'daily' => 'デイリー',
                'date' => 'デート',
                'special' => '特別な日',
                'work' => '仕事',
                'casual' => 'カジュアル',
                default => $occasion,
            };
        }
        $profile['occasions'] = $occasions;

        $profile['season'] = match ($quizData['season']) {
            'spring' => '春夏向け',
            'fall' => '秋冬向け',
            default => 'オールシーズン',
        };

        return $profile;
    }
}
