<?php

use App\Console\Commands\ProcessDueRepositoryScans;
use Illuminate\Support\Facades\Schedule;

Schedule::command(ProcessDueRepositoryScans::class)->everyMinute();
