<?php
// Simple PHP Mailer class (lightweight alternative to PHPMailer library)
class SimpleMailer
{
    private $host;
    private $port;
    private $username;
    private $password;
    private $from_email;
    private $from_name;

    public function __construct($host, $port, $username, $password, $from_email, $from_name)
    {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->from_email = $from_email;
        $this->from_name = $from_name;
    }

    public function send($to_email, $to_name, $subject, $html_body)
    {
        try {
            // Create SMTP connection
            $smtp = fsockopen($this->host, $this->port, $errno, $errstr, 30);

            if (!$smtp) {
                throw new Exception("Failed to connect to SMTP server: $errstr ($errno)");
            }

            // Read server response
            $this->getResponse($smtp);

            // Send EHLO
            fputs($smtp, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
            $this->getResponse($smtp);

            // Start TLS
            fputs($smtp, "STARTTLS\r\n");
            $this->getResponse($smtp);

            // Enable crypto
            stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

            // Send EHLO again after TLS
            fputs($smtp, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
            $this->getResponse($smtp);

            // Authenticate
            fputs($smtp, "AUTH LOGIN\r\n");
            $this->getResponse($smtp);

            fputs($smtp, base64_encode($this->username) . "\r\n");
            $this->getResponse($smtp);

            fputs($smtp, base64_encode($this->password) . "\r\n");
            $this->getResponse($smtp);

            // Send MAIL FROM
            fputs($smtp, "MAIL FROM: <" . $this->from_email . ">\r\n");
            $this->getResponse($smtp);

            // Send RCPT TO
            fputs($smtp, "RCPT TO: <" . $to_email . ">\r\n");
            $this->getResponse($smtp);

            // Send DATA
            fputs($smtp, "DATA\r\n");
            $this->getResponse($smtp);

            // Build email headers and body
            $headers = "From: " . $this->from_name . " <" . $this->from_email . ">\r\n";
            $headers .= "To: " . $to_name . " <" . $to_email . ">\r\n";
            $headers .= "Subject: " . $subject . "\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "\r\n";

            $message = $headers . $html_body . "\r\n.\r\n";

            fputs($smtp, $message);
            $this->getResponse($smtp);

            // Send QUIT
            fputs($smtp, "QUIT\r\n");
            $this->getResponse($smtp);

            fclose($smtp);
            return true;

        } catch (Exception $e) {
            error_log("Mail Error: " . $e->getMessage());
            return false;
        }
    }

    private function getResponse($smtp)
    {
        $response = '';
        while ($line = fgets($smtp, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ')
                break;
        }
        return $response;
    }
}
?>