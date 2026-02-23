<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('❌ Please log in first.'); window.location.href='../login_page.html';</script>";
    exit();
}

// Handle fresh start parameter (from payment page)
if (isset($_GET['fresh_start']) && $_GET['fresh_start'] == '1') {
    // Clear chat state to force fresh start
    require_once 'db_config.php';
    try {
        $stmt = $pdo->prepare("DELETE FROM chat_sessions WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    } catch (PDOException $e) {
        // Silently fail
    }
    // Redirect to clean URL
    header("Location: index.php");
    exit();
}

$userName = isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'User';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Museum Ticket Bot</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/easy-qrcode-js/4.4.1/easy.qrcode.min.js"></script>

    <!-- Loader CSS -->
    <link rel="stylesheet" href="../loader.css">

    <style>
        /* Set base font */
        body {
            font-family: 'Inter', sans-serif;
        }

        /* Custom scrollbar for chat */
        #chat-messages::-webkit-scrollbar {
            width: 6px;
        }

        #chat-messages::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }

        #chat-messages::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 10px;
        }

        #chat-messages::-webkit-scrollbar-thumb:hover {
            background: #475569;
        }

        /* Ensure the layout takes full height */
        html,
        body {
            height: 100%;
            overflow: hidden;
        }

        /* Style for the QR Code container */
        .qr-code-container {
            background-color: white;
            padding: 16px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            display: inline-block;
            /* Makes it fit the content */
            margin-top: 8px;
            position: relative;
            /* For the timer text */
        }

        /* Style for the timer */
        .qr-timer {
            font-size: 0.9rem;
            font-weight: 500;
            color: #ef4444;
            /* red-500 */
            text-align: center;
            margin-top: 12px;
        }

        /* Style for the expired QR code */
        .qr-code-expired {
            opacity: 0.2;
        }

        /* Style for the receipt */
        .receipt {
            background-color: #f3f4f6;
            /* gray-100 */
            border: 1px solid #e5e7eb;
            /* gray-200 */
            border-radius: 8px;
            padding: 16px;
            font-size: 0.9rem;
            color: #1f2937;
            /* gray-800 */
        }

        .receipt h4 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #16a34a;
            /* green-600 */
            margin-bottom: 8px;
        }

        .receipt p {
            margin-bottom: 12px;
        }

        .receipt ul {
            list-style-type: none;
            padding-left: 0;
            margin-bottom: 12px;
        }

        .receipt li {
            padding: 4px 0;
            border-bottom: 1px solid #e5e7eb;
            /* gray-200 */
        }

        .receipt li:last-child {
            border-bottom: none;
        }

        .receipt li strong {
            color: #374151;
            /* gray-700 */
        }

        /* Start New Chat Button */
        .start-new-chat-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 50px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 1000;
            display: none;
        }

        .start-new-chat-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .start-new-chat-btn.show {
            display: block;
            animation: slideInUp 0.3s ease;
        }

        @keyframes slideInUp {
            from {
                transform: translateY(100px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Continue Flow Banner */
        .continue-banner {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-left: 4px solid #f59e0b;
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: none;
            animation: slideDown 0.3s ease;
        }

        .continue-banner.show {
            display: block;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .continue-banner h3 {
            font-weight: 600;
            color: #92400e;
            margin-bottom: 4px;
        }

        .continue-banner p {
            color: #78350f;
            font-size: 0.9rem;
            margin-bottom: 12px;
        }

        .continue-banner .btn-group {
            display: flex;
            gap: 8px;
        }

        .continue-banner button {
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .continue-banner .btn-continue {
            background: #10b981;
            color: white;
            border: none;
        }

        .continue-banner .btn-continue:hover {
            background: #059669;
        }

        .continue-banner .btn-fresh {
            background: #6b7280;
            color: white;
            border: none;
        }

        .continue-banner .btn-fresh:hover {
            background: #4b5563;
        }
    </style>
</head>

<body class="bg-gray-100 flex flex-col h-screen">
    <!-- Loader Overlay -->
    <div id="page-loader"
        style="display: flex; position: fixed; top: 0; left: 0; width: 100%; height: 100vh; background: rgba(255,255,255,0.98); z-index: 9999; justify-content: center; align-items: center;">
        <div class="container" style="height: auto;">
            <div class="loader"></div>
            <p class="text">Loading Chatbot...</p>
        </div>
    </div>


    <header class="bg-white shadow-md">
        <div class="container mx-auto max-w-3xl px-4 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 bg-indigo-600 text-white rounded-full flex items-center justify-center">
                    <svg id="ticket-icon" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path
                            d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z" />
                        <path d="M13 5v2" />
                        <path d="M13 17v2" />
                        <path d="M13 11v2" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-800">National Museum</h1>
                    <p class="text-sm text-gray-500 flex items-center">
                        <span id="status-light" class="w-2 h-2 bg-yellow-400 rounded-full mr-1.5 animate-pulse"></span>
                        <span id="status-text">Connecting...</span>
                        <span class="ml-4 text-gray-700 font-medium">Welcome, <?php echo $userName; ?></span>
                    </p>
                </div>
            </div>

            <div class="flex items-center space-x-3">
                <!-- Existing History Link (Booking Reports) -->
                <a href="history.php"
                    class="text-gray-600 hover:text-indigo-600 font-medium text-sm px-3 py-2 rounded-md hover:bg-gray-100 transition duration-200">
                    Reports
                </a>
                <!-- Pending Payments Button (Hidden by default) -->
                <button id="pending-payments-btn"
                    class="text-gray-600 hover:text-indigo-600 font-medium text-sm px-3 py-2 rounded-md hover:bg-gray-100 transition duration-200 relative hidden">
                    💳 Pending
                    <span id="pending-badge"
                        class="ml-1 bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">0</span>
                </button>
                <!-- New Chat History Button -->
                <a href="chat_history.php"
                    class="text-gray-600 hover:text-indigo-600 font-medium text-sm px-3 py-2 rounded-md hover:bg-gray-100 transition duration-200">
                    Chat History
                </a>
                <a href="../userpage.php"
                    class="bg-indigo-600 text-white font-medium text-sm px-4 py-2 rounded-md hover:bg-indigo-700 transition duration-200 shadow-sm">
                    Home
                </a>
                <a href="logout.php"
                    class="bg-red-500 text-white font-medium text-sm px-4 py-2 rounded-md hover:bg-red-600 transition duration-200 shadow-sm">
                    Logout
                </a>
            </div>

        </div>
    </header>

    <div class="flex-1 min-h-0">
        <div class="container mx-auto max-w-3xl h-full flex flex-col p-4">

            <!-- Continue Flow Banner (Hidden by default) -->
            <div id="continue-banner" class="continue-banner">
                <h3>📋 Active Booking in Progress</h3>
                <p id="flow-description">You were in the middle of a booking</p>
                <div class="btn-group">
                    <button class="btn-continue" id="btn-continue">Continue Where I Left Off</button>
                    <button class="btn-fresh" id="btn-start-fresh">Start Fresh</button>
                </div>
            </div>

            <div id="chat-messages" class="flex-1 space-y-4 p-4 overflow-y-auto bg-white rounded-t-lg shadow-inner">
            </div>

            <div id="quick-replies" class="p-4 bg-white border-t border-gray-200">
            </div>

            <div class="p-4 bg-white rounded-b-lg shadow-md">
                <form id="chat-form" class="flex items-center space-x-3">
                    <input type="text" id="user-input" placeholder="Type your message..."
                        class="flex-1 w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 transition duration-200"
                        autocomplete="off" disabled>
                    <button type="submit" id="send-button"
                        class="bg-indigo-600 text-white px-5 py-3 rounded-lg font-semibold hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition duration-200"
                        disabled>
                        Send
                    </button>
                </form>
            </div>

        </div>
    </div>

    <!-- Start New Chat Button (Fixed Position) -->
    <button id="start-new-chat-btn" class="start-new-chat-btn">
        🔄 Start New Chat
    </button>

    <script>
        // --- 1. DOM Elements ---
        const chatMessages = document.getElementById('chat-messages');
        const chatForm = document.getElementById('chat-form');
        const userInput = document.getElementById('user-input');
        const quickRepliesContainer = document.getElementById('quick-replies');
        const sendButton = document.getElementById('send-button');
        const statusLight = document.getElementById('status-light');
        const statusText = document.getElementById('status-text');
        const startNewChatBtn = document.getElementById('start-new-chat-btn');
        const continueBanner = document.getElementById('continue-banner');
        const btnContinue = document.getElementById('btn-continue');
        const btnStartFresh = document.getElementById('btn-start-fresh');
        const flowDescription = document.getElementById('flow-description');
        const pendingPaymentsBtn = document.getElementById('pending-payments-btn');
        const pendingBadge = document.getElementById('pending-badge');

        // --- 2. Chat State (This is the bot's "memory") ---
        let currentChatState = {
            language: null,
            bookingStep: 'start',
            ticket: {}
        };

        let qrCodeInstance = null; // To hold the QR code object
        let qrCodeTimer = null; // NEW: To hold the timer interval
        let qrCodeContainerId = null; // NEW: To track the QR code's container

        // --- 3. Event Listeners ---

        // Handle form submission
        chatForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const message = userInput.value.trim();
            if (message) {
                clearQrTimer(); // NEW: Clear timer on any new message
                displayUserMessage(message);
                handleBotResponse(message); // Send message to the server
                userInput.value = '';
            }
        });

        // Handle quick reply clicks
        quickRepliesContainer.addEventListener('click', (e) => {
            if (e.target.tagName === 'BUTTON') {
                const message = e.target.getAttribute('data-value');
                clearQrTimer(); // NEW: Clear timer on any new message
                displayUserMessage(message);
                handleBotResponse(message); // Send choice to the server
            }
        });

        // Handle Start New Chat button click
        startNewChatBtn.addEventListener('click', () => {
            // Clear the chat messages visually
            chatMessages.innerHTML = '';
            // Hide the button and banner
            startNewChatBtn.classList.remove('show');
            continueBanner.classList.remove('show');
            // Trigger start over which will clear logs and reset state
            handleBotResponse('start over');
        });

        // Handle Continue button click
        btnContinue.addEventListener('click', () => {
            // Just hide the banner and enable chat
            continueBanner.classList.remove('show');
            userInput.focus();
        });

        // Handle Start Fresh button click
        btnStartFresh.addEventListener('click', () => {
            // Clear the chat messages visually
            chatMessages.innerHTML = '';
            // Hide the banner
            continueBanner.classList.remove('show');
            // Trigger start over which will clear logs and reset state
            handleBotResponse('start over');
        });

        // Handle Pending Payments button click
        pendingPaymentsBtn.addEventListener('click', () => {
            // Trigger check pending flow
            chatMessages.innerHTML = '';
            handleBotResponse('check pending');
        });

        // --- 4. Main Chat Logic ---

        /**
         * Sends a message to the backend server and gets the bot's response.
         */
        async function handleBotResponse(message) {
            userInput.disabled = true;
            sendButton.disabled = true;
            showQuickReplies([]); // Clear replies

            // NEW: Clear old QR code if it exists
            if (qrCodeInstance) {
                qrCodeInstance.clear();
                qrCodeInstance = null;
            }
            clearQrTimer(); // NEW: Clear any existing timer

            // Show a loading indicator (only if not init)
            let loadingMsgId = null;
            if (message !== '__INIT__') {
                loadingMsgId = displayBotMessage('', true);
            }

            try {
                // Define the API endpoint (your server)
                const apiEndpoint = 'chat.php';

                // Send the user's message AND the current state to the server
                const response = await fetch(apiEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        message: message,
                        chatState: currentChatState // Send the bot's "memory"
                    })
                });

                if (!response.ok) {
                    throw new Error(`Server error: ${response.statusText}`);
                }

                // Get the response from the server
                const data = await response.json();

                // 3. IMPORTANT: Update the chat state with the new state from the server
                currentChatState = data.chatState;

                // Show/hide Start New Chat button based on booking step
                if (currentChatState.bookingStep === 'payment_pending' || currentChatState.bookingStep === 'done') {
                    startNewChatBtn.classList.add('show');
                } else if (currentChatState.bookingStep === 'start' || currentChatState.bookingStep === 'language') {
                    startNewChatBtn.classList.remove('show');
                }

                // Handle active flow detection (from INIT)
                if (data.hasActiveFlow && data.flowDescription) {
                    flowDescription.textContent = data.flowDescription;
                    continueBanner.classList.add('show');
                } else {
                    continueBanner.classList.remove('show');
                }

                // Set status to online *after* a successful fetch
                if (message === 'start' || message === '__INIT__') {
                    setStatus(true, 'Bot is online');
                }

                // --- NEW: Handle History ---
                if (data.history && data.history.length > 0) {
                    data.history.forEach(msg => {
                        if (msg.sender === 'user') {
                            displayUserMessage(msg.text);
                        } else {
                            // Render bot message immediately without animation
                            displayBotMessage(msg.text, false);
                        }
                    });
                }

                // If this was INIT and we have history, we might not have new "messages"
                // If history is empty, we should trigger start flow?
                if (message === '__INIT__') {
                    if ((!data.history || data.history.length === 0) && (!data.messages || data.messages.length === 0)) {
                        // No history, start fresh
                        handleBotResponse('start');
                        return; // recursive call, then exit this one
                    }
                }

                // 4. Display the bot's messages
                if (data.messages && data.messages.length > 0) {
                    // Update loading message with the first response
                    if (loadingMsgId) {
                        updateBotMessage(loadingMsgId, data.messages[0]);
                    } else {
                        displayBotMessage(data.messages[0]);
                    }

                    // Display subsequent messages with a delay
                    if (data.messages.length > 1) {
                        data.messages.slice(1).forEach((msg, index) => {
                            setTimeout(() => {
                                displayBotMessage(msg);
                            }, 500 * (index + 1));
                        });
                    }
                } else {
                    // If no messages and we had a loading indicator, we should remove it?
                    // Actually, if it was init, we didn't show it.
                    // If it was a normal message and no response (weird), remove loader.
                    if (loadingMsgId) {
                        const loader = document.getElementById(loadingMsgId);
                        if (loader) loader.parentElement.parentElement.remove(); // remove the bubble
                    }
                }

                // NEW: Check if the server sent QR code data
                if (data.qrData) {
                    setTimeout(() => {
                        displayQRCode(data.qrData);
                    }, 500 * (data.messages ? data.messages.length : 0));
                }

                // NEW: Check if the server sent an HTML receipt
                if (data.htmlReceipt) {
                    setTimeout(() => {
                        displayHtmlReceipt(data.htmlReceipt);
                    }, 500 * (data.messages ? data.messages.length : 0));
                }

                // NEW: Check if we need to show a Date Picker
                if (data.showDatePicker) {
                    setTimeout(() => {
                        displayDatePicker();
                    }, 500 * (data.messages ? data.messages.length : 0));
                }


                // Show new quick replies after all messages are sent
                // Don't show replies if a QR code is active (timer will handle it)
                if (!data.qrData) {
                    setTimeout(() => {
                        showQuickReplies(data.quickReplies);
                    }, 500 * (data.messages ? data.messages.length : 0));
                }


            } catch (error) {
                console.error('Error fetching bot response:', error);
                // Show an error message in the chat
                if (loadingMsgId) {
                    updateBotMessage(loadingMsgId, "Oops! I'm having trouble connecting to my brain. Please make sure the server is running and try again.");
                } else if (message !== '__INIT__') {
                    displayBotMessage("Oops! I'm having trouble connecting to my brain. Please check your connection.");
                }
                // Update status indicator
                setStatus(false, 'Connection error');
            } finally {
                // Re-enable input
                userInput.disabled = false;
                sendButton.disabled = false;
                userInput.focus();
            }
        }

        // --- 5. Helper Functions (To make everything work) ---

        /**
         * Displays a user's message in the chat.
         */
        function displayUserMessage(message) {
            const messageElement = document.createElement('div');
            messageElement.className = 'flex justify-end';
            messageElement.innerHTML = `
                <div class="bg-indigo-600 text-white p-3 rounded-lg rounded-br-none max-w-xs md:max-w-md break-words">
                    <p>${message}</p>
                </div>
            `;
            chatMessages.appendChild(messageElement);
            scrollToBottom();
        }

        /**
         * Displays a bot message or a loading indicator.
         */
        function displayBotMessage(message, isLoading = false) {
            const messageId = `bot-msg-${Date.now()}`;
            const messageElement = document.createElement('div');
            messageElement.className = 'flex justify-start';
            messageElement.innerHTML = `
                <div class="flex items-end space-x-2">
                    <div class="w-8 h-8 bg-gray-200 text-gray-600 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg id="${messageId}-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8V4H8L4 8v4h4v4h4V8z"/><path d="M20 12h-4v4h4v4l4-4v-4l-4-4z"/><path d="M16 8V4h-4v4h4z"/></svg>
                    </div>
                    <div id="${messageId}" class="bg-gray-200 text-gray-800 p-3 rounded-lg rounded-bl-none max-w-xs md:max-w-md break-words">
                        ${isLoading ?
                    '<div class="flex items-center space-x-1"><div class="w-2 h-2 bg-gray-500 rounded-full animate-bounce" style="animation-delay: 0s;"></div><div class="w-2 h-2 bg-gray-500 rounded-full animate-bounce" style="animation-delay: 0.2s;"></div><div class="w-2 h-2 bg-gray-500 rounded-full animate-bounce" style="animation-delay: 0.4s;"></div></div>' :
                    `<p>${message}</p>`}
                    </div>
                </div>
            `;
            chatMessages.appendChild(messageElement);
            // Check if lucide is available before using it
            if (typeof lucide !== 'undefined') {
                lucide.createIcons(); // Re-render icon
            }
            scrollToBottom();
            return messageId;
        }

        /**
         * NEW: Displays a QR code in the chat and starts a timer.
         */
        function displayQRCode(qrData) {
            const messageId = `qr-msg-${Date.now()}`;
            qrCodeContainerId = `qr-container-${Date.now()}`;
            const timerId = `qr-timer-${Date.now()}`;

            const messageElement = document.createElement('div');
            messageElement.className = 'flex justify-start';
            messageElement.innerHTML = `
                <div class="flex items-end space-x-2">
                    <div class="w-8 h-8 bg-gray-200 text-gray-600 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg id="${messageId}-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8V4H8L4 8v4h4v4h4V8z"/><path d="M20 12h-4v4h4v4l4-4v-4l-4-4z"/><path d="M16 8V4h-4v4h4z"/></svg>
                    </div>
                    <div class="bg-gray-200 text-gray-800 p-3 rounded-lg rounded-bl-none max-w-xs md:max-w-md">
                        <div class="qr-code-container" id="${qrCodeContainerId}">
                            </div>
                        <div class="qr-timer" id="${timerId}">Expires in 02:00</div>
                    </div>
                </div>
            `;
            chatMessages.appendChild(messageElement);

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }

            // Generate the QR code
            try {
                // Check if the library is loaded
                if (typeof EasyQRCodeJS === 'undefined') {
                    throw new Error("EasyQRCodeJS library is not loaded.");
                }

                qrCodeInstance = new EasyQRCodeJS(document.getElementById(qrCodeContainerId), {
                    text: qrData,
                    width: 200,
                    height: 200,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: EasyQRCodeJS.CorrectLevel.H
                });
            } catch (e) {
                console.error("Error generating QR code:", e);
                // This is the error message the user is seeing
                document.getElementById(qrCodeContainerId).innerText = "Error generating QR code.";
            }

            startQrTimer(timerId, qrCodeContainerId); // Start the 2-minute timer
            showQuickReplies([{ label: "I have paid", value: "paid" }]);
            scrollToBottom();
            return messageId;
        }

        /**
         * NEW: Starts the 2-minute (120s) countdown timer for the QR code.
         */
        function startQrTimer(timerElementId, qrContainerElementId) {
            let timeLeft = 120; // 2 minutes in seconds
            const timerElement = document.getElementById(timerElementId);

            qrCodeTimer = setInterval(() => {
                timeLeft--;
                let minutes = Math.floor(timeLeft / 60);
                let seconds = timeLeft % 60;

                // Format as 00:00
                minutes = minutes < 10 ? '0' + minutes : minutes;
                seconds = seconds < 10 ? '0' + seconds : seconds;

                if (timerElement) {
                    timerElement.textContent = `Expires in ${minutes}:${seconds}`;
                }

                if (timeLeft <= 0) {
                    clearQrTimer();
                    if (timerElement) {
                        timerElement.textContent = "QR Code Expired";
                    }
                    // Gray out the expired QR code
                    const qrContainer = document.getElementById(qrContainerElementId);
                    if (qrContainer) {
                        qrContainer.classList.add('qr-code-expired');
                    }
                    // Show regenerate button
                    showQuickReplies([
                        { label: "Regenerate QR code", value: "regenerate" }
                    ]);
                }
            }, 1000);
        }

        /**
         * NEW: Clears the QR code timer.
         */
        function clearQrTimer() {
            if (qrCodeTimer) {
                clearInterval(qrCodeTimer);
                qrCodeTimer = null;
            }
        }

        /**
         * NEW: Displays the HTML receipt from the server.
         */
        function displayHtmlReceipt(htmlContent) {
            const messageId = `receipt-msg-${Date.now()}`;
            const messageElement = document.createElement('div');
            messageElement.className = 'flex justify-start';
            messageElement.innerHTML = `
                <div class="flex items-end space-x-2">
                    <div class="w-8 h-8 bg-gray-200 text-gray-600 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg id="${messageId}-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8V4H8L4 8v4h4v4h4V8z"/><path d="M20 12h-4v4h4v4l4-4v-4l-4-4z"/><path d="M16 8V4h-4v4h4z"/></svg>
                    </div>
                    <div id="${messageId}" class="bg-gray-200 text-gray-800 p-3 rounded-lg rounded-bl-none max-w-xs md:max-w-md break-words">
                        </div>
                </div>
            `;
            chatMessages.appendChild(messageElement);
            // Safely inject the HTML
            document.getElementById(messageId).innerHTML = htmlContent;

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
            scrollToBottom();
            return messageId;
        }


        /**
         * Replaces the content of a message (e.g., to replace "..." with the real message).
         *
         * ---
         * FIX: Made this function safer to prevent errors with undefined messages.
         * ---
         */
        function updateBotMessage(messageId, newMessage) {
            try {
                const messageContent = document.getElementById(messageId);
                if (messageContent) {
                    let content = '';
                    // Check if newMessage is a valid string
                    if (typeof newMessage === 'string') {
                        // Check if the message is HTML (for the summary)
                        if (newMessage.includes('<br>') || newMessage.includes('<strong>')) {
                            content = newMessage;
                        } else {
                            content = `<p>${newMessage}</p>`;
                        }
                    } else {
                        // Fallback for empty, null, or undefined message
                        content = '<p>...</p>';
                    }
                    messageContent.innerHTML = content;
                } else {
                    console.warn('Could not find message element to update:', messageId);
                }
            } catch (e) {
                console.error('Error in updateBotMessage:', e);
                // Try to recover
                const messageContent = document.getElementById(messageId);
                if (messageContent) {
                    messageContent.innerHTML = '<p>Error loading message.</p>';
                }
            }
        }


        /**
         * Generates and displays quick reply buttons.
         */
        function showQuickReplies(replies) {
            quickRepliesContainer.innerHTML = '';
            if (!replies || replies.length === 0) {
                quickRepliesContainer.classList.add('hidden');
                return;
            }

            quickRepliesContainer.classList.remove('hidden');
            const container = document.createElement('div');
            container.className = 'flex flex-wrap gap-2';

            replies.forEach(reply => {
                const button = document.createElement('button');
                button.className = 'px-4 py-2 bg-indigo-50 border border-indigo-200 text-indigo-700 font-medium rounded-full hover:bg-indigo-100 transition duration-200';
                button.textContent = reply.label;
                button.setAttribute('data-value', reply.value);
                container.appendChild(button);
            });
            quickRepliesContainer.appendChild(container);
            scrollToBottom();
        }

        /**
         * Updates the "online" status indicator.
         */
        function setStatus(online, message) {
            if (online) {
                statusLight.classList.remove('bg-yellow-400', 'bg-red-500', 'animate-pulse');
                statusLight.classList.add('bg-green-500');
                statusText.textContent = message;
            } else {
                statusLight.classList.remove('bg-yellow-400', 'bg-green-500', 'animate-pulse');
                statusLight.classList.add('bg-red-500');
                statusText.textContent = message;
            }
        }

        /**
         * Scrolls the chat window to the bottom.
         */
        function scrollToBottom() {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        /**
         * NEW: Displays a Date Picker input in the chat.
         */
        function displayDatePicker() {
            const messageElement = document.createElement('div');
            messageElement.className = 'flex justify-start'; // Bot side
            messageElement.innerHTML = `
                <div class="flex items-end space-x-2">
                     <div class="w-8 h-8 bg-gray-200 text-gray-600 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    </div>
                    <div class="bg-gray-200 text-gray-800 p-3 rounded-lg rounded-bl-none max-w-xs md:max-w-md">
                        <p class="mb-2 text-sm font-semibold">Select Date:</p>
                        <input type="date" id="date-picker-input" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm"
                            min="${new Date().toISOString().split('T')[0]}"
                        />
                        <button id="date-picker-confirm" class="mt-2 w-full bg-indigo-600 text-white py-1.5 rounded-md text-sm font-medium hover:bg-indigo-700 transition">
                            Confirm Date
                        </button>
                    </div>
                </div>
            `;
            chatMessages.appendChild(messageElement);
            scrollToBottom();

            // Add event listener
            const confirmBtn = messageElement.querySelector('#date-picker-confirm');
            const dateInput = messageElement.querySelector('#date-picker-input');

            confirmBtn.addEventListener('click', () => {
                const selectedDate = dateInput.value;
                if (selectedDate) {
                    confirmBtn.disabled = true;
                    dateInput.disabled = true;
                    // Send to bot
                    displayUserMessage(selectedDate);
                    handleBotResponse(selectedDate);
                } else {
                    alert("Please select a date first.");
                }
            });
        }

        /**
         * Check for pending payments and update header button
         */
        async function checkPendingPayments() {
            try {
                const response = await fetch('get_pending_count.php');
                const data = await response.json();

                if (data.count > 0) {
                    pendingBadge.textContent = data.count;
                    pendingPaymentsBtn.classList.remove('hidden');
                } else {
                    pendingPaymentsBtn.classList.add('hidden');
                }
            } catch (error) {
                console.error('Error checking pending payments:', error);
            }
        }

        // --- 6. Initialize Chat ---
        window.onload = () => {
            // Initialize Lucide icons
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }

            // Check for pending payments
            checkPendingPayments();

            // Trigger the initialization message by calling the server
            setTimeout(() => {
                // Use a special initialization signal
                handleBotResponse('__INIT__').finally(() => {
                    // Hide loader after initialization completes
                    document.getElementById('page-loader').style.display = 'none';
                });
            }, 500);
        };

    </script>
</body>

</html>