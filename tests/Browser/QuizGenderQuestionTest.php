<?php

use Laravel\Dusk\Browser;

test('quiz page displays gender question in browser', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/fragrance-diagnosis')
            ->waitForText('あなたの印象は？')
            ->click('button[value="romantic"]')
            ->click('@next-button')
            ->waitForText('好む香りのタイプは？')
            ->click('button[value="floral"]')
            ->click('@next-button')
            ->waitForText('使用するシーンは？')
            ->click('button[value="daily"]')
            ->click('@next-button')
            ->waitForText('あなたのスタイルは？')
            ->click('button[value="feminine"]')
            ->click('@next-button')
            ->waitForText('予算はどのくらい？')
            ->click('button[value="5000"]')
            ->click('@next-button')
            ->waitForText('香水の経験は？')
            ->click('button[value="beginner"]')
            ->click('@next-button')
            ->waitForText('季節の好みは？')
            ->click('button[value="spring"]')
            ->click('@next-button')
            ->waitForText('性別は？')
            ->assertSee('女性')
            ->assertSee('男性')
            ->assertSee('ユニセックス');
    });
});

test('quiz can select gender women and complete', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/fragrance-diagnosis')
            ->waitForText('あなたの印象は？')
            ->click('button[value="romantic"]')
            ->click('@next-button')
            ->waitForText('好む香りのタイプは？')
            ->click('button[value="floral"]')
            ->click('@next-button')
            ->waitForText('使用するシーンは？')
            ->click('button[value="daily"]')
            ->click('@next-button')
            ->waitForText('あなたのスタイルは？')
            ->click('button[value="feminine"]')
            ->click('@next-button')
            ->waitForText('予算はどのくらい？')
            ->click('button[value="5000"]')
            ->click('@next-button')
            ->waitForText('香水の経験は？')
            ->click('button[value="beginner"]')
            ->click('@next-button')
            ->waitForText('季節の好みは？')
            ->click('button[value="spring"]')
            ->click('@next-button')
            ->waitForText('性別は？')
            ->click('button[value="women"]')
            ->click('@next-button')
            ->waitForLocation('/fragrance-diagnosis/results');
    });
});

test('quiz can select gender men and complete', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/fragrance-diagnosis')
            ->waitForText('あなたの印象は？')
            ->click('button[value="cool"]')
            ->click('@next-button')
            ->waitForText('好む香りのタイプは？')
            ->click('button[value="citrus"]')
            ->click('@next-button')
            ->waitForText('使用するシーンは？')
            ->click('button[value="work"]')
            ->click('@next-button')
            ->waitForText('あなたのスタイルは？')
            ->click('button[value="chic"]')
            ->click('@next-button')
            ->waitForText('予算はどのくらい？')
            ->click('button[value="10000"]')
            ->click('@next-button')
            ->waitForText('香水の経験は？')
            ->click('button[value="experienced"]')
            ->click('@next-button')
            ->waitForText('季節の好みは？')
            ->click('button[value="fall"]')
            ->click('@next-button')
            ->waitForText('性別は？')
            ->click('button[value="men"]')
            ->click('@next-button')
            ->waitForLocation('/fragrance-diagnosis/results');
    });
});
