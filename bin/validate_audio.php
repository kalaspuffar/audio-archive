<?php
require_once(__DIR__ . '/../include/constants.php');
require_once(__DIR__ . '/../include/dbconnect.php');

$workerId = 'worker_' . getmypid();

while (true) {
    $mysqli->autocommit(false);
    
    $stmt = $mysqli->prepare("SELECT a.id, s.textline, a.clip_path FROM audio_clip as a, statements as s WHERE status = ? AND model = 'cleaned' AND a.statement_id = s.id LIMIT 1");
    $stmt->bind_param('i', $STATUS_NEW);
    $stmt->execute();
    $result = $stmt->get_result();
    $textclip = $result->fetch_all(MYSQLI_ASSOC);

    if (empty($textclip)) {
        $mysqli->rollback();
        echo "No more pending clips. Worker $workerId is done.\n";
        break;
    }

    $currentClip = $textclip[0];
 
    $stmt = $mysqli->prepare('UPDATE audio_clip SET status = ? WHERE id = ?;');
    $stmt->bind_param("ii", 
        $STATUS_PENDING,
        $currentClip['id']
    );
    $stmt->execute();

    $mysqli->commit();

    $clipId = $currentClip['id'];
    echo "Worker $workerId processing clip #$clipId...\n";
    handleClipRecord($currentClip);
}

function cleanString($str) {
    $str = preg_replace("/[^\p{L}\s\d]+/u", "", $str);
    $str = strtolower(trim($str));
    $str = preg_replace("/\s+/u", " ", $str);
    return $str;
}

function handleClipRecord($currentClip) {
    global $mysqli, $REPO_DIR, $STATUS_MACHINE_APPROVED, $STATUS_DO_USER_REVIEW;
    $cmdSilence = sprintf(
        'whisper --language Swedish -f txt %s 2>&1',
        escapeshellarg($REPO_DIR . $currentClip['clip_path']),
    );
    $outputSoX = shell_exec($cmdSilence);

    $textFile = basename($currentClip['clip_path'], '.mp3') . '.txt'; 
    if (file_exists($textFile)) {
        $cleanedTextLine = cleanString($currentClip['textline']);
        $cleanedTextFile = cleanString(file_get_contents($textFile));

        if (strcmp($cleanedTextFile, $cleanedTextLine) == 0) {
            echo "Approved : " . $cleanedTextFile . "\n";
            $stmt = $mysqli->prepare('UPDATE audio_clip SET status = ? WHERE id = ?;');
            $stmt->bind_param("ii", 
                $STATUS_MACHINE_APPROVED,
                $currentClip['id']
            );
            $stmt->execute();
        } else {
            echo "User required : " . $cleanedTextFile . "\n";
            $stmt = $mysqli->prepare('UPDATE audio_clip SET status = ? WHERE id = ?;');
            $stmt->bind_param("ii", 
                $STATUS_DO_USER_REVIEW,
                $currentClip['id']
            );
            $stmt->execute();
        }
        unlink($textFile);
    }
}