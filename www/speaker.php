<?php
    require_once(__DIR__ . '/../include/dbconnect.php');

    if (isset($_GET['disabled'])) {
        $stmt = $mysqli->prepare('UPDATE speakers SET disabled = ?, new = 0 WHERE id = ?;');
        $stmt->bind_param("ii", $_GET['disabled'], $_GET['id']);
        $stmt->execute();
    }

    $stmt = $mysqli->prepare('SELECT * FROM speakers WHERE id = ' . $_GET['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $speakers = $result->fetch_all(MYSQLI_ASSOC);
    $color = $speakers[0]['disabled'] ? '#DDD' : '#FFF';
    $color = $speakers[0]['new'] ? '#6F6' : $color;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="css/normalize.css?r=<?php echo $CSS_UPDATE ?>">
    <link rel="stylesheet" href="css/skeleton.css?r=<?php echo $CSS_UPDATE ?>">
    <link rel="stylesheet" href="css/custom.css?r=<?php echo $CSS_UPDATE ?>">   
</head>
    <body style="background-color: <?php echo $color ?>;">
        <div class="section hero">
            <div class="container">    
                <?php require_once(__DIR__ . "/../include/head.php"); ?>

                <h1>Speaker</h1>                

                <div>
                    <a href="index.php#speaker_<?php echo $speakers[0]['id']; ?>"><< Back</a>
                    <a href="speaker.php?id=<?php echo $speakers[0]['id'] + 1; ?>">Next >></a>
                    <br>
                    ID : <?php echo $speakers[0]['id']; ?><br>
                    Speaker : <?php echo $speakers[0]['speaker']; ?><br>
                    Disable : <?php echo $speakers[0]['disabled']; ?>&nbsp;
                    <a href="?id=<?php echo $speakers[0]['id']; ?>&disabled=1">Disable</a>&nbsp;
                    <a href="?id=<?php echo $speakers[0]['id']; ?>&disabled=0">Enable</a>
                    <br>
                    New : <?php echo $speakers[0]['new']; ?><br>
                </div>

                <table>
                    <tr>
                        <th>id</th>
                        <th>text</th>
                        <th>audio</th>
                    </tr>
                <?php
                    $stmt = $mysqli->prepare('SELECT * FROM statements as st, audio_clip as a WHERE ' . 
                    ' st.id = a.statement_id AND a.model = "original" AND speaker_id = ' . $_GET['id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $statements = $result->fetch_all(MYSQLI_ASSOC);


                    $stmt = $mysqli->prepare('SELECT st.id as id, a.clip_path FROM statements as st, audio_clip as a WHERE ' . 
                    ' st.id = a.statement_id AND a.model = "cleaned" AND speaker_id = ' . $_GET['id']);
                    $stmt->execute();
                    $cleaned = fetchAssocAll($stmt, 'id');
                
                    foreach ($statements as $statement) {
                        ?>
                        <tr>
                            <td><?php echo $statement["id"] ?></td>
                            <td><?php echo $statement["textline"] ?></td>
                            <td>
                                Original:<br>
                                <audio controls src="<?php echo $statement["clip_path"] ?>"></audio><br>
                                <?php 
                                if (isset($cleaned[$statement["id"]])) {
                                    $cleanedPath = $cleaned[$statement["id"]]["clip_path"];
                                    ?>
                                    Cleaned:<br>
                                    <audio controls src="<?php echo $cleanedPath ?>"></audio><br>
                                    <?php 
                                }
                                ?>
                            </td>
                        </tr>
                        <?php
                    }
                ?>
                </table>
            </div>
        </div>
    </body>
</html>
