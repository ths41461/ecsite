<?php

echo "=== Quiz Debug Script ===\n\n";

echo "1. Checking Quiz Results Table...\n";
$quizResults = App\Models\QuizResult::orderBy('created_at', 'desc')->limit(10)->get();
echo '   Total Quiz Results: '.App\Models\QuizResult::count()."\n";

foreach ($quizResults as $result) {
    echo "---------------------------------\n";
    echo "ID: {$result->id}\n";
    echo "Session Token: {$result->session_token}\n";
    echo "Profile Type: {$result->profile_type}\n";
    echo "Created: {$result->created_at}\n";

    $answers = $result->answers_json;
    if ($answers) {
        echo "Answers:\n";
        foreach ($answers as $key => $value) {
            $valueStr = is_array($value) ? implode(', ', $value) : $value;
            echo "  - {$key}: {$valueStr}\n";
        }
    }

    $profileData = $result->profile_data_json;
    if ($profileData) {
        echo "Profile Data:\n";
        echo '  '.json_encode($profileData, JSON_UNESCAPED_UNICODE)."\n";
    }

    $recommended = $result->recommended_product_ids;
    if ($recommended) {
        echo 'Recommended Products: '.implode(', ', $recommended)."\n";
    }
    echo "\n";
}

echo "2. Checking AI Chat Sessions...\n";
$sessions = App\Models\AiChatSession::orderBy('created_at', 'desc')->limit(5)->get();
echo '   Total Sessions: '.App\Models\AiChatSession::count()."\n";

foreach ($sessions as $session) {
    echo "---------------------------------\n";
    echo "ID: {$session->id}\n";
    echo "Session Token: {$session->session_token}\n";
    echo "Quiz Result ID: {$session->quiz_result_id}\n";
    echo 'Messages: '.$session->messages()->count()."\n";
    echo "Created: {$session->created_at}\n";
    echo "\n";
}

echo "3. Checking User Scent Profiles...\n";
$profiles = App\Models\UserScentProfile::with('user')->limit(5)->get();
echo '   Total Profiles: '.App\Models\UserScentProfile::count()."\n";

foreach ($profiles as $profile) {
    echo "---------------------------------\n";
    echo "ID: {$profile->id}\n";
    echo "User ID: {$profile->user_id}\n";
    echo "Profile Type: {$profile->profile_type}\n";
    echo 'Profile Data: '.json_encode($profile->profile_data_json, JSON_UNESCAPED_UNICODE)."\n";
    echo "\n";
}

echo "=== Done ===\n";
