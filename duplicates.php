<?php
// Initialize connection to database
require_once 'init.php';

// Start session management and check if the user is logged in
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

// Store the username for use in database logging
$username = $_SESSION['username'];

// Check if the form has been submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve the input data based on the button pressed
    $finderInput = filter_input(INPUT_POST, "finderInput", FILTER_SANITIZE_SPECIAL_CHARS);
    $removerInput = filter_input(INPUT_POST, "removerInput", FILTER_SANITIZE_SPECIAL_CHARS);
    $delimiter = isset($_POST['delimiter']) ? $_POST['delimiter'] : 'whitespace'; // Get the chosen delimiter

    // Initialize a variable to hold output messages
    $output = "";

    // Function to process input based on the delimiter type
    function processInput($input, $delimiter)
    {
        switch ($delimiter) {
            case 'comma':
                return array_map('trim', explode(',', strtolower($input))); // Split by comma
            case 'character':
                return str_split(strtolower($input)); // Split by each character
            default:
                return array_filter(array_map('trim', preg_split('/\s+/', strtolower($input)))); // Split by whitespace
        }
    }

    // Check if the 'Find Duplicates' button was pressed
    if (isset($_POST['find_duplicates'])) {
        // Process input based on chosen delimiter
        $inputArray = processInput($finderInput, $delimiter);
        $itemCount = array_count_values($inputArray); // Count occurrences of each item
        $duplicates = [];

        // Identify duplicates
        foreach ($itemCount as $item => $count) {
            if ($count > 1 && trim($item) !== '') { // Ignore empty items
                $duplicates[$item] = $count;
            }
        }

        // Prepare the output for display
        if (!empty($duplicates)) {
            $output .= "<h3>Duplicate Items:</h3>";
            foreach ($duplicates as $item => $count) {
                $output .= "Item '$item' appears $count times.<br>";
            }
        } else {
            $output .= "<h3>No duplicates found.</h3>";
        }

        try {
            // Log the action in the database
            global $connect;
            $sql = "INSERT INTO duplicatefinder (username, date) VALUES(:username, CURRENT_TIMESTAMP)";
            $stmt = $connect->prepare($sql);
            $stmt->bindParam(":username", $username);
            $stmt->execute();
        } catch (PDOException $e) {
            $output = $e->getMessage();
        }

    } elseif (isset($_POST['remove_duplicates'])) { // Handle 'Remove Duplicates' button
        // Process input based on chosen delimiter
        if ($delimiter === ',') {
            // Split by comma, trim whitespace, remove duplicates, and rejoin with commas
            $inputArray = array_map('trim', explode(',', $removerInput));
            $uniqueItems = array_unique($inputArray); // Remove duplicates
            $result = implode(', ', $uniqueItems); // Join items with commas
        } else {
            // Split based on the selected delimiter
            $inputArray = processInput($removerInput, $delimiter);
            $uniqueItems = array_unique($inputArray); // Remove duplicates
            $result = implode(
                $delimiter === 'whitespace' ? ' ' : '',
                $uniqueItems
            );
        }

        // Display the result string with duplicates removed
        $output .= "<h3>String with Duplicates Removed:</h3>";
        $output .= "<p>" . nl2br(htmlspecialchars($result)) . "</p>";

        try {
            // Log the action in the database
            global $connect;
            $sql = "INSERT INTO duplicateremover (username, date) VALUES(:username, CURRENT_TIMESTAMP)";
            $stmt = $connect->prepare($sql);
            $stmt->bindParam(":username", $username);
            $stmt->execute();
        } catch (PDOException $e) {
            $output = $e->getMessage();
        }
    }

}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>ConsoliDev | Duplicates</title>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <link rel="stylesheet" href="CSS/styles.css"/>
    <link rel="stylesheet" href="CSS/duplicates.css"/>
    <script src="https://kit.fontawesome.com/d0af7889fc.js" crossorigin="anonymous"></script>
</head>

<body>
<?php include('header.php'); ?>
<main class="content-wrapper">

<div class="title-container">
    <i class="fa-solid fa-magnifying-glass title-icon"></i>
	<h1 class="page-title">Duplicate Finder/Remover</h1>
<div>

<div class="container">
    <!-- Left Side: Duplicate Finder -->
    <div class="leftHalf">
        <h1 style="text-align:center;">Duplicate Finder</h1>
        <form action="duplicates.php" method="post">
            <label for="finderInput">Enter a string to find duplicates:</label><br>
            <textarea style="resize:none;" id="finderInput" name="finderInput" rows="5" cols="50" required
          placeholder="Enter text or items separated by your chosen delimiter"><?php echo isset($_POST['finderInput']) ? htmlspecialchars($_POST['finderInput']) : ''; ?></textarea>
            <br/><label style="padding-right:5px;">Delimiter:</label><br/>
            <input type="radio" name="delimiter" value="whitespace" checked> Whitespace
            <input type="radio" name="delimiter" value="comma"> Comma
            <input type="radio" name="delimiter" value="character"> Character<br><br>
            <input type="submit" name="find_duplicates" value="Find Duplicates">
        </form>
        <div class="result-section">
        <h2>Duplicate Finder Results:</h2>
        <?php
        if (isset($_POST['find_duplicates']) && !empty($output)) {
            echo $output;
        }
        ?>
        </div>
    </div>

    <!-- Right Side: Duplicate Remover -->
    <div class="rightHalf">
        <h1 style="text-align:center;">Duplicate Remover</h1>
        <form action="duplicates.php" method="post">
            <label for="removerInput">Enter a string to remove duplicates:</label><br>
            <textarea style="resize:none;" id="removerInput" name="removerInput" rows="5" cols="50" required
          placeholder="Enter text or items separated by your chosen delimiter"><?php echo isset($_POST['removerInput']) ? htmlspecialchars($_POST['removerInput']) : ''; ?></textarea>
            <br/><label style="padding-right:5px;">Delimiter:</label><br/>
            <input type="radio" name="delimiter" value="whitespace" checked> Whitespace
            <input type="radio" name="delimiter" value="comma"> Comma
            <input type="radio" name="delimiter" value="character"> Character<br><br>
            <input type="submit" name="remove_duplicates" value="Remove Duplicates">
        </form>

        <div class="result-section">
        <h2>Duplicate Remover Results:</h2>
        <?php
        if (isset($_POST['remove_duplicates']) && !empty($output)) {
            echo $output;
        }
        ?>
        </div>
    </div>
</div>
</main>

<footer>
    <p>&copy; <span id="2024"></span> consoliDev. All Rights Reserved.</p>
</footer>

</body>
</html>
