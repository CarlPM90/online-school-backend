<?php
// Ultra-simple health check that bypasses Laravel entirely
http_response_code(200);
header('Content-Type: text/plain');
header('Cache-Control: no-cache');
echo 'OK';
exit;
?>