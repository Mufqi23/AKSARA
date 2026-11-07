<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\LearningMaterial;
use App\Models\Lesson;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class LearningHubTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function user(string $role): User
    {
        return User::where('email', $role.'@example.com')->firstOrFail();
    }

    public function test_visible_sidebar_separates_classes_and_learning_hub_without_duplicate_targets(): void
    {
        foreach (['student', 'teacher', 'admin'] as $role) {
            $page = $this->actingAs($this->user($role))->get('/dashboard')->assertOk();
            $doc = new DOMDocument;
            @$doc->loadHTML('<?xml encoding="UTF-8">'.$page->getContent());
            $xpath = new DOMXPath($doc);

            $hrefs = array_map(
                fn ($n) => $n->getAttribute('href'),
                iterator_to_array($xpath->query('//div[@id="sidebarMenu"]//a[@href]'))
            );

            $this->assertContains('/classes', $hrefs);
            $this->assertContains('/learning', $hrefs);
            $this->assertSame(count($hrefs), count(array_unique($hrefs)), "Duplicate sidebar URLs found for {$role}");
        }
    }

    public function test_learning_hub_separates_modules_materials_and_assignments_and_enforces_access(): void
    {
        Lesson::create([
            'teacher_assignment_id' => 1,
            'title' => 'Bab Rahasia Draf',
            'content' => 'Konten draf guru',
            'position' => 9,
            'status' => 'draft',
        ]);
        Lesson::create([
            'teacher_assignment_id' => 7,
            'title' => 'Bab Rombel Lain',
            'content' => 'Konten kelas lain',
            'position' => 1,
            'status' => 'published',
        ]);

        // Student sees only published content from enrolled classes
        $studentPage = $this->actingAs($this->user('student'))
            ->get('/learning')
            ->assertOk()
            ->assertSee('Modul, Materi &amp; Tugas Pelajaran', false)
            ->assertSee('lw-modul-card', false)
            ->assertSee('lw-materi-card', false)
            ->assertSee('lw-tugas-card', false)
            ->assertDontSee('Bab Rahasia Draf')
            ->assertDontSee('Bab Rombel Lain');

        $studentPage->assertViewHas('isStudent', true);

        // Filtering by class_id and tab works cleanly
        $this->get('/learning?class_id=1&tab=assignments')
            ->assertOk()
            ->assertSee('PF102')
            ->assertViewHas('activeTab', 'assignments');

        // Teacher sees own drafts on /learning
        $this->actingAs($this->user('teacher'))
            ->get('/learning?class_id=1')
            ->assertOk()
            ->assertSee('Bab Rahasia Draf')
            ->assertDontSee('Bab Rombel Lain');

        // Admin can monitor across classes
        $this->actingAs($this->user('admin'))
            ->get('/learning')
            ->assertOk()
            ->assertSee('Bab Rahasia Draf')
            ->assertSee('Bab Rombel Lain');
    }

    public function test_classroom_show_separates_modul_materi_and_tugas_tabs_and_cards(): void
    {
        $this->actingAs($this->user('student'))
            ->get('/classes/1')
            ->assertOk()
            ->assertSee('data-lw-tab="lessons"', false)
            ->assertSee('data-lw-tab="materials"', false)
            ->assertSee('data-lw-tab="assignments"', false)
            ->assertSee('lw-modul-card', false)
            ->assertSee('lw-materi-card', false)
            ->assertSee('lw-tugas-card', false);
    }
}

