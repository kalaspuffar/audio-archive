DROP DATABASE audio_archive;
CREATE DATABASE audio_archive;
USE audio_archive;

CREATE TABLE statements (
    id BIGINT AUTO_INCREMENT,
    speaker_id BIGINT NOT NULL,
    textline TEXT,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE audio_clip (
    id BIGINT AUTO_INCREMENT,
    status INT NOT NULL DEFAULT 0,
    statement_id BIGINT NOT NULL,    
    format VARCHAR(10),
    model VARCHAR(255),
    clip_path VARCHAR(255),
    duration_seconds BIGINT NOT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT fk_statement
        FOREIGN KEY (statement_id)
        REFERENCES statements(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE speakers (
    id BIGINT AUTO_INCREMENT,
    speaker VARCHAR(255),
    disabled BOOLEAN,
    new BOOLEAN,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;