<?php
// Gmail SMTP Configuration for sending OTP emails
// IMPORTANT: Update these credentials with your actual Gmail and App Password

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls'); // or 'ssl' for port 465

// ⚠️ REPLACE WITH YOUR GMAIL CREDENTIALS
define('SMTP_USERNAME', 'pipaldebadal6@gmail.com'); // Your Gmail address
define('SMTP_PASSWORD', 'phqh srhx ungd janc'); // Your Gmail App Password (16 characters)
define('SMTP_FROM_EMAIL', 'pipaldebadal6@gmail.com'); // Same as username
define('SMTP_FROM_NAME', 'Museum booking');

// OTP Configuration
define('OTP_EXPIRY_MINUTES', 10); // OTP valid for 10 minutes
define('OTP_LENGTH', 6); // 6-digit OTP

// Email Template
function getOTPEmailTemplate($name, $otp)
{
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background-color: #f4f4f4;
                margin: 0;
                padding: 0;
            }
            .email-container {
                max-width: 600px;
                margin: 40px auto;
                background-color: #ffffff;
                border-radius: 10px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                overflow: hidden;
            }
            .header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 30px;
                text-align: center;
            }
            .header h1 {
                margin: 0;
                font-size: 24px;
            }
            .content {
                padding: 40px 30px;
            }
            .otp-box {
                background-color: #f8f9fa;
                border: 2px dashed #667eea;
                border-radius: 8px;
                padding: 20px;
                text-align: center;
                margin: 30px 0;
            }
            .otp-code {
                font-size: 36px;
                font-weight: bold;
                color: #667eea;
                letter-spacing: 8px;
                font-family: 'Courier New', monospace;
            }
            .message {
                color: #333;
                line-height: 1.6;
                font-size: 16px;
            }
            .footer {
                background-color: #f8f9fa;
                padding: 20px;
                text-align: center;
                color: #666;
                font-size: 14px;
            }
            .warning {
                color: #dc3545;
                font-size: 14px;
                margin-top: 20px;
            }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <div class='header'>
                <h1>🎟️ Museum Ticket Booking</h1>
            </div>
            <div class='content'>
                <p class='message'>Hello <strong>" . htmlspecialchars($name) . "</strong>,</p>
                <p class='message'>Thank you for signing up! To complete your registration, please verify your email address using the One-Time Password (OTP) below:</p>
                
                <div class='otp-box'>
                    <div class='otp-code'>" . $otp . "</div>
                </div>
                
                <p class='message'>This OTP is valid for <strong>10 minutes</strong>. Please do not share this code with anyone.</p>
                <p class='message'>If you didn't request this verification, please ignore this email.</p>
                
                <p class='warning'>⚠️ This is an automated email. Please do not reply to this message.</p>
            </div>
            <div class='footer'>
                <p>&copy; 2024 Museum Ticket Booking System. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}
?>