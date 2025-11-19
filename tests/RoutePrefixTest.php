<?php

it('respects custom route prefix', function () {
    $this->get('/admin/vantage')
        ->assertStatus(200)
        ->assertSee('Dashboard');
});

