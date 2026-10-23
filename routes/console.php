<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Schedule;

Schedule::command('performance:prune')->daily()->withoutOverlapping();
