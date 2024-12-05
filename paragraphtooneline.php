<?php
// session information
require_once 'init.php';

session_start();

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$username = $_SESSION['username'];
/*-------------------------------------------------------------------------------*/
//initialize the newstr variable to an empty string
$newStr = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input = filter_input(INPUT_POST, "entry", FILTER_UNSAFE_RAW);

    /*-------------------------------------------------------------------------------*/
// Logic for the paragraph to one line tool

    $newStr = trim(str_replace(array("\r\n", "\n", "\r"), ' ', $input));

    try {
        // Database interaction
        global $connect;

        $sql = "INSERT INTO paragraphtoone (username, date) VALUES(:username, CURRENT_TIMESTAMP)";

        $stmt = $connect->prepare($sql);
        $stmt->bindParam(":username", $username);

        $stmt->execute();

        $display = $newStr;

    } catch (exception $e) {
        $display = "Error: " . $e->getMessage();

    }


}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Consolidev | Paragraph to One Line</title>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <link rel="stylesheet" href="CSS/styles.css">
	<link rel="stylesheet" href="CSS/paragraphtooneline.css">
	<script src="https://kit.fontawesome.com/d0af7889fc.js" crossorigin="anonymous"></script>
</head>
<body>

<?php include 'header.php'; ?>

<div class="form-title">
	<i class="fa-solid fa-i-cursor icon"></i>
	<h1>Paragraph to One Line</h1>
</div>

<div class="container">
<!-- Form for text conversion -->
<form method="POST" action="">
    <textarea id="entry" name="entry" placeholder="Enter your text here" required></textarea>
	<br>
    <button type="submit" class="button">Submit</button>
</form>

<textarea id="result" name="result"
          readonly><?php if (isset($display)) echo htmlspecialchars($display, ENT_QUOTES, 'UTF-8'); ?></textarea>
</div>

<footer>
    <p>&copy; <span id="2024"></span> consoliDev. All Rights Reserved.</p>
</footer>

</body>
</html>
