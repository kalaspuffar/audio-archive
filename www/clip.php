<?php
    require_once(__DIR__ . '/../include/constants.php');
    require_once(__DIR__ . '/../include/dbconnect.php');

    if (!isset($_GET['id'])) {
        $stmt = $mysqli->prepare('SELECT * FROM audio_clip WHERE status = ? AND model = "cleaned" limit 1');
        $stmt->bind_param("i", $STATUS_DO_USER_REVIEW);
        $stmt->execute();
        $result = $stmt->get_result();
        $clip = $result->fetch_all(MYSQLI_ASSOC);
        $redirectUrl = $_SERVER['PHP_SELF'] . '?id=' . $clip[0]['id'];
        header('Location: ' . $redirectUrl);
        exit;
    }

    $stmt = $mysqli->prepare('SELECT * FROM audio_clip WHERE id = ' . $_GET['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $clip = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clip</title>
    <link rel="stylesheet" href="css/normalize.css?r=<?php echo $CSS_UPDATE ?>">
    <link rel="stylesheet" href="css/skeleton.css?r=<?php echo $CSS_UPDATE ?>">
    <link rel="stylesheet" href="css/custom.css?r=<?php echo $CSS_UPDATE ?>">   
</head>
    <body style="background-color: <?php echo $color ?>;">
        <div class="section hero">
            <div class="container">    
                <?php require_once(__DIR__ . "/../include/head.php"); ?>

                <h1>Clip</h1>

                <table class="u-full-width">
                    <tr>
                        <th>corrupted</th>
                        <th>text</th>
                        <th>audio</th>
                        <th>accepted</th>
                    </tr>
                <?php
                    $stmt = $mysqli->prepare('SELECT * FROM statements as st, audio_clip as a WHERE ' . 
                    ' st.id = a.statement_id AND a.model = "original" AND st.id = ' . $clip[0]['statement_id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $statements = $result->fetch_all(MYSQLI_ASSOC);
                
                    foreach ($statements as $statement) {
                        ?>
                        <a href="speaker.php?id=<?php echo $statement['speaker_id'] ?>">Speaker</a>

                        <tr>
                            <td><button id="corrupted_button">CORRUPTED</button></td>
                            <td><?php echo $statement["textline"] ?></td>
                            <td>
                                Original:<br>
                                <audio controls src="<?php echo $statement["clip_path"] ?>"></audio><br>
                                <?php                                 
                                $cleanedPath = $clip[0]["clip_path"];
                                ?>
                                Cleaned:<br>
                                <audio autoplay controls src="<?php echo $cleanedPath ?>"></audio><br>
                            </td>
                            <td><button id="accepted_button">ACCEPTED</button></td>
                            <td><button id="reclean_button">RECLEAN</button></td>
                        </tr>
                        <?php
                    }
                ?>
                </table>
            </div>
        </div>

        <script type="text/javascript">
            const corruptedButton = document.getElementById('corrupted_button');
            const acceptedButton = document.getElementById('accepted_button');
            const recleanButton = document.getElementById('reclean_button');
            

            corruptedButton.addEventListener('click', function() {
                save(<?php echo $STATUS_BAD; ?>);
            });
            recleanButton.addEventListener('click', function() {
                save(<?php echo $STATUS_RECLEAN; ?>);
            });
            acceptedButton.addEventListener('click', function() {
                save(<?php echo $STATUS_USER_APPROVED; ?>);
            });

            function save(val) {
                const data = {
                    'id': <?php echo $clip[0]['id'] ?>,
                    'value': val
                }
                fetch("backAjax.php", {
                    method: 'POST',
                    body: JSON.stringify(data)
                }).then((res) => res.json()
                ).then((body) => {
                    window.location.href = window.location.pathname + '?id=' + body.id;
                });                
            }
        </script>
    </body>
</html>
