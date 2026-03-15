<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../WS/login.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat with Destination Name</title>
    <style>
        :root {
            --eco-green: #2e7d32;
            --light-green: #4caf50;
            --accent-green: #81c784;
            --background: #f1f8e9;
            --card-bg: #ffffff;
            --text-dark: #1b5e20;
            --text-medium: #388e3c;
            --text-light: #666;
            --shadow: 0 2px 8px rgba(46, 125, 50, 0.1);
            --border-radius: 12px;
            --admin-blue: #2196f3;
            --chat-blue: #3a8ee6;
            --chat-blue-light: #64b5f6;
            --incoming-bg: #f8f9f8;
            --border-light: #c8e6c9;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            background-color: var(--background);
            min-height: 100vh;
            color: var(--text-dark);
        }

        .chat-container {
            max-width: 700px; 
            margin: 20px auto;
            border: 1px solid var(--border-light);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            height: 90vh;
            background-color: var(--card-bg);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .chat-header {
            padding: 20px 25px;
            background-color: var(--eco-green);
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            color: white;
            font-weight: bold;
            font-size: 1.1em;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-info {
            flex-grow: 1;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-info h3 {
            margin: 0;
            font-size: 1.3em;
            font-weight: 700;
            color: white;
        }

        .group-info {
            font-size: 0.85em;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
            background-color: rgba(255, 255, 255, 0.15);
            padding: 4px 10px;
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .close-btn {
            font-size: 28px;
            font-weight: 300;
            color: white;
            cursor: pointer;
            padding: 5px 10px;
            line-height: 1;
            flex-shrink: 0;
            background-color: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
        }

        .close-btn:hover {
            background-color: rgba(255, 255, 255, 0.25);
        }

        .chat-part {
            flex-grow: 1;
            padding: 25px;
            overflow-y: auto;
            background-color: var(--incoming-bg);
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .chat-part::-webkit-scrollbar {
            width: 8px;
        }

        .chat-part::-webkit-scrollbar-track {
            background-color: rgba(200, 230, 201, 0.3);
            border-radius: 4px;
        }

        .chat-part::-webkit-scrollbar-thumb {
            background-color: var(--light-green);
            border-radius: 4px;
            border: 2px solid var(--incoming-bg);
        }

        .chat-part::-webkit-scrollbar-thumb:hover {
            background-color: var(--accent-green);
        }

        .message-row {
            display: flex;
            margin-bottom: 5px;
        }

        .message-bubble {
            padding: 14px 18px;
            border-radius: 20px;
            max-width: 75%;
            line-height: 1.5;
            min-height: 30px;
            white-space: pre-wrap;
            word-break: break-word;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            position: relative;
            border: 1px solid transparent;
        }

        .incoming {
            justify-content: flex-start;
        }

        .incoming .message-bubble {
            background-color: white;
            color: var(--text-dark);
            border: 1px solid var(--border-light);
            border-bottom-left-radius: 6px;
        }

        .incoming .message-bubble::before {
            content: '';
            position: absolute;
            left: -8px;
            bottom: 0;
            width: 12px;
            height: 12px;
            background-color: white;
            border-bottom: 1px solid var(--border-light);
            border-left: 1px solid var(--border-light);
            transform: rotate(45deg);
            z-index: 0;
        }

        .outgoing {
            justify-content: flex-end;
        }

        .outgoing .message-bubble {
            background-color: var(--chat-blue);
            color: white;
            min-width: 40%;
            border-bottom-right-radius: 6px;
        }

        .outgoing .message-bubble::after {
            content: '';
            position: absolute;
            right: -8px;
            bottom: 0;
            width: 12px;
            height: 12px;
            background-color: var(--chat-blue);
            border-bottom: 1px solid var(--chat-blue);
            border-right: 1px solid var(--chat-blue);
            transform: rotate(-45deg);
            z-index: 0;
        }

        .message-time {
            font-size: 0.75em;
            color: var(--text-light);
            margin: 5px 10px;
            opacity: 0.8;
            font-weight: 500;
        }

        .incoming + .message-time {
            text-align: left;
            margin-left: 15px;
        }

        .outgoing + .message-time {
            text-align: right;
            margin-right: 15px;
        }

        .bottom {
            padding: 20px 25px;
            border-top: 1px solid var(--border-light);
            background-color: white;
            flex-shrink: 0;
            box-shadow: 0 -1px 4px rgba(0, 0, 0, 0.05);
        }

        .chat-functions {
            display: flex;
            align-items: flex-end;
            gap: 12px;
        }

        .chat-functions textarea {
            flex-grow: 1;
            padding: 14px 18px;
            border: 2px solid var(--border-light);
            border-radius: 20px;
            resize: none;
            min-height: 48px;
            max-height: 120px;
            overflow-y: auto;
            outline: none;
            font-family: inherit;
            font-size: 14px;
            color: var(--text-dark);
            background-color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .chat-functions textarea:focus {
            border-color: var(--light-green);
        }

        .chat-functions textarea::placeholder {
            color: var(--text-light);
            opacity: 0.7;
        }

        .chat-functions button {
            padding: 14px 28px;
            border: none;
            border-radius: 20px;
            background-color: var(--chat-blue);
            color: white;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(33, 150, 243, 0.2);
            min-width: 80px;
        }

        .chat-functions button:hover {
            background-color: var(--admin-blue);
        }

        /* Typing indicator */
        .typing-indicator {
            display: none;
            align-items: center;
            gap: 8px;
            margin: 10px 15px;
            padding: 8px 15px;
            background-color: white;
            border-radius: 15px;
            border: 1px solid var(--border-light);
            width: fit-content;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .typing-dots {
            display: flex;
            gap: 4px;
        }

        .typing-dot {
            width: 8px;
            height: 8px;
            background-color: var(--light-green);
            border-radius: 50%;
            animation: bounce 1.4s infinite ease-in-out;
        }

        .typing-dot:nth-child(2) {
            animation-delay: 0.2s;
        }

        .typing-dot:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes bounce {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-5px); }
        }

        @media (max-width: 768px) {
            .chat-container {
                max-width: 100%;
                height: 100vh;
                margin: 0;
                border-radius: 0;
                border: none;
            }
            
            .chat-header {
                padding: 15px 20px;
            }
            
            .chat-part {
                padding: 15px;
            }
            
            .bottom {
                padding: 15px 20px;
            }
            
            .message-bubble {
                max-width: 85%;
            }
            
            .chat-functions {
                gap: 8px;
            }
            
            .chat-functions button {
                padding: 12px 20px;
                min-width: 70px;
            }
        }

        @media (max-width: 480px) {
            .chat-header h3 {
                font-size: 1.1em;
            }
            
            .group-info {
                font-size: 0.75em;
                padding: 3px 8px;
            }
            
            .chat-functions textarea {
                padding: 12px 15px;
                font-size: 13px;
            }
            
            .message-bubble {
                padding: 12px 15px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class='chat-container'>
        
        <div class='chat-header'>
            <div class="header-info">
                <h3>Carpool Chat</h3>
                <p class="group-info">To: Downtown Center</p>
            </div>
            
            <button class="close-btn" onclick="window.history.back()">×</button>
        </div>

        <div class='chat-part' id="chat-part">
            <!-- Initial chat messages -->
            <div class="message-row incoming">
                <div class="message-bubble">Hello! Welcome to the carpool chat! 🚗</div>
            </div>
            <div class="message-time" style="text-align: left; margin-left: 15px;">09:00</div>
            
            <div class="message-row incoming">
                <div class="message-bubble">Hi everyone! Is this carpool still available?</div>
            </div>
            <div class="message-time" style="text-align: left; margin-left: 15px;">09:05</div>
            
            <div class="message-row outgoing">
                <div class="message-bubble">Yes, we have 2 seats left!</div>
            </div>
            <div class="message-time" style="text-align: right; margin-right: 15px;">09:06</div>
            
            <div class="message-row incoming">
                <div class="message-bubble">What time are we meeting tomorrow?</div>
            </div>
            <div class="message-time" style="text-align: left; margin-left: 15px;">09:07</div>
            
            <div class="message-row outgoing">
                <div class="message-bubble">Meeting at 9:00 AM at Central Station</div>
            </div>
            <div class="message-time" style="text-align: right; margin-right: 15px;">09:08</div>
        </div>

        <div class='bottom'>
            <div class='chat-functions'>
                <textarea 
                    id='chat-input' 
                    placeholder='Type your message here...' 
                    rows='1'
                ></textarea>
                
                <button type='button' onclick="sendMessage()">Send</button>
            </div>
        </div>
        
    </div>

    <script>
        // Get elements
        const chatInput = document.getElementById('chat-input');
        const chatPart = document.getElementById('chat-part');
        
        // Auto-resize textarea
        chatInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
        
        // Auto-reply messages (simulated responses)
        const autoReplies = [
            "Great! See you tomorrow! 🚗",
            "The cost is $5 per person.",
            "Yes, seats are still available!",
            "Meeting point is at Central Station Gate 3.",
            "We'll be leaving at 9:00 AM sharp.",
            "Don't forget to bring your ID!",
            "The driver's name is John.",
            "Parking is available at the station.",
            "The estimated travel time is 45 minutes.",
            "Weather looks good for tomorrow! ☀️"
        ];
        
        // User messages history
        const userMessages = [
            "Hello everyone!",
            "Is there a seat available?",
            "What time are we meeting?",
            "How much does it cost?",
            "Where is the meeting point?",
            "See you tomorrow!",
            "Thanks for organizing this!",
            "Can I bring a small bag?",
            "What's the driver's name?",
            "Is there parking available?"
        ];
        
        // Function to get current time in HH:MM format
        function getCurrentTime() {
            const now = new Date();
            return now.getHours().toString().padStart(2, '0') + ':' + 
                   now.getMinutes().toString().padStart(2, '0');
        }
        
        // Function to add message to chat
        function addMessage(text, type) {
            const time = getCurrentTime();
            
            // Create message row
            const messageRow = document.createElement('div');
            messageRow.className = `message-row ${type}`;
            
            // Create message bubble
            const messageBubble = document.createElement('div');
            messageBubble.className = 'message-bubble';
            messageBubble.textContent = text;
            messageRow.appendChild(messageBubble);
            
            // Create timestamp
            const timeElement = document.createElement('div');
            timeElement.className = 'message-time';
            timeElement.textContent = time;
            timeElement.style.textAlign = type === 'outgoing' ? 'right' : 'left';
            timeElement.style.marginLeft = type === 'incoming' ? '15px' : 'auto';
            timeElement.style.marginRight = type === 'outgoing' ? '15px' : 'auto';
            
            // Add to chat
            chatPart.appendChild(messageRow);
            chatPart.appendChild(timeElement);
            
            // Scroll to bottom
            chatPart.scrollTop = chatPart.scrollHeight;
        }
        
        // Function to show typing indicator
        function showTypingIndicator() {
            let indicator = document.getElementById('typing-indicator');
            if (!indicator) {
                indicator = document.createElement('div');
                indicator.id = 'typing-indicator';
                indicator.className = 'typing-indicator';
                indicator.innerHTML = `
                    <span style="color: var(--text-light); font-size: 0.85em;">Driver is typing</span>
                    <div class="typing-dots">
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                    </div>
                `;
                chatPart.appendChild(indicator);
            }
            indicator.style.display = 'flex';
            chatPart.scrollTop = chatPart.scrollHeight;
        }
        
        // Function to hide typing indicator
        function hideTypingIndicator() {
            const indicator = document.getElementById('typing-indicator');
            if (indicator) {
                indicator.style.display = 'none';
            }
        }
        
        // Function to get a random auto-reply
        function getRandomReply() {
            return autoReplies[Math.floor(Math.random() * autoReplies.length)];
        }
        
        // Function to get a random user message (for simulation)
        function getRandomUserMessage() {
            return userMessages[Math.floor(Math.random() * userMessages.length)];
        }
        
        // Function to send message
        function sendMessage() {
            const message = chatInput.value.trim();
            
            if (message === '') {
                // If empty, send a random user message (for demo)
                const randomMessage = getRandomUserMessage();
                chatInput.value = randomMessage;
                setTimeout(() => {
                    sendRealMessage(randomMessage);
                }, 100);
                return;
            }
            
            sendRealMessage(message);
        }
        
        // Actual message sending logic
        function sendRealMessage(message) {
            // Add user message
            addMessage(message, 'outgoing');
            
            // Clear input
            chatInput.value = '';
            chatInput.style.height = 'auto';
            
            // Show typing indicator after 0.5 seconds
            setTimeout(() => {
                showTypingIndicator();
                
                // Hide typing indicator and send reply after 1-2 seconds
                setTimeout(() => {
                    hideTypingIndicator();
                    
                    // Get appropriate reply
                    let reply;
                    const lowerMsg = message.toLowerCase();
                    
                    if (lowerMsg.includes('hello') || lowerMsg.includes('hi') || lowerMsg.includes('hey')) {
                        reply = "Hello! Welcome to the carpool chat! 🚗";
                    } else if (lowerMsg.includes('price') || lowerMsg.includes('cost') || lowerMsg.includes('money')) {
                        reply = "The cost is $5 per person. Cash or digital payment accepted!";
                    } else if (lowerMsg.includes('time') || lowerMsg.includes('when') || lowerMsg.includes('schedule')) {
                        reply = "We're meeting at 9:00 AM tomorrow at Central Station.";
                    } else if (lowerMsg.includes('where') || lowerMsg.includes('location') || lowerMsg.includes('meet')) {
                        reply = "Meeting point is Central Station, Gate 3. Look for the blue car!";
                    } else if (lowerMsg.includes('seat') || lowerMsg.includes('available') || lowerMsg.includes('space')) {
                        reply = "Yes, we have 2 seats available! Would you like to join?";
                    } else if (lowerMsg.includes('thank') || lowerMsg.includes('thanks')) {
                        reply = "You're welcome! Looking forward to our trip! 😊";
                    } else if (lowerMsg.includes('?')) {
                        reply = "Good question! Let me check and confirm for you.";
                    } else {
                        reply = getRandomReply();
                    }
                    
                    // Add the reply
                    addMessage(reply, 'incoming');
                    
                    // Randomly simulate another user message after 3-5 seconds
                    if (Math.random() > 0.5) {
                        setTimeout(() => {
                            const randomUserMsg = getRandomUserMessage();
                            addMessage(randomUserMsg, 'incoming');
                            
                            // Auto-reply to that message too
                            setTimeout(() => {
                                showTypingIndicator();
                                setTimeout(() => {
                                    hideTypingIndicator();
                                    addMessage(getRandomReply(), 'outgoing');
                                }, 1500);
                            }, 2000);
                        }, 3000 + Math.random() * 2000);
                    }
                    
                }, 1000 + Math.random() * 1000); // Random delay 1-2 seconds
                
            }, 500); // Wait 0.5 seconds before showing typing
        }
        
        // Enter key to send message (Shift+Enter for new line)
        chatInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
        
        // Auto-focus on input
        chatInput.focus();
        
        // Simulate some activity every 10-20 seconds
        setInterval(() => {
            if (Math.random() > 0.7) { // 30% chance
                const randomUserMsg = getRandomUserMessage();
                addMessage(randomUserMsg, 'incoming');
            }
        }, 10000 + Math.random() * 10000);
        
    </script>
</body>
</html>