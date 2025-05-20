<?php
require_once __DIR__ . '/mail_config.php';

/**
 * Adds a new task to the task list
 * 
 * @param string $task_name The name of the task to add.
 * @return bool True on success, false on failure.
 */
function addTask( string $task_name ): bool {
	$file  = __DIR__ . '/tasks.txt';
	
	// Trim the task name to prevent issues with whitespace
	$task_name = trim($task_name);
	
	if (empty($task_name)) {
		return false;
	}
	
	// Get all existing tasks to check for duplicates
	$tasks = getAllTasks();
	
	// Check for duplicates
	foreach ($tasks as $task) {
		if ($task['name'] === $task_name) {
			return false; // Task already exists
		}
	}
	
	// Create a new task with a unique ID
	$new_task = [
		'id' => uniqid(),
		'name' => $task_name,
		'completed' => false,
	];
	
	// Add the new task to the array
	$tasks[] = $new_task;
	
	// Save all tasks back to the file
	$success = file_put_contents($file, json_encode($tasks, JSON_PRETTY_PRINT));
	
	return $success !== false;
}

/**
 * Retrieves all tasks from the tasks.txt file
 * 
 * @return array Array of tasks.
 */
function getAllTasks(): array {
	$file = __DIR__ . '/tasks.txt';
	
	if (!file_exists($file) || filesize($file) === 0) {
		return [];
	}
	
	$tasks_json = file_get_contents($file);
	$tasks = json_decode($tasks_json, true);
	
	// If json_decode fails or doesn't return an array, return empty array
	if (!is_array($tasks)) {
		return [];
	}
	
	return $tasks;
}

/**
 * Marks a task as completed or uncompleted
 * 
 * @param string  $task_id The ID of the task to mark.
 * @param bool $is_completed True to mark as completed, false to mark as uncompleted.
 * @return bool True on success, false on failure
 */
function markTaskAsCompleted( string $task_id, bool $is_completed ): bool {
	$file  = __DIR__ . '/tasks.txt';
	
	$tasks = getAllTasks();
	$found = false;
	
	// Find and update the task with the given ID
	foreach ($tasks as &$task) {
		if ($task['id'] === $task_id) {
			$task['completed'] = $is_completed;
			$found = true;
			break;
		}
	}
	
	if (!$found) {
		return false;
	}
	
	// Save the updated tasks array
	$success = file_put_contents($file, json_encode($tasks, JSON_PRETTY_PRINT));
	
	return $success !== false;
}

/**
 * Deletes a task from the task list
 * 
 * @param string $task_id The ID of the task to delete.
 * @return bool True on success, false on failure.
 */
function deleteTask( string $task_id ): bool {
	$file  = __DIR__ . '/tasks.txt';
	
	$tasks = getAllTasks();
	$found = false;
	$updated_tasks = [];
	
	// Create a new array without the task to delete
	foreach ($tasks as $task) {
		if ($task['id'] === $task_id) {
			$found = true;
		} else {
			$updated_tasks[] = $task;
		}
	}
	
	if (!$found) {
		return false;
	}
	
	// Save the updated tasks array
	$success = file_put_contents($file, json_encode($updated_tasks, JSON_PRETTY_PRINT));
	
	return $success !== false;
}

/**
 * Generates a 6-digit verification code
 * 
 * @return string The generated verification code.
 */
function generateVerificationCode(): string {
	// Generate a random 6-digit code
	return sprintf('%06d', mt_rand(0, 999999));
}

/**
 * Subscribe an email address to task notifications.
 *
 * Generates a verification code, stores the pending subscription,
 * and sends a verification email to the subscriber.
 *
 * @param string $email The email address to subscribe.
 * @return bool True if verification email sent successfully, false otherwise.
 */
function subscribeEmail( string $email ): bool {
	$file = __DIR__ . '/pending_subscriptions.txt';
	
	// Validate email
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		return false;
	}
	
	// Check if already subscribed
	$subscribers_file = __DIR__ . '/subscribers.txt';
	if (file_exists($subscribers_file) && filesize($subscribers_file) > 0) {
		$subscribers = file($subscribers_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		if (in_array($email, $subscribers)) {
			return false; // Already subscribed
		}
	}
	
	// Check if already in pending verification
	$pending_subscriptions = [];
	if (file_exists($file) && filesize($file) > 0) {
		$pending_json = file_get_contents($file);
		$pending_subscriptions = json_decode($pending_json, true) ?: [];
	}
	
	// Generate verification code
	$code = generateVerificationCode();
	
	// Update pending subscriptions
	$pending_subscriptions[$email] = $code;
	
	// Save pending subscriptions
	$success = file_put_contents($file, json_encode($pending_subscriptions, JSON_PRETTY_PRINT));
	
	if (!$success) {
		return false;
	}
	
	// Get server information for verification link
	$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
	$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
	
	// Create verification link
	$verification_link = "$protocol://$host/verify.php?email=" . urlencode($email) . "&code=$code";
	
	// Prepare email
	$subject = 'Verify subscription to Task Planner';
	
	$message = '
	<p>Click the link below to verify your subscription to Task Planner:</p>
	<p><a id="verification-link" href="' . $verification_link . '">Verify Subscription</a></p>
	';
	
	// Send verification email using SMTP
	return sendSmtpEmail($email, $subject, $message);
}

