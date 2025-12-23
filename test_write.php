<?php
file_put_contents(__DIR__ . '/test_log.txt', date('Y-m-d H:i:s') . " | Test ghi file\n", FILE_APPEND);
echo 'Đã ghi file!';
