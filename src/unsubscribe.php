<?php
require_once 'functions.php';

// Process unsubscription
$message = '';
$status = 'error';

if (isset($_GET['email'])) {
    $email = $_GET['email'];
    
    if (unsubscribeEmail($email)) {
        $message = 'You have been successfully unsubscribed from task reminders.';
        $status = 'success';
    } else {
        $message = 'Unsubscription failed. The email may not be in our subscriber list.';
    }
} else {
    $message = 'Invalid unsubscription link. Please try again or contact support.';
}
?>

<!DOCTYPE html>
<html>
<head>
	<title>Unsubscribe - Task Scheduler</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<style>
		body {
			font-family: Arial, sans-serif;
			max-width: 800px;
			margin: 0 auto;
			padding: 20px;
		}
		.container {
			background-color: #f9f9f9;
			border-radius: 5px;
			padding: 20px;
			margin-bottom: 20px;
			text-align: center;
		}
		h2 {
			color: #333;
		}
		.message {
			padding: 15px;
			margin: 20px 0;
			border-radius: 4px;
		}
		.success {
			background-color: #d4edda;
			color: #155724;
		}
		.error {
			background-color: #f8d7da;
			color: #721c24;
		}
		a {
			display: inline-block;
			margin-top: 20px;
			padding: 10px 15px;
			background-color: #4CAF50;
			color: white;
			text-decoration: none;
			border-radius: 4px;
		}
		a:hover {
			background-color: #45a049;
		}
	</style>
</head>
<body>
	<div class="container">
		<h2 id="unsubscription-heading">Unsubscribe from Task Updates</h2>
		<div class="message <?php echo $status; ?>">
			<?php echo $message; ?>
		</div>
		<a href="index.php">Back to Task Scheduler</a>
	</div>
</body>
</html>
