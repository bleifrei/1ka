# Vse tabele, ki so potrebne za modul mod_hierarhija

#Hierarhija admin nivo - izgradnja nivojev in šifrantov
CREATE TABLE srv_hierarhija_ravni (
  id integer NOT NULL auto_increment,
  anketa_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  level TINYINT NULL DEFAULT NULL,
  ime VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (anketa_id) REFERENCES srv_anketa (id) ON DELETE CASCADE
)ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE srv_hierarhija_sifranti (
  id integer NOT NULL auto_increment,
  hierarhija_ravni_id INTEGER NOT NULL,
  ime VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (hierarhija_ravni_id) REFERENCES srv_hierarhija_ravni (id) ON DELETE CASCADE
)ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE srv_hierarhija_users (
  user_id INTEGER NOT NULL,
  type TINYINT NULL DEFAULT 10
)ENGINE=InnoDB DEFAULT CHARSET=utf8;


# Hierarhija uporabniki, kjer se zgradi struktura
CREATE TABLE srv_hierarhija_struktura (
  id INTEGER NOT NULL auto_increment,
  hierarhija_ravni_id INTEGER NOT NULL,
  parent_id INTEGER DEFAULT NULL,
  hierarhija_sifranti_id INTEGER NOT NULL,
  level TINYINT NOT NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (hierarhija_ravni_id) REFERENCES srv_hierarhija_ravni (id) ON DELETE CASCADE,
  FOREIGN KEY (parent_id) REFERENCES srv_hierarhija_struktura (id) ON DELETE CASCADE,
  FOREIGN KEY (hierarhija_sifranti_id) REFERENCES srv_hierarhija_sifranti (id) ON DELETE CASCADE
)ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE srv_hierarhija_struktura_users (
  hierarhija_struktura_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  FOREIGN KEY (hierarhija_struktura_id) REFERENCES srv_hierarhija_struktura (id) ON DELETE CASCADE
)ENGINE=InnoDB DEFAULT CHARSET=utf8;

#Pivot table srv_hierarhija in srv_vrednost
CREATE TABLE srv_hierarhija_sifrant_vrednost (
  sifrant_id INTEGER NOT NULL,
  vrednost_id INTEGER NOT NULL,
  FOREIGN KEY (sifrant_id) REFERENCES srv_hierarhija_sifranti (id) ON DELETE CASCADE,
  FOREIGN KEY (vrednost_id) REFERENCES srv_vrednost (id) ON DELETE CASCADE
)ENGINE=InnoDB DEFAULT CHARSET=utf8;

#Pivot table srv_hierarhija in srv_vrednost
#ALTER TABLE srv_hierarhija_struktura CHANGE hidden hidden ENUM('0','1','2') NOT NULL DEFAULT '0';

ALTER TABLE srv_hierarhija_struktura
    ADD COLUMN anketa_id INTEGER NOT NULL AFTER id,
    ADD FOREIGN KEY (anketa_id) REFERENCES srv_anketa (id) ON DELETE CASCADE;

# Dodamo možnost unikaten, da upošteva samo unikatne šifrante na omenjeni ravni
ALTER TABLE srv_hierarhija_ravni
    ADD COLUMN unikaten INTEGER DEFAULT 0;


# 23.12.2015
# dDoločimo user type glede na anketo
ALTER TABLE srv_hierarhija_users
  ADD COLUMN id INT UNSIGNED NOT NULL AUTO_INCREMENT FIRST,
  ADD COLUMN anketa_id INTEGER NOT NULL AFTER user_id,
  ADD PRIMARY KEY (id),
  ADD FOREIGN KEY (anketa_id) REFERENCES srv_anketa (id) ON DELETE CASCADE;

# kreiranje naključnega 5 znakov dolgega unikatnega niza
# CAST(MD5(RAND()) as CHAR(5))
# 8.4.2016
CREATE TABLE srv_hierarhija_koda (
  koda VARCHAR(10) NOT NULL UNIQUE,
  anketa_id INTEGER NOT NULL,
  url TEXT NOT NULL,
  vloga ENUM('ucitelj', 'ucenec') NOT NULL,
  user_id INT(15) NOT NULL,
  hierarhija_struktura_id INT(15) NOT NULL,
  datetime DATETIME,
  PRIMARY KEY (koda),
  FOREIGN KEY (anketa_id) REFERENCES srv_anketa (id) ON DELETE CASCADE,
  FOREIGN KEY (hierarhija_struktura_id) REFERENCES srv_hierarhija_struktura (id) ON DELETE CASCADE
)ENGINE=InnoDB DEFAULT CHARSET=utf8;

# Možnost shranjevanja hierarhije
# 10.05.2016
CREATE TABLE srv_hierarhija_shrani (
  id integer NOT NULL auto_increment,
  anketa_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  ime VARCHAR(255) NULL DEFAULT NULL,
  hierarhija LONGTEXT,
  PRIMARY KEY (id),
  FOREIGN KEY (anketa_id) REFERENCES srv_anketa (id) ON DELETE CASCADE
)ENGINE=InnoDB DEFAULT CHARSET=utf8;

