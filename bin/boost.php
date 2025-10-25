<?php

exec(sprintf(
    'symfony console go:boost %s %s %s',
    escapeshellarg($argv[1]),
    escapeshellarg($argv[2]),
    escapeshellarg($argv[3]),
));
