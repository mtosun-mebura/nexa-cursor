-- SQL script om alle gebruikers op actief te zetten
UPDATE users SET is_active = true;

-- Verificatie: tel aantal actieve gebruikers
SELECT COUNT(*) as active_users FROM users WHERE is_active = true;





