<?php
    require_once(__DIR__ . '/../include/dbconnect.php');
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
    <body>
        <div class="section hero">
            <div class="container">    
                <?php require_once(__DIR__ . "/../include/head.php"); ?>

                <h1>Voices</h1>

                <table>
                    <tr>
                        <th>id</th>
                        <th>speaker</th>
                    </tr>
                <?php
                    $stmt = $mysqli->prepare('SELECT * FROM speakers');
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $speakers = $result->fetch_all(MYSQLI_ASSOC);

                    foreach ($speakers as $speaker) {
                        $color = $speaker['disabled'] ? '#666' : '#000';
                        $color = $speaker['new'] ? '#0F0' : $color;
                        ?>
                        <tr id="speaker_<?php echo $speaker["id"] ?>" style="color: <?php echo $color ?>">
                            <td><a href="speaker.php?id=<?php echo $speaker["id"] ?>"><?php echo $speaker["id"] ?></a></td>
                            <td><?php echo $speaker["speaker"] ?></td>
                        </tr>
                        <?php
                    }
                ?>
                </table>
            </div>
        </div>
    </body>
</html>
