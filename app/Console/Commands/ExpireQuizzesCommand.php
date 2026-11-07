<?php

namespace App\Console\Commands;

use App\Http\Controllers\QuizController;
use App\Models\QuizAttempt;
use Illuminate\Console\Command;

class ExpireQuizzesCommand extends Command
{
    protected $signature = 'lms:expire-quizzes';

    protected $description = 'Menyelesaikan dan menilai otomatis percobaan kuis yang waktunya sudah habis';

    public function handle(QuizController $quizzes): int
    {
        foreach (QuizAttempt::with('quiz.questions')->whereNull('completed_at')->where('expires_at', '<', now())->get() as $attempt) {
            $quizzes->finish($attempt, []);
        }

        $this->info('Percobaan kuis yang kedaluwarsa telah diselesaikan.');

        return self::SUCCESS;
    }
}

