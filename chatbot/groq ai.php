<?php
session_start();
date_default_timezone_set('Asia/Manila'); // Set timezone to Philippine time

class Chatbot
{
    private $apiKey;
    private $apiUrl = "https://api.groq.com/openai/v1/chat/completions"; // Groq API endpoint

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function getResponse($chatHistory)
    {
        $messages = array_map(function ($entry) {
            return [
                "role" => $entry["role"] === "user" ? "user" : "assistant",
                "content" => $entry["parts"][0]["text"]
            ];
        }, $chatHistory);

        $data = [
            "model" => "llama3-70b-8192",
            "messages" => $messages,
            "temperature" => 0.7,
            "max_tokens" => 1024
        ];

        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer " . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            return "Error: Connection failed. Details: " . $error;
        }

        curl_close($ch);

        if ($httpCode !== 200) {
            return "Error: API request failed. HTTP Code: " . $httpCode . ". Response: " . $response;
        }

        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return "Error: Invalid JSON response. Details: " . json_last_error_msg();
        }

        // Remove asterisks from the response
        $content = $result['choices'][0]['message']['content'] ?? "Sorry, I couldn't process that.";
        $content = str_replace('*', '', $content);
        
        return $content;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    $apiKey = "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"; // Replace with your actual Groq API key
    $chatbot = new Chatbot($apiKey);

    if (isset($_POST['new_chat'])) {
        $_SESSION['chat_history'] = [];
        echo json_encode(['status' => 'success', 'action' => 'new_chat']);
        exit;
    } elseif (isset($_POST['get_suggestions'])) {
        $suggestions = getSuggestions();
        echo json_encode(['status' => 'success', 'suggestions' => $suggestions]);
        exit;
    } elseif (!empty($_POST['message'])) {
        $userInput = htmlspecialchars($_POST['message']);
        if (!isset($_SESSION['chat_history'])) {
            $_SESSION['chat_history'] = [];
        }

        $userEntry = [
            "role" => "user",
            "parts" => [["text" => $userInput]],
            "timestamp" => date("g:i A") // Changed to time only
        ];
        $_SESSION['chat_history'][] = $userEntry;

        $botResponse = $chatbot->getResponse($_SESSION['chat_history']);
        $botEntry = [
            "role" => "model",
            "parts" => [["text" => $botResponse]],
            "timestamp" => date("g:i A") // Changed to time only
        ];
        $_SESSION['chat_history'][] = $botEntry;

        echo json_encode([
            'status' => 'success',
            'user_message' => $userEntry,
            'bot_message' => $botEntry,
            'index' => count($_SESSION['chat_history']) - 2
        ]);
        exit;
    }
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

// Function to get random suggestions
function getSuggestions() {
    $allSuggestions = [
        // Creative writing
        "Write a story",
        "Create a poem",
        "Write a joke",
        "Invent a character",
        "Design a movie plot",
        // Knowledge
        "Explain quantum physics",
        "Tell me about black holes",
        "How does the brain work?",
        "Describe photosynthesis",
        "History of the internet",
        // Travel
        "Japan travel tips",
        "Best places in Europe",
        "Weekend getaway ideas",
        "Budget travel hacks",
        "Must-see natural wonders",
        // Technology
        "Todo app code",
        "Explain blockchain",
        "How AI works",
        "Future tech trends",
        "Smart home setup",
        // Lifestyle
        "Dinner party ideas",
        "Workout routine",
        "Meditation techniques",
        "Productivity tips",
        "Home organization",
        // Learning
        "Machine learning basics",
        "Learn a new language",
        "Chess strategies",
        "Stock market basics",
        "Public speaking tips"
    ];
    
    // Shuffle the array
    shuffle($allSuggestions);
    
    // Return the first 6 items
    return array_slice($allSuggestions, 0, 6);
}

// Get initial suggestions
$initialSuggestions = getSuggestions();

