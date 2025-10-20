-- Add remember_token column to users table
-- This migration adds support for "Remember Me" functionality
-- Run this SQL if you want to enable persistent login sessions

USE geo_cms;

-- Add remember_token column if it doesn't exist
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS remember_token VARCHAR(64) NULL DEFAULT NULL;

-- Add index for faster lookups
CREATE INDEX IF NOT EXISTS idx_remember_token ON users(remember_token);

-- Optional: Add expiry column for remember tokens
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS remember_token_expires TIMESTAMP NULL DEFAULT NULL;

SELECT 'Remember token columns added successfully!' as message;
