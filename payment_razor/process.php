<?php
session_start();
require_once '../backend/db_config.php';
require_once 'razorpay_config.php';

// Handle Cancel
if (isset($_GET['action']) && $_GET['action'] === 'cancel') {
    $ref = $_GET['ref'] ?? '';
    // Optional: Update DB to 'cancelled' if desired
    header("Location: result.php?status=failed&ref=$ref");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $success = true;
    $error = "Payment Failed";

    if (empty($_POST['razorpay_payment_id']) === false) {
        
        $razorpay_order_id = $_POST['razorpay_order_id'];
        $razorpay_payment_id = $_POST['razorpay_payment_id'];
        $razorpay_signature = $_POST['razorpay_signature'];
        $ref = $_POST['ref'];

        // Verify Signature
        $generated_signature = hash_hmac('sha256', $razorpay_order_id . "|" . $razorpay_payment_id, RAZORPAY_KEY_SECRET);

        if ($generated_signature == $razorpay_signature) {
                // Update Ticket Status
                // Note: 'payment_id' column is missing in schema, so we only update status. 
                // To store payment_id, run: ALTER TABLE tickets ADD COLUMN payment_id VARCHAR(255);
                $stmt = $pdo->prepare("UPDATE tickets SET payment_status = 'paid' WHERE booking_ref = ?");
                $stmt->execute([$ref]);

                // Redirect to Success
                header("Location: result.php?status=success&ref=$ref");
                exit();

            } catch (PDOException $e) {
                die("Database Error: " . $e->getMessage());
            }
        } else {
             $success = false;
             $error = "Invalid Signature";
        }
    } else {
        $success = false;
         $error = "Payment details missing";
    }

    if (!$success) {
         die("Payment Error: " . $error);
    }

} else {
    header("Location: ../backend/index.php");
}
?>