<?php
session_start();
require_once 'config.php';

function getChapter($pdo, $chapter_id) {
    $stmt = $pdo->prepare("SELECT * FROM chapters WHERE id = ?");
    $stmt->execute([$chapter_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getAllBooks($pdo) {
    $stmt = $pdo->query("SELECT * FROM books ORDER BY title");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$error = '';
$success = '';
$chapter = null;
$existing_books = getAllBooks($pdo);

$chapter_id = isset($_GET['chapter_id']) ? (int)$_GET['chapter_id'] : null;
$delete = isset($_GET['delete']) && $_GET['delete'] == 1;

if ($chapter_id) {
    $chapter = getChapter($pdo, $chapter_id);
    if (!$chapter) {
        $error = "Chapter not found.";
    }
}

if ($chapter && $delete && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("DELETE FROM chapters WHERE id = ?");
    $stmt->execute([$chapter_id]);
    $_SESSION['success'] = "Chapter deleted successfully!";
    header("Location: index.php");
    exit();
}

if ($chapter && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $chapter_title = trim($_POST['chapter_title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $book_id = (int)($_POST['book_id'] ?? $chapter['book_id']);

    try {
        if (empty($chapter_title) || empty($content)) {
            throw new Exception("Chapter title and content are required");
        }
        /
        
        $stmt = $pdo->prepare("SELECT id FROM chapters WHERE book_id = ? AND LOWER(chapter_title) = LOWER(?) AND id != ?");
        $stmt->execute([$book_id, $chapter_title, $chapter_id]);
        $existing = $stmt->fetch();
        if ($existing) {
            throw new Exception("Another chapter with this title already exists in this book.");
        }
        
        $stmt = $pdo->prepare("UPDATE chapters SET book_id = ?, chapter_title = ?, content = ? WHERE id = ?");
        $stmt->execute([$book_id, $chapter_title, $content, $chapter_id]);
        $_SESSION['success'] = "Chapter updated successfully!";
        header("Location: index.php");
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
    <title>Edit Chapter</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-xl mx-auto p-6 bg-white rounded-lg shadow mt-10">
        <h1 class="text-3xl font-bold mb-6 text-center">Edit Chapter</h1>
        <?php if ($error): ?>
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded flex items-center gap-2"><span>❌</span> <?= $error ?></div>
        <?php endif; ?>
        <?php if ($chapter): ?>
        <form method="post" class="space-y-4">
            <div>
                <label class="block mb-1 font-medium">Book</label>
                <select name="book_id" class="w-full border rounded px-3 py-2">
                    <?php foreach ($existing_books as $book): ?>
                        <option value="<?= $book['id'] ?>"<?= ($chapter['book_id'] == $book['id']) ? ' selected' : '' ?>><?= htmlspecialchars($book['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block mb-1 font-medium">Chapter Title</label>
                <input type="text" name="chapter_title" value="<?= htmlspecialchars($chapter['chapter_title']) ?>" required class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block mb-1 font-medium">Content</label>
                <textarea name="content" required class="w-full border rounded px-3 py-2"><?= htmlspecialchars($chapter['content']) ?></textarea>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Save Changes</button>
                <a href="index.php" class="bg-gray-300 text-gray-800 px-4 py-2 rounded hover:bg-gray-400">Cancel</a>
                <a href="update.php?chapter_id=<?= $chapter['id'] ?>&delete=1" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700" onclick="return confirm('Are you sure you want to delete this chapter?')">Delete</a>
            </div>
        </form>
        <?php else: ?>
            <div class="text-center text-gray-500">No chapter found.</div>
        <?php endif; ?>
        <div class="text-center mt-8">
            <a href="index.php" class="inline-block text-blue-600 hover:underline">View All Books</a>
        </div>
    </div>
</body>
</html>
