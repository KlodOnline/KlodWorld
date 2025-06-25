CREATE SCHEMA klodonline;

CREATE TABLE klodonline.locatables ( 
    id                  INT  NOT NULL AUTO_INCREMENT PRIMARY KEY,
    col                 INT NOT NULL,
    row                 INT NOT NULL,
    owner_id            INT DEFAULT NULL,
    kind                VARCHAR(100) DEFAULT NULL,
    data                MEDIUMTEXT DEFAULT NULL,
    INDEX idx_locatables_col_row (col, row),
    INDEX idx_locatables_owner_id (owner_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE klodonline.inventory_cells ( 
    id                  INT  NOT NULL AUTO_INCREMENT PRIMARY KEY,
    item_id             INT DEFAULT NULL,
    owner_id			INT NOT NULL,
    volume              INT DEFAULT NULL,
    CONSTRAINT fk_inventory_cells_locatables FOREIGN KEY (owner_id) REFERENCES klodonline.locatables(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE klodonline.players ( 
    meta_id             INT  NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name                VARCHAR(100) DEFAULT NULL,
    color               VARCHAR(10) DEFAULT NULL,
    discover            MEDIUMTEXT DEFAULT NULL,
    paidto              BIGINT,
    INDEX idx_players_meta_id (meta_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE klodonline.orders ( 
    owner_id            INT NOT NULL PRIMARY KEY,
    order_type          VARCHAR(255) DEFAULT NULL,
    data                MEDIUMTEXT DEFAULT NULL,
    turn                INT DEFAULT NULL,
    INDEX idx_orders_owner_id (owner_id),
    CONSTRAINT fk_orders_locatables FOREIGN KEY (owner_id) REFERENCES klodonline.locatables(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
