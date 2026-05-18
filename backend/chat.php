<?php
session_start();

// CORS for Credentials (Cookie)
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
// Allow valid origins or specific localhost ports
if (strpos($origin, 'localhost') !== false || strpos($origin, '127.0.0.1') !== false) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    // Fallback or strict, for dev 'localhost:3000' usually sends Origin
    header("Access-Control-Allow-Origin: http://localhost/minor/backend/index.html");
}
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

try {
    require_once 'db_config.php';

    // Get the posted data
    $data = json_decode(file_get_contents("php://input"));

    if (!$data || !isset($data->message) || !isset($data->chatState)) {
        http_response_code(400);
        echo json_encode(["error" => "Missing message or chatState"]);
        exit();
    }

    // Check Authentication
    if (!isset($_SESSION['user_id'])) {
        // Return a special message asking to login? or Error?
        // Let's allow the "start" message but block booking?
        // For simplicity, let's treat it as a state where we ask them to click login link if we can,
        // OR just return error 401. 
        // User requested: "user will login... then chatbot will generate ticket".
        // Let's send a friendly JSON response they can handle or display.
        // Actually, if we return 401, frontend might just error out. 
        // Let's allow the chat but restrict payment/confirmation? 
        // The user implied strict flow. Let's error if not logged in.
        // But wait, "messages" array is safer for chatbot UI.
        /*
        echo json_encode([
            "messages" => ["Please log in to use the chatbot ticketing system."],
            "chatState" => $data->chatState
        ]);
        exit();
        */
        // Actually, let's just use the session ID if present, if not, logic fails later? 
        // Better:
    }
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $userName = isset($_SESSION['name']) ? $_SESSION['name'] : 'Guest';

    $message = $data->message;
    $chatState = $data->chatState;

    // --- 1. HANDLE INIT REQUEST (Load state) ---
    if ($message === '__INIT__') {
        $savedState = getChatState($pdo, $userId);

        // Fetch recent chat history
        $historyMessages = [];
        if ($userId) {
            try {
                // Fetch last 50 messages
                $stmt = $pdo->prepare("SELECT sender, message, created_at FROM chat_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
                $stmt->execute([$userId]);
                $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Format for frontend
                $logs = array_reverse($logs); // Oldest first
                foreach ($logs as $log) {
                    $historyMessages[] = [
                        'sender' => $log['sender'],
                        'text' => $log['message'],
                    ];
                }

            } catch (PDOException $e) {
                // ignore
            }
        }

        // Return saved state or default
        $responseState = $savedState ? $savedState : $chatState;

        // Detect if user has an active flow (mid-conversation)
        $activeFlowSteps = [
            'select_ticket',
            'quantity',
            'date',
            'time',
            'confirm',
            'reschedule_fetch',
            'reschedule_date',
            'reschedule_time',
            'reschedule_confirm',
            'cancel_fetch',
            'cancel_confirm',
            'manage_pending'
        ];

        $hasActiveFlow = false;
        $flowDescription = '';

        if ($responseState && isset($responseState->bookingStep)) {
            $step = $responseState->bookingStep;
            $hasActiveFlow = in_array($step, $activeFlowSteps);

            if ($hasActiveFlow) {
                // Generate human-readable description
                $flowDescription = match ($step) {
                    'select_ticket' => 'You were selecting a ticket type',
                    'quantity' => 'You were selecting the number of tickets',
                    'date' => 'You were selecting a visit date',
                    'time' => 'You were selecting a time slot',
                    'confirm' => 'You were confirming your booking',
                    'reschedule_fetch', 'reschedule_date', 'reschedule_time', 'reschedule_confirm' => 'You were rescheduling a booking',
                    'cancel_fetch', 'cancel_confirm' => 'You were canceling a booking',
                    'manage_pending' => 'You were managing a pending booking',
                    default => 'You have an active booking session'
                };
            }
        }

        echo json_encode([
            "messages" => [], // No new messages to animate
            "history" => $historyMessages, // New field for restoration
            "chatState" => $responseState,
            "quickReplies" => [], // Assume empty to avoid clutter
            "hasActiveFlow" => $hasActiveFlow,
            "flowDescription" => $flowDescription
        ]);
        exit();
    }

    // --- 2. NORMAL MESSAGE PROCESSING ---

    // Log User Message
    logChatMessage($pdo, $userId, 'user', $message);

    $response = getBotResponses($message, strtolower($message), $chatState, $pdo);

    // Log Bot Response(s)
    foreach ($response['messages'] as $msgText) {
        logChatMessage($pdo, $userId, 'bot', $msgText);
    }

    // --- 3. SAVE STATE ---
    if ($userId) {
        saveChatState($pdo, $userId, $response['chatState']);
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Server Error: " . $e->getMessage(),
        "messages" => ["Oops! I'm having trouble connecting to my brain (Database Error). Please try again later."],
        "chatState" => isset($chatState) ? $chatState : null
    ]);
    exit();
}

