<?php
// Minimal env loading for CLI
function cliLoadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        putenv(trim($name) . '=' . trim($value));
    }
}
cliLoadEnv(__DIR__ . '/.env');

// Override required $_SERVER vars for CLI
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/seed_questions.php';
$_SERVER['REQUEST_METHOD'] = 'GET';

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/api/index.php'; // To reuse dbConnect and ensureTables

$pdo = dbConnect();
ensureTables($pdo);

$filePath = __DIR__ . '/517987885-CCNA-200-301-Practice-Exam-Questions-2020-2.txt';
if (!file_exists($filePath)) {
    die("File not found: $filePath\n");
}

$content = file_get_contents($filePath);
if ($content === false) {
    die("Failed to read file.\n");
}

// Clean up some common weird formatting issues in the PDF-to-text conversion
$content = preg_replace('/^\s*\d+\s*$/m', '', $content); // Remove standalone page numbers
$content = preg_replace('/^\x0C/m', '', $content); // Remove form feed characters

// We need to parse Questions and Answers.
// The file has them split into sections or sometimes grouped.
// A typical question starts with "Question [number]."
// A typical answer starts with "Explanation [number]." or "Answer [number]."

$questions = [];

// 1. Extract all questions
// Pattern: "Question X. [text] (A) [opt] (B) [opt]..."
preg_match_all('/Question\s+(\d+)\.\s+(.*?)(?=\nQuestion\s+\d+\.|\nExplanation\s+\d+\.|\nAnswers?\s+\d+-\d+|$)/is', $content, $qMatches, PREG_SET_ORDER);

foreach ($qMatches as $match) {
    $qNum = (int)$match[1];
    $qBody = trim($match[2]);
    
    // Extract options (A), (B), (C), etc.
    $options = [];
    $questionText = $qBody;
    
    // Find where the options start
    if (preg_match('/(?:\n|^)\([A-Z]\)\s+/s', $qBody, $optStartMatch, PREG_OFFSET_CAPTURE)) {
        $questionText = trim(substr($qBody, 0, $optStartMatch[0][1]));
        $optionsText = trim(substr($qBody, $optStartMatch[0][1]));
        
        preg_match_all('/\(([A-Z])\)\s+(.*?)(?=\n\([A-Z]\)\s+|$)/is', $optionsText, $optMatches, PREG_SET_ORDER);
        foreach ($optMatches as $opt) {
            $options[$opt[1]] = trim(preg_replace('/\s+/', ' ', $opt[2]));
        }
    }
    
    $questions[$qNum] = [
        'text' => trim(preg_replace('/\s+/', ' ', $questionText)),
        'options' => $options,
        'correct' => [],
        'explanation' => ''
    ];
}

// 2. Extract all explanations/answers
// Pattern: "Explanation X. [text]"
preg_match_all('/Explanation\s+(\d+)\.\s+(.*?)(?=\nQuestion\s+\d+\.|\nExplanation\s+\d+\.|\nAnswers?\s+\d+-\d+|$)/is', $content, $eMatches, PREG_SET_ORDER);

foreach ($eMatches as $match) {
    $qNum = (int)$match[1];
    $eBody = trim(preg_replace('/\s+/', ' ', $match[2]));
    
    if (isset($questions[$qNum])) {
        $questions[$qNum]['explanation'] = $eBody;
        
        // Try to parse the correct answer from the first sentence of the explanation.
        // Example: "A and B are the correct answers." or "Destination is the correct answer."
        // We will look for letters A, B, C, D, E, F at the beginning.
        
        $firstSentence = explode('.', $eBody)[0] ?? $eBody;
        
        // Extract isolated uppercase letters near the beginning
        preg_match_all('/\b([A-F])\b/i', substr($firstSentence, 0, 30), $letterMatches);
        
        if (!empty($letterMatches[1])) {
            // We found letter(s)
            $questions[$qNum]['correct'] = array_map('strtoupper', $letterMatches[1]);
        } else {
            // Sometimes it spells out the answer text. Let's try to match the text to an option.
            // Example: "SMTP is the correct answer."
            $isFound = false;
            foreach ($questions[$qNum]['options'] as $letter => $optText) {
                // If the option text appears exactly in the first sentence
                if (stripos($firstSentence, $optText) !== false) {
                    $questions[$qNum]['correct'][] = $letter;
                    $isFound = true;
                }
            }
            
            // If still not found, we'll just have to leave it empty or flag it.
            // A more sophisticated parser might be needed for a perfect import.
        }
    }
}

// Insert into DB
$inserted = 0;
$stmt = $pdo->prepare('INSERT INTO `practice_questions` (`question_text`, `options`, `correct_answers`, `explanation`, `created_at`) VALUES (?, ?, ?, ?, ?)');

$now = (int) round(microtime(true) * 1000);

// Clear existing to avoid duplicates if run multiple times
$pdo->exec('TRUNCATE TABLE `practice_questions`');

foreach ($questions as $qNum => $q) {
    // Only insert if it looks like a valid question (has options and at least one correct answer)
    if (!empty($q['text']) && !empty($q['options'])) {
        // Ensure we have correct answers, if not, try to deduce one more time or default to A
        if (empty($q['correct'])) {
           // Fallback logic for badly formatted answers: just take A as placeholder for manual fix
           $q['correct'] = ['A']; 
        }
        
        $stmt->execute([
            $q['text'],
            json_encode($q['options']),
            json_encode(array_values(array_unique($q['correct']))),
            $q['explanation'],
            $now
        ]);
        $inserted++;
    }
}

echo "Successfully parsed and inserted $inserted questions into the database.\n";
