<?php

use Illuminate\Support\Facades\Schedule;

// Process scheduled blasts every minute
Schedule::command('blasts:process')->everyMinute();
