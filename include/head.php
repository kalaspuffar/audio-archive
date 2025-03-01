<?php
    require_once('constants.php');
    require_once('dbconnect.php');
?>
<div>
    <p><a href="index.php">Voices</a>&nbsp;<a href="clip.php">Clip</a></p>

    <?php               
    $stmt = $mysqli->prepare(
        'SELECT SEC_TO_TIME(SUM(duration_seconds)) AS total_time FROM ' . 
        ' audio_clip WHERE model = "original"'
    );
    $stmt->execute();
    $result = $stmt->get_result();
    $audioResult = $result->fetch_all(MYSQLI_ASSOC);
    $total = $audioResult[0]['total_time'];


    $stmt = $mysqli->prepare(
        'SELECT SEC_TO_TIME(SUM(duration_seconds)) AS total_time FROM ' . 
        ' audio_clip as a, statements as st, speakers as sp WHERE ' . 
        ' a.statement_id = st.id AND st.speaker_id = sp.id AND a.model = "original" ' . 
        ' AND sp.disabled = 0 AND (sp.new != 1 OR sp.new IS NULL)'
    );
    $stmt->execute();
    $result = $stmt->get_result();
    $audioResult = $result->fetch_all(MYSQLI_ASSOC);
    $active = $audioResult[0]['total_time'];

    $stmt = $mysqli->prepare(
        'SELECT SEC_TO_TIME(SUM(duration_seconds)) AS total_time FROM ' . 
        ' audio_clip as a, statements as st, speakers as sp WHERE ' . 
        ' a.statement_id = st.id AND st.speaker_id = sp.id AND a.model = "cleaned" ' . 
        ' AND sp.disabled = 0 AND (sp.new != 1 OR sp.new IS NULL)'
    );
    $stmt->execute();
    $result = $stmt->get_result();
    $audioResult = $result->fetch_all(MYSQLI_ASSOC);
    $cleaned = $audioResult[0]['total_time'];

    $stmt = $mysqli->prepare(
        'SELECT SEC_TO_TIME(SUM(duration_seconds)) AS total_time FROM ' . 
        ' audio_clip WHERE status = 1'
    );
    $stmt->execute();
    $result = $stmt->get_result();
    $audioResult = $result->fetch_all(MYSQLI_ASSOC);
    $approved = $audioResult[0]['total_time'];

    $stmt = $mysqli->prepare(
        'SELECT status, COUNT(*) AS count_of_status FROM audio_clip WHERE model = "cleaned" GROUP BY status;'
    );
    $stmt->execute();
    $statusResult = fetchAssocAll($stmt, 'status');
    ?>

    <p>
        Active sound clips (<?php echo $active . " / " . $total; ?>) 
        Cleaned sound clips (<?php echo $cleaned ?>) 
        Approved (<?php echo $approved ?>) <br>        
        Clean (<?php echo isset($statusResult[$STATUS_NEW]) ? $statusResult[$STATUS_NEW]['count_of_status'] : 0 ?>)
        Machine Approved (<?php echo isset($statusResult[$STATUS_MACHINE_APPROVED]) ? $statusResult[$STATUS_MACHINE_APPROVED]['count_of_status'] : 0 ?>)
        For review (<?php echo isset($statusResult[$STATUS_DO_USER_REVIEW]) ? $statusResult[$STATUS_DO_USER_REVIEW]['count_of_status'] : 0 ?>)
        Approved (<?php echo isset($statusResult[$STATUS_USER_APPROVED]) ? $statusResult[$STATUS_USER_APPROVED]['count_of_status'] : 0 ?>)
        Corrupted (<?php echo isset($statusResult[$STATUS_BAD]) ? $statusResult[$STATUS_BAD]['count_of_status'] : 0 ?>)
        ReClean (<?php echo isset($statusResult[$STATUS_RECLEAN]) ? $statusResult[$STATUS_RECLEAN]['count_of_status'] : 0 ?>)
    </p>
</div>

