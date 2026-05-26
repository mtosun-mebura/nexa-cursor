-- SQL script to manually add is_active column to users table
-- Run this if the migration was marked as executed but the column doesn't exist

-- For PostgreSQL
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT true;

-- Update existing users to be active by default (if needed)
UPDATE users SET is_active = true WHERE is_active IS NULL;





