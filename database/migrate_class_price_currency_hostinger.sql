ALTER TABLE class_listings
ADD COLUMN price_currency VARCHAR(10) NOT NULL DEFAULT 'USD' AFTER price;
