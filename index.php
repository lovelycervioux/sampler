<?php
session_start();
require_once 'config.php';

// Helper: fetch all books
function getAllBooks($pdo) {
    $stmt = $pdo->query("SELECT * FROM books ORDER BY title");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Helper: fetch all books with chapters
function getBooksWithChapters($pdo) {
    $stmt = $pdo->query("
        SELECT books.id AS book_id, books.title AS book_title,
               chapters.id AS chapter_id, chapters.chapter_title, chapters.content
        FROM books
        LEFT JOIN chapters ON books.id = chapters.book_id
        ORDER BY books.title, chapters.chapter_title
    ");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $books = [];
    foreach ($results as $row) {
        $book_id = $row['book_id'];
        if (!isset($books[$book_id])) {
            $books[$book_id] = [
                'title' => $row['book_title'],
                'chapters' => []
            ];
        }
        if ($row['chapter_id']) {
            $books[$book_id]['chapters'][] = [
                'id' => $row['chapter_id'],
                'title' => $row['chapter_title'],
                'content' => $row['content']
            ];
        }
    }
    return $books;
}

$books = [];
$error = '';

try {
    $books = getBooksWithChapters($pdo);
} catch(PDOException $e) {
    $error = "Error fetching data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Manager</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-2xl mx-auto p-6 bg-white rounded-lg shadow mt-10">
        <h1 class="text-3xl font-bold mb-6 text-center">📚 Book Manager</h1>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded flex items-center gap-2">
                <span>✅</span> <?= $_SESSION['success'] ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded flex items-center gap-2">
                <span>❌</span> <?= $error ?>
            </div>
        <?php else: ?>
            <?php if (empty($books)): ?>
                <div class="text-center text-gray-500">No books found. <a href='add.php' class='text-blue-600 hover:underline'>Add your first book!</a></div>
            <?php endif; ?>
            <?php foreach ($books as $book_id => $book): ?>
                <div class="book mb-8 border-b pb-4">
                    <h2 class="text-xl font-semibold flex items-center gap-4">
                        <span class="truncate"><?= htmlspecialchars($book['title']) ?></span>
                        <a href="add.php?book_id=<?= $book_id ?>" class="text-blue-600 hover:underline text-sm">+ Add Chapter</a>
                    </h2>
                    <?php if (!empty($book['chapters'])): ?>
                        <ul class="chapters ml-6 mt-2 list-disc">
                            <?php foreach ($book['chapters'] as $chapter): ?>
                                <li class="mb-1 flex items-center gap-2">
                                    <span class="font-medium text-gray-700"><?= htmlspecialchars($chapter['title']) ?></span>
                                    <a href="update.php?chapter_id=<?= $chapter['id'] ?>" class="text-yellow-600 hover:underline text-xs">Edit</a>
                                    <a href="update.php?chapter_id=<?= $chapter['id'] ?>&delete=1" class="text-red-600 hover:underline text-xs" onclick="return confirm('Are you sure you want to delete this chapter?')">Delete</a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-gray-500 ml-6">No chapters yet.</p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <div class="text-center mt-8">
            <a href="add.php" class="inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Add New Book + Chapter</a>
        </div>
    </div>
</body>
</html>
