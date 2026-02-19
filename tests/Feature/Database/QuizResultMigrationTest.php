<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('quiz_results table has correct columns', function () {
    $this->assertTrue(Schema::hasTable('quiz_results'));

    $this->assertTrue(Schema::hasColumn('quiz_results', 'id'));
    $this->assertTrue(Schema::hasColumn('quiz_results', 'user_id'));
    $this->assertTrue(Schema::hasColumn('quiz_results', 'session_token'));
    $this->assertTrue(Schema::hasColumn('quiz_results', 'answers_json'));
    $this->assertTrue(Schema::hasColumn('quiz_results', 'profile_type'));
    $this->assertTrue(Schema::hasColumn('quiz_results', 'profile_data_json'));
    $this->assertTrue(Schema::hasColumn('quiz_results', 'recommended_product_ids'));
    $this->assertTrue(Schema::hasColumn('quiz_results', 'created_at'));
    $this->assertTrue(Schema::hasColumn('quiz_results', 'updated_at'));

    $indexes = DB::select('SHOW INDEX FROM quiz_results');
    $indexNames = collect($indexes)->pluck('Key_name')->unique()->toArray();
    $this->assertContains('quiz_results_session_token_index', $indexNames);
    $this->assertContains('quiz_results_user_id_index', $indexNames);
    $this->assertContains('quiz_results_profile_type_index', $indexNames);
});

test('quiz_result model can create record', function () {
    $quizResult = \App\Models\QuizResult::create([
        'session_token' => 'quiz-session-123',
        'answers_json' => [
            'personality' => 'romantic',
            'vibe' => 'floral',
            'budget' => 5000,
        ],
        'profile_type' => 'romantic_floral',
        'profile_data_json' => [
            'name' => 'ロマンチック・ブルーム',
            'description' => '優しく夢見がちなあなたにぴったりの華やかな香り',
        ],
        'recommended_product_ids' => [1, 2, 3, 4, 5],
    ]);

    $this->assertDatabaseHas('quiz_results', [
        'session_token' => 'quiz-session-123',
        'profile_type' => 'romantic_floral',
    ]);

    expect($quizResult->session_token)->toBe('quiz-session-123');
    expect($quizResult->answers_json)->toBeArray();
    expect($quizResult->answers_json['personality'])->toBe('romantic');
    expect($quizResult->recommended_product_ids)->toBe([1, 2, 3, 4, 5]);
});

test('quiz_result model has correct fillable attributes', function () {
    $model = new \App\Models\QuizResult;
    $fillable = $model->getFillable();

    expect($fillable)->toContain('user_id');
    expect($fillable)->toContain('session_token');
    expect($fillable)->toContain('answers_json');
    expect($fillable)->toContain('profile_type');
    expect($fillable)->toContain('profile_data_json');
    expect($fillable)->toContain('recommended_product_ids');
});

test('quiz_result model casts json fields as array', function () {
    $quizResult = \App\Models\QuizResult::create([
        'session_token' => 'quiz-session-casting',
        'answers_json' => [
            'personality' => 'cool',
            'vibe' => 'woody',
            'occasion' => ['daily', 'work'],
            'budget' => 8000,
        ],
        'profile_type' => 'cool_woody',
        'profile_data_json' => [
            'name' => 'クール・ウッディ',
            'description' => '洗練された大人の香り',
        ],
        'recommended_product_ids' => [10, 20, 30],
    ]);

    $retrieved = \App\Models\QuizResult::find($quizResult->id);

    expect($retrieved->answers_json)->toBeArray();
    expect($retrieved->answers_json['occasion'])->toBe(['daily', 'work']);
    expect($retrieved->profile_data_json)->toBeArray();
    expect($retrieved->profile_data_json['name'])->toBe('クール・ウッディ');
    expect($retrieved->recommended_product_ids)->toBeArray();
    expect($retrieved->recommended_product_ids)->toBe([10, 20, 30]);
});

test('quiz_result belongs to user', function () {
    $user = \App\Models\User::factory()->create();

    $quizResult = \App\Models\QuizResult::create([
        'user_id' => $user->id,
        'session_token' => 'quiz-session-with-user',
        'answers_json' => ['personality' => 'natural'],
        'profile_type' => 'natural',
        'profile_data_json' => ['name' => 'ナチュラル'],
        'recommended_product_ids' => [1],
    ]);

    expect($quizResult->user)->toBeInstanceOf(\App\Models\User::class);
    expect($quizResult->user->id)->toBe($user->id);
});

test('quiz_result can be created without user (anonymous)', function () {
    $quizResult = \App\Models\QuizResult::create([
        'session_token' => 'quiz-session-anonymous',
        'answers_json' => ['personality' => 'energetic'],
        'profile_type' => 'energetic',
        'profile_data_json' => ['name' => 'エネルギッシュ'],
        'recommended_product_ids' => [1, 2],
    ]);

    expect($quizResult->user_id)->toBeNull();
    expect($quizResult->session_token)->toBe('quiz-session-anonymous');
});