if (!isset($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ask AI</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&family=Roboto:wght@300;400;500&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --primary-color: #8C52FF;
            --primary-light: #EFE6FD;
            --background-color: #FFFFFF;
            --surface-color: #F8F9FA;
            --border-color: #E1E3E6;
            --text-primary: #1F1F1F;
            --text-secondary: #5F6368;
            --user-msg-bg: #8C52FF;
            --user-msg-text: #FFFFFF;
            --bot-msg-bg: #F5F5F7;
            --bot-msg-text: #1F1F1F;
            --input-bg: #F8F9FA;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 2px 10px rgba(0, 0, 0, 0.08);
            --radius-sm: 10px;
            --radius-md: 14px;
            --radius-lg: 24px;
            --font-family: 'Google Sans', 'Roboto', sans-serif;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-family);
            background-color: #F2F3F9;
            color: var(--text-primary);
            line-height: 1.5;
            font-size: 16px;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .chat-app {
            background-color: var(--background-color);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            width: 100%;
            height: 90vh;
            max-height: 800px;
            border: 1px solid var(--border-color);
            transition: var(--transition);
        }

        .chat-header {
            padding: 18px 24px;
            border-bottom: 1px solid var(--border-color);
            background-color: var(--background-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 10;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .chat-title {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .chat-title h1 {
            font-size: 20px;
            font-weight: 500;
            color: var(--text-primary);
        }

        .chat-logo {
            width: 40px;
            height: 40px;
            background-color: var(--primary-color);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            box-shadow: 0 2px 8px rgba(140, 82, 255, 0.3);
        }

        .chat-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .btn {
            border: none;
            border-radius: var(--radius-sm);
            padding: 10px 18px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: var(--font-family);
        }

        .btn-primary {
            background-color: var(--primary-light);
            color: var(--primary-color);
            border: 1px solid transparent;
        }

        .btn-primary:hover {
            background-color: rgba(140, 82, 255, 0.12);
            border-color: var(--primary-color);
            transform: translateY(-1px);
        }

        .chat-container {
            flex: 1;
            overflow-y: auto;
            padding: 24px;
            background-color: var(--background-color);
            background-image: 
                radial-gradient(circle at 20% 35%, rgba(140, 82, 255, 0.04) 0%, transparent 40%),
                radial-gradient(circle at 75% 70%, rgba(140, 82, 255, 0.04) 0%, transparent 40%);
        }

        .message-group {
            margin-bottom: 24px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            animation: fadeIn 0.3s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message {
            padding: 14px 18px;
            border-radius: var(--radius-md);
            margin-bottom: 8px;
            position: relative;
            line-height: 1.5;
            display: inline-block;
            max-width: 85%;
            word-wrap: break-word;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }
        
        .message:hover {
            box-shadow: var(--shadow-md);
        }

        .user-message {
            background-color: var(--user-msg-bg);
            color: var(--user-msg-text);
            margin-left: auto;
            border-radius: var(--radius-md) var(--radius-md) 0 var(--radius-md);
            align-self: flex-end;
            padding: 14px 18px;
            max-width: 85%;
            word-break: break-word;
        }

        .bot-message {
            background-color: var(--bot-msg-bg);
            color: var(--bot-msg-text);
            margin-right: auto;
            border-radius: var(--radius-md) var(--radius-md) var(--radius-md) 0;
            align-self: flex-start;
        }

        .message-header {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 6px;
            color: #6c38d0;
        }

        .message-content {
            word-break: break-word;
            font-size: 15px;
            white-space: normal;
        }

        .message-timestamp {
            font-size: 11px;
            margin-top: 4px;
            opacity: 0.7;
            text-align: right;
        }

        .user-message .message-timestamp {
            color: rgba(255, 255, 255, 0.8);
            text-align: right;
        }

        .message-actions {
            position: absolute;
            top: 8px;
            right: 8px;
            display: none;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 4px;
            padding: 2px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .user-message .message-actions {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .message:hover .message-actions {
            display: flex;
            animation: fadeIn 0.2s ease-out;
        }

        .action-btn {
            background: none;
            border: none;
            cursor: pointer;
            color: inherit;
            opacity: 0.7;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .action-btn:hover {
            opacity: 1;
            background-color: rgba(0, 0, 0, 0.1);
            transform: scale(1.1);
        }

        .typing-indicator {
            display: none;
            align-items: center;
            gap: 8px;
            color: var(--text-secondary);
            font-size: 14px;
            margin-bottom: 16px;
            padding: 10px 16px;
            background-color: var(--bot-msg-bg);
            border-radius: var(--radius-md);
            width: fit-content;
            box-shadow: var(--shadow-sm);
            animation: fadeIn 0.3s ease-out;
        }

        .typing-dot {
            width: 7px;
            height: 7px;
            background-color: var(--primary-color);
            border-radius: 50%;
            animation: typingAnimation 1.4s infinite ease-in-out;
        }

        .typing-dot:nth-child(1) {
            animation-delay: 0s;
        }

        .typing-dot:nth-child(2) {
            animation-delay: 0.2s;
        }

        .typing-dot:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes typingAnimation {
            0%, 60%, 100% {
                transform: translateY(0);
            }
            30% {
                transform: translateY(-4px);
            }
        }

        .chat-input-container {
            padding: 16px 24px 20px;
            border-top: 1px solid var(--border-color);
            background-color: var(--background-color);
            box-shadow: 0 -1px 3px rgba(0, 0, 0, 0.05);
            position: relative;
            z-index: 5;
        }

        .chat-input-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
            background-color: var(--input-bg);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 8px 14px 8px 20px;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }

        .chat-input-wrapper:focus-within {
            box-shadow: 0 2px 8px rgba(140, 82, 255, 0.15);
            border-color: var(--primary-color);
            transform: translateY(-1px);
        }

        .chat-input {
            flex: 1;
            border: none;
            background: none;
            padding: 8px 0;
            font-size: 15px;
            color: var(--text-primary);
            outline: none;
            font-family: var(--font-family);
        }

        .chat-input::placeholder {
            color: var(--text-secondary);
        }

        .send-btn {
            background-color: var(--primary-color);
            border: none;
            color: white;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            font-size: 16px;
            box-shadow: 0 2px 5px rgba(140, 82, 255, 0.3);
        }

        .send-btn:hover {
            background-color: #7840e6;
            transform: scale(1.05);
        }

        .send-btn:disabled {
            background-color: #DADCE0;
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            gap: 16px;
            text-align: center;
            padding: 32px;
            color: var(--text-secondary);
            animation: fadeIn 0.4s ease-out;
        }

        .empty-icon {
            font-size: 48px;
            color: var(--primary-color);
            margin-bottom: 16px;
            background-color: var(--primary-light);
            width: 90px;
            height: 90px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(140, 82, 255, 0.2);
        }

        .empty-state h2 {
            font-size: 24px;
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .empty-state p {
            font-size: 16px;
            max-width: 500px;
            color: var(--text-secondary);
            margin-bottom: 10px;
        }

        .chat-container::-webkit-scrollbar {
            width: 6px;
        }

        .chat-container::-webkit-scrollbar-thumb {
            background-color: #DADCE0;
            border-radius: 3px;
        }

        .chat-container::-webkit-scrollbar-thumb:hover {
            background-color: #BABDC2;
        }

        .chat-container::-webkit-scrollbar-track {
            background-color: transparent;
        }

        .mic-button {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-secondary);
            opacity: 0.8;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            position: relative;
            transition: var(--transition);
            border-radius: 50%;
        }

        .mic-button:hover {
            opacity: 1;
            background-color: rgba(0, 0, 0, 0.05);
        }

        .mic-button.recording {
            position: relative;
            opacity: 1;
            background-color: rgba(234, 67, 53, 0.1);
        }

        .mic-button.recording::after {
            content: '';
            position: absolute;
            width: 8px;
            height: 8px;
            background-color: #EA4335;
            border-radius: 50%;
            right: 4px;
            top: 4px;
            animation: pulse 1.2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 0.7;
            }
            50% {
                transform: scale(1.2);
                opacity: 1;
            }
            100% {
                transform: scale(1);
                opacity: 0.7;
            }
        }

        .mic-button i {
            font-size: 16px;
        }

        .mic-button i.fa-microphone-slash {
            color: #EA4335;
        }

        .suggestions-container {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            padding: 20px;
            justify-content: center;
            max-width: 700px;
            margin: 16px auto;
        }
        
        .suggestion-chip {
            background-color: var(--primary-light);
            color: var(--primary-color);
            border-radius: 20px;
            padding: 10px 18px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            border: 1px solid transparent;
            white-space: nowrap;
            text-overflow: ellipsis;
            overflow: hidden;
            max-width: 230px;
            box-shadow: var(--shadow-sm);
        }
        
        .suggestion-chip:hover {
            background-color: rgba(140, 82, 255, 0.15);
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(140, 82, 255, 0.2);
        }

        pre {
            background-color: #F8F9FA;
            border: 1px solid #DADCE0;
            border-radius: 8px;
            padding: 14px;
            overflow-x: auto;
            margin: 14px 0;
            font-family: 'Roboto Mono', monospace;
            font-size: 13px;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        code {
            font-family: 'Roboto Mono', monospace;
            font-size: 13px;
            background-color: #F8F9FA;
            padding: 2px 6px;
            border-radius: 4px;
        }

        @media (max-width: 768px) {
            .chat-app {
                height: 95vh;
                border-radius: 12px;
            }

            .message {
                max-width: 90%;
                padding: 12px 16px;
            }

            .container {
                padding: 10px;
            }

            .empty-state h2 {
                font-size: 22px;
            }

            .empty-state p {
                font-size: 15px;
            }

            .chat-input-container {
                padding: 14px 16px 18px;
            }

            .chat-container {
                padding: 20px;
            }

            .chat-header {
                padding: 16px 20px;
            }

            .send-btn,
            .mic-button {
                width: 34px;
                height: 34px;
            }
            
            .suggestions-container {
                padding: 10px;
                gap: 8px;
            }
            
            .suggestion-chip {
                padding: 8px 16px;
                font-size: 13px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="chat-app">
            <div class="chat-header">
                <div class="chat-title">
                    <div class="chat-logo">
                        <i class="fas fa-robot"></i>
                    </div>
                    <h1>Ask AI</h1>
                </div>
                <div class="chat-actions">
                    <button class="btn btn-primary" id="new-chat-btn" aria-label="Start a new chat">
                        <i class="fas fa-plus"></i> New Chat
                    </button>
                </div>
            </div>

            <div class="chat-container" id="chat-container">
                <div id="message-area">
                    <?php if (empty($_SESSION['chat_history'])): ?>
                        <div class="empty-state" id="empty-state">
                            <div class="empty-icon"><i class="fas fa-robot"></i></div>
                            <h2>How can I help you today?</h2>
                            <p>I'm here to assist with any questions or tasks you have!</p>
                            
                            <div class="suggestions-container" id="suggestions-container">
                                <?php foreach($initialSuggestions as $suggestion): ?>
                                    <div class="suggestion-chip"><?php echo htmlspecialchars($suggestion); ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php
                        $currentRole = null;
                        $messageGroupOpen = false;

                        foreach ($_SESSION['chat_history'] as $index => $entry):
                            $isUser = $entry['role'] === 'user';
                            $messageClass = $isUser ? 'user-message' : 'bot-message';
                            $label = $isUser ? 'You' : 'Ask AI';
                            $text = $entry['parts'][0]['text'];
                            $timestamp = isset($entry['timestamp']) ? $entry['timestamp'] : 'Unknown';

                            if ($currentRole !== $entry['role']) {
                                if ($messageGroupOpen) {
                                    echo "</div>";
                                    $messageGroupOpen = false;
                                }
                                echo "<div class='message-group'>";
                                $messageGroupOpen = true;
                                $currentRole = $entry['role'];
                            }
                            ?>
                            <div class="message <?php echo $messageClass; ?>" id="message-wrapper-<?php echo $index; ?>">
                                <?php if (!$isUser): ?>
                                    <div class="message-header"><?php echo $label; ?></div>
                                <?php endif; ?>
                                <div class="message-content" id="message-content-<?php echo $index; ?>">
                                    <?php
                                    $pattern = '/```([a-zA-Z]*)\n([\s\S]*?)```/';
                                    $replacement = '<pre><code class="language-$1">$2</code></pre>';
                                    $processed_text = preg_replace($pattern, $replacement, htmlspecialchars($text));
                                    echo nl2br($processed_text);
                                    ?>
                                </div>
                                <div class="message-timestamp"><?php echo $timestamp; ?></div>
                                <div class="message-actions">
                                    <button class="action-btn copy-btn" onclick="copyToClipboard(<?php echo $index; ?>)"
                                        aria-label="Copy message">
                                        <i class="fas fa-copy" id="copy-icon-<?php echo $index; ?>"></i>
                                    </button>
                                </div>
                            </div>
                            <?php
                        endforeach;
                        if ($messageGroupOpen) {
                            echo "</div>";
                        }
                        ?>
                    <?php endif; ?>
                </div>
                <div class="typing-indicator" id="typing-indicator">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <span>Ask AI is thinking...</span>
                </div>
            </div>

            <div class="chat-input-container">
                <div class="chat-input-wrapper">
                    <input type="text" class="chat-input" id="message-input" placeholder="Type your message..."
                        autocomplete="off" required aria-label="Type your message">
                    <button class="mic-button" onclick="voiceToText.toggleListening()">
                        <i id="mic-icon" class="fas fa-microphone"></i>
                    </button>
                    <button class="send-btn" id="send-btn" aria-label="Send message">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const chatContainer = document.getElementById('chat-container');
        const messageArea = document.getElementById('message-area');
        const messageInput = document.getElementById('message-input');
        const sendBtn = document.getElementById('send-btn');
        const typingIndicator = document.getElementById('typing-indicator');
        const newChatBtn = document.getElementById('new-chat-btn');
        let messageIndex = <?php echo count($_SESSION['chat_history']); ?>;

        chatContainer.scrollTop = chatContainer.scrollHeight;

        // Add suggestion chip functionality
        document.addEventListener('DOMContentLoaded', function() {
            setupSuggestionChips();
        });

        function setupSuggestionChips() {
            const suggestionChips = document.querySelectorAll('.suggestion-chip');
            suggestionChips.forEach(chip => {
                chip.addEventListener('click', function() {
                    messageInput.value = this.textContent;
                    messageInput.focus();
                    
                    // Automatically send the message
                    sendMessage();
                });
            });
        }

        // Function to get new suggestions from the server
        function getNewSuggestions() {
            return new Promise((resolve, reject) => {
                const formData = new FormData();
                formData.append('get_suggestions', '1');
                formData.append('ajax', '1');

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        resolve(data.suggestions);
                    } else {
                        reject('Failed to get suggestions');
                    }
                })
                .catch(error => {
                    reject(error);
                });
            });
        }

        // Function to update suggestion chips
        function updateSuggestionChips() {
            getNewSuggestions()
                .then(suggestions => {
                    const container = document.getElementById('suggestions-container');
                    if (container) {
                        container.innerHTML = '';
                        suggestions.forEach(suggestion => {
                            const chip = document.createElement('div');
                            chip.className = 'suggestion-chip';
                            chip.textContent = suggestion;
                            container.appendChild(chip);
                        });
                        setupSuggestionChips();
                    }
                })
                .catch(error => console.error('Error updating suggestions:', error));
        }

        function sendMessage() {
            const message = messageInput.value.trim();
            if (!message) return;

            // Immediately display user message with time only
            const timestamp = new Date().toLocaleString('en-US', {
                hour: 'numeric',
                minute: 'numeric',
                hour12: true
            });
            appendMessage('user', 'You', message, timestamp, messageIndex);
            messageIndex++;

            sendBtn.disabled = true;
            typingIndicator.style.display = 'flex';
            messageInput.value = '';
            scrollToBottom();

            const emptyState = document.getElementById('empty-state');
            if (emptyState) emptyState.remove();

            // Send message to server for AI response
            const formData = new FormData();
            formData.append('message', message);
            formData.append('ajax', '1');

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        appendMessage(data.bot_message.role, 'Ask AI', data.bot_message.parts[0].text, data.bot_message.timestamp, messageIndex);
                        messageIndex++;
                    } else {
                        appendMessage('bot', 'Ask AI', 'Error: ' + data.message, new Date().toLocaleString('en-US', { hour: 'numeric', minute: 'numeric', hour12: true }), messageIndex);
                        messageIndex++;
                    }
                })
                .catch(error => {
                    appendMessage('bot', 'Ask AI', 'Error: Could not connect to server.', new Date().toLocaleString('en-US', { hour: 'numeric', minute: 'numeric', hour12: true }), messageIndex);
                    messageIndex++;
                })
                .finally(() => {
                    sendBtn.disabled = false;
                    typingIndicator.style.display = 'none';
                    scrollToBottom();
                });
        }

        function appendMessage(role, label, text, timestamp, index) {
            const isUser = role === 'user';
            const messageClass = isUser ? 'user-message' : 'bot-message';

            let messageGroup = messageArea.querySelector('.message-group:last-child');

            if (!messageGroup || (isUser && messageGroup.querySelector('.message:last-child')?.classList.contains('bot-message')) || (!isUser && messageGroup.querySelector('.message:last-child')?.classList.contains('user-message'))) {
                messageGroup = document.createElement('div');
                messageGroup.className = 'message-group';
                messageArea.appendChild(messageGroup);
            }

            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${messageClass}`;
            messageDiv.id = `message-wrapper-${index}`;

            const pattern = /```([a-zA-Z]*)\n([\s\S]*?)```/g;
            const processedText = text.replace(pattern, '<pre><code class="language-$1">$2</code></pre>');

            let headerHtml = '';
            if (!isUser) {
                headerHtml = `<div class="message-header">${label}</div>`;
            }

            messageDiv.innerHTML = `
                ${headerHtml}
                <div class="message-content" id="message-content-${index}">${processedText.replace(/\n/g, '<br>')}</div>
                <div class="message-timestamp">${timestamp}</div>
                <div class="message-actions">
                    <button class="action-btn copy-btn" onclick="copyToClipboard(${index})" aria-label="Copy message">
                        <i class="fas fa-copy" id="copy-icon-${index}"></i>
                    </button>
                </div>
            `;
            messageGroup.appendChild(messageDiv);
            scrollToBottom();
        }

        function scrollToBottom() {
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        function copyToClipboard(index) {
            const text = document.getElementById(`message-content-${index}`).innerText;
            const copyIcon = document.getElementById(`copy-icon-${index}`);
            navigator.clipboard.writeText(text).then(() => {
                copyIcon.classList.replace('fa-copy', 'fa-check');
                setTimeout(() => copyIcon.classList.replace('fa-check', 'fa-copy'), 2000);
            });
        }

        sendBtn.addEventListener('click', (e) => {
            e.preventDefault();
            sendMessage();
        });

        messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        newChatBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const formData = new FormData();
            formData.append('new_chat', '1');
            formData.append('ajax', '1');

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.action === 'new_chat') {
                        // Get new suggestions for the empty state
                        getNewSuggestions()
                            .then(suggestions => {
                                const suggestionHtml = suggestions.map(suggestion => 
                                    `<div class="suggestion-chip">${suggestion}</div>`
                                ).join('');
                                
                                messageArea.innerHTML = `
                                <div class="empty-state" id="empty-state">
                                    <div class="empty-icon"><i class="fas fa-robot"></i></div>
                                    <h2>How can I help you today?</h2>
                                    <p>I'm here to assist with any questions or tasks you have!</p>
                                    
                                    <div class="suggestions-container" id="suggestions-container">
                                        ${suggestionHtml}
                                    </div>
                                </div>
                                `;
                                messageIndex = 0;
                                setupSuggestionChips(); // Setup event listeners for new suggestions
                            })
                            .catch(error => {
                                console.error('Error getting suggestions:', error);
                                messageArea.innerHTML = `
                                <div class="empty-state" id="empty-state">
                                    <div class="empty-icon"><i class="fas fa-robot"></i></div>
                                    <h2>How can I help you today?</h2>
                                    <p>I'm here to assist with any questions or tasks you have!</p>
                                </div>
                                `;
                                messageIndex = 0;
                            });
                    }
                })
                .catch(error => console.error('New Chat error:', error));
        });

        class VoiceToText {
            constructor() {
                this.recognition = null;
                this.isListening = false;
                this.micButton = document.querySelector('.mic-button');
                this.initSpeechRecognition();
            }

            initSpeechRecognition() {
                if ('webkitSpeechRecognition' in window) {
                    this.recognition = new webkitSpeechRecognition();
                    this.recognition.continuous = true;
                    this.recognition.interimResults = true;
                    this.recognition.lang = 'en-US';

                    let timeoutId = null;

                    this.recognition.onstart = () => {
                        this.isListening = true;
                        this.updateMicIcon();
                    };

                    this.recognition.onresult = (event) => {
                        clearTimeout(timeoutId);
                        const transcript = event.results[event.results.length - 1][0].transcript;
                        const isFinal = event.results[event.results.length - 1].isFinal;
                        const inputField = document.querySelector('#message-input');

                        if (inputField) {
                            inputField.value = transcript;
                            if (isFinal) {
                                timeoutId = setTimeout(() => sendMessage(), 100);
                            }
                        }
                    };

                    this.recognition.onerror = (event) => {
                        console.error('Speech recognition error:', event.error);
                        this.isListening = false;
                        this.updateMicIcon();
                    };

                    this.recognition.onend = () => {
                        if (this.isListening) {
                            this.recognition.start();
                        } else {
                            this.updateMicIcon();
                        }
                    };
                } else {
                    console.error('Speech recognition not supported in this browser');
                }
            }

            toggleListening() {
                if (!this.recognition) {
                    alert('Speech recognition is not supported in your browser');
                    return;
                }

                if (this.isListening) {
                    this.isListening = false;
                    this.recognition.stop();
                } else {
                    this.isListening = true;
                    this.recognition.start();
                }
                this.updateMicIcon();
            }

            updateMicIcon() {
                const micIcon = document.querySelector('#mic-icon');
                if (micIcon) {
                    micIcon.className = this.isListening ? 'fas fa-microphone-slash' : 'fas fa-microphone';
                    if (this.isListening) {
                        this.micButton.classList.add('recording');
                    } else {
                        this.micButton.classList.remove('recording');
                    }
                }
            }
        }

        const voiceToText = new VoiceToText();
    </script>
</body>

</html>