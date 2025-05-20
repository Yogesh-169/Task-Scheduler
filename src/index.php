<?php
require_once 'functions.php';

// Process task form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	// Handle task actions
	if (isset($_POST['action'])) {
		// Mark task as completed/uncompleted
		if ($_POST['action'] === 'toggle_task' && isset($_POST['task_id']) && isset($_POST['completed'])) {
			$task_id = $_POST['task_id'];
			$is_completed = $_POST['completed'] === '1';
			markTaskAsCompleted($task_id, $is_completed);
		}
		
		// Delete task
		if ($_POST['action'] === 'delete_task' && isset($_POST['task_id'])) {
			$task_id = $_POST['task_id'];
			deleteTask($task_id);
		}
		
		// Edit task (saving edited task)
		if ($_POST['action'] === 'edit_task' && isset($_POST['task_id']) && isset($_POST['new_task_name'])) {
			$task_id = $_POST['task_id'];
			$new_task_name = $_POST['new_task_name'];
			
			// Delete old task and add new one with the same ID
			$tasks = getAllTasks();
			$is_completed = false;
			
			// Find original completion status
			foreach ($tasks as $task) {
				if ($task['id'] === $task_id) {
					$is_completed = $task['completed'];
					break;
				}
			}
			
			// Delete the old task
			deleteTask($task_id);
			
			// Add the task with new name but same ID and completion status
			$new_task = [
				'id' => $task_id,
				'name' => $new_task_name,
				'completed' => $is_completed,
			];
			
			// Get all tasks again
			$tasks = getAllTasks();
			$tasks[] = $new_task;
			
			// Save the updated tasks
			file_put_contents(__DIR__ . '/tasks.txt', json_encode($tasks, JSON_PRETTY_PRINT));
		}
	}
	
	// Add new task
	if (isset($_POST['task-name']) && !empty($_POST['task-name'])) {
		$task_name = $_POST['task-name'];
		if (!addTask($task_name)) {
			$task_error = "Unable to add task or task already exists.";
		}
	}
	
	// Subscribe email
	if (isset($_POST['email']) && !empty($_POST['email'])) {
		$email = $_POST['email'];
		if (subscribeEmail($email)) {
			$email_success = "Verification email sent. Please check your inbox to confirm your subscription.";
			$notification_type = "success";
			$notification_icon = "check-circle";
			$notification_title = "Email Sent!";
		} else {
			$email_error = "Unable to subscribe or email already subscribed.";
			$notification_type = "error";
			$notification_icon = "exclamation-circle";
			$notification_title = "Subscription Failed!";
		}
	}
}

// Get all tasks
$tasks = getAllTasks();

// Calculate stats
$total_tasks = count($tasks);
$completed_tasks = count(array_filter($tasks, function($task) { return $task['completed']; }));
$pending_tasks = $total_tasks - $completed_tasks;

