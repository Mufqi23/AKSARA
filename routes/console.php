<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('lms:deadlines')->hourly()->withoutOverlapping();
Schedule::command('lms:expire-quizzes')->everyMinute()->withoutOverlapping();
