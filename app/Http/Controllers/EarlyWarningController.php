<?php

namespace App\Http\Controllers;

use App\Services\Access;
use App\Services\Ai\EarlyWarningCalculator;
use Illuminate\Http\Request;

class EarlyWarningController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'student') {
            $user->loadMissing('student.block.program', 'student.block.yearLevel', 'student.block.academicYear');
            $analysis = EarlyWarningCalculator::analyzeStudent($user);

            return view('early-warning.student', [
                'user' => $user,
                'overall' => $analysis['overall'],
                'courses' => $analysis['courses'],
            ]);
        }

        $validated = $request->validate([
            'class_id' => 'nullable|integer',
            'program_id' => 'nullable|integer',
            'block_id' => 'nullable|integer',
            'tier' => 'nullable|in:high,medium,low',
            'q' => 'nullable|string|max:100',
        ]);

        $classes = Access::classes($user)->orderBy('block_id')->orderBy('id')->get();
        $analysis = EarlyWarningCalculator::analyzeCohort(
            $user,
            isset($validated['class_id']) ? (int) $validated['class_id'] : null,
            isset($validated['program_id']) ? (int) $validated['program_id'] : null,
            isset($validated['block_id']) ? (int) $validated['block_id'] : null,
            $validated['tier'] ?? null,
            $validated['q'] ?? null
        );

        $programs = $classes->mapWithKeys(fn ($c) => [$c->block->program_id => $c->block->program->code.' — '.$c->block->program->name]);
        $blocks = $classes->mapWithKeys(fn ($c) => [$c->block_id => $c->block->program->code.' · Rombel '.$c->block->name]);

        return view('early-warning.index', [
            'user' => $user,
            'classes' => $classes,
            'programs' => $programs,
            'blocks' => $blocks,
            'students' => $analysis['students'],
            'counts' => $analysis['counts'],
            'executive' => $analysis['executive'],
        ]);
    }
}

