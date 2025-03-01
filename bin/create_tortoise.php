<?php
require_once(__DIR__ . '/../include/constants.php');
require_once(__DIR__ . '/../include/dbconnect.php');

$tortoise_statement = "SELECT statement_id FROM audio_clip WHERE model = 'tortoise'";
$statement_query = "SELECT st.id FROM statements as st, speakers as sp WHERE st.speaker_id = sp.id AND sp.disabled = 0 AND st.id NOT IN ($tortoise_statement)";
$stmt = $mysqli->prepare("SELECT * FROM audio_clip WHERE status IN (?, ?) AND model = 'cleaned' AND statement_id IN ($statement_query);");
$stmt->bind_param("ii", $STATUS_MACHINE_APPROVED, $STATUS_USER_APPROVED);
$stmt->execute();
$result = $stmt->get_result();
$clips = $result->fetch_all(MYSQLI_ASSOC);

echo count($clips) . "\n";

$tortoiseDataDir = 'data/audio/tortoise/';
@mkdir(__DIR__ . '/../' . $tortoiseDataDir, 0777, true);

for ($i = 0; $i < count($clips); $i++) {
    $outfile = basename($clips[$i]['clip_path'], ".mp3") . ".wav";
    generateTortoise(__DIR__ . '/../' . $clips[$i]['clip_path'], __DIR__ . '/../' . $tortoiseDataDir . $outfile);
    insertFile($clips[$i]['statement_id'], $tortoiseDataDir . $outfile);    
}



exportFiles("all", "SELECT LOWER(s.textline) as textline, a.clip_path FROM audio_clip as a, statements as s WHERE model = 'tortoise' AND a.statement_id = s.id AND length(s.textline) < 80 AND frames < 255995 AND NOT s.textline REGEXP('\\([0-9]{4}\\)')");
exportFiles("mattias", "SELECT LOWER(s.textline) as textline, a.clip_path FROM audio_clip as a, statements as s WHERE model = 'tortoise' AND a.statement_id = s.id AND s.speaker_id = 801 AND length(s.textline) < 80 AND frames < 255995 AND NOT s.textline REGEXP('\\([0-9]{4}\\)')");
exportFiles("pure", "SELECT LOWER(s.textline) as textline, a.clip_path FROM audio_clip as a, statements as s WHERE model = 'tortoise' AND a.statement_id = s.id AND s.speaker_id != 801 AND length(s.textline) < 80 AND frames < 255995 AND NOT s.textline REGEXP('\\([0-9]{4}\\)')");

function cleanString($str) {
    $str = trim($str);
    $str = preg_replace("/\s+/u", " ", $str);
    return $str;
}

function exportFiles($prefix, $sql) {
    global $mysqli;
    $stmt = $mysqli->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $textclip = $result->fetch_all(MYSQLI_ASSOC);
    
    shuffle($textclip);
    $splitIndex = floor(0.85 * count($textclip));
    
    $train = array_slice($textclip, 0, $splitIndex);
    $validation = array_slice($textclip, $splitIndex);
    
    $trainStr = array_map(function ($record) {
        return substr($record["clip_path"], 5) . "|" . cleanString($record["textline"]);
    }, $train);
    file_put_contents(__DIR__ . '/../data/' . $prefix . '_train.txt', implode("\n", $trainStr));
    
    $validationStr = array_map(function ($record) {
        return substr($record["clip_path"], 5) . "|" . cleanString($record["textline"]);
    }, $validation);
    file_put_contents(__DIR__ . '/../data/' . $prefix . '_val.txt', implode("\n", $validationStr));    
}

function generateTortoise($inputFile, $finalFile) {
    $cmdTortoise = sprintf(
        'ffmpeg -y -i %s -c:a pcm_f32le -ac 1 -ar 22050 %s 2>&1',
        escapeshellarg($inputFile),
        escapeshellarg($finalFile)
    );
    $output1 = shell_exec($cmdTortoise);
    echo "Done! Final file: $finalFile\n";
}

function insertFile($statement_id, $clip_path) {
    global $mysqli;

    $cmd = sprintf(
        'ffprobe -v quiet -of csv=p=0 -show_entries format=duration %s 2>&1',
        escapeshellarg(__DIR__ . '/../' . $clip_path)
    );
    $durationSeconds = intval(shell_exec($cmd));

    $cmd = sprintf(
        'soxi -V0 -s %s 2>&1',
        escapeshellarg(__DIR__ . '/../' . $clip_path)
    );
    $frames = intval(shell_exec($cmd));

    $stmt = $mysqli->prepare('INSERT INTO audio_clip (statement_id, format, clip_path, model, duration_seconds, frames) VALUES (?, "wav", ?, "tortoise", ?, ?);');
    $stmt->bind_param("isii", 
        $statement_id,
        $clip_path,
        $durationSeconds,
        $frames
    );
    $stmt->execute();
}
