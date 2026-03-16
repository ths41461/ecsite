<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendChatMessageRequest;
use App\Models\AiChatSession;
use App\Models\AiMessage;
use App\Services\AI\AIRecommendationService;
use Illuminate\Http\JsonResponse;

class ChatController extends Controller
{
    public function __construct(
        private AIRecommendationService $recommendationService
    ) {}

    public function sendMessage(SendChatMessageRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $sessionId = $validated['session_id'];
        $message = $validated['message'];

        $session = AiChatSession::where('session_token', $sessionId)->first();

        if (! $session) {
            return response()->json([
                'success' => false,
                'message' => 'セッションが見つかりません',
            ], 404);
        }

        $userMessage = AiMessage::create([
            'session_id' => $session->id,
            'role' => 'user',
            'content' => $message,
            'metadata_json' => [],
        ]);

        try {
            $aiResponse = $this->recommendationService->chat($message, $sessionId);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('ChatController: AI service error', ['error' => $e->getMessage()]);
            $aiResponse = ['message' => '申し訳ございません。エラーが発生しました。もう一度お試しください。'];
        }

        AiMessage::create([
            'session_id' => $session->id,
            'role' => 'assistant',
            'content' => $aiResponse['message'] ?? '',
            'metadata_json' => [],
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'message' => $aiResponse['message'] ?? '',
                'products' => $aiResponse['products'] ?? [],
                'session_id' => $sessionId,
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }
}