/**
 * Verifies an email subscription
 * 
 * @param string $email The email address to verify.
 * @param string $code The verification code.
 * @return bool True on success, false on failure.
 */
function verifySubscription( string $email, string $code ): bool {
	$pending_file     = __DIR__ . '/pending_subscriptions.txt';
	$subscribers_file = __DIR__ . '/subscribers.txt';
	
	// Check if the pending subscriptions file exists
	if (!file_exists($pending_file) || filesize($pending_file) === 0) {
		return false;
	}
	
	// Load pending subscriptions
	$pending_json = file_get_contents($pending_file);
	$pending_subscriptions = json_decode($pending_json, true) ?: [];
	
	// Verify the code
	if (!isset($pending_subscriptions[$email]) || $pending_subscriptions[$email] !== $code) {
		return false;
	}
	
	// Remove from pending subscriptions
	unset($pending_subscriptions[$email]);
	file_put_contents($pending_file, json_encode($pending_subscriptions, JSON_PRETTY_PRINT));
	
	// Add to verified subscribers
	$subscribers = [];
	if (file_exists($subscribers_file) && filesize($subscribers_file) > 0) {
		$subscribers = file($subscribers_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	}
	
	if (!in_array($email, $subscribers)) {
		// Add the email to the subscribers file
		$subscribers[] = $email;
		$success = file_put_contents($subscribers_file, implode("\n", $subscribers));
		return $success !== false;
	}
	
	return true; // Already subscribed
}

/**
 * Unsubscribes an email from the subscribers list
 * 
 * @param string $email The email address to unsubscribe.
 * @return bool True on success, false on failure.
 */
function unsubscribeEmail( string $email ): bool {
	$subscribers_file = __DIR__ . '/subscribers.txt';
	
	if (!file_exists($subscribers_file) || filesize($subscribers_file) === 0) {
		return false;
	}
	
	// Load subscribers
	$subscribers = file($subscribers_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	
	// Find the email in the list
	$key = array_search($email, $subscribers);
	if ($key === false) {
		return false; // Email not found
	}
	
	// Remove the email from the list
	unset($subscribers[$key]);
	
	// Save the updated list
	$success = file_put_contents($subscribers_file, implode("\n", $subscribers));
	
	return $success !== false;
}

/**
 * Sends task reminders to all subscribers
 * Internally calls sendTaskEmail() for each subscriber
 */
function sendTaskReminders(): void {
	$subscribers_file = __DIR__ . '/subscribers.txt';
	
	if (!file_exists($subscribers_file) || filesize($subscribers_file) === 0) {
		return; // No subscribers
	}
	
	// Get pending tasks
	$tasks = getAllTasks();
	$pending_tasks = array_filter($tasks, function($task) {
		return !$task['completed'];
	});
	
	if (empty($pending_tasks)) {
		return; // No pending tasks
	}
	
	// Get subscribers
	$subscribers = file($subscribers_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	
	// Send emails to all subscribers
	foreach ($subscribers as $email) {
		sendTaskEmail($email, $pending_tasks);
	}
}

/**
 * Sends a task reminder email to a subscriber with pending tasks.
 *
 * @param string $email The email address of the subscriber.
 * @param array $pending_tasks Array of pending tasks to include in the email.
 * @return bool True if email was sent successfully, false otherwise.
 */
function sendTaskEmail( string $email, array $pending_tasks ): bool {
	$subject = 'Task Planner - Pending Tasks Reminder';
	
	// Get server information for unsubscribe link
	$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
	$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
	
	// Create unsubscribe link
	$unsubscribe_link = "$protocol://$host/unsubscribe.php?email=" . urlencode($email);
	
	// Prepare task list HTML
	$tasks_html = '';
	foreach ($pending_tasks as $task) {
		$tasks_html .= '<li>' . htmlspecialchars($task['name']) . '</li>';
	}
	
	// Prepare email
	$message = '
	<h2>Pending Tasks Reminder</h2>
	<p>Here are the current pending tasks:</p>
	<ul>
		' . $tasks_html . '
	</ul>
	<p><a id="unsubscribe-link" href="' . $unsubscribe_link . '">Unsubscribe from notifications</a></p>
	';
	
	// Send email using SMTP
	return sendSmtpEmail($email, $subject, $message);
}
