<?php

// A receiver that answers with 32 MB of text packed into a few kilobytes of gzip, for the delivery size tests
header('Content-Type: text/plain');
header('Content-Encoding: gzip');

echo gzencode(str_repeat('a', 32 * 1024 * 1024), 9);
