<?php
// Start the session before sending any HTML.
session_start();

// Count visits for this browser session.
if (!isset($_SESSION['visits'])) {
    $_SESSION['visits'] = 1;
} else {
    $_SESSION['visits']++;
}

// Connect to PostgreSQL.
require_once 'db.php';

// Escape all text displayed in the page.
function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Handle a new message submitted with POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $author = trim($_POST['author'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $remember = isset($_POST['remember_name']);

    if ($author !== '' && $body !== '') {
        // Prepared statement protects user input.
        $insert = $pdo->prepare(
            'INSERT INTO messages (author, body) VALUES (:author, :body)'
        );
        $insert->execute([
            ':author' => $author,
            ':body' => $body
        ]);

        // Save or remove the name cookie depending on the checkbox.
        if ($remember) {
            setcookie('remembered_name', $author, [
                'expires' => time() + (30 * 24 * 60 * 60),
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        } else {
            setcookie('remembered_name', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }

        // Redirect to prevent the browser from resubmitting the form.
        header('Location: index.php');
        exit;
    }

    $error = 'Please enter both your name and a message.';
}

// Read search text using GET.
$search = trim($_GET['search'] ?? '');

if ($search !== '') {
    $select = $pdo->prepare(
        "SELECT id, author, body, created_at
         FROM messages
         WHERE author ILIKE :search OR body ILIKE :search
         ORDER BY created_at DESC, id DESC"
    );
    $select->execute([':search' => '%' . $search . '%']);
} else {
    $select = $pdo->query(
        'SELECT id, author, body, created_at
         FROM messages
         ORDER BY created_at DESC, id DESC'
    );
}

$messages = $select->fetchAll();

// Use the cookie to pre-fill the name field on the next visit.
$rememberedName = $_COOKIE['remembered_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClassWall - Message Board</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 30px 16px;
            background: #f1f5f9;
            color: #1e293b;
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 760px;
            margin: 0 auto;
        }
        h1 { color: #1d4ed8; margin-bottom: 6px; }
        .subtitle { color: #64748b; margin-top: 0; }
        .panel, .message {
            background: white;
            padding: 22px;
            margin: 18px 0;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,.06);
        }
        label { display: block; margin: 12px 0 6px; font-weight: bold; }
        input[type="text"], textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font: inherit;
        }
        textarea { min-height: 110px; resize: vertical; }
        .remember {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 14px 0;
        }
        .remember label { margin: 0; font-weight: normal; }
        button {
            background: #2563eb;
            color: white;
            border: 0;
            padding: 11px 18px;
            border-radius: 7px;
            cursor: pointer;
            font-size: 15px;
        }
        button:hover { background: #1d4ed8; }
        .search-row { display: flex; gap: 8px; }
        .search-row input { flex: 1; min-width: 0; }
        .message h3 { margin: 0 0 8px; }
        .message p { white-space: pre-wrap; overflow-wrap: anywhere; }
        .date, .visit { color: #64748b; font-size: 13px; }
        .error { color: #b91c1c; }
        a { color: #2563eb; }
    </style>
</head>
<body>
<div class="container">
    <h1>ClassWall</h1>
    <p class="subtitle">A shared message board for our class!</p>

    <p class="visit">You've visited <?php echo h($_SESSION['visits']); ?> times during this browser session.</p>

    <section class="panel">
        <h2>Post a message</h2>

        <?php if (!empty($error)): ?>
            <p class="error"><?php echo h($error); ?></p>
        <?php endif; ?>

        <form method="POST" action="index.php">
            <label for="author">Your name</label>
            <input
                type="text"
                id="author"
                name="author"
                maxlength="100"
                value="<?php echo h($rememberedName); ?>"
                required
            >

            <label for="body">Your message</label>
            <textarea id="body" name="body" required></textarea>

            <div class="remember">
                <input type="checkbox" id="remember_name" name="remember_name">
                <label for="remember_name">Remember my name</label>
            </div>

            <button type="submit">Post Message</button>
        </form>
    </section>

    <section class="panel">
        <h2>Search messages</h2>
        <form method="GET" action="index.php" class="search-row">
            <input
                type="text"
                name="search"
                placeholder="Search messages..."
                value="<?php echo h($search); ?>"
            >
            <button type="submit">Search</button>
        </form>
        <?php if ($search !== ''): ?>
            <p>Showing results for: <strong><?php echo h($search); ?></strong>
                | <a href="index.php">Clear search</a>
            </p>
        <?php endif; ?>
    </section>

    <h2><?php echo $search !== '' ? 'Search Results' : 'All Messages'; ?></h2>

    <?php if (count($messages) === 0): ?>
        <section class="message">No messages found. Be the first to post!</section>
    <?php else: ?>
        <?php foreach ($messages as $message): ?>
            <article class="message">
                <h3><?php echo h($message['author']); ?></h3>
                <p><?php echo h($message['body']); ?></p>
                <div class="date"><?php echo h($message['created_at']); ?></div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>