# Shranjevanje opcij za hierarhijo
# 26.5.2016
CREATE TABLE srv_hierarhija_options (
  id integer NOT NULL auto_increment,
  anketa_id INTEGER NOT NULL,
  option_name VARCHAR(200) NOT NULL,
  option_value LONGTEXT,
  PRIMARY KEY (id),
  FOREIGN KEY (anketa_id) REFERENCES srv_anketa (id) ON DELETE CASCADE
)ENGINE=InnoDB DEFAULT CHARSET=utf8;

# Hierarhija help
INSERT INTO srv_help (what, help) VALUES ('srv_hierarchy_edit_elements', 'Za vsak izbran nivo se lahko dodaja nove elemente. Z izbiro mo&#382;nosti brisanja se izbri&#353;e celoten nivo z vsemi &#353;ifranti. Lahko pa se omenejene elemente ureja in odstrani zgolj poljuben element nivoja.');
INSERT INTO srv_help (what, help) VALUES ('srv_hierarhy_last_level_missing', 'Na zadnjem nivoju manjka izbran element in elektronski naslov osebe, ki bo preko elektronske po&#353;te dobila kodo za re&#353;evanje ankete.');

# Shranjevanje strukture
# 3.11.2016
ALTER TABLE srv_hierarhija_shrani ADD COLUMN struktura LONGTEXT DEFAULT NULL;

# Dodana stolpec za seštevek vseh učitelje v in vseh uporabnikov
# 25.11.2016
ALTER TABLE srv_hierarhija_shrani ADD COLUMN st_uciteljev INTEGER DEFAULT NULL;
ALTER TABLE srv_hierarhija_shrani ADD COLUMN st_vseh_uporabnikov INTEGER DEFAULT NULL;

# Omogočimo komentarje za posamezno anketo
# 02.12.2016
ALTER TABLE srv_hierarhija_shrani ADD COLUMN komentar TEXT DEFAULT NULL;

# Piškot zapišemo za učitelja
# 26.05.2017
ALTER TABLE srv_hierarhija_koda
  ADD COLUMN srv_user_id INT(11) DEFAULT NULL AFTER vloga,
  ADD FOREIGN KEY (srv_user_id) REFERENCES srv_user (id);

# Omogočimo komentarje za posamezno anketo
# V mod_hierarhija/porocila je potreno ustvariti mapo logo (755)
# 09.06.2017
ALTER TABLE srv_hierarhija_shrani ADD COLUMN logo VARCHAR(255) DEFAULT NULL;

# 16.06.2017
DROP INDEX koda ON srv_hierarhija_koda;
ALTER TABLE srv_hierarhija_koda ADD UNIQUE INDEX (koda);

# 19.06.2017
INSERT INTO srv_hierarhija_options (anketa_id, option_name, option_value) SELECT anketa_id, 'ne_poslji_kode_ucencem', '1' FROM srv_hierarhija_options WHERE option_name='poslji_kode' AND option_value='uciteljem';
INSERT INTO srv_hierarhija_options (anketa_id, option_name, option_value) SELECT anketa_id, 'ne_poslji_kode_ucencem', '1' FROM srv_hierarhija_options WHERE option_name='poslji_kode' AND option_value='nikomur';
INSERT INTO srv_hierarhija_options (anketa_id, option_name, option_value) SELECT anketa_id, 'ne_poslji_kodo_ucitelju', '1' FROM srv_hierarhija_options WHERE option_name='poslji_kode' AND option_value='nikomur';
DELETE FROM srv_hierarhija_options WHERE option_name='poslji_kode';

#22.06.2017
ALTER TABLE srv_hierarhija_shrani ADD COLUMN uporabniki_list TEXT DEFAULT NULL;

# Ustvarjena tabela za superšifro
# 06.07.2017
CREATE TABLE srv_hierarhija_supersifra (
  koda VARCHAR(10) NOT NULL UNIQUE,
  anketa_id INTEGER NOT NULL,
  kode TEXT NOT NULL,
  datetime DATETIME,
  PRIMARY KEY (koda),
  FOREIGN KEY (anketa_id) REFERENCES srv_anketa (id) ON DELETE CASCADE
)ENGINE=InnoDB DEFAULT CHARSET=utf8;

# tabela, ki beleži uporabnike in katere ankete je rešil s superšiframi
# 10.7.2017
CREATE TABLE srv_hierarhija_supersifra_resevanje (
  user_id INTEGER NOT NULL,
  supersifra VARCHAR(10) NOT NULL,
  koda VARCHAR(10) NOT NULL,
  status TINYINT DEFAULT NULL,
  datetime DATETIME DEFAULT NOW(),
  PRIMARY KEY (user_id),
  FOREIGN KEY (user_id) REFERENCES srv_user (id) ON DELETE CASCADE,
  FOREIGN KEY (supersifra) REFERENCES srv_hierarhija_supersifra (koda) ON DELETE CASCADE,
  FOREIGN KEY (koda) REFERENCES srv_hierarhija_koda (koda) ON DELETE CASCADE #na testu povzročal težave
)ENGINE=InnoDB DEFAULT CHARSET=utf8;

# Dostop do mdula SA
CREATE TABLE srv_hierarhija_dostop (
  user_id INTEGER NOT NULL,
  dostop TINYINT DEFAULT 0,
  ustanova VARCHAR(255) DEFAULT NULL,
  aai_email VARCHAR(100) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
)ENGINE=InnoDB DEFAULT CHARSET=utf8;
