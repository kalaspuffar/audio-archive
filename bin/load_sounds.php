<?php
require_once(__DIR__ . '/../include/dbconnect.php');

$commonVoiceVersion = 'cv-corpus-20.0-2024-12-06';
$dataTSV = "/home/woden/github/TTS/recipes/vctk/daniel/common/$commonVoiceVersion/sv-SE/validated.tsv";
$dataClips = "/home/woden/github/TTS/recipes/vctk/daniel/common/$commonVoiceVersion/sv-SE/clips/";

$originalDataDir = 'data/audio/original/';

@mkdir(__DIR__ . '/../' . $originalDataDir, 0777, true);

$data = explode("\n", file_get_contents($dataTSV));

$stmt = $mysqli->prepare('SELECT * FROM speakers');
$stmt->execute();
$result = $stmt->get_result();
$speakers = $result->fetch_all(MYSQLI_ASSOC);

$speakerArr = [];
foreach ($speakers as $speaker) {
    $speakerArr[$speaker['speaker']] = $speaker['id'];
}

foreach ($data as $line) {
    if (empty($line)) continue;
    $lineArr = explode("\t", $line);
    if ($lineArr[1] == 'path') continue;
    if (!array_key_exists($lineArr[0], $speakerArr)) {
        $stmt = $mysqli->prepare('INSERT INTO speakers (speaker, new) VALUES (?, 1);');
        $stmt->bind_param("s", $lineArr[0]);
        $stmt->execute();
        $speakerArr[$lineArr[0]] = $stmt->insert_id;
    }
    $stmt = $mysqli->prepare('INSERT INTO statements (speaker_id, textline) VALUES (?, ?);');
    $speaker = $speakerArr[$lineArr[0]];
    $stmt->bind_param("is", $speaker, $lineArr[3]);
    $stmt->execute();
    
    copy($dataClips . $lineArr[1], __DIR__ . '/../' . $originalDataDir . $lineArr[1]);

    $cmd = sprintf(
        'ffprobe -v quiet -of csv=p=0 -show_entries format=duration %s 2>&1',
        escapeshellarg(__DIR__ . '/../' . $originalDataDir . $lineArr[1])
    );
    $durationSeconds = intval(shell_exec($cmd));    

    $insertedStatementId = $stmt->insert_id;
    $stmt = $mysqli->prepare('INSERT INTO audio_clip (statement_id, format, clip_path, model, duration_seconds) VALUES (?, "mp3", ?, "original", ?);');
    $speaker = $speakerArr[$lineArr[0]];
    $path = $originalDataDir . $lineArr[1];
    $stmt->bind_param("isi", 
        $insertedStatementId,
        $path,
        $durationSeconds
    );
    $stmt->execute();
}