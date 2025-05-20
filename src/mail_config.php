<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

/**
 * Sends an email using SMTP (Gmail)
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $message Email message (HTML)
 * @return bool True if email was sent successfully, false otherwise
 */
function sendSmtpEmail(string $to, string $subject, string $message): bool {
    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = ''; // Gmail address
        $mail->Password = ''; // App password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->SMTPDebug = 0; // Set to 0 for no output, 1 for client messages, 2 for client and server messages
        
        // Increase timeout value to prevent connection issues
        $mail->Timeout = 60; // 60 seconds

        // Recipients
        $mail->setFrom('', 'Task Scheduler');
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $message));

        // Send the email
        $mail->send();
        
        // Log the successful email for debugging
        error_log("PHPMailer success: Email sent to {$to}");
        
        return true;
    } catch (Exception $e) {
        // Log error message
        error_log("PHPMailer error: {$mail->ErrorInfo}");
        
        // For development: Save the email content to a file
        $filename = 'email_' . uniqid() . '.html';
        file_put_contents(__DIR__ . '/' . $filename, $message);
        error_log("Email content saved to {$filename}");
        
        return false;
    }
} 