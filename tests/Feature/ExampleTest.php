<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use DatabaseMigrations;

    public function test_the_application_returns_a_successful_response(): void
    {
        $this->seed();

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('AKSARA')
            ->assertSee('Delivery, Interaction, Assessment')
            ->assertSee('Aplikasi Kegiatan Sekolah dan Akademik Terpadu')
            ->assertSee('Siswa Terdaftar')
            ->assertSee('Guru Pengajar')
            ->assertSee('AI Deteksi Dini Siswa Berisiko')
            ->assertDontSee('Akses Cepat Sivitas')
            ->assertDontSee('Katalog Program Studi');
    }
}
