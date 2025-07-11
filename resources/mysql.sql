-- #! mysql
-- #{ hit

-- # { create_table
CREATE TABLE IF NOT EXISTS player_hiteffects (
    name VARCHAR(32) PRIMARY KEY NOT NULL,
    current VARCHAR(32) DEFAULT NULL,
    unlocked TEXT DEFAULT NULL
);
-- # }

-- # { load
-- #   :name string
SELECT * FROM player_hiteffects WHERE name = :name;
-- # }

-- # { save
-- #   :name string
-- #   :current string
-- #   :unlocked string
REPLACE INTO player_hiteffects (name, current, unlocked)
VALUES (:name, :current, :unlocked);
-- # }

-- #}
