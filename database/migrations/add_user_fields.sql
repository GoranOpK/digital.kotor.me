-- SQL komande za dodavanje novih polja u users tabelu
-- Izvršite ove komande u phpMyAdmin-u

ALTER TABLE `users` 
ADD COLUMN `user_type` ENUM('Fizičko lice', 'Registrovan privredni subjekt') NULL AFTER `name`,
ADD COLUMN `first_name` VARCHAR(255) NULL AFTER `user_type`,
ADD COLUMN `last_name` VARCHAR(255) NULL AFTER `first_name`,
ADD COLUMN `phone` VARCHAR(50) NULL AFTER `email`,
ADD COLUMN `date_of_birth` DATE NULL AFTER `phone`;

