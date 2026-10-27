USE sistema_rifas;

ALTER TABLE users
  ADD COLUMN purchase_mode ENUM('view','buy') NOT NULL DEFAULT 'buy' AFTER role;
