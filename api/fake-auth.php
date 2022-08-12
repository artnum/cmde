<?php
$token = uniqid('00abcdef');
mkdir('/tmp/' . $token);
echo json_encode(['token' => $token]);