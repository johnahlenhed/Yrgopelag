PRAGMA foreign_keys = ON;

CREATE TABLE rooms (
    id INTEGER PRIMARY KEY,
    type TEXT NOT NULL,
    price INTEGER NOT NULL
);

INSERT INTO rooms (type, price) VALUES
('economy', 1),
('standard', 5),
('luxury', 10);

CREATE TABLE bookings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    guest_name TEXT NOT NULL,
    room_type TEXT NOT NULL,
    arrival_date TEXT NOT NULL,
    departure_date TEXT NOT NULL,
    total_price INTEGER NOT NULL,
    -- 'pending': row reserved, payment not yet confirmed with Centralbank.
    -- 'confirmed': deposit() succeeded, payment complete.
    -- 'payment_failed': money left the guest (or was validated) but deposit() failed;
    --   Centralbank has no refund/reversal endpoint, so these need manual follow-up
    --   rather than being deleted -- the transfer_code is kept for that purpose.
    status TEXT NOT NULL DEFAULT 'pending',
    transfer_code TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- Prevents two bookings from racing onto the same room/date (belt-and-braces
-- alongside the application-level availability check).
CREATE UNIQUE INDEX idx_bookings_room_arrival ON bookings(room_type, arrival_date);

CREATE TABLE features (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category TEXT NOT NULL,      -- for internal grouping
    activity TEXT NOT NULL,       -- for Centralbank API
    tier TEXT NOT NULL,           
    name TEXT NOT NULL,           
    price INTEGER NOT NULL,
    is_active INTEGER NOT NULL DEFAULT 1
);

INSERT INTO features (category, activity, tier, name, price, is_active) VALUES
('water', 'water', 'economy', 'pool', 1, 1),
('water', 'water', 'basic', 'scuba_diving', 2, 1),
('water', 'water', 'premium', 'olympic_pool', 4, 1),
('water', 'water', 'superior', 'waterpark_with_fire_and_minibar', 7, 1),

('games', 'games', 'economy', 'yahtzee', 1, 1),
('games', 'games', 'basic', 'ping_pong_table', 2, 1),
('games', 'games', 'premium', 'PS5', 4, 1),
('games', 'games', 'superior', 'casino', 7, 1),

('wheels', 'wheels', 'economy', 'unicycle', 1, 1),
('wheels', 'wheels', 'basic', 'bicycle', 2, 1),
('wheels', 'wheels', 'premium', 'trike', 4, 1),
('wheels', 'wheels', 'superior', 'four_wheeled_motorized_beast', 7, 1),

('hotel-specific', 'hotel-specific', 'economy', 'svenskt_kaffe_on_arrival', 1, 1),
('hotel-specific', 'hotel-specific', 'basic', 'smörgåsbord_lunch', 2, 1),
('hotel-specific', 'hotel-specific', 'premium', 'en_burk_surströmming', 4, 1),
('hotel-specific', 'hotel-specific', 'superior', 'Jan-Emanuel_sköter_din_deklaration', 7, 1);

-- Junction table
CREATE TABLE booking_features (
    booking_id INTEGER NOT NULL,
    feature_id INTEGER NOT NULL,
    PRIMARY KEY (booking_id, feature_id),
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (feature_id) REFERENCES features(id) ON DELETE CASCADE
);

-- Admin
CREATE TABLE settings (
    key TEXT PRIMARY KEY,
    value TEXT NOT NULL
);

INSERT INTO settings (key, value) VALUES
('star_rating', '4'),
('loyalty_discount', '10');