// Calculate progress percentage
$progress_percentage = $total_tasks > 0 ? round(($completed_tasks / $total_tasks) * 100) : 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Task Scheduler</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
	<style>
		:root {
			--primary-color: #4361ee;
			--primary-light: #4895ef;
			--primary-dark: #3f37c9;
			--success-color: #4cc9f0;
			--accent-color: #f72585;
			--warning-color: #f8961e;
			--danger-color: #f94144;
			--info-color: #3da9fc;
			--gray-100: #f8f9fa;
			--gray-200: #e9ecef;
			--gray-300: #dee2e6;
			--gray-400: #ced4da;
			--gray-500: #adb5bd;
			--gray-600: #6c757d;
			--gray-700: #495057;
			--gray-800: #343a40;
			--gray-900: #212529;
			--box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.08);
			--transition: all 0.3s ease;
		}
		
		* {
			margin: 0;
			padding: 0;
			box-sizing: border-box;
		}
		
		body {
			font-family: 'Poppins', sans-serif;
			background-color: #f5f8ff;
			color: var(--gray-800);
			line-height: 1.6;
			padding: 20px;
			min-height: 100vh;
			background-image: linear-gradient(120deg, #fdfbfb 0%, #ebedee 100%);
		}
		
		.container {
			max-width: 850px;
			margin: 0 auto;
			padding: 30px;
		}
		
		.app-header {
			text-align: center;
			margin-bottom: 40px;
			padding-bottom: 15px;
			border-bottom: 2px solid var(--gray-200);
			position: relative;
		}
		
		.app-title {
			font-size: 2.5rem;
			font-weight: 700;
			color: var(--primary-color);
			margin-bottom: 10px;
			letter-spacing: -0.5px;
		}
		
		.app-description {
			color: var(--gray-600);
			font-size: 1.1rem;
			font-weight: 300;
		}
		
		.card {
			background-color: #ffffff;
			border-radius: 12px;
			padding: 25px;
			margin-bottom: 30px;
			box-shadow: var(--box-shadow);
			transition: var(--transition);
			border: 1px solid var(--gray-200);
		}
		
		.card:hover {
			box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1), 0 3px 6px rgba(0, 0, 0, 0.05);
			transform: translateY(-2px);
		}
		
		.card-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 20px;
			padding-bottom: 15px;
			border-bottom: 1px solid var(--gray-200);
		}
		
		.card-title {
			font-size: 1.5rem;
			font-weight: 600;
			color: var(--gray-800);
			display: flex;
			align-items: center;
		}
		
		.card-title i {
			margin-right: 10px;
			color: var(--primary-color);
		}
		
		form {
			margin-bottom: 20px;
		}
		
		.input-group {
			display: flex;
			gap: 10px;
			margin-bottom: 20px;
		}
		
		input[type="text"], 
		input[type="email"] {
			flex: 1;
			padding: 12px 15px;
			border-radius: 8px;
			border: 1px solid var(--gray-300);
			font-size: 16px;
			color: var(--gray-800);
			transition: var(--transition);
			font-family: 'Poppins', sans-serif;
		}
		
		input[type="text"]:focus,
		input[type="email"]:focus {
			outline: none;
			border-color: var(--primary-color);
			box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
		}
		
		button {
			background-color: var(--primary-color);
			color: white;
			border: none;
			border-radius: 8px;
			padding: 12px 20px;
			font-size: 16px;
			cursor: pointer;
			font-weight: 500;
			transition: var(--transition);
			font-family: 'Poppins', sans-serif;
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 8px;
		}
		
		button:hover {
			background-color: var(--primary-dark);
			transform: translateY(-1px);
		}
		
		button:active {
			transform: translateY(0);
		}
		
		.btn-success {
			background-color: var(--success-color);
		}
		
		.btn-success:hover {
			background-color: #3db8df;
		}
		
		.btn-warning {
			background-color: var(--warning-color);
		}
		
		.btn-warning:hover {
			background-color: #e78818;
		}
		
		.btn-danger {
			background-color: var(--danger-color);
		}
		
		.btn-danger:hover {
			background-color: #e53e3e;
		}
		
		.tasks-list {
			list-style: none;
			padding: 0;
		}
		
		.task-item {
			padding: 16px;
			margin: 10px 0;
			background-color: white;
			border-radius: 8px;
			display: flex;
			align-items: center;
			border: 1px solid var(--gray-200);
			transition: var(--transition);
			box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
		}
		
		.task-item:hover {
			box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
			border-color: var(--gray-300);
		}
		
		.task-item.completed {
			background-color: #f8faff;
			border-left: 4px solid var(--success-color);
		}
		
		.task-item.completed .task-text {
			text-decoration: line-through;
			color: var(--gray-500);
		}
		
		.task-checkbox {
			appearance: none;
			-webkit-appearance: none;
			height: 22px;
			width: 22px;
			background-color: #fff;
			border: 2px solid var(--gray-400);
			border-radius: 6px;
			cursor: pointer;
			display: flex;
			align-items: center;
			justify-content: center;
			flex-shrink: 0;
			margin-right: 12px;
			transition: var(--transition);
			position: relative;
		}
		
		.task-checkbox:checked {
			background-color: var(--success-color);
			border-color: var(--success-color);
		}
		
		.task-checkbox:checked::after {
			content: '\2714';
			color: white;
			font-size: 14px;
			position: absolute;
		}
		
		.task-text {
			flex-grow: 1;
			font-size: 1.05rem;
			padding: 0 10px;
			word-break: break-word;
		}
		
		.task-buttons {
			display: flex;
			gap: 6px;
		}
		
		.task-buttons button {
			padding: 6px 12px;
			font-size: 14px;
		}
		
		.message {
			padding: 15px;
			margin-bottom: 20px;
			border-radius: 8px;
			font-weight: 500;
			animation: fadeIn 0.4s ease;
			display: flex;
			align-items: center;
		}
		
		.message i {
			margin-right: 10px;
			font-size: 20px;
		}
		
		@keyframes fadeIn {
			from { opacity: 0; transform: translateY(-10px); }
			to { opacity: 1; transform: translateY(0); }
		}
		
		.success {
			background-color: rgba(76, 201, 240, 0.15);
			color: #0e7490;
			border-left: 4px solid var(--success-color);
		}
		
		.error {
			background-color: rgba(249, 65, 68, 0.15);
			color: #be123c;
			border-left: 4px solid var(--danger-color);
		}
		
		.edit-form {
			display: none;
			margin-top: 10px;
			width: 100%;
			animation: fadeIn 0.3s;
		}
		
		.subscription-box {
			background-color: #f0f7ff;
			border-radius: 8px;
			padding: 20px;
			margin-top: 20px;
			border: 1px solid #d0e1fd;
		}
		
		.subscription-box h3 {
			color: var(--primary-color);
			margin-bottom: 15px;
			font-size: 1.2rem;
		}
		
		.empty-list {
			text-align: center;
			color: var(--gray-500);
			padding: 30px 0;
			font-size: 1.1rem;
		}
		
		.empty-list i {
			font-size: 50px;
			margin-bottom: 15px;
			color: var(--gray-400);
			display: block;
		}
		
		.stats-container {
			display: grid;
			grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
			gap: 15px;
			margin-bottom: 25px;
		}
		
		.stat-card {
			background-color: white;
			border-radius: 8px;
			padding: 15px;
			text-align: center;
			box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
			border: 1px solid var(--gray-200);
		}
		
		.stat-card.primary {
			background-color: rgba(67, 97, 238, 0.1);
			border-color: rgba(67, 97, 238, 0.2);
		}
		
		.stat-card.success {
			background-color: rgba(76, 201, 240, 0.1);
			border-color: rgba(76, 201, 240, 0.2);
		}
		
		.stat-card.warning {
			background-color: rgba(248, 150, 30, 0.1);
			border-color: rgba(248, 150, 30, 0.2);
		}
		
		.stat-value {
			font-size: 28px;
			font-weight: 600;
			margin: 5px 0;
		}
		
		.stat-label {
			color: var(--gray-600);
			font-size: 14px;
			font-weight: 500;
		}
		
		.stat-card.primary .stat-value {
			color: var(--primary-color);
		}
		
		.stat-card.success .stat-value {
			color: var(--success-color);
		}
		
		.stat-card.warning .stat-value {
			color: var(--warning-color);
		}
		
		.progress-container {
			width: 100%;
			height: 8px;
			background-color: var(--gray-200);
			border-radius: 4px;
			margin-bottom: 30px;
			overflow: hidden;
		}
		
		.progress-bar {
			height: 100%;
			background: linear-gradient(90deg, var(--primary-light) 0%, var(--success-color) 100%);
			border-radius: 4px;
			transition: width 0.5s ease;
		}
		
		.progress-label {
			display: flex;
			justify-content: space-between;
			margin-bottom: 8px;
			color: var(--gray-700);
			font-weight: 500;
			font-size: 14px;
		}
		
		@media (max-width: 768px) {
			.container {
				padding: 15px;
			}
			
			.card {
				padding: 20px;
			}
			
			.input-group {
				flex-direction: column;
			}
			
			.task-buttons {
				flex-wrap: wrap;
			}
			
			.app-title {
				font-size: 2rem;
			}
			
			.stats-container {
				grid-template-columns: repeat(2, 1fr);
			}
		}
		
		/* Toast notifications */
		.toast-container {
			position: fixed;
			top: 20px;
			right: 20px;
			z-index: 9999;
			display: flex;
			flex-direction: column;
			gap: 10px;
			width: 350px;
			max-width: calc(100vw - 40px);
		}
		
		.toast {
			border-radius: 8px;
			padding: 16px;
			color: white;
			display: flex;
			align-items: flex-start;
			box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
			animation: slideInRight 0.3s ease-out, fadeOut 0.5s ease-out 4.5s forwards;
			transform-origin: top right;
			overflow: hidden;
			position: relative;
		}
		
		.toast::before {
			content: '';
			position: absolute;
			top: 0;
			left: 0;
			width: 5px;
			height: 100%;
			background-color: rgba(255, 255, 255, 0.4);
		}
		
		.toast-success {
			background-color: #10b981;
		}
		
		.toast-error {
			background-color: #ef4444;
		}
		
		.toast-warning {
			background-color: #f59e0b;
		}
		
		.toast-info {
			background-color: #3b82f6;
		}
		
		.toast-icon {
			font-size: 22px;
			margin-right: 12px;
			flex-shrink: 0;
		}
		
		.toast-content {
			flex-grow: 1;
		}
		
		.toast-title {
			font-weight: 600;
			margin-bottom: 4px;
			font-size: 1.1rem;
		}
		
		.toast-message {
			font-size: 0.95rem;
			opacity: 0.9;
		}
		
		.toast-close {
			background: transparent;
			border: none;
			color: white;
			font-size: 18px;
			cursor: pointer;
			padding: 0;
			margin-left: 10px;
			opacity: 0.7;
			flex-shrink: 0;
			transition: opacity 0.2s;
		}
		
		.toast-close:hover {
			opacity: 1;
			background: transparent;
		}
		
		.toast-progress {
			position: absolute;
			bottom: 0;
			left: 0;
			width: 100%;
			height: 3px;
			background-color: rgba(255, 255, 255, 0.3);
		}
		
		.toast-progress-bar {
			height: 100%;
			background-color: rgba(255, 255, 255, 0.7);
			width: 100%;
			animation: progressShrink 5s linear forwards;
		}
		
		.notification-dot {
			display: inline-block;
			width: 10px;
			height: 10px;
			border-radius: 50%;
			margin-right: 8px;
		}
		
		.notification-dot.success {
			background-color: #10b981;
		}
		
		.notification-dot.error {
			background-color: #ef4444;
		}
		
		.notification-dot.warning {
			background-color: #f59e0b;
		}
		
		.notification-dot.info {
			background-color: #3b82f6;
		}
		
		.notification-history {
			margin-top: 20px;
			background-color: rgba(255, 255, 255, 0.7);
			border-radius: 8px;
			padding: 10px;
			max-height: 200px;
			overflow-y: auto;
		}
		
		.notification-item {
			padding: 8px 10px;
			border-radius: 6px;
			margin-bottom: 5px;
			font-size: 14px;
			display: flex;
			align-items: center;
			transition: background-color 0.2s;
		}
		
		.notification-item:hover {
			background-color: rgba(255, 255, 255, 0.8);
		}
		
		.notification-time {
			color: var(--gray-600);
			font-size: 12px;
			margin-left: auto;
		}
		
		@keyframes slideInRight {
			from {
				transform: translateX(100%);
				opacity: 0;
			}
			to {
				transform: translateX(0);
				opacity: 1;
			}
		}
		
		@keyframes fadeOut {
			from {
				opacity: 1;
				transform: scale(1);
				max-height: 200px;
				margin-bottom: 10px;
				padding: 16px;
			}
			to {
				opacity: 0;
				transform: scale(0.8);
				max-height: 0;
				margin-bottom: 0;
				padding: 0;
			}
		}
		
		@keyframes progressShrink {
			from {
				width: 100%;
			}
			to {
				width: 0%;
			}
		}
	</style>
</head>

<body>
	<div class="container">
		<div class="app-header">
			<h1 class="app-title">Task Scheduler</h1>
			<p class="app-description">Manage your tasks and get email reminders</p>
		</div>
		
		<div class="card">
			<div class="card-header">
				<h2 class="card-title"><i class="fas fa-chart-line"></i> Task Overview</h2>
			</div>
			
			<div class="stats-container">
				<div class="stat-card primary">
					<div class="stat-value"><?php echo $total_tasks; ?></div>
					<div class="stat-label">Total Tasks</div>
				</div>
				
				<div class="stat-card success">
					<div class="stat-value"><?php echo $completed_tasks; ?></div>
					<div class="stat-label">Completed</div>
				</div>
				
				<div class="stat-card warning">
					<div class="stat-value"><?php echo $pending_tasks; ?></div>
					<div class="stat-label">Pending</div>
				</div>
			</div>
			
			<div class="progress-label">
				<span>Progress</span>
				<span><?php echo $progress_percentage; ?>%</span>
			</div>
			<div class="progress-container">
				<div class="progress-bar" style="width: <?php echo $progress_percentage; ?>%"></div>
			</div>
		</div>
		
		<div class="card">
			<div class="card-header">
				<h2 class="card-title"><i class="fas fa-plus-circle"></i> Add New Task</h2>
			</div>
			
			<?php if (isset($task_error)): ?>
			<div class="message error">
				<i class="fas fa-exclamation-circle"></i>
				<?php echo $task_error; ?>
			</div>
			<?php endif; ?>
			
			<!-- Add Task Form -->
			<form method="POST" action="">
				<div class="input-group">
					<input type="text" name="task-name" id="task-name" placeholder="What do you need to do?" required>
					<button type="submit" id="add-task"><i class="fas fa-plus"></i> Add Task</button>
				</div>
			</form>
			
			<div class="card-header">
				<h2 class="card-title"><i class="fas fa-tasks"></i> Your Tasks</h2>
			</div>
			
			<ul class="tasks-list">
				<?php if (empty($tasks)): ?>
					<li class="empty-list">
						<i class="fas fa-clipboard-list"></i>
						No tasks yet. Add one above.
					</li>
				<?php else: ?>
					<?php foreach ($tasks as $task): ?>
					<li class="task-item <?php echo $task['completed'] ? 'completed' : ''; ?>" id="task-<?php echo $task['id']; ?>">
						<form method="POST" action="" style="display: flex; width: 100%; align-items: center; margin: 0;">
							<input type="hidden" name="action" value="toggle_task">
							<input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
							<input type="hidden" name="completed" value="<?php echo $task['completed'] ? '0' : '1'; ?>">
							<input type="checkbox" class="task-status task-checkbox" <?php echo $task['completed'] ? 'checked' : ''; ?> onChange="this.form.submit()">
							<span class="task-text"><?php echo htmlspecialchars($task['name']); ?></span>
							
							<div class="task-buttons">
								<button type="button" class="btn-success" onclick="toggleTaskStatus('<?php echo $task['id']; ?>', <?php echo $task['completed'] ? 'false' : 'true'; ?>)">
									<i class="fas <?php echo $task['completed'] ? 'fa-undo' : 'fa-check'; ?>"></i>
									<?php echo $task['completed'] ? 'Undo' : 'Complete'; ?>
								</button>
								<button type="button" class="btn-warning" onclick="showEditForm('<?php echo $task['id']; ?>')">
									<i class="fas fa-edit"></i> Edit
								</button>
								<button type="button" class="btn-danger" onclick="deleteTask('<?php echo $task['id']; ?>')">
									<i class="fas fa-trash"></i> Delete
								</button>
							</div>
						</form>
						
						<!-- Edit Form (Hidden by default) -->
						<form method="POST" action="" class="edit-form" id="edit-form-<?php echo $task['id']; ?>">
							<div class="input-group">
								<input type="hidden" name="action" value="edit_task">
								<input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
								<input type="text" name="new_task_name" value="<?php echo htmlspecialchars($task['name']); ?>" required>
								<button type="submit" class="btn-success"><i class="fas fa-save"></i> Save</button>
								<button type="button" class="btn-danger" onclick="hideEditForm('<?php echo $task['id']; ?>')">
									<i class="fas fa-times"></i> Cancel
								</button>
							</div>
						</form>
					</li>
					<?php endforeach; ?>
				<?php endif; ?>
			</ul>
		</div>
		
		<div class="card">
			<div class="card-header">
				<h2 class="card-title"><i class="fas fa-envelope"></i> Email Subscription</h2>
			</div>
			
			<p>Subscribe to receive hourly reminders of pending tasks directly in your inbox.</p>
			
			<?php if (isset($email_success)): ?>
			<div class="message success">
				<i class="fas fa-check-circle"></i>
				<?php echo $email_success; ?>
			</div>
			<?php endif; ?>
			
			<?php if (isset($email_error)): ?>
			<div class="message error">
				<i class="fas fa-exclamation-circle"></i>
				<?php echo $email_error; ?>
			</div>
			<?php endif; ?>
			
			<div class="subscription-box">
				<h3><i class="fas fa-bell"></i> Stay Updated</h3>
				
				<!-- Subscription Form -->
				<form method="POST" action="">
					<div class="input-group">
						<input type="email" name="email" placeholder="Enter your email address" required>
						<button type="submit" id="submit-email"><i class="fas fa-paper-plane"></i> Subscribe</button>
					</div>
				</form>
			</div>
		</div>
		
		<div style="text-align: center; margin-top: 30px; color: var(--gray-500); font-size: 14px;">
			<p>© <?php echo date('Y'); ?> Task Scheduler</p>
		</div>
	</div>

	<script>
		function deleteTask(taskId) {
			if (confirm('Are you sure you want to delete this task?')) {
				const form = document.createElement('form');
				form.method = 'POST';
				form.action = '';
				
				const actionInput = document.createElement('input');
				actionInput.type = 'hidden';
				actionInput.name = 'action';
				actionInput.value = 'delete_task';
				
				const taskIdInput = document.createElement('input');
				taskIdInput.type = 'hidden';
				taskIdInput.name = 'task_id';
				taskIdInput.value = taskId;
				
				form.appendChild(actionInput);
				form.appendChild(taskIdInput);
				document.body.appendChild(form);
				form.submit();
			}
		}
		
		function toggleTaskStatus(taskId, completed) {
			const form = document.createElement('form');
			form.method = 'POST';
			form.action = '';
			
			const actionInput = document.createElement('input');
			actionInput.type = 'hidden';
			actionInput.name = 'action';
			actionInput.value = 'toggle_task';
			
			const taskIdInput = document.createElement('input');
			taskIdInput.type = 'hidden';
			taskIdInput.name = 'task_id';
			taskIdInput.value = taskId;
			
			const completedInput = document.createElement('input');
			completedInput.type = 'hidden';
			completedInput.name = 'completed';
			completedInput.value = completed ? '1' : '0';
			
			form.appendChild(actionInput);
			form.appendChild(taskIdInput);
			form.appendChild(completedInput);
			document.body.appendChild(form);
			form.submit();
		}
		
		function showEditForm(taskId) {
			// Hide the task content
			document.getElementById('task-' + taskId).querySelector('form').style.display = 'none';
			
			// Show the edit form
			document.getElementById('edit-form-' + taskId).style.display = 'flex';
		}
		
		function hideEditForm(taskId) {
			// Show the task content
			document.getElementById('task-' + taskId).querySelector('form').style.display = 'flex';
			
			// Hide the edit form
			document.getElementById('edit-form-' + taskId).style.display = 'none';
		}
		
		// Auto hide messages after 5 seconds
		document.addEventListener('DOMContentLoaded', function() {
			const messages = document.querySelectorAll('.message');
			if (messages.length > 0) {
				setTimeout(function() {
					messages.forEach(function(message) {
						message.style.opacity = '0';
						message.style.transform = 'translateY(-10px)';
						message.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
						
						setTimeout(function() {
							message.style.display = 'none';
						}, 500);
					});
				}, 5000);
			}
		});
	</script>
</body>
</html>
