<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitQuizRequest;
use App\Models\AiChatSession;
use App\Models\QuizResult;
use App\Services\AI\AIRecommendationService;
use App\Services\AI\ContextBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AIRecommendationController extends Controller
{
    public function __construct(
        private AIRecommendationService $recommendationService,
        private ContextBuilder $contextBuilder
    ) {}

    public function submitQuiz(SubmitQuizRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $quizData = [
            'personality' => $validated['personality'],
            'vibe' => $validated['vibe'],
            'occasion' => $validated['occasion'],
            'style' => $validated['style'],
            'budget' => (int) $validated['budget'],
            'experience' => $validated['experience'],
            'season' => $validated['season'] ?? 'all_year',
        ];

        $profile = $this->generateScentProfile($quizData);

        $context = $this->contextBuilder->build($quizData);
        $recommendations = $this->formatRecommendations($context['available_products'] ?? [], $profile);

        $quizResult = QuizResult::create([
            'user_id' => $request->user()?->id,
            'session_token' => (string) Str::uuid(),
            'profile_type' => $quizData['personality'].'_'.$quizData['vibe'],
            'profile_data_json' => $profile,
            'answers_json' => $quizData,
            'recommended_product_ids' => collect($recommendations)->pluck('id')->toArray(),
        ]);

        $session = AiChatSession::create([
            'user_id' => $request->user()?->id,
            'session_token' => (string) Str::uuid(),
            'quiz_result_id' => $quizResult->id,
            'context_json' => $quizData,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'profile' => $profile,
                'recommendations' => $recommendations,
                'session_id' => $session->session_token,
            ],
        ]);
    }

    public function getRecommendations(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string|max:255',
        ]);

        $sessionId = $request->input('session_id');

        $session = AiChatSession::with('quizResult')
            ->where('session_token', $sessionId)
            ->first();

        if (! $session) {
            return response()->json([
                'success' => false,
                'message' => 'セッションが見つかりません',
            ], 404);
        }

        $quizResult = $session->quizResult;

        if (! $quizResult) {
            return response()->json([
                'success' => false,
                'message' => '診断結果が見つかりません',
            ], 404);
        }

        $profile = $quizResult->profile_data_json;
        $productIds = $quizResult->recommended_product_ids ?? [];

        $recommendations = $this->getProductsByIds($productIds, $profile);

        return response()->json([
            'success' => true,
            'data' => [
                'profile' => $profile,
                'recommendations' => $recommendations,
            ],
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
                    'category' => $product['category'] ?? '',
                    'price' => $product['min_price'] ?? 0,
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

    private function getProductsByIds(array $productIds, array $profile): array
    {
        if (empty($productIds)) {
            return [];
        }

        $products = \App\Models\Product::with(['brand', 'category', 'variants.inventory'])
            ->whereIn('id', $productIds)
            ->where('is_active', true)
            ->get();

        return $products->map(function ($product) {
            $minPrice = $product->variants->min('price_yen') ?? 0;
            $minSalePrice = $product->variants->whereNotNull('sale_price_yen')->min('sale_price_yen');
            $finalPrice = $minSalePrice ?? $minPrice;

            return [
                'id' => $product->id,
                'name' => $product->name,
                'brand' => $product->brand?->name ?? '',
                'category' => $product->category?->name ?? '',
                'price' => $finalPrice,
                'imageUrl' => $product->heroImage?->path ?? null,
                'notes' => $product->attributes_json['notes'] ?? [],
                'matchScore' => 85,
                'reason' => "{$product->brand?->name}の{$product->name}は、あなたにおすすめの一本です。",
            ];
        })->toArray();
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

        $profileName = $profile['name'] ?? 'あなた';

        return "{$product['brand']}の{$product['name']}は、{$vibeNote}なノートが特徴的。{$profileName}におすすめの一本です。";
    }

    private function generateScentProfile(array $quizData): array
    {
        $profiles = [
            'romantic' => [
                'floral' => [
                    'type' => 'romantic',
                    'name' => 'フレッシュ&ロマンチック',
                    'description' => '華やかで女性らしいあなたにぴったりのフローラルな香り。',
                    'color' => '#FFE4E1',
                ],
                'citrus' => [
                    'type' => 'romantic',
                    'name' => 'シュガー&スパイス',
                    'description' => '明るく元気なあなたにぴったりのフルーティーな香り。',
                    'color' => '#FFFACD',
                ],
                'vanilla' => [
                    'type' => 'romantic',
                    'name' => 'スイートドリーム',
                    'description' => '優しく包み込むような甘い香り。',
                    'color' => '#FFF8DC',
                ],
                'woody' => [
                    'type' => 'romantic',
                    'name' => 'ミステリアスロマンス',
                    'description' => '上品で深みのある香り。',
                    'color' => '#DEB887',
                ],
                'ocean' => [
                    'type' => 'romantic',
                    'name' => 'クリアウォーター',
                    'description' => '爽やかで透明感のある香り。',
                    'color' => '#E0FFFF',
                ],
            ],
            'energetic' => [
                'floral' => [
                    'type' => 'energetic',
                    'name' => 'サンシャインガール',
                    'description' => '明るく元気なあなたにぴったりの華やかな香り。',
                    'color' => '#FFE4B5',
                ],
                'citrus' => [
                    'type' => 'energetic',
                    'name' => 'フレッシュエナジー',
                    'description' => 'みずみずしく弾けるような柑橘の香り。',
                    'color' => '#FFFACD',
                ],
                'vanilla' => [
                    'type' => 'energetic',
                    'name' => 'ハッピースイート',
                    'description' => '甘さの中に元気の詰まった香り。',
                    'color' => '#FFF8DC',
                ],
                'woody' => [
                    'type' => 'energetic',
                    'name' => 'アクティブウッド',
                    'description' => '自然なウッディノート。',
                    'color' => '#D2B48C',
                ],
                'ocean' => [
                    'type' => 'energetic',
                    'name' => 'オーシャンスプラッシュ',
                    'description' => '潮風とフルーツの爽やかな香り。',
                    'color' => '#87CEEB',
                ],
            ],
            'cool' => [
                'floral' => [
                    'type' => 'cool',
                    'name' => 'シックローズ',
                    'description' => '洗練された都会的なあなたに。',
                    'color' => '#DCDCDC',
                ],
                'citrus' => [
                    'type' => 'cool',
                    'name' => 'クールシトラス',
                    'description' => 'クリアでシャープな柑橘の香り。',
                    'color' => '#F5F5F5',
                ],
                'vanilla' => [
                    'type' => 'cool',
                    'name' => 'モダンスイート',
                    'description' => '甘さを抑えたモダンなバニラ。',
                    'color' => '#F5F5DC',
                ],
                'woody' => [
                    'type' => 'cool',
                    'name' => 'アーバンウッド',
                    'description' => '大人っぽく洗練されたウッディノート。',
                    'color' => '#696969',
                ],
                'ocean' => [
                    'type' => 'cool',
                    'name' => 'クリスタルブルー',
                    'description' => '氷のようにクリアで洗練された香り。',
                    'color' => '#B0E0E6',
                ],
            ],
            'natural' => [
                'floral' => [
                    'type' => 'natural',
                    'name' => 'ピュアフラワー',
                    'description' => '飾らない自然体のあなたに。',
                    'color' => '#F0FFF0',
                ],
                'citrus' => [
                    'type' => 'natural',
                    'name' => 'オーガニックシトラス',
                    'description' => '自然の恵みを感じる爽やかなシトラス。',
                    'color' => '#FAFAD2',
                ],
                'vanilla' => [
                    'type' => 'natural',
                    'name' => 'コットンバニラ',
                    'description' => '優しく包み込むようなナチュラルなバニラ。',
                    'color' => '#FAEBD7',
                ],
                'woody' => [
                    'type' => 'natural',
                    'name' => 'フォレストブリーズ',
                    'description' => '森林浴のような心地よいウッディノート。',
                    'color' => '#8FBC8F',
                ],
                'ocean' => [
                    'type' => 'natural',
                    'name' => 'ピュアウォーター',
                    'description' => '澄んだ水のような透明感のある香り。',
                    'color' => '#E0FFFF',
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

        $profile['season'] = match ($quizData['season'] ?? 'all_year') {
            'spring_summer', 'spring' => '春夏向け',
            'fall_winter', 'fall' => '秋冬向け',
            default => 'オールシーズン',
        };

        return $profile;
    }
}
