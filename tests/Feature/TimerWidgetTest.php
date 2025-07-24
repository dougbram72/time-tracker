<?php

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('dashboard loads successfully', function () {
    $user = \App\Models\User::factory()->create();
    
    $response = $this->actingAs($user)->get('/dashboard');
    
    $response->assertStatus(200)
             ->assertViewIs('dashboard')
             ->assertSee('Dashboard')
             ->assertSee('Track your time across projects and issues');
});

test('dashboard contains timer widget component', function () {
    $user = \App\Models\User::factory()->create();
    
    $response = $this->actingAs($user)->get('/dashboard');
    
    $response->assertStatus(200)
             ->assertSee('x-data="timerStore()"', false)
             ->assertSee('No Active Timer')
             ->assertSee('Start');
});

test('dashboard includes required scripts and styles', function () {
    $user = \App\Models\User::factory()->create();
    
    $response = $this->actingAs($user)->get('/dashboard');
    
    $response->assertStatus(200)
             ->assertSee('alpinejs', false)
             ->assertSee('tailwindcss', false)
             ->assertSee('timerStore', false);
});

test('dashboard shows getting started information', function () {
    $user = \App\Models\User::factory()->create();
    
    $response = $this->actingAs($user)->get('/dashboard');
    
    $response->assertStatus(200)
             ->assertSee('Getting Started')
             ->assertSee('Welcome to your time tracker')
             ->assertSee('Use the timer widget');
});

test('dashboard displays stats cards', function () {
    $user = \App\Models\User::factory()->create();
    
    $response = $this->actingAs($user)->get('/dashboard');
    
    $response->assertStatus(200)
             ->assertSee('Today')
             ->assertSee('Time')
             ->assertSee('This Week')
             ->assertSee('Total Entries');
});

test('timer widget has mobile responsive classes', function () {
    $user = \App\Models\User::factory()->create();
    
    $response = $this->actingAs($user)->get('/dashboard');
    
    $response->assertStatus(200)
             ->assertSee('fixed bottom-0 left-0 right-0', false)
             ->assertSee('md:relative', false)
             ->assertSee('md:max-w-md', false);
});

test('timer widget includes all control buttons', function () {
    $user = \App\Models\User::factory()->create();
    
    $response = $this->actingAs($user)->get('/dashboard');
    
    $response->assertStatus(200)
             ->assertSee('Start')
             ->assertSee('Pause')
             ->assertSee('Resume')
             ->assertSee('Stop');
});

test('timer widget includes start modal form', function () {
    $user = \App\Models\User::factory()->create();
    
    $response = $this->actingAs($user)->get('/dashboard');
    
    $response->assertStatus(200)
             ->assertSee('Start New Timer')
             ->assertSee('Project')
             ->assertSee('Issue')
             ->assertSee('Description (Optional)');
});