function getBotResponses($message, $lowerMessage, $chatState, $pdo)
{
    if (!isset($chatState->language)) {
        $chatState->language = 'en';
    }
    $lang = $chatState->language;

    // Ensure ticket is an object if not present (PHP json_decode makes it object or null)
    if (!isset($chatState->ticket)) {
        $chatState->ticket = new stdClass();
    }

    if (!isset($chatState->bookingStep)) {
        $chatState->bookingStep = 'start';
    }

    $response = [
        "messages" => [],
        "quickReplies" => [],
        "chatState" => $chatState,
        "qrData" => null,
        "htmlReceipt" => null
    ];

    // Handle "Start Over" globally - clear state and start fresh
    if (strpos($lowerMessage, 'start over') !== false || strpos($lowerMessage, 'new booking') !== false || strpos($lowerMessage, 'नयी बुकिंग') !== false) {
        // CLEAR CHAT LOGS to prevent old messages from persisting
        if (isset($_SESSION['user_id'])) {
            try {
                $stmt = $pdo->prepare("DELETE FROM chat_logs WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
            } catch (PDOException $e) {
                // Silently fail, don't break the flow
                error_log("Error clearing chat logs: " . $e->getMessage());
            }
        }

        $chatState->language = $lang; // Preserve language
        $chatState->bookingStep = 'start_fresh'; // Use different state to skip pending check
        $chatState->ticket = new stdClass();
        // Clear any reschedule/cancel state
        unset($chatState->rescheduleTicketId);
        unset($chatState->rescheduleBookingRef);
        unset($chatState->cancelTicketId);
        unset($chatState->cancelBookingRef);
        unset($chatState->newDate);
        unset($chatState->newTime);

        // Return fresh start flow
        return getBotResponses('start_fresh', 'start_fresh', $chatState, $pdo);
    }

    // Handle explicit pending booking check request
    if (strpos($lowerMessage, 'check pending') !== false || strpos($lowerMessage, 'pending booking') !== false) {
        $chatState->bookingStep = 'check_pending';
        return getBotResponses('check_pending', 'check_pending', $chatState, $pdo);
    }

    // If message is "reschedule" and we are not already in a reschedule flow, switch flow
    if (strpos($lowerMessage, 'reschedule') !== false && strpos($chatState->bookingStep, 'reschedule') === false) {
        $chatState->bookingStep = 'reschedule_start';
    }

    switch ($chatState->bookingStep) {
        case 'check_pending':
            // Dedicated flow to check and manage pending bookings
            if (isset($_SESSION['user_id'])) {
                try {
                    $stmt = $pdo->prepare("SELECT * FROM tickets WHERE customer_id = ? AND payment_status = 'pending' ORDER BY id DESC LIMIT 1");
                    $stmt->execute([$_SESSION['user_id']]);
                    $pendingTicket = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($pendingTicket) {
                        $paymentLink = "http://localhost/minor/payment/pay.php?ref=" . $pendingTicket['booking_ref'];

                        $formattedAmount = number_format($pendingTicket['total_amount'], 2);
                        $msg = ($lang === 'en') ?
                            "I found a pending booking for <strong>{$pendingTicket['ticket_type']}</strong>.<br>Date: {$pendingTicket['visit_date']}<br>Time: {$pendingTicket['time_slot']}<br>Amount: ₹{$formattedAmount}" :
                            "<strong>{$pendingTicket['ticket_type']}</strong> के लिए एक लंबित बुकिंग मिली।<br>तारीख: {$pendingTicket['visit_date']}<br>समय: {$pendingTicket['time_slot']}<br>राशि: ₹{$formattedAmount}";

                        $askMsg = ($lang === 'en') ?
                            "What would you like to do?" :
                            "आप क्या करना चाहेंगे?";

                        $response['messages'] = [$msg, $askMsg];

                        // Set state for managing this booking
                        $chatState->ticket->bookingRef = $pendingTicket['booking_ref'];
                        $chatState->pendingTicketId = $pendingTicket['id'];
                        $chatState->bookingStep = 'manage_pending';

                        $response['quickReplies'] = ($lang === 'en') ?
                            [
                                ["label" => "💳 Complete Payment", "value" => "pay_pending"],
                                ["label" => "📅 Reschedule", "value" => "reschedule_pending"],
                                ["label" => "❌ Cancel", "value" => "cancel_pending"],
                                ["label" => "🔙 Back", "value" => "Start over"]
                            ] :
                            [
                                ["label" => "💳 भुगतान पूरा करें", "value" => "pay_pending"],
                                ["label" => "📅 पुनर्निर्धारित करें", "value" => "reschedule_pending"],
                                ["label" => "❌ रद्द करें", "value" => "cancel_pending"],
                                ["label" => "🔙 वापस", "value" => "Start over"]
                            ];
                        return $response;
                    } else {
                        $msg = ($lang === 'en') ?
                            "No pending bookings found." :
                            "कोई लंबित बुकिंग नहीं मिली।";
                        $response['messages'] = [$msg];
                        $chatState->bookingStep = 'done';
                        $response['quickReplies'] = [["label" => "Back", "value" => "Start over"]];
                    }
                } catch (PDOException $e) {
                    $response['messages'] = ["Error checking database."];
                }
            } else {
                $msg = ($lang === 'en') ? "Please log in first." : "कृपया पहले लॉग इन करें।";
                $response['messages'] = [$msg];
            }
            break;

        case 'manage_pending':
            // Handle actions on pending booking
            if (strpos($lowerMessage, 'pay') !== false) {
                // Send payment link
                $ref = $chatState->ticket->bookingRef;
                $paymentLink = "http://localhost/minor/payment/pay.php?ref=" . $ref;

                $linkMsg = ($lang === 'en') ?
                    "<a href='{$paymentLink}' target='_blank' class='inline-block bg-indigo-600 text-white px-4 py-2 rounded mt-2 hover:bg-indigo-700'>Complete Payment</a>" :
                    "<a href='{$paymentLink}' target='_blank' class='inline-block bg-indigo-600 text-white px-4 py-2 rounded mt-2 hover:bg-indigo-700'>भुगतान पूरा करें</a>";

                $response['messages'] = [$linkMsg];
                $response['quickReplies'] = [["label" => "Back", "value" => "Start over"]];
            } elseif (strpos($lowerMessage, 'reschedule') !== false) {
                // Go to reschedule flow
                $chatState->rescheduleTicketId = $chatState->pendingTicketId;
                $chatState->rescheduleBookingRef = $chatState->ticket->bookingRef;
                $chatState->bookingStep = 'reschedule_date';

                $msg = ($lang === 'en') ? "Please select the NEW date from the calendar:" : "कृपया कैलेंडर से नई तारीख चुनें:";
                $response['messages'] = [$msg];
                $response['showDatePicker'] = true;
                $response['quickReplies'] = ($lang === 'en') ?
                    [["label" => "Start Over", "value" => "Start over"]] :
                    [["label" => "फिर से शुरू करें", "value" => "Start over"]];
            } elseif (strpos($lowerMessage, 'cancel') !== false) {
                // Go to cancel flow
                $chatState->cancelTicketId = $chatState->pendingTicketId;
                $chatState->cancelBookingRef = $chatState->ticket->bookingRef;
                $chatState->bookingStep = 'cancel_confirm';

                $msg = ($lang === 'en') ?
                    "Are you sure you want to cancel this booking?" :
                    "क्या आप वाकई इस बुकिंग को रद्द करना चाहते हैं?";
                $response['messages'] = [$msg];
                $response['quickReplies'] = ($lang === 'en') ?
                    [["label" => "Yes, Cancel", "value" => "Confirm Cancel"], ["label" => "No, Keep it", "value" => "No"]] :
                    [["label" => "हाँ, रद्द करें", "value" => "Confirm Cancel"], ["label" => "नहीं, रखें", "value" => "No"]];
            }
            break;

        case 'start_fresh':
            // Fresh start - skip pending booking check
            $chatState->bookingStep = 'language';
            $response['messages'] = ["Welcome to the National Museum of India! / अद्भुत संग्रहालय में आपका स्वागत है!", "Please select your language. / कृपया अपनी भाषा चुनें।"];
            $response['quickReplies'] = [
                ["label" => "English", "value" => "English"],
                ["label" => "हिन्दी", "value" => "हिन्दी"]
            ];
            break;

        case 'start':
            // Check for Pending Bookings (if logged in) and OFFER to check them
            $hasPending = false;
            if (isset($_SESSION['user_id'])) {
                try {
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tickets WHERE customer_id = ? AND payment_status = 'pending'");
                    $stmt->execute([$_SESSION['user_id']]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $hasPending = ($result['count'] > 0);
                } catch (PDOException $e) {
                    // Ignore error
                }
            }

            $chatState->bookingStep = 'language';

            if ($hasPending) {
                $msg = ($lang === 'en') ?
                    "Welcome back! You have pending bookings. Would you like to check them or start a new booking?" :
                    "वापसी पर स्वागत है! आपके पास लंबित बुकिंग हैं। क्या आप उन्हें देखना चाहते हैं या नई बुकिंग शुरू करना चाहते हैं?";

                $response['messages'] = [$msg];
                $response['quickReplies'] = ($lang === 'en') ?
                    [
                        ["label" => "📋 Check Pending Bookings", "value" => "check pending"],
                        ["label" => "🎫 Start New Booking", "value" => "new booking"]
                    ] :
                    [
                        ["label" => "📋 लंबित बुकिंग देखें", "value" => "check pending"],
                        ["label" => "🎫 नई बुकिंग शुरू करें", "value" => "new booking"]
                    ];
            } else {
                $response['messages'] = ["Welcome to the National Museum of India! / अद्भुत संग्रहालय में आपका स्वागत है!", "Please select your language. / कृपया अपनी भाषा चुनें।"];
                $response['quickReplies'] = [
                    ["label" => "English", "value" => "English"],
                    ["label" => "हिन्दी", "value" => "हिन्दी"]
                ];
            }
            break;

        case 'language':
            if (strpos($lowerMessage, 'english') !== false) {
                $chatState->language = 'en';
            } elseif (strpos($lowerMessage, 'हिन्दी') !== false || strpos($lowerMessage, 'hindi') !== false) {
                $chatState->language = 'hi';
            } else {
                $response['messages'] = ["Please select a language. / कृपया अपनी भाषा चुनें।"];
                $response['quickReplies'] = [
                    ["label" => "English", "value" => "English"],
                    ["label" => "हिन्दी", "value" => "हिन्दी"]
                ];
                return $response;
            }

            $chatState->bookingStep = 'ticket_type';
            $msg = ($chatState->language === 'en') ?
                "Great! I can help you book tickets or reschedule an existing one. What would you like to do?" :
                "बढ़िया! मैं टिकट बुक करने या मौजूदा टिकट को पुनर्निर्धारित करने में आपकी मदद कर सकता हूँ। आप क्या करना चाहेंगे?";

            // Offering Reschedule as an option early on
            $response['messages'] = [$msg];
            $response['quickReplies'] = ($chatState->language === 'en') ?
                [
                    ["label" => "Book Ticket", "value" => "Book Ticket"],
                    ["label" => "Reschedule", "value" => "Reschedule"],
                    ["label" => "Cancel Ticket", "value" => "Cancel Ticket"]
                ] :
                [
                    ["label" => "टिकट बुक करें", "value" => "Book Ticket"],
                    ["label" => "पुनर्निर्धारित करें", "value" => "Reschedule"],
                    ["label" => "टिकट रद्द करें", "value" => "Cancel Ticket"]
                ];
            break;

        // New intermediate step to direct flow
        case 'ticket_type':
            // Reuse this state name but logic checks for "Book" vs "Reschedule" or specific ticket types
            // Actually, let's keep the flow simple: If they picked language, we showed them options.

            if (strpos($lowerMessage, 'reschedule') !== false || strpos($lowerMessage, 'पुनर्निर्धारित') !== false) {
                $chatState->bookingStep = 'reschedule_start';
                return getBotResponses('reschedule', 'reschedule', $chatState, $pdo);
            }
            if (strpos($lowerMessage, 'cancel') !== false || strpos($lowerMessage, 'रद्द') !== false) {
                $chatState->bookingStep = 'cancel_start';
                return getBotResponses('cancel', 'cancel', $chatState, $pdo);
            }
            if (strpos($lowerMessage, 'history') !== false || strpos($lowerMessage, 'इतिहास') !== false) {
                $chatState->bookingStep = 'history_fetch';
                return getBotResponses('history', 'history', $chatState, $pdo);
            }

            // If they want to book (or just said "start" or defaulted), show ticket types
            $chatState->bookingStep = 'select_ticket'; // Renaming internal step for clarity
            $msg = ($lang === 'en') ? "What would you like to see?" : "आप क्या देखना चाहेंगे?";
            $response['messages'] = [$msg];
            $response['quickReplies'] = ($lang === 'en') ?
                [
                    ["label" => "General Admission (₹25)", "value" => "General Admission"],
                    ["label" => "Special Exhibit: Cosmos (₹40)", "value" => "Special Exhibit: Cosmos"],
                    ["label" => "Both (₹55)", "value" => "Both"],
                    ["label" => "Start Over", "value" => "Start over"]
                ] :
                [
                    ["label" => "सामान्य प्रवेश (₹25)", "value" => "सामान्य प्रवेश"],
                    ["label" => "विशेष प्रदर्शनी: ब्रह्मांड (₹40)", "value" => "विशेष प्रदर्शनी: ब्रह्मांड"],
                    ["label" => "दोनों (₹55)", "value" => "दोनों"],
                    ["label" => "फिर से शुरू करें", "value" => "Start over"]
                ];
            break;

        case 'select_ticket':
            if (strpos($lowerMessage, 'general') !== false || strpos($lowerMessage, 'सामान्य') !== false) {
                $chatState->ticket->type = 'General Admission';
                $chatState->ticket->pricePer = 25;
            } elseif (strpos($lowerMessage, 'special') !== false || strpos($lowerMessage, 'cosmos') !== false || strpos($lowerMessage, 'ब्रह्मांड') !== false) {
                $chatState->ticket->type = 'Special Exhibit: Cosmos';
                $chatState->ticket->pricePer = 40;
            } elseif (strpos($lowerMessage, 'both') !== false || strpos($lowerMessage, 'दोनों') !== false) {
                $chatState->ticket->type = 'Combined Ticket';
                $chatState->ticket->pricePer = 55;
            } else {
                $msg = ($lang === 'en') ? "Sorry, I didn't catch that. Please select a ticket type." : "क्षमा करें, मैं समझा नहीं। कृपया एक टिकट प्रकार चुनें।";
                $response['messages'] = [$msg];
                $response['quickReplies'] = ($lang === 'en') ?
                    [["label" => "General Admission (₹25)", "value" => "General Admission"], ["label" => "Special Exhibit: Cosmos (₹40)", "value" => "Special Exhibit: Cosmos"], ["label" => "Both (₹55)", "value" => "Both"]] :
                    [["label" => "सामान्य प्रवेश (₹25)", "value" => "सामान्य प्रवेश"], ["label" => "विशेष प्रदर्शनी: ब्रह्मांड (₹40)", "value" => "विशेष प्रदर्शनी: ब्रह्मांड"], ["label" => "दोनों (₹55)", "value" => "दोनों"]];
                return $response;
            }

            $chatState->bookingStep = 'quantity';
            $msg_qty = ($lang === 'en') ? "How many tickets for \"{$chatState->ticket->type}\"?" : "\"{$chatState->ticket->type}\" के लिए कितने टिकट?";
            $response['messages'] = [$msg_qty];
            $response['quickReplies'] = [
                ["label" => "1", "value" => "1"],
                ["label" => "2", "value" => "2"],
                ["label" => "3", "value" => "3"],
                ["label" => "4", "value" => "4"]
            ];
            break;

        case 'quantity':
            $quantity = parseQuantity($lowerMessage);
            if ($quantity <= 0) {
                $msg = ($lang === 'en') ? "Please enter a valid number of tickets." : "कृपया टिकटों की एक वैध संख्या दर्ज करें।";
                $response['messages'] = [$msg];
                $response['quickReplies'] = [["label" => "1", "value" => "1"], ["label" => "2", "value" => "2"], ["label" => "3", "value" => "3"], ["label" => "4", "value" => "4"], ["label" => "Start Over", "value" => "Start over"]];
                return $response;
            }
            $chatState->ticket->quantity = $quantity;
            $chatState->bookingStep = 'date';

            $msg_date = ($lang === 'en') ? "Please select a visit date from the calendar:" : "कृपया कैलेंडर से यात्रा की तारीख चुनें:";
            $response['messages'] = [$msg_date];
            $response['showDatePicker'] = true;
            // Removed text quick replies for date, but adding Start Over
            $response['quickReplies'] = ($lang === 'en') ?
                [["label" => "Start Over", "value" => "Start over"]] :
                [["label" => "फिर से शुरू करें", "value" => "Start over"]];
            break;

        case 'date':
            // Validate Date
            // The frontend sends YYYY-MM-DD or similar
            // We can do a basic check
            $inputDate = trim($message);
            // Basic regex check for YYYY-MM-DD or standard date string
            // Or just accept it if it's not empty
            if (empty($inputDate)) {
                $msg = ($lang === 'en') ? "Please select a valid date." : "कृपया एक मान्य तारीख चुनें।";
                $response['messages'] = [$msg];
                $response['showDatePicker'] = true;
                $response['quickReplies'] = ($lang === 'en') ?
                    [["label" => "Start Over", "value" => "Start over"]] :
                    [["label" => "फिर से शुरू करें", "value" => "Start over"]];
                return $response;
            }

            // --- CHECK TICKET AVAILABILITY ---
            try {
                // Count total tickets booked for this date (pending + paid)
                // Exclude cancelled
                $stmt = $pdo->prepare("SELECT SUM(quantity) as total_booked FROM tickets WHERE visit_date = ? AND payment_status != 'cancelled'");
                $stmt->execute([$inputDate]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $totalBooked = $result['total_booked'] ? (int) $result['total_booked'] : 0;
                $dailyLimit = 2000;

                $requestedWithError = isset($chatState->ticket->quantity) ? (int) $chatState->ticket->quantity : 1;

                if (($totalBooked + $requestedWithError) > $dailyLimit) {
                    $available = $dailyLimit - $totalBooked;
                    $available = ($available < 0) ? 0 : $available;

                    if ($available == 0) {
                        $msg = ($lang === 'en') ?
                            "Sorry, tickets are sold out for {$inputDate}. Please choose another date." :
                            "क्षमा करें, {$inputDate} के लिए टिकट बिक चुके हैं। कृपया कोई अन्य तारीख चुनें।";
                    } else {
                        $msg = ($lang === 'en') ?
                            "Sorry, only {$available} tickets are available for {$inputDate}. You requested {$requestedWithError}." :
                            "क्षमा करें, {$inputDate} के लिए केवल {$available} टिकट उपलब्ध हैं। आपने {$requestedWithError} का अनुरोध किया था।";
                    }

                    $response['messages'] = [$msg];
                    $response['showDatePicker'] = true;
                    // Add Start Over option here too
                    $response['quickReplies'] = ($lang === 'en') ?
                        [["label" => "Start Over", "value" => "Start over"]] :
                        [["label" => "फिर से शुरू करें", "value" => "Start over"]];
                    return $response;
                }

            } catch (PDOException $e) {
                // Determine what to do on error - maybe allow for now or block?
                // Let's log it and allow to avoid blocking user flow if DB glitches, or block for safety.
                // Blocking is safer for "limit" logic.
                error_log("Limit check error: " . $e->getMessage());
                $msg = ($lang === 'en') ? "System check failed. Please try again." : "सिस्टम चेक विफल रहा। कृपया पुनः प्रयास करें।";
                $response['messages'] = [$msg];
                $response['showDatePicker'] = true;
                $response['quickReplies'] = ($lang === 'en') ?
                    [["label" => "Start Over", "value" => "Start over"]] :
                    [["label" => "फिर से शुरू करें", "value" => "Start over"]];
                return $response;
            }

            $chatState->ticket->date = $inputDate;
            $chatState->bookingStep = 'time';
            $msg_time = ($lang === 'en') ? "Please select an available time slot:" : "कृपया एक उपलब्ध टाइम स्लॉट चुनें:";
            $response['messages'] = [$msg_time];
            $response['quickReplies'] = [
                ["label" => "10:00 AM - 12:00 PM", "value" => "10:00 AM"],
                ["label" => "12:00 PM - 2:00 PM", "value" => "12:00 PM"],
                ["label" => "2:00 PM - 4:00 PM", "value" => "2:00 PM"],
                ["label" => ($lang === 'en') ? "Start Over" : "फिर से शुरू करें", "value" => "Start over"]
            ];
            break;

        case 'time':
            $chatState->ticket->time = $message;
            $chatState->bookingStep = 'confirm';

            $t = $chatState->ticket;
            // Sanitize quantity and price to prevent calculation errors from formatted strings
            $qty = intval(str_replace(',', '', $t->quantity));
            $price = intval(str_replace(',', '', $t->pricePer));
            $t->quantity = $qty;

            $rawTotal = $price * $qty;
            $chatState->ticket->total = $rawTotal;
            $total = number_format($rawTotal, 2);

            if ($lang === 'en') {
                $response['messages'] = [
                    "Great! Let's review your order:",
                    "<strong>Type:</strong> {$t->type}<br><strong>Tickets:</strong> {$t->quantity}<br><strong>Date:</strong> {$t->date}<br><strong>Time:</strong> {$t->time}<br><strong class=\"text-lg\">Total: ₹{$total}</strong>",
                    "Does this look correct?"
                ];
                $response['quickReplies'] = [
                    ["label" => "Yes, proceed to payment", "value" => "Yes"],
                    ["label" => "No, start over", "value" => "Start over"]
                ];
            } else {
                $response['messages'] = [
                    "बढ़िया! आइए आपके ऑर्डर की समीक्षा करें:",
                    "<strong>प्रकार:</strong> {$t->type}<br><strong>टिकट:</strong> {$t->quantity}<br><strong>तारीख:</strong> {$t->date}<br><strong>समय:</strong> {$t->time}<br><strong class=\"text-lg\">कुल: ₹{$total}</strong>",
                    "क्या यह सही है?"
                ];
                $response['quickReplies'] = [
                    ["label" => "हां, भुगतान के लिए आगे बढ़ें", "value" => "हां"],
                    ["label" => "नहीं, फिर से शुरू करें", "value" => "नहीं"]
                ];
            }
            break;

        case 'confirm':
            if (strpos($lowerMessage, 'yes') !== false || strpos($lowerMessage, 'हां') !== false) {

                // Get User ID from Session
                if (isset($_SESSION['user_id'])) {
                    $chatState->ticket->customerId = $_SESSION['user_id'];
                } else {
                    $response['messages'] = [
                        ($lang === 'en') ? "You are not logged in. Please log in first." : "आप लॉग इन नहीं हैं। कृपया पहले लॉग इन करें।"
                    ];
                    $chatState->bookingStep = 'start';
                    $response['quickReplies'] = ($lang === 'en') ?
                        [["label" => "Start Over", "value" => "Start over"]] :
                        [["label" => "फिर से शुरू करें", "value" => "Start over"]];
                    return $response;
                }


                // Recalculate total for safety
                $t = $chatState->ticket;
                $qty = intval(str_replace(',', '', $t->quantity));
                $price = intval(str_replace(',', '', $t->pricePer));
                $total = $qty * $price;


                $chatState->ticket->total = $total;
                $formattedTotal = number_format($total, 2);
                $bookingRef = 'TKT' . time();
                $chatState->ticket->bookingRef = $bookingRef;

                // --- DATABASE INSERT (PENDING) ---
                try {
                    $stmt = $pdo->prepare("INSERT INTO tickets (booking_ref, customer_id, ticket_type, quantity, visit_date, time_slot, total_amount, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
                    $stmt->execute([
                        $bookingRef,
                        $chatState->ticket->customerId,
                        $chatState->ticket->type,
                        $chatState->ticket->quantity,
                        $chatState->ticket->date,
                        $chatState->ticket->time,
                        $chatState->ticket->total
                    ]);

                    // Generate Payment Link
                    // Assuming project root is /minor/
                    $paymentLink = "http://localhost/minor/payment/pay.php?ref=" . $bookingRef;

                    $msg = ($lang === 'en') ?
                        "Booking created! Please complete your payment securely via our gateway." :
                        "बुकिंग बनाई गई! कृपया हमारे गेटवे के माध्यम से अपना भुगतान पूरा करें।";

                    $linkMsg = ($lang === 'en') ?
                        "<a href='{$paymentLink}' target='_blank' class='inline-block bg-indigo-600 text-white px-4 py-2 rounded mt-2 hover:bg-indigo-700'>Pay Now (₹{$formattedTotal})</a>" :
                        "<a href='{$paymentLink}' target='_blank' class='inline-block bg-indigo-600 text-white px-4 py-2 rounded mt-2 hover:bg-indigo-700'>अभी भुगतान करें (₹{$formattedTotal})</a>";

                    $response['messages'] = [$msg, $linkMsg];

                    // Reset or Set to 'waiting_for_payment' if we want them to say "I paid"
                    // But since we have a result page, let's just reset or done.
                    $chatState->bookingStep = 'payment_pending';
                    // We can add a "Check Status" button
                    $response['quickReplies'] = ($lang === 'en') ?
                        [["label" => "Check Status", "value" => "Check Status"], ["label" => "Start New Booking", "value" => "Start over"]] :
                        [["label" => "स्थिति जाँचे", "value" => "Check Status"], ["label" => "नयी बुकिंग", "value" => "Start over"]];

                } catch (PDOException $e) {
                    error_log("DB Insert Error: " . $e->getMessage());
                    $response['messages'] = ["System Error: Could not create booking."];
                    $chatState->bookingStep = 'start';
                    $response['quickReplies'] = ($lang === 'en') ?
                        [["label" => "Start Over", "value" => "Start over"]] :
                        [["label" => "फिर से शुरू करें", "value" => "Start over"]];
                }

            } else {
                $chatState->bookingStep = 'start';
                $chatState->ticket = new stdClass();
                return getBotResponses('start', 'start', $chatState, $pdo);
            }
            break;

        case 'payment_pending':
            // Allow user to manually check or just say "I paid"
            // Start over if they want
            if (strpos($lowerMessage, 'start') !== false) {
                $chatState->bookingStep = 'start';
                return getBotResponses('start', 'start', $chatState, $pdo);
            }

            // Check Status Logic
            $ref = $chatState->ticket->bookingRef;
            try {
                $stmt = $pdo->prepare("SELECT payment_status FROM tickets WHERE booking_ref = ?");
                $stmt->execute([$ref]);
                $t = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($t && $t['payment_status'] === 'paid') {
                    $title = ($lang === 'en') ? "Payment Received!" : "भुगतान प्राप्त हुआ!";
                    $response['messages'] = [$title, ($lang === 'en') ? "Your ticket is confirmed." : "आपका टिकट पक्का हो गया है।"];
                    $chatState->bookingStep = 'done';
                    $response['quickReplies'] = ($lang === 'en') ?
                        [["label" => "Start New", "value" => "Start over"]] :
                        [["label" => "नई शुरुआत", "value" => "Start over"]];
                } else {
                    $response['messages'] = [
                        ($lang === 'en') ? "Payment is still pending. Please complete the payment." : "भुगतान अभी भी लंबित है। कृपया भुगतान पूरा करें।",
                        "<a href='http://localhost/minor/payment/pay.php?ref={$ref}' target='_blank' class='text-indigo-600 underline'>Pay Link</a>"
                    ];
                    $response['quickReplies'] = ($lang === 'en') ?
                        [["label" => "Check Status", "value" => "Check Status"], ["label" => "Start Over", "value" => "Start over"]] :
                        [["label" => "स्थिति जाँचे", "value" => "Check Status"], ["label" => "फिर से शुरू करें", "value" => "Start over"]];
                }

            } catch (PDOException $e) {
                $response['messages'] = ["Error checking status."];
                $response['quickReplies'] = ($lang === 'en') ?
                    [["label" => "Start Over", "value" => "Start over"]] :
                    [["label" => "फिर से शुरू करें", "value" => "Start over"]];
            }
            break;

        case 'done':
            if (strpos($lowerMessage, 'start') !== false || strpos($lowerMessage, 'book') !== false) {
                $chatState->bookingStep = 'start';
                $chatState->ticket = new stdClass();
                return getBotResponses('start', 'start', $chatState, $pdo);
            }
            if (strpos($lowerMessage, 'reschedule') !== false || strpos($lowerMessage, 'पुनर्निर्धारित') !== false) {
                $chatState->bookingStep = 'reschedule_start';
                // Fall through to reschedule_start below or recursive call?
                // Let's recursively call to render the reschedule prompt immediately
                return getBotResponses('reschedule', 'reschedule', $chatState, $pdo);
            }
            // Default "done" handler
            $response['messages'] = [($lang === 'en') ? "Let me know if you need anything else." : "बताएं कि क्या आपको किसी और चीज़ की ज़रूरत है।"];
            $response['quickReplies'] = ($lang === 'en') ?
                [["label" => "Book another", "value" => "Start over"], ["label" => "Reschedule", "value" => "Reschedule"], ["label" => "Cancel Ticket", "value" => "Cancel Ticket"]] :
                [["label" => "एक और बुक करें", "value" => "Start over"], ["label" => "पुनर्निर्धारित करें", "value" => "Reschedule"], ["label" => "टिकट रद्द करें", "value" => "Cancel Ticket"]];
            break;

        // --- RESCHEDULE FLOW ---
        case 'reschedule_start':
            $msg = ($lang === 'en') ? "I can help you reschedule. Please enter your Booking ID (e.g., TKT123...)." : "मैं आपकी मदद कर सकता हूँ। कृपया अपनी बुकिंग आईडी दर्ज करें (उदा. TKT123...)।";
            $response['messages'] = [$msg];
            $chatState->bookingStep = 'reschedule_fetch';
            $response['quickReplies'] = ($lang === 'en') ?
                [["label" => "Start Over", "value" => "Start over"]] :
                [["label" => "फिर से शुरू करें", "value" => "Start over"]];
            break;

        case 'reschedule_fetch':
            $bookingId = trim($message);

            // Check DB
            try {
                $stmt = $pdo->prepare("SELECT * FROM tickets WHERE booking_ref = ?");
                $stmt->execute([$bookingId]);
                $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($ticket) {
                    $chatState->rescheduleTicketId = $ticket['id'];
                    $chatState->rescheduleBookingRef = $ticket['booking_ref'];
                    $chatState->ticket->type = $ticket['ticket_type']; // For display

                    $msg = ($lang === 'en') ?
                        "Found your booking for {$ticket['ticket_type']} on {$ticket['visit_date']} at {$ticket['time_slot']}. Please select the NEW date:" :
                        "{$ticket['visit_date']} को {$ticket['time_slot']} बजे {$ticket['ticket_type']} के लिए आपकी बुकिंग मिली। कृपया नई तारीख चुनें:";
                    $response['messages'] = [$msg];
                    $response['showDatePicker'] = true;
                    $response['quickReplies'] = ($lang === 'en') ?
                        [["label" => "Start Over", "value" => "Start over"]] :
                        [["label" => "फिर से शुरू करें", "value" => "Start over"]];
                    $chatState->bookingStep = 'reschedule_date';
                } else {
                    $msg = ($lang === 'en') ? "I couldn't find that unique ID. Please try again." : "मुझे वह आईडी नहीं मिली। कृपया पुनः प्रयास करें।";
                    $response['messages'] = [$msg];
                    // Stay in reschedule_fetch
                    $response['quickReplies'] = ($lang === 'en') ?
                        [["label" => "Start Over", "value" => "Start over"]] :
                        [["label" => "फिर से शुरू करें", "value" => "Start over"]];
                }
            } catch (PDOException $e) {
                // Error handling
                $response['messages'] = ["Error checking database."];
                $response['quickReplies'] = ($lang === 'en') ?
                    [["label" => "Start Over", "value" => "Start over"]] :
                    [["label" => "फिर से शुरू करें", "value" => "Start over"]];
            }
            break;

        case 'reschedule_date':
            $chatState->newDate = $message;
            $chatState->bookingStep = 'reschedule_time';
            $msg = ($lang === 'en') ? "Select a new time:" : "एक नया समय चुनें:";
            $response['messages'] = [$msg];
            $response['quickReplies'] = [
                ["label" => "10:00 AM", "value" => "10:00 AM"],
                ["label" => "12:00 PM", "value" => "12:00 PM"],
                ["label" => "2:00 PM", "value" => "2:00 PM"],
                ["label" => ($lang === 'en') ? "Start Over" : "फिर से शुरू करें", "value" => "Start over"]
            ];
            break;

        case 'reschedule_time':
            $chatState->newTime = $message;
            $chatState->bookingStep = 'reschedule_confirm';

            $msg = ($lang === 'en') ?
                "Reschedule to {$chatState->newDate} at {$chatState->newTime}?" :
                "{$chatState->newDate} को {$chatState->newTime} बजे के लिए पुनर्निर्धारित करें?";
            $response['messages'] = [$msg];
            $response['quickReplies'] = ($lang === 'en') ?
                [["label" => "Confirm", "value" => "Confirm"], ["label" => "Cancel", "value" => "Cancel"], ["label" => "Start Over", "value" => "Start over"]] :
                [["label" => "पुष्टि करें", "value" => "Confirm"], ["label" => "रद्द करें", "value" => "Cancel"], ["label" => "फिर से शुरू करें", "value" => "Start over"]];
            break;

        case 'reschedule_confirm':
            if (strpos($lowerMessage, 'confirm') !== false || strpos($lowerMessage, 'पुष्टि') !== false) {
                try {
                    $stmt = $pdo->prepare("UPDATE tickets SET visit_date = ?, time_slot = ? WHERE id = ?");
                    $stmt->execute([$chatState->newDate, $chatState->newTime, $chatState->rescheduleTicketId]);

                    $msg = ($lang === 'en') ? "Your ticket has been rescheduled!" : "आपका टिकट पुनर्निर्धारित कर दिया गया है!";
                    $response['messages'] = [$msg];
                    $chatState->bookingStep = 'done';
                    // Show receipt again? Maybe just text for now.
                    $response['quickReplies'] = ($lang === 'en') ?
                        [["label" => "Back", "value" => "Start over"]] :
                        [["label" => "वापस", "value" => "Start over"]];

                } catch (PDOException $e) {
                    $response['messages'] = ["Error updating ticket."];
                    $response['quickReplies'] = ($lang === 'en') ?
                        [["label" => "Start Over", "value" => "Start over"]] :
                        [["label" => "फिर से शुरू करें", "value" => "Start over"]];
                }
            } else {
                $chatState->bookingStep = 'done';
                $response['messages'] = ["Cancelled."];
                $response['quickReplies'] = ($lang === 'en') ?
                    [["label" => "Back", "value" => "Start over"]] :
                    [["label" => "वापस", "value" => "Start over"]];
            }
            break;

        // --- CANCEL FLOW ---
        case 'cancel_start':
            $msg = ($lang === 'en') ? "I can help you cancel your ticket. Please enter your Booking ID (e.g., TKT123...)." : "मैं आपका टिकट रद्द करने में मदद कर सकता हूँ। कृपया अपनी बुकिंग आईडी दर्ज करें (उदा. TKT123...)।";
            $response['messages'] = [$msg];
            $chatState->bookingStep = 'cancel_fetch';
            break;

        case 'cancel_fetch':
            $bookingId = trim($message);

            // Check DB
            try {
                $stmt = $pdo->prepare("SELECT * FROM tickets WHERE booking_ref = ?");
                $stmt->execute([$bookingId]);
                $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($ticket) {
                    if ($ticket['payment_status'] === 'cancelled') {
                        $msg = ($lang === 'en') ? "This ticket is already cancelled." : "यह टिकट पहले ही रद्द कर दिया गया है।";
                        $response['messages'] = [$msg];
                        $chatState->bookingStep = 'done';
                        $response['quickReplies'] = [["label" => "Back", "value" => "Start over"]];
                    } else {
                        $chatState->cancelTicketId = $ticket['id'];
                        $chatState->cancelBookingRef = $ticket['booking_ref'];

                        $msg = ($lang === 'en') ?
                            "Found booking for {$ticket['ticket_type']} (mount: ₹{$ticket['total_amount']}). Are you sure you want to cancel?" :
                            "{$ticket['ticket_type']} (राशि: ₹{$ticket['total_amount']}) के लिए बुकिंग मिली। क्या आप वाकई रद्द करना चाहते हैं?";
                        $response['messages'] = [$msg];
                        $response['quickReplies'] = ($lang === 'en') ?
                            [["label" => "Yes, Cancel", "value" => "Confirm Cancel"], ["label" => "No, Keep it", "value" => "No"]] :
                            [["label" => "हाँ, रद्द करें", "value" => "Confirm Cancel"], ["label" => "नहीं, रखें", "value" => "No"]];
                        $chatState->bookingStep = 'cancel_confirm';
                    }
                } else {
                    $msg = ($lang === 'en') ? "I couldn't find that booking ID. Please try again." : "मुझे वह बुकिंग आईडी नहीं मिली। कृपया पुनः प्रयास करें।";
                    $response['messages'] = [$msg];
                    // Stay in cancel_fetch
                }
            } catch (PDOException $e) {
                // Error handling
                $response['messages'] = ["Error checking database."];
            }
            break;

        case 'cancel_confirm':
            if (strpos($lowerMessage, 'confirm') !== false || strpos($lowerMessage, 'yes') !== false || strpos($lowerMessage, 'हाँ') !== false) {
                try {
                    $stmt = $pdo->prepare("UPDATE tickets SET payment_status = 'cancelled' WHERE id = ?");
                    $stmt->execute([$chatState->cancelTicketId]);

                    $msg = ($lang === 'en') ? "Your ticket has been cancelled." : "आपका टिकट रद्द कर दिया गया है।";
                    $response['messages'] = [$msg];
                    $chatState->bookingStep = 'done';
                    $response['quickReplies'] = [["label" => "Back", "value" => "Start over"]];

                } catch (PDOException $e) {
                    $response['messages'] = ["Error updating ticket."];
                }
            } else {
                $chatState->bookingStep = 'done';
                $response['messages'] = [($lang === 'en') ? "Cancellation aborted." : "रद्दीकरण निरस्त।"];
                $response['quickReplies'] = [["label" => "Back", "value" => "Start over"]];
            }
            break;


        // --- CHAT HISTORY FLOW ---
        case 'history_fetch':
            // Get User ID from Session
            if (isset($_SESSION['user_id'])) {
                $userId = $_SESSION['user_id'];
                try {
                    $stmt = $pdo->prepare("SELECT sender, message, created_at FROM chat_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 30");
                    $stmt->execute([$userId]);
                    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if ($logs) {
                        $historyHtml = "<div class='text-left max-h-60 overflow-y-auto bg-gray-50 p-3 rounded text-sm'>";
                        $logs = array_reverse($logs); // Oldest first
                        foreach ($logs as $log) {
                            $time = date("h:i A", strtotime($log['created_at']));
                            $senderLabel = ($log['sender'] === 'user') ? "You" : "Bot";
                            $color = ($log['sender'] === 'user') ? "text-blue-600" : "text-gray-600";
                            $historyHtml .= "<div class='mb-1'><span class='text-xs text-gray-400'>[$time]</span> <strong class='$color'>$senderLabel:</strong> " . htmlspecialchars($log['message']) . "</div>";
                        }
                        $historyHtml .= "</div>";

                        $msg = ($lang === 'en') ? "Here are your last 30 messages:" : "यहाँ आपके अंतिम 30 सन्देश हैं:";
                        $response['messages'] = [$msg, $historyHtml];
                    } else {
                        $msg = ($lang === 'en') ? "No chat history found." : "कोई चैट इतिहास नहीं मिला।";
                        $response['messages'] = [$msg];
                    }
                } catch (PDOException $e) {
                    $response['messages'] = ["Error fetching history."];
                }
            } else {
                $msg = ($lang === 'en') ? "Please log in to view history." : "इतिहास देखने के लिए कृपया लॉग इन करें।";
                $response['messages'] = [$msg];
            }
            $chatState->bookingStep = 'done';
            $response['quickReplies'] = [["label" => "Back", "value" => "Start over"]];
            break;

        default:
            $response['messages'] = ["Hello!"];
            $chatState->bookingStep = 'start';
            return getBotResponses('start', 'start', $chatState, $pdo);
    }

    return $response;
}

function parseQuantity($message)
{
    // Remove commas and find the first number
    $cleanIdx = str_replace(',', '', $message);
    if (preg_match('/\d+/', $cleanIdx, $matches)) {
        return intval($matches[0]);
    }
    return 0;
}
function logChatMessage($pdo, $userId, $sender, $message)
{
    try {
        $stmt = $pdo->prepare("INSERT INTO chat_logs (user_id, sender, message) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $sender, $message]);
    } catch (PDOException $e) {
        // Silently fail or log to file, don't break chat
        error_log("Chat Log Error: " . $e->getMessage());
    }
}

// --- NEW PERSISTENCE FUNCTIONS ---

function saveChatState($pdo, $userId, $state)
{
    try {
        $jsonState = json_encode($state);
        // Insert or Update
        $stmt = $pdo->prepare("INSERT INTO chat_sessions (user_id, chat_state) VALUES (?, ?) ON DUPLICATE KEY UPDATE chat_state = VALUES(chat_state)");
        $stmt->execute([$userId, $jsonState]);
    } catch (PDOException $e) {
        error_log("Save State Error: " . $e->getMessage());
    }
}

function getChatState($pdo, $userId)
{
    try {
        $stmt = $pdo->prepare("SELECT chat_state FROM chat_sessions WHERE user_id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return json_decode($row['chat_state']);
        }
    } catch (PDOException $e) {
        error_log("Get State Error: " . $e->getMessage());
    }
    return null;
}
?>