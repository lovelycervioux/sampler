<?php
session_start();
require_once 'config.php';

function getAllBooks($pdo) {
    $stmt = $pdo->query("SELECT * FROM books ORDER BY title");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$error = '';
$existing_books = getAllBooks($pdo);

$selected_book_id = isset($_GET['book_id']) ? (int)$_GET['book_id'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book_id = null;
    $chapter_title = trim($_POST['chapter_title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    try {
        // Handle book selection/creation
        if (isset($_POST['new_book']) && $_POST['new_book']) {
            $book_title = trim($_POST['book_title'] ?? '');
            if (empty($book_title)) {
                throw new Exception("Book title is required");
            }
            $stmt = $pdo->prepare("INSERT OR IGNORE INTO books (title) VALUES (?)");
            $stmt->execute([$book_title]);
            $stmt = $pdo->prepare("SELECT id FROM books WHERE title = ?");
            $stmt->execute([$book_title]);
            $book = $stmt->fetch(PDO::FETCH_ASSOC);
            $book_id = $book['id'] ?? null;
        } else {
            if (isset($_POST['book_id']) && $_POST['book_id']) {
                $book_id = $_POST['book_id'];
            } else {
                throw new Exception("No book selected or created.");
            }
        }
    
        if (empty($chapter_title) || empty($content)) {
            throw new Exception("Chapter title and content are required");
        }

        $stmt = $pdo->prepare("SELECT id FROM chapters WHERE book_id = ? AND LOWER(chapter_title) = LOWER(?)");
        $stmt->execute([$book_id, $chapter_title]);
        $existing = $stmt->fetch();

        if ($existing) {
            $_SESSION['error'] = "Chapter already exists! You can edit it.";
            header("Location: update.php?chapter_id=" . $existing['id']);
            exit();
        }

        $stmt = $pdo->prepare("INSERT INTO chapters (book_id, chapter_title, content) VALUES (?, ?, ?)");
        $stmt->execute([$book_id, $chapter_title, $content]);
        $_SESSION['success'] = "Chapter added successfully!";
        header("Location: add.php");
        exit();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Chapter</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-xl mx-auto p-6 bg-white rounded-lg shadow mt-10">
        <h1 class="text-3xl font-bold mb-6 text-center">Add Chapter</h1>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded flex items-center gap-2">
                <span>✅</span> <?= $_SESSION['success'] ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded flex items-center gap-2">
                <span>❌</span> <?= $error ?></div>
        <?php endif; ?>
        <form method="post" class="space-y-4">
            <div>
                <label class="inline-flex items-center">
                    <input type="checkbox" name="new_book" id="new_book" onchange="toggleNewBook()" class="form-checkbox h-4 w-4 text-blue-600">
                    <span class="ml-2">New Book?</span>
                </label>
            </div>
            <div id="book_select"<?= isset($_GET['book_id']) ? ' style="display:none"' : '' ?>>
                <select name="book_id" class="w-full border rounded px-3 py-2">
                    <option value="">Select a book</option>
                    <?php foreach ($existing_books as $book): ?>
                        <option value="<?= $book['id'] ?>"<?= ($selected_book_id == $book['id']) ? ' selected' : '' ?>><?= htmlspecialchars($book['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div id="new_book_group" style="display:none">
                <input type="text" name="book_title" placeholder="New Book Title" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <input type="text" name="chapter_title" placeholder="Chapter Title" required class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <textarea name="content" placeholder="Chapter Content" required class="w-full border rounded px-3 py-2"></textarea>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Add Book + Chapter</button>
                <button type="reset" class="bg-gray-300 text-gray-800 px-4 py-2 rounded hover:bg-gray-400">Reset</button>
            </div>
        </form>
        <div class="text-center mt-8">
            <a href="index.php" class="inline-block text-blue-600 hover:underline">View All Books</a>
        </div>
    </div>
    <script>
        function toggleNewBook() {
            const newBookGroup = document.getElementById('new_book_group');
            const bookSelect = document.getElementById('book_select');
            if (document.getElementById('new_book').checked) {
                newBookGroup.style.display = 'block';
                bookSelect.style.display = 'none';
            } else {
                newBookGroup.style.display = 'none';
                bookSelect.style.display = 'block';
            }
        }
        // Auto-hide book select if pre-filled
        if (document.getElementById('book_select') && document.getElementById('book_select').style.display === 'none') {
            document.getElementById('new_book').checked = false;
        }
    </script>
</body>
</html>
