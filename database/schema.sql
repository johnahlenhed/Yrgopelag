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
    id INTEGER PRIMARY KEY,
    guest_name TEXT NOT NULL,
    room_type TEXT NOT NULL,
    arrival_date TEXT NOT NULL,
    departure_date TEXT NOT NULL,
    total_price INTEGER NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE features (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category TEXT NOT NULL,
    tier TEXT NOT NULL,
    name TEXT NOT NULL,
    price INTEGER NOT NULL
);

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
('star_rating', '3'),
('loyalty_discount', '10');