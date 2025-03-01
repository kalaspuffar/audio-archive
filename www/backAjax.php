<?php
require_once(__DIR__ . '/../include/constants.php');
require_once(__DIR__ . '/../include/dbconnect.php');

$data = json_decode(file_get_contents('php://input'));

$stmt = $mysqli->prepare('UPDATE audio_clip SET status = ? WHERE id = ?;');
$stmt->bind_param("ii", $data->value, $data->id);
$stmt->execute();

$stmt = $mysqli->prepare('SELECT * FROM audio_clip WHERE status = ? AND model = "cleaned" limit 1');
$stmt->bind_param("i", $STATUS_DO_USER_REVIEW);       
$stmt->execute();
$result = $stmt->get_result();
$clip = $result->fetch_all(MYSQLI_ASSOC);

echo '{"id":' . $clip[0]['id'] . '}';