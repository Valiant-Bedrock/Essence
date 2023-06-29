-- #! mysql
-- #{ player
-- #    { save
-- # 	   :uuid string
-- #       :role_name string
-- #       :username string
-- #       :xuid string
-- #       :device_id string
REPLACE INTO player_data (uuid, role_name, username, xuid, device_id) VALUES (:uuid, :role_name, :username, :xuid, :device_id)
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
-- #{ bans
-- #    { save
-- #       :username string
-- # 	   :xuid string
-- #       :ip_address string
-- #       :device_id string
-- #       :reason string
-- #       :creation_time string
-- #       :expiry_time string
-- #       :banned_by string
REPLACE INTO player_bans (username, xuid, ip_address, device_id, reason, creation_time, expiry_time, banned_by) VALUES (:username, :xuid, :ip_address, :device_id, :reason, :creation_time, :expiry_time, :banned_by)
-- #    }
-- #    { load
-- #       :username string
-- # 	   :xuid string
-- #       :ip_address string
-- #       :device_id string
SELECT * FROM player_bans WHERE LOWER(username) = LOWER(:username) OR xuid = :xuid OR ip_address = :ip_address OR device_id = :device_id
-- #    }
-- #    { load_by_username
-- #       :username string
SELECT * FROM player_bans WHERE LOWER(username) = LOWER(:username)
-- #    }
-- #    { update
-- #       :username string
-- # 	   :xuid string
-- #       :ip_address string
-- #       :device_id string
-- #       :reason string
-- #       :creation_time string
-- #       :expiry_time string
-- #       :banned_by string
-- #       :unbanned_by string
-- #       :ban_id int
REPLACE INTO player_bans (username, xuid, ip_address, device_id, reason, creation_time, expiry_time, banned_by, unbanned_by, ban_id) VALUES (:username, :xuid, :ip_address, :device_id, :reason, :creation_time, :expiry_time, :banned_by, :unbanned_by, :ban_id)
-- #    }
-- #}