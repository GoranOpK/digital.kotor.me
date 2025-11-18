-- SQL komande za popunjavanje tabele roles
-- Izvršite ove komande u phpMyAdmin-u ako tabela roles nije popunjena

INSERT INTO `roles` (`id`, `name`, `display_name`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'Administrator', NOW(), NOW()),
(2, 'komisija', 'Komisija', NOW(), NOW()),
(3, 'prijavitelj', 'Prijavitelj', NOW(), NOW())
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

