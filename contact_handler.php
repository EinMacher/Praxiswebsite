<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session for CSRF protection
session_start();

// Set content type for JSON response
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// SPAM PROTECTION FUNCTIONS
function getRealIpAddr() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

function checkRateLimit($ip) {
    $rateLimitFile = 'rate_limit.json';
    $maxSubmissions = 3; // Max 3 submissions per hour
    $timeWindow = 3600; // 1 hour in seconds
    
    $currentTime = time();
    $rateLimitData = [];
    
    // Load existing rate limit data
    if (file_exists($rateLimitFile)) {
        $rateLimitData = json_decode(file_get_contents($rateLimitFile), true) ?: [];
    }
    
    // Clean old entries
    foreach ($rateLimitData as $recordedIp => $data) {
        if ($currentTime - $data['first_attempt'] > $timeWindow) {
            unset($rateLimitData[$recordedIp]);
        }
    }
    
    // Check current IP
    if (isset($rateLimitData[$ip])) {
        if ($rateLimitData[$ip]['count'] >= $maxSubmissions) {
            return false; // Rate limit exceeded
        }
        $rateLimitData[$ip]['count']++;
    } else {
        $rateLimitData[$ip] = [
            'count' => 1,
            'first_attempt' => $currentTime
        ];
    }
    
    // Save updated rate limit data
    file_put_contents($rateLimitFile, json_encode($rateLimitData));
    
    return true;
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function checkSpamContent($text) {
    $spamKeywords = [
        'viagra', 'cialis', 'casino', 'poker', 'loan', 'credit',
        'crypto', 'bitcoin', 'investment', 'money back', 'guarantee',
        'free money', 'make money', 'earn money', 'click here',
        'limited time', 'act now', 'urgent', 'congratulations',
        'winner', 'selected', 'special offer', 'bonus'
    ];
    
    $text = strtolower($text);
    
    foreach ($spamKeywords as $keyword) {
        if (strpos($text, $keyword) !== false) {
            return true; // Spam detected
        }
    }
    
    // Check for excessive links
    if (preg_match_all('/https?:\/\//', $text) > 2) {
        return true; // Too many links
    }
    
    // Check for excessive repetition
    if (preg_match('/(.{3,})\1{3,}/', $text)) {
        return true; // Repetitive content
    }
    
    return false;
}

function checkFormTiming() {
    $minTime = 3; // Minimum 3 seconds to fill form
    $maxTime = 3600; // Maximum 1 hour
    
    if (!isset($_POST['form_start_time'])) {
        return false;
    }
    
    $formStartTime = intval($_POST['form_start_time']);
    $currentTime = time();
    $timeTaken = $currentTime - $formStartTime;
    
    return ($timeTaken >= $minTime && $timeTaken <= $maxTime);
}

// Sanitize and validate input data
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// SPAM PROTECTION CHECKS
$userIP = getRealIpAddr();

// Check rate limiting
if (!checkRateLimit($userIP)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Zu viele Anfragen. Bitte versuchen Sie es später erneut.']);
    exit;
}

// Check CSRF token
if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Sicherheitstoken ungültig. Bitte laden Sie die Seite neu.']);
    exit;
}

// Check honeypot field (should be empty)
if (!empty($_POST['website'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Spam erkannt.']);
    exit;
}

// Check form timing
if (!checkFormTiming()) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Formular zu schnell ausgefüllt. Bitte versuchen Sie es erneut.']);
    exit;
}

// Get form data
$vorname = sanitizeInput($_POST['user_vorname'] ?? '');
$nachname = sanitizeInput($_POST['user_nachname'] ?? '');
$email = sanitizeInput($_POST['user_email'] ?? '');
$message = sanitizeInput($_POST['user_message'] ?? '');

// Check for spam content
$allContent = $vorname . ' ' . $nachname . ' ' . $message;
if (checkSpamContent($allContent)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nachricht enthält nicht erlaubte Inhalte.']);
    exit;
}

// Validate required fields
$errors = [];

if (empty($vorname) || strlen($vorname) < 2) {
    $errors[] = 'Vorname ist erforderlich (mindestens 2 Zeichen)';
}

if (empty($nachname) || strlen($nachname) < 2) {
    $errors[] = 'Nachname ist erforderlich (mindestens 2 Zeichen)';
}

if (empty($email)) {
    $errors[] = 'E-Mail ist erforderlich';
} elseif (!validateEmail($email)) {
    $errors[] = 'Ungültige E-Mail-Adresse';
}

if (empty($message) || strlen($message) < 10) {
    $errors[] = 'Nachricht ist erforderlich (mindestens 10 Zeichen)';
}

// Check for errors
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// Email configuration
$to = 'info@hausarzt-yilmaz-krumbach.de';
$subject = 'Neue Kontaktanfrage von der Website - ' . $vorname . ' ' . $nachname;
$from = $email;
$replyTo = $email;

// Create email body
$emailBody = "
Neue Kontaktanfrage von der Praxis-Website

Von: $vorname $nachname
E-Mail: $email
Datum: " . date('d.m.Y H:i:s') . "

Nachricht:
$message

---
Diese E-Mail wurde automatisch von der Praxis-Website gesendet.
";

// Email headers
$headers = [
    'From: Praxis Website <noreply@hausarzt-yilmaz-krumbach.de>',
    'Reply-To: ' . $replyTo,
    'X-Mailer: PHP/' . phpversion(),
    'Content-Type: text/plain; charset=UTF-8'
];

// Send email
$mailSent = mail($to, $subject, $emailBody, implode("\r\n", $headers));

if ($mailSent) {
    echo json_encode([
        'success' => true, 
        'message' => 'Vielen Dank für Ihre Nachricht! Wir werden uns bald bei Ihnen melden.',
        'redirect' => 'thank-you.html'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Entschuldigung, beim Senden der Nachricht ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut.'
    ]);
}

// Log the submission (optional - for debugging)
$logEntry = date('Y-m-d H:i:s') . " - Contact form submission from: $email\n";
file_put_contents('contact_log.txt', $logEntry, FILE_APPEND | LOCK_EX);
?>

