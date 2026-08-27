<?php

use Tests\TestCase;
use Tests\ScrambleTestCase;

pest()->extend(TestCase::class)->in('Feature');
pest()->extend(ScrambleTestCase::class)->in('Unit/Scramble');
