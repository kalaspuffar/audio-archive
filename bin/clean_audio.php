<?php
require_once(__DIR__ . '/../include/constants.php');
require_once(__DIR__ . '/../include/dbconnect.php');

$cleaned_statement = "SELECT statement_id FROM audio_clip WHERE model = 'cleaned'";
$statement_query = "SELECT st.id FROM statements as st, speakers as sp WHERE st.speaker_id = sp.id AND sp.disabled = 0 AND st.id NOT IN ($cleaned_statement)";
$stmt = $mysqli->prepare("SELECT * FROM audio_clip WHERE model = 'original' AND statement_id IN ($statement_query);");
$stmt->execute();
$result = $stmt->get_result();
$clips = $result->fetch_all(MYSQLI_ASSOC);

echo count($clips) . "\n";

$cleanedDataDir = 'data/audio/cleaned/';
@mkdir(__DIR__ . '/../' . $cleanedDataDir, 0777, true);

for ($i = 0; $i < count($clips); $i++) {
    cleanFile(__DIR__ . '/../' . $clips[$i]['clip_path'], __DIR__ . '/../' . $cleanedDataDir . basename($clips[$i]['clip_path']));
    insertFile($clips[$i]['statement_id'], $cleanedDataDir . basename($clips[$i]['clip_path']));    
}

function insertFile($statement_id, $clip_path) {
    global $mysqli;

    $cmd = sprintf(
        'ffprobe -v quiet -of csv=p=0 -show_entries format=duration %s 2>&1',
        escapeshellarg(__DIR__ . '/../' . $clip_path)
    );
    $durationSeconds = intval(shell_exec($cmd));    

    $stmt = $mysqli->prepare('INSERT INTO audio_clip (statement_id, format, clip_path, model, duration_seconds) VALUES (?, "mp3", ?, "cleaned", ?);');
    $stmt->bind_param("isi", 
        $statement_id,
        $clip_path,
        $durationSeconds
    );
    $stmt->execute();
}

function cleanFile($inputFile, $finalFile) {
    $denoisedFile   = tempnam(sys_get_temp_dir(), "denoised") . '.wav';
    $dynaudnormFile = tempnam(sys_get_temp_dir(), "dynaudnorm") . '.wav';
    $peakFixedFile  = tempnam(sys_get_temp_dir(), "peek_fixed") . '.wav';

    // ============================
    // 1) NOISE REDUCTION
    // ============================
    $cmdNoiseReduce = sprintf(
        'ffmpeg -y -i %s -af "afftdn=nf=-25" %s 2>&1',
        escapeshellarg($inputFile),
        escapeshellarg($denoisedFile)
    );
    $output1 = shell_exec($cmdNoiseReduce);
    //echo "Noise Reduction Output:\n$output1\n\n";

    // ============================
    // 2) DYNAMIC NORMALIZATION
    // ============================
    $cmdDynaudnorm = sprintf(
        'ffmpeg -y -i %s -af "dynaudnorm=g=35:p=0.9" %s 2>&1',
        escapeshellarg($denoisedFile),
        escapeshellarg($dynaudnormFile)
    );
    $output2 = shell_exec($cmdDynaudnorm);
    //echo "Dynaudnorm Output:\n$output2\n\n";

    // ============================
    // 3) VOLUME DETECT (ANALYSIS)
    //    We'll parse the max_volume from the console output
    // ============================
    $cmdVolDetect = sprintf(
        'ffmpeg -i %s -af volumedetect -f null /dev/null 2>&1',
        escapeshellarg($dynaudnormFile)
    );
    $output3 = shell_exec($cmdVolDetect);
    //echo "Volumedetect Analysis:\n$output3\n\n";

    // Extract max_volume (e.g. "max_volume: -5.0 dB" )
    $maxVolume = null;
    if (preg_match('/max_volume:\s+([-0-9\.]+) dB/i', $output3, $matches)) {
        $maxVolume = floatval($matches[1]); // e.g. -5.0
        //echo "Parsed max_volume: $maxVolume dB\n";
    } else {
        echo "Could not parse max_volume. Check volumedetect output.\n";
    }

    // ============================
    // 4) APPLY FINAL VOLUME BOOST
    //    E.g. if we want final peak = -1 dB
    //    neededBoost = targetPeak - maxVolume
    //    If maxVolume = -5.0, neededBoost = (-1) - (-5) = +4.0 dB
    // ============================
    $targetPeak = -1.0;
    $neededBoost = 0.0;

    if (!is_null($maxVolume)) {
        $neededBoost = $targetPeak - $maxVolume; 
        //echo "We will apply: {$neededBoost} dB of additional gain.\n";
    }

    $cmdPeakFix = sprintf(
        'ffmpeg -y -i %s -af "volume=%fdB" %s 2>&1',
        escapeshellarg($dynaudnormFile),
        $neededBoost,
        escapeshellarg($peakFixedFile)
    );
    $output4 = shell_exec($cmdPeakFix);
    //echo "Applying final volume boost:\n$output4\n\n";

    // ============================
    // 5) REMOVE SILENCE
    //    We remove silence from start/end.
    //    For reference https://digitalcardboard.com/blog/2009/08/25/the-sox-of-silence/
    // ============================    
    $cmdSilence = sprintf(
        'sox %s %s pad 1 1 silence -l 1 0.5 1%% reverse silence 1 0.5 1%% reverse 2>&1',
        escapeshellarg($peakFixedFile),
        escapeshellarg($finalFile)
    );
    $outputSoX = shell_exec($cmdSilence);

    //echo "Silence Removal Output:\n$outputSoX\n\n";

    echo "Done! Final file: $finalFile\n";
    unlink($denoisedFile);
    unlink($dynaudnormFile);
    unlink($peakFixedFile);
}

$stmt = $mysqli->prepare('UPDATE audio_clip SET status = ? WHERE duration_seconds = 0;');
$stmt->bind_param("i", $STATUS_RECLEAN);
$stmt->execute();