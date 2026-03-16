<?php

echo "=== AI Test Script ===\n\n";

$provider = new App\Services\AI\Providers\OllamaProvider;

echo "1. Testing Ollama Connection...\n";
$available = $provider->isAvailable();
echo '   Ollama Available: '.($available ? 'Yes' : 'No')."\n";

if (! $available) {
    echo "   ERROR: Ollama is not running!\n";
    exit(1);
}

echo "\n2. Testing Basic Chat...\n";
$response = $provider->chat('こんにちは！、香水について教えてください。', []);
echo '   Response: '.($response['message']['content'] ?? 'No content')."\n";

echo "\n3. Testing Model Configuration...\n";
echo '   Current Model: '.$provider->getModel()."\n";

$models = $provider->getAvailableModels();
echo '   Available Models: '.implode(', ', array_column($models, 'name'))."\n";

echo "\n4. Testing ContextBuilder...\n";
$builder = new App\Services\AI\ContextBuilder;
$context = $builder->build([
    'budget' => 10000,
    'gender' => 'women',
    'personality' => 'romantic',
]);
echo '   Context keys: '.implode(', ', array_keys($context))."\n";
echo '   Products in context: '.count($context['products'] ?? [])."\n";

echo "\n5. Testing AIRecommendationService...\n";
$service = new App\Services\AI\AIRecommendationService;
$quizData = [
    'budget' => 10000,
    'gender' => 'women',
    'personality' => 'romantic',
    'vibe' => 'floral',
    'occasion' => ['daily', 'date'],
    'style' => 'feminine',
    'experience' => 'beginner',
];

$recommendation = $service->recommend($quizData);
echo '   Recommendation keys: '.implode(', ', array_keys($recommendation))."\n";

echo "\n=== Done ===\n";
