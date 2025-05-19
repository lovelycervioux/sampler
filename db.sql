CREATE DATABASE book_manager;
GO
USE book_manager;
GO

CREATE TABLE books (
    id INT IDENTITY(1,1) PRIMARY KEY,
    title NVARCHAR(255) UNIQUE NOT NULL
);
GO

CREATE TABLE chapters (
    id INT IDENTITY(1,1) PRIMARY KEY,
    book_id INT NOT NULL,
    chapter_title NVARCHAR(255) NOT NULL,
    content NVARCHAR(MAX) NOT NULL,
    CONSTRAINT unique_chapter UNIQUE (book_id, chapter_title),
    CONSTRAINT fk_book FOREIGN KEY (book_id) REFERENCES books(id)
);
GO
