<?php

test('products index renders', function () {
    $this->get(route('products.index'))
        ->assertOk()
        ->assertSee('Products');
});
