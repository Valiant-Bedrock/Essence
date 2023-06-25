-- #! mysql
-- #{ player
-- #    { save
-- # 	   :uuid string
-- #       :role_name string
-- #       :username string
REPLACE INTO player_data (uuid, role_name, username) VALUES (:uuid, :role_name, :username)
-- #    }
-- #    { load
-- # 	  :uuid string
SELECT * FROM player_data WHERE uuid = :uuid
-- #    }
-- #    { load_by_username
-- # 	  :username string
SELECT * FROM player_data WHERE username = :username
-- #    }
-- #}
