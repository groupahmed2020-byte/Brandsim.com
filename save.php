<?php
$data = file_get_contents('php://input');
if ($data) { file_put_contents('data.json', $data); }
?>
