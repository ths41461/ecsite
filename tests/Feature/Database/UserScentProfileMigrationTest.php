<?php

/**
 * @group feature
 * @group database
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('user_scent_profiles table has correct columns', function () {
    $this->assertTrue(Schema::hasTable('user_scent_profiles'));

    $this->assertTrue(Schema::hasColumn('user_scent_profiles', 'id'));
    $this->assertTrue(Schema::hasColumn('user_scent_profiles', 'user_id'));
    $this->assertTrue(Schema::hasColumn('user_scent_profiles', 'profile_type'));
    $this->assertTrue(Schema::hasColumn('user_scent_profiles', 'profile_data_json'));
    $this->assertTrue(Schema::hasColumn('user_scent_profiles', 'preferences_json'));
    $this->assertTrue(Schema::hasColumn('user_scent_profiles', 'created_at'));
    $this->assertTrue(Schema::hasColumn('user_scent_profiles', 'updated_at'));

    $dbName = DB::connection()->getDatabaseName();
    $uniqueConstraints = DB::select("
        SELECT COLUMN_NAME 
        FROM information_schema.STATISTICS 
        WHERE TABLE_SCHEMA = ? 
        AND TABLE_NAME = 'user_scent_profiles' 
        AND NON_UNIQUE = 0
        AND INDEX_NAME != 'PRIMARY'
    ", [$dbName]);
    $uniqueColumns = collect($uniqueConstraints)->pluck('COLUMN_NAME')->toArray();
    $this->assertContains('user_id', $uniqueColumns, 'user_id should have unique constraint');
});

test('user_scent_profile model can create record', function () {
    $user = \App\Models\User::factory()->create();

    $profile = \App\Models\UserScentProfile::create([
        'user_id' => $user->id,
        'profile_type' => 'romantic_floral',
        'profile_data_json' => [
            'name' => 'ロマンチック・ブルーム',
            'description' => '優しく夢見がちなあなたにぴったりの華やかな香り',
        ],
        'preferences_json' => [
            'favorite_notes' => ['rose', 'peony', 'jasmine'],
            'avoid_notes' => ['musk'],
            'budget_range' => [3000, 8000],
        ],
    ]);

    $this->assertDatabaseHas('user_scent_profiles', [
        'user_id' => $user->id,
        'profile_type' => 'romantic_floral',
    ]);

    expect($profile->user_id)->toBe($user->id);
    expect($profile->profile_type)->toBe('romantic_floral');
});

test('user_scent_profile model has correct fillable attributes', function () {
    $model = new \App\Models\UserScentProfile;
    $fillable = $model->getFillable();

    expect($fillable)->toContain('user_id');
    expect($fillable)->toContain('profile_type');
    expect($fillable)->toContain('profile_data_json');
    expect($fillable)->toContain('preferences_json');
});

test('user_scent_profile model casts json fields as array', function () {
    $user = \App\Models\User::factory()->create();

    $profile = \App\Models\UserScentProfile::create([
        'user_id' => $user->id,
        'profile_type' => 'cool_woody',
        'profile_data_json' => [
            'name' => 'クール・ウッディ',
            'description' => '洗練された大人の香り',
        ],
        'preferences_json' => [
            'favorite_notes' => ['sandalwood', 'cedar'],
            'budget_range' => [5000, 15000],
        ],
    ]);

    $retrieved = \App\Models\UserScentProfile::find($profile->id);

    expect($retrieved->profile_data_json)->toBeArray();
    expect($retrieved->profile_data_json['name'])->toBe('クール・ウッディ');
    expect($retrieved->preferences_json)->toBeArray();
    expect($retrieved->preferences_json['favorite_notes'])->toBe(['sandalwood', 'cedar']);
});

test('user_scent_profile belongs to user', function () {
    $user = \App\Models\User::factory()->create();

    $profile = \App\Models\UserScentProfile::create([
        'user_id' => $user->id,
        'profile_type' => 'natural',
        'profile_data_json' => ['name' => 'ナチュラル'],
        'preferences_json' => [],
    ]);

    expect($profile->user)->toBeInstanceOf(\App\Models\User::class);
    expect($profile->user->id)->toBe($user->id);
});

test('user_scent_profile has unique constraint on user_id', function () {
    $user = \App\Models\User::factory()->create();

    \App\Models\UserScentProfile::create([
        'user_id' => $user->id,
        'profile_type' => 'first_profile',
        'profile_data_json' => [],
        'preferences_json' => [],
    ]);

    $this->expectException(\Illuminate\Database\QueryException::class);

    \App\Models\UserScentProfile::create([
        'user_id' => $user->id,
        'profile_type' => 'second_profile',
        'profile_data_json' => [],
        'preferences_json' => [],
    ]);
});
