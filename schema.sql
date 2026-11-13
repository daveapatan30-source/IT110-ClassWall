-- First create a database named classwall in pgAdmin.
-- Then open Query Tool while connected to classwall and run this file.

CREATE TABLE IF NOT EXISTS messages (
    id SERIAL PRIMARY KEY,
    author VARCHAR(100) NOT NULL,
    body TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
