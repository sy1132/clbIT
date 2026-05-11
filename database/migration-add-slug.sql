-- Migration: Add missing slug columns
-- Run this if you have existing data that needs the slug column

USE clubit_db;

-- Add slug column to categories if not exists
ALTER TABLE categories ADD COLUMN slug VARCHAR(120) UNIQUE NULL AFTER name;
UPDATE categories SET slug = LOWER(REPLACE(REPLACE(REPLACE(name, ' ', '-'), 'ă', 'a'), 'đ', 'd')) WHERE slug IS NULL;
ALTER TABLE categories MODIFY COLUMN slug VARCHAR(120) NOT NULL;

-- Add slug column to posts if not exists
ALTER TABLE posts ADD COLUMN slug VARCHAR(255) UNIQUE NULL AFTER title;
UPDATE posts SET slug = LOWER(REPLACE(REPLACE(REPLACE(title, ' ', '-'), 'ă', 'a'), 'đ', 'd')) WHERE slug IS NULL;
ALTER TABLE posts MODIFY COLUMN slug VARCHAR(255) NOT NULL;

-- Add slug column to events if not exists
ALTER TABLE events ADD COLUMN slug VARCHAR(255) UNIQUE NULL AFTER event_name;
UPDATE events SET slug = LOWER(REPLACE(REPLACE(REPLACE(event_name, ' ', '-'), 'ă', 'a'), 'đ', 'd')) WHERE slug IS NULL;
ALTER TABLE events MODIFY COLUMN slug VARCHAR(255) NOT NULL;

-- Add updated_at to categories if not exists
ALTER TABLE categories ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- Add updated_at to events if not exists
ALTER TABLE events ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- Add missing indexes if not exists
ALTER TABLE posts ADD INDEX idx_posts_status_date (status, published_at) IF NOT EXISTS;
ALTER TABLE events ADD INDEX idx_events_status_date (status, start_date) IF NOT EXISTS;
