<?php

use App\Http\Requests\SubmitQuizRequest;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    $this->request = new SubmitQuizRequest;
});

it('passes validation with all required fields', function () {
    $data = [
        'personality' => 'romantic',
        'vibe' => 'floral',
        'occasion' => ['daily', 'date'],
        'style' => 'feminine',
        'budget' => 5000,
        'experience' => 'beginner',
    ];

    $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

    expect($validator->passes())->toBeTrue();
});

it('passes validation with optional season field', function () {
    $data = [
        'personality' => 'cool',
        'vibe' => 'citrus',
        'occasion' => ['work'],
        'style' => 'chic',
        'budget' => 10000,
        'experience' => 'intermediate',
        'season' => 'spring_summer',
    ];

    $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

    expect($validator->passes())->toBeTrue();
});

it('fails when personality is missing', function () {
    $data = [
        'vibe' => 'floral',
        'occasion' => ['daily'],
        'style' => 'feminine',
        'budget' => 5000,
        'experience' => 'beginner',
    ];

    $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('personality'))->toBeTrue();
    expect($validator->errors()->first('personality'))->toBe('性格を選択してください');
});

it('fails when personality is invalid', function () {
    $data = [
        'personality' => 'invalid_type',
        'vibe' => 'floral',
        'occasion' => ['daily'],
        'style' => 'feminine',
        'budget' => 5000,
        'experience' => 'beginner',
    ];

    $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('personality'))->toBeTrue();
});

it('accepts all valid personality values', function ($personality) {
    $data = [
        'personality' => $personality,
        'vibe' => 'floral',
        'occasion' => ['daily'],
        'style' => 'feminine',
        'budget' => 5000,
        'experience' => 'beginner',
    ];

    $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

    expect($validator->passes())->toBeTrue();
})->with(['romantic', 'energetic', 'cool', 'natural']);

it('fails when vibe is missing', function () {
    $data = [
        'personality' => 'romantic',
        'occasion' => ['daily'],
        'style' => 'feminine',
        'budget' => 5000,
        'experience' => 'beginner',
    ];

    $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->first('vibe'))->toBe('好みの香りを選択してください');
});

it('accepts all valid vibe values', function ($vibe) {
    $data = [
        'personality' => 'romantic',
        'vibe' => $vibe,
        'occasion' => ['daily'],
        'style' => 'feminine',
        'budget' => 5000,
        'experience' => 'beginner',
    ];

    $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

    expect($validator->passes())->toBeTrue();
})->with(['floral', 'citrus', 'vanilla', 'woody', 'ocean']);

it('fails when occasion is missing', function () {
    $data = [
        'personality' => 'romantic',
        'vibe' => 'floral',
        'style' => 'feminine',
        'budget' => 5000,
        'experience' => 'beginner',
    ];

    $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->first('occasion'))->toBe('使用シーンを選択してください');
});

it('fails when occasion is empty array', function () {
    $data = [
        'personality' => 'romantic',
        'vibe' => 'floral',
        'occasion' => [],
        'style' => 'feminine',
        'budget' => 5000,
        'experience' => 'beginner',
    ];

    $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

    expect($validator->fails())->toBeTrue();
});

it('accepts all valid occasion values', function ($occasion) {
    $data = [
        'personality' => 'romantic',
        'vibe' => 'floral',
        'occasion' => [$occasion],
        'style' => 'feminine',
        'budget' => 5000,
        'experience' => 'beginner',
    ];

    $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

    expect($validator->passes())->toBeTrue();
})->with(['daily', 'date', 'special', 'work', 'casual']);

it('fails when style is missing', function () {
    $data = [
        'personality' => 'romantic',
        'vibe' => 'floral',
        'occasion' => ['daily'],
        'budget' => 5000,
        'experience' => 'beginner',
    ];

    $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->first('style'))->toBe('スタイルを選択してください');
});

it('accepts all valid style values', function ($style) {
    $data = [
        'personality' => 'romantic',
        'vibe' => 'floral',
        'occasion' => ['daily'],
        'style' => $style,
        'budget' => 5000,
        'experience' => 'beginner',
    ];

    $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

    expect($validator->passes())->toBeTrue();
})->with(['feminine', 'casual', 'chic', 'natural']);

it('fails when budget is missing', function () {
    $data = [
        'personality' => 'romantic',
        'vibe' => 'floral',
        'occasion' => ['daily'],
        'style' => 'feminine',
        'experience' => 'beginner',
    ];

    $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->first('budget'))->toBe('予算を設定してください');
});

it('fails when budget is below minimum', function () {
    $data = [
        'personality' => 'romantic',
        'vibe' => 'floral',
        'occasion' => ['daily'],
        'style' => 'feminine',
        'budget' => 500,
        'experience' => 'beginner',
    ];

    $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('budget'))->toBeTrue();
});

it('fails when budget exceeds maximum', function () {
    $data = [
        'personality' => 'romantic',
        'vibe' => 'floral',
        'occasion' => ['daily'],
        'style' => 'feminine',
        'budget' => 60000,
        'experience' => 'beginner',
    ];

    $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('budget'))->toBeTrue();
});

it('fails when experience is missing', function () {
    $data = [
        'personality' => 'romantic',
        'vibe' => 'floral',
        'occasion' => ['daily'],
        'style' => 'feminine',
        'budget' => 5000,
    ];

    $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->first('experience'))->toBe('香水経験を選択してください');
});

it('accepts all valid experience values', function ($experience) {
    $data = [
        'personality' => 'romantic',
        'vibe' => 'floral',
        'occasion' => ['daily'],
        'style' => 'feminine',
        'budget' => 5000,
        'experience' => $experience,
    ];

    $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

    expect($validator->passes())->toBeTrue();
})->with(['beginner', 'intermediate', 'advanced']);

it('accepts valid season values', function ($season) {
    $data = [
        'personality' => 'romantic',
        'vibe' => 'floral',
        'occasion' => ['daily'],
        'style' => 'feminine',
        'budget' => 5000,
        'experience' => 'beginner',
        'season' => $season,
    ];

    $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

    expect($validator->passes())->toBeTrue();
})->with(['spring_summer', 'fall_winter', 'all_year']);

it('fails when season is invalid', function () {
    $data = [
        'personality' => 'romantic',
        'vibe' => 'floral',
        'occasion' => ['daily'],
        'style' => 'feminine',
        'budget' => 5000,
        'experience' => 'beginner',
        'season' => 'invalid_season',
    ];

    $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('season'))->toBeTrue();
});

it('authorizes all users', function () {
    expect($this->request->authorize())->toBeTrue();
});
