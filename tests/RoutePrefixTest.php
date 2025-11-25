<?php

it('respects custom route prefix', function (): void {
    $this->get('/admin/vantage')
        ->assertStatus(200)
        ->assertSee('Dashboard');
});

