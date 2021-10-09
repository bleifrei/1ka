3update misc set value="04.04.16" where what="version";
ALTER TABLE data_baze CHANGE avtor avtor VARCHAR(250) NOT NULL;

update misc set value="04.04.19" where what="version";
ALTER TABLE menu ADD forum INT NOT NULL AFTER base;

ALTER TABLE post ADD FULLTEXT (naslov);
ALTER TABLE post ADD FULLTEXT (user);
ALTER TABLE post ADD FULLTEXT (vsebina);
ALTER TABLE data_baze ADD FULLTEXT (naslov);
ALTER TABLE data_baze ADD FULLTEXT (naslov2);
ALTER TABLE data_baze ADD FULLTEXT (avtor);
ALTER TABLE data_baze ADD FULLTEXT (abstract);
ALTER TABLE data_baze ADD FULLTEXT (abstract2);
ALTER TABLE data_baze ADD FULLTEXT (opis1);
ALTER TABLE data_baze ADD FULLTEXT (opis2);
ALTER TABLE data_baze ADD FULLTEXT (details);

update misc set value="04.04.20" where what="version";

INSERT INTO misc VALUES ("BeforeName", "");
INSERT INTO misc VALUES ("BeforeNews", "");
INSERT INTO misc VALUES ("AfterNews", "");
INSERT INTO misc VALUES ("AlertFrom", "");
INSERT INTO misc VALUES ("AlertReply", "");
INSERT INTO misc VALUES ("AlertSubject", "");

INSERT INTO misc VALUES ("m2w2all", "");

ALTER TABLE mailnovice ADD COLUMN naslovnica tinyint(1) not null default 1;
ALTER TABLE mailnovice ADD COLUMN mailati tinyint(1) not null default 1;
ALTER TABLE mailnovice ADD COLUMN popravil varchar(255) not null default "Neznan";
ALTER TABLE mailnovice ADD COLUMN datumpopravka varchar(12) not null default "Neznan";
ALTER TABLE mailnovice ADD COLUMN vpisal varchar(255) not null default "Neznan";
ALTER TABLE mailnovice ADD COLUMN datumvpisa varchar(12) not null default "Neznan";

update misc set value="04.05.01" where what="version";

INSERT INTO misc VALUES ("UserDataFrom", "");
INSERT INTO misc VALUES ("UserDataReply", "");
INSERT INTO misc VALUES ("NewUserSubject", "");
INSERT INTO misc VALUES ("ChangedUserSubject", "");
INSERT INTO misc VALUES ("M2WNotAllowedSubject", "");
INSERT INTO misc VALUES ("LostPasswordSubject", "");INSERT INTO misc VALUES ("HelloNewUser", "");
INSERT INTO misc VALUES ("NewUserMail", "");
INSERT INTO misc VALUES ("ChangedUserMail", "");
INSERT INTO misc VALUES ("M2WNotAllowed", "");
INSERT INTO misc VALUES ("LostPasswordMail", "");

update misc set value="04.05.02" where what="version";

ALTER TABLE struktura_baze ADD virx1 TINYINT NOT NULL AFTER vi1,
ADD virx11 TINYINT NOT NULL AFTER virx1,
ADD vix1 VARCHAR(50) NOT NULL AFTER virx11;

ALTER TABLE struktura_baze ADD db1 TINYINT NOT NULL AFTER vi2,
ADD db11 TINYINT NOT NULL AFTER db1,
ADD d1 VARCHAR(50) NOT NULL AFTER db11;

ALTER TABLE struktura_grid ADD pvirx1 TINYINT NOT NULL AFTER rvir1,
ADD fvirx1 TINYINT NOT NULL AFTER pvirx1,
ADD rvirx1 TINYINT NOT NULL AFTER fvirx1;

ALTER TABLE struktura_grid ADD pdb1 TINYINT NOT NULL AFTER rvir2,
ADD fdb1 TINYINT NOT NULL AFTER pdb1,
ADD rdb1 TINYINT NOT NULL AFTER fdb1;

CREATE TABLE struktura_db (
id int(11) NOT NULL AUTO_INCREMENT ,

url varchar(255) NOT NULL default '',
email varchar(255) NOT NULL default '',
slika varchar(255) NOT NULL default '',
opomba tinytext NOT NULL ,
PRIMARY KEY (id)
);

ALTER TABLE data_baze ADD virx1 VARCHAR(250) NOT NULL AFTER vir1;
ALTER TABLE data_baze ADD db1 VARCHAR(250) NOT NULL AFTER vir2;

CREATE TABLE data_db (
dbid int(11) NOT NULL default '0',
did int(11) NOT NULL default '0'
);

update misc set value="04.05.05" where what="version";

alter table data_baze change naslov naslov varchar(255);
alter table data_baze change naslov2 naslov2 varchar(255);

insert into misc values ('underline', '');
insert into misc values ('showsender', '');

ALTER TABLE rubrika3 ADD COLUMN naslovnica tinyint(1) not null default 1;
ALTER TABLE rubrika3 ADD COLUMN mailati tinyint(1) not null default 1;
ALTER TABLE rubrika3 ADD COLUMN popravil varchar(255) not null default "Neznan";
ALTER TABLE rubrika3 ADD COLUMN datumpopravka varchar(12) not null default "Neznan";
ALTER TABLE rubrika3 ADD COLUMN vpisal varchar(255) not null default "Neznan";
ALTER TABLE rubrika3 ADD COLUMN show_date tinyint(1) not null default 1;


ALTER TABLE rubrika4 ADD COLUMN naslovnica tinyint(1) not null default 1;
ALTER TABLE rubrika4 ADD COLUMN mailati tinyint(1) not null default 1;
ALTER TABLE rubrika4 ADD COLUMN popravil varchar(255) not null default "Neznan";
ALTER TABLE rubrika4 ADD COLUMN datumpopravka varchar(12) not null default "Neznan";
ALTER TABLE rubrika4 ADD COLUMN vpisal varchar(255) not null default "Neznan";
ALTER TABLE rubrika4 ADD COLUMN datumvpisa varchar(12) not null default "Neznan";
ALTER TABLE rubrika4 ADD COLUMN show_date tinyint(1) not null default 1;

ALTER TABLE vodic ADD COLUMN naslovnica tinyint(1) not null default 1;
ALTER TABLE vodic ADD COLUMN mailati tinyint(1) not null default 1;
ALTER TABLE vodic ADD COLUMN popravil varchar(255) not null default "Neznan";
ALTER TABLE vodic ADD COLUMN datumpopravka varchar(12) not null default "Neznan";
ALTER TABLE vodic ADD COLUMN vpisal varchar(255) not null default "Neznan";
ALTER TABLE vodic ADD COLUMN datumvpisa varchar(12) not null default "Neznan";
ALTER TABLE vodic ADD COLUMN show_date tinyint(1) not null default 1;

CREATE TABLE rubrika3 (
  naslov varchar(100) NOT NULL default '',
  vsebina mediumtext NOT NULL,
  datum date NOT NULL default '0000-00-00',
  sid int(10) unsigned NOT NULL auto_increment,
  cid int(11) NOT NULL default '0',
  avtor varchar(100) NOT NULL default 'RIS',
  link varchar(255) default NULL,
  kategorije varchar(255) default NULL,
  email varchar(255) default NULL,
  datoteka varchar(255) default NULL,
  online int(11) NOT NULL default '1',
  thread int(10) NOT NULL default '0',
  comment tinyint(4) NOT NULL default '1',
  naslovnica tinyint(1) NOT NULL default '1',
  mailati tinyint(1) NOT NULL default '1',
  vpisal varchar(255) NOT NULL default 'Neznan',
  datumvpisa varchar(12) NOT NULL default 'Neznan',
  popravil varchar(255) NOT NULL default 'Neznan',
  datumpopravka varchar(12) NOT NULL default 'Neznan',
  show_date tinyint(1) NOT NULL default '1',
  KEY sid (sid),
  FULLTEXT KEY naslov (naslov,vsebina,kategorije)
) TYPE=MyISAM;

CREATE TABLE rubrika4 (
  naslov varchar(100) NOT NULL default '',
  vsebina mediumtext NOT NULL,
  datum date NOT NULL default '0000-00-00',
  sid int(10) unsigned NOT NULL auto_increment,
  cid int(11) NOT NULL default '0',
  avtor varchar(100) NOT NULL default 'RIS',
  link varchar(255) default NULL,
  kategorije varchar(255) default NULL,
  email varchar(255) default NULL,
  datoteka varchar(255) default NULL,
  online int(11) NOT NULL default '1',
  thread int(10) NOT NULL default '0',
  comment tinyint(4) NOT NULL default '1',
  naslovnica tinyint(1) NOT NULL default '1',
  mailati tinyint(1) NOT NULL default '1',
  vpisal varchar(255) NOT NULL default 'Neznan',
  datumvpisa varchar(12) NOT NULL default 'Neznan',
  popravil varchar(255) NOT NULL default 'Neznan',
  datumpopravka varchar(12) NOT NULL default 'Neznan',
  show_date tinyint(1) NOT NULL default '1',
  KEY sid (sid),
  FULLTEXT KEY naslov (naslov,vsebina,kategorije)
) TYPE=MyISAM;

update misc set value="04.05.10" where what="version";


ALTER TABLE struktura_baze ADD recnum1 TINYINT NOT NULL AFTER yea,
ADD rec VARCHAR(50) NOT NULL AFTER recnum1;


ALTER TABLE struktura_grid ADD prec TINYINT NOT NULL AFTER rleto,
ADD fnum TINYINT NOT NULL AFTER prec,
ADD rnum TINYINT NOT NULL AFTER frec;


ALTER TABLE struktura_baze ADD dd1 TINYINT NOT NULL AFTER d1,
ADD dd11 TINYINT NOT NULL AFTER dd1,
ADD d_1 VARCHAR( 50 ) NOT NULL AFTER dd11;


ALTER TABLE struktura_grid ADD pdd1 TINYINT NOT NULL AFTER rdb1,
ADD fdd1 TINYINT NOT NULL AFTER pdd1,
ADD rdd1 TINYINT NOT NULL AFTER fdd1;

CREATE TABLE data_dd (
ddid int(11) NOT NULL default '0',
did int(11) NOT NULL default '0'
);

CREATE TABLE struktura_dd (
id int(11) NOT NULL AUTO_INCREMENT ,
dd varchar(255) NOT NULL default '',
url varchar(255) NOT NULL default '',
email varchar(255) NOT NULL default '',
slika varchar(255) NOT NULL default '',
opomba tinytext NOT NULL ,
PRIMARY KEY (id)
);

ALTER TABLE data_baze ADD dd1 VARCHAR(250) NOT NULL AFTER db1;

CREATE TABLE struktura_dds (
id int(11) NOT NULL AUTO_INCREMENT,
dds varchar(255) NOT NULL default '',
url varchar(255) NOT NULL default '',
email varchar(255) NOT NULL default '',
slika varchar(255) NOT NULL default '',
opomba tinytext NOT NULL ,
PRIMARY KEY (id)
);

CREATE TABLE data_dds (
dd int(11) NOT NULL default '0',
dds int(11) NOT NULL default '0'
);

update misc set value="04.05.14" where what='version';
update misc set value="04.05.20" where what='version';

ALTER TABLE struktura_grid ADD prec TINYINT(4) NOT NULL AFTER rleto, ADD frec TINYINT(4) NOT NULL AFTER prec, ADD rrec TINYINT(4) NOT NULL AFTER frec;

update misc set value="04.05.24" where what='version';

insert into misc values ('PageName', '');
update misc set value="04.05.26" where what='version';

update misc set value="" where what='version';
update misc set value="04.05.26" where what='version';

DELETE FROM misc WHERE what='BeforeName';
DELETE FROM misc WHERE what='BeforeNews';
DELETE FROM misc WHERE what='AfterNews';
DELETE FROM misc WHERE what='AlertFrom';
DELETE FROM misc WHERE what='AlertSubject';
DELETE FROM misc WHERE what='UserDataFrom';
DELETE FROM misc WHERE what='AlertReply';
DELETE FROM misc WHERE what='NewUserSubject';
DELETE FROM misc WHERE what='ChangedUserSubject';
DELETE FROM misc WHERE what='M2WNotAllowedSubject';
DELETE FROM misc WHERE what='LostPasswordSubject';
DELETE FROM misc WHERE what='HelloNewUser';
DELETE FROM misc WHERE what='NewUserMail';
DELETE FROM misc WHERE what='ChangedUserMail';
DELETE FROM misc WHERE what='M2WNotAllowed';
DELETE FROM misc WHERE what='LostPasswordMail';
DELETE FROM misc WHERE what='PageName';


INSERT INTO misc VALUES ('BeforeName','xHi,');
INSERT INTO misc VALUES ('BeforeNews','xx SFINT xx SFMAIL xx<hr /><br />aaaasadadsasfff');
INSERT INTO misc VALUES ('AfterNews','\r\n              Pozdravljeni, SFNAME!<br /><br />Obvescamo vas o novostih na SFPAGENAME na dan SFINT: <br /><hr width=\"100%\" align=\"center\" /><br />SFNEWS<br /><hr width=\"100%\" /><br /><br />Lep pozdrav,<br /><br />Sisplet.org <br /><br />Sporocilo smo vam poslali na SFMAIL<br />Za odjavo kliknite SFOUT tukaj SFEND<br />Za spremembo nastavitev kliknite SFCHANGE tukaj SFEND           ');
INSERT INTO misc VALUES ('m2w2all','1');
INSERT INTO misc VALUES ('AlertFrom','info@sisplet.org');
INSERT INTO misc VALUES ('AlertSubject','Novica Sisplet.org');
INSERT INTO misc VALUES ('m2w2all','1');
INSERT INTO misc VALUES ('UserDataFrom','info@sisplet.org');
INSERT INTO misc VALUES ('AlertReply','info@sisplet.org');
INSERT INTO misc VALUES ('AlertFrom','info@sisplet.org');
INSERT INTO misc VALUES ('AlertSubject','Novica Sisplet.org');
INSERT INTO misc VALUES ('AlertReply','info@sisplet.org');
INSERT INTO misc VALUES ('UserDataReply','info@sisplet.org');
INSERT INTO misc VALUES ('NewUserSubject','Pozdravljeni na Sisplet.org!');
INSERT INTO misc VALUES ('ChangedUserSubject','Sprememba profila');
INSERT INTO misc VALUES ('M2WNotAllowedSubject','ZAVRNJEN EMAIL');
INSERT INTO misc VALUES ('LostPasswordSubject','Izgubljeno geslo');
INSERT INTO misc VALUES ('HelloNewUser','\r\n           <p>Pozdravljeni na SFPAGENAME!<br /></p><p>Kot registriran uporabnik boste lahko:</p><ul><li>prejemali avtomatska obvestila, </li><li>podrobneje opredelili svoj profil narocanja, </li><li>dobili dostop do internih strani, </li><li>imeli druge ugodnosti, o katerih vas bomo sproti obvescali.</li></ul>            ');
INSERT INTO misc VALUES ('NewUserMail','\r\n            <p>Pozdravljeni, SFNAME!<br /><br />Uspesno ste se prijavili na SFPAGENAME. Na tej osnovi boste prejemali obvestila in imeli dostop do internih vsebin.</p><blockquote style=\"margin-right: 0px;\" dir=\"ltr\"><p>Vase nastavitve so naslednje:<br /><br />Ime: SFNAME<br />Email: SFMAIL<br />Geslo: SFPASS<br />Prijava: SFWITH<br /></p></blockquote><p><br />Lep pozdrav, </p><p>SFPAGENAME<br /><br /><br />Sporocilo smo vam poslali na SFMAIL<br />Za odjavo kliknite SFOUT tukaj SFEND<br />Za spremembo nastavitev kliknite SFCHANGE tukaj SFEND <br /></p>           ');
INSERT INTO misc VALUES ('ChangedUserMail','\r\n                <p>Pozdravljen, SFNAME!<br /><br />Spremenili ste svoje nastavitve. Novi podatki so:<br /><br /></p><div style=\"margin-left: 40px;\">Ime: SFNAME<br />Email: SFMAIL<br />Geslo: SFPASS<br />Prijava: SFWITH<br /></div><p>Lep pozdrav, </p><p>SFPAGENAME<br /><br /><br />Sporocilo smo vam poslali na SFMAIL<br />Za odjavo kliknite SFOUT tukaj SFEND<br />Za spremembo nastavitev kliknite SFCHANGE tukaj SFEND <br /></p>          ');
INSERT INTO misc VALUES ('M2WNotAllowed','\r\n          <p>Vase M2W sporocilo je bilo zavrnjeno:</p><ul><li>Preverite, ce ste uporabili registrirani email. </li><li>Pri administratorju preverite ce ste avtorizirani za M2W opcijo.</li></ul><p><br />Lep pozdrav,<br /><br />SFPAGENAME<br /><br /></p> ');
INSERT INTO misc VALUES ('LostPasswordMail','\r\n               <p>Pozdravljen, SFNAME!</p><p>Zahtevali ste novo geslo. Vase nove nastavitve so naslednje:<br /><br /></p><div style=\"margin-left: 40px;\">Ime: SFNAME<br />Email: SFMAIL<br />Geslo: SFPASS<br />Prijava: SFWITH<br /></div><p><br />Lep pozdrav, </p><p>SFPAGENAME<br /><br /><br /></p>             ');
INSERT INTO misc VALUES ('PageName','Sisplet.org');

ALTER TABLE menu ADD FULLTEXT (name);

ALTER TABLE aktualno ADD COLUMN naslovnica tinyint(1) not null default 1;
ALTER TABLE aktualno ADD COLUMN mailati tinyint(1) not null default 1;
ALTER TABLE aktualno ADD COLUMN popravil varchar(255) not null default "Neznan";
ALTER TABLE aktualno ADD COLUMN datumpopravka varchar(12) not null default "Neznan";
ALTER TABLE aktualno ADD COLUMN vpisal varchar(255) not null default "Neznan";
ALTER TABLE aktualno ADD COLUMN show_date tinyint(1) not null default 1;



update misc set value="04.05.30" where what='version';

update misc set value="04.06.04" where what='version';

ALTER IGNORE TABLE PdfIndex ADD UNIQUE INDEX (ImeFajla);
ALTER IGNORE TABLE narocanje ADD UNIQUE INDEX (email);
update misc set value="04.06.28" where what='version';
update misc set value="04.07.01" where what='version';

ALTER TABLE struktura_kategorij ADD admin INT NOT NULL AFTER type;

ALTER TABLE struktura_baze ADD dd1 TINYINT NOT NULL AFTER d1;
ALTER TABLE struktura_baze ADD dd11 TINYINT NOT NULL AFTER dd1;
ALTER TABLE struktura_baze ADD d_1 VARCHAR(50) NOT NULL AFTER dd11;

ALTER TABLE struktura_grid ADD pdd1 TINYINT NOT NULL AFTER rdb1;
ALTER TABLE struktura_grid ADD fdd1 TINYINT NOT NULL AFTER pdd1;
ALTER TABLE struktura_grid ADD rdd1 TINYINT NOT NULL AFTER fdd1;

CREATE TABLE data_dd (ddid int(11) NOT NULL default 0, did int(11) NOT NULL default 0);

CREATE TABLE struktura_dd (
  id int(11) NOT NULL AUTO_INCREMENT ,
  dd varchar(255) NOT NULL default '',
  url varchar(255) NOT NULL default '',
  email varchar(255) NOT NULL default '',
  slika varchar(255) NOT NULL default '',
  opomba tinytext NOT NULL,
PRIMARY KEY (id)
);

ALTER TABLE data_baze ADD dd1 VARCHAR(250) NOT NULL AFTER db1;

CREATE TABLE struktura_dds (
  id int(11) NOT NULL AUTO_INCREMENT ,
  dds varchar(255) NOT NULL default '',
  url varchar(255) NOT NULL default '',
  email varchar(255) NOT NULL default '',
  slika varchar(255) NOT NULL default '',
  opomba tinytext NOT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE data_dds (dd int(11) NOT NULL default 0, dds int(11) NOT NULL default 0);

ALTER TABLE PdfIndex ADD COLUMN Zacetek varchar(255) not null default "";
ALTER TABLE PdfIndex ADD COLUMN NaslovRubrike varchar(255) not null default "";

update misc set value="04.07.04" where what='version';
update misc set value="04.07.08" where what='version';


ALTER TABLE struktura_baze ADD dd1 TINYINT NOT NULL AFTER d1,
ADD dd11 TINYINT NOT NULL AFTER dd1,
ADD d_1 VARCHAR( 50 ) NOT NULL AFTER dd11;


ALTER TABLE struktura_grid ADD pdd1 TINYINT NOT NULL AFTER rdb1,
ADD fdd1 TINYINT NOT NULL AFTER pdd1,
ADD rdd1 TINYINT NOT NULL AFTER fdd1;

CREATE TABLE data_dd (
ddid int(11) NOT NULL default 0,
did int(11) NOT NULL default 0
);

CREATE TABLE struktura_dd (
id int(11) NOT NULL AUTO_INCREMENT ,
dd varchar(255) NOT NULL default '',
url varchar(255) NOT NULL default '',
email varchar(255) NOT NULL default '',
slika varchar(255) NOT NULL default '',
opomba tinytext NOT NULL,
PRIMARY KEY (id)
);

ALTER TABLE data_baze ADD dd1 VARCHAR(250) NOT NULL AFTER db1;

CREATE TABLE struktura_dds (
id int(11) NOT NULL AUTO_INCREMENT ,
dds varchar(255) NOT NULL default '',
url varchar(255) NOT NULL default '',
email varchar(255) NOT NULL default '',
slika varchar(255) NOT NULL default '',
opomba tinytext NOT NULL ,
PRIMARY KEY (id)
);

CREATE TABLE data_dds (
dd int(11) NOT NULL default 0,
dds int(11) NOT NULL default 0
);

CREATE TABLE selections (
id INT NOT NULL ,
naslov VARCHAR(200) NOT NULL ,
editor TEXT NOT NULL
);

ALTER TABLE neww ADD name VARCHAR(250) NOT NULL;

ALTER TABLE struktura_baze ADD listchar2 VARCHAR(10) NOT NULL AFTER listchar;

ALTER TABLE menu ADD counter INT DEFAULT 0 NOT NULL;
ALTER TABLE menu ADD count INT DEFAULT 0 NOT NULL;

ALTER TABLE data_baze ADD count INT DEFAULT 0 NOT NULL;

ALTER TABLE forum ADD user INT DEFAULT 0 NOT NULL,
ADD clan INT DEFAULT 0 NOT NULL,
ADD admin INT DEFAULT 0 NOT NULL;

CREATE TABLE manager_forum (
forum INT NOT NULL ,
manager INT NOT NULL
);

CREATE TABLE clan_forum (
forum INT NOT NULL ,
clan INT NOT NULL
);

ALTER TABLE menu ADD comment INT DEFAULT 0 NOT NULL ;
ALTER TABLE menu ADD thread INT DEFAULT 0 NOT NULL ;

update misc set value="04.07.11" where what='version';
update misc set value="04.07.12" where what='version';

ALTER TABLE tracking ADD COLUMN name varchar(255) not null default "";


DELETE FROM misc WHERE what='OKNewUser';
DELETE FROM misc WHERE what='OKByeUser';
DELETE FROM misc WHERE what='ByeEmail';
DELETE FROM misc WHERE what='ByeWarning';
DELETE FROM misc WHERE what='OKEdited';
DELETE FROM misc WHERE what='CommonError';
DELETE FROM misc WHERE what='ByeEmailSubject';
DELETE FROM misc WHERE what='LoginError';
DELETE FROM misc WHERE what='showsearch';
DELETE FROM misc WHERE what='showlogin';
DELETE FROM misc WHERE what='keywordssi';
DELETE FROM misc WHERE what='keywordsde';
DELETE FROM misc WHERE what='author';
DELETE FROM misc WHERE what='abstract';
DELETE FROM misc WHERE what='publisher';
DELETE FROM misc WHERE what='copyright';
DELETE FROM misc WHERE what='audience';
DELETE FROM misc WHERE what='pagetopic';
DELETE FROM misc WHERE what='revisit';

INSERT INTO misc VALUES ('OKNewUser', 'You are succeccfully subscribed to e-mail.');
INSERT INTO misc VALUES ('OKByeUser', 'You are successfully unsubscribed.');
INSERT INTO misc VALUES ('ByeEmail', 'You are unsubscribed.');
INSERT INTO misc VALUES ('ByeWarning', 'You are going to unsubscribe from out page.');
INSERT INTO misc VALUES ('OKEdited', 'You successfully edited your profile.');
INSERT INTO misc VALUES ('CommonError', 'There is an error.');
INSERT INTO misc VALUES ('ByeEmailSubject', 'Successful unsubscription');

INSERT INTO misc VALUES ('LoginError', 'There is an error.');

INSERT INTO misc VALUES ('showsearch', '1');
INSERT INTO misc VALUES ('showlogin', '1');

INSERT INTO misc VALUES ('keywordssi', '');
INSERT INTO misc VALUES ('keywordsde', '');
INSERT INTO misc VALUES ('author', '');
INSERT INTO misc VALUES ('abstract', '');
INSERT INTO misc VALUES ('publisher', '');
INSERT INTO misc VALUES ('copyright', '');
INSERT INTO misc VALUES ('audience', '');
INSERT INTO misc VALUES ('pagetopic', '');
INSERT INTO misc VALUES ('revisit', '');

ALTER TABLE `struktura_kategorij` ADD `grid` INT NOT NULL AFTER `admin` ;
ALTER TABLE `struktura_dd` ADD `type` INT DEFAULT '0' NOT NULL ;
TRUNCATE TABLE `data_dds` ;
DROP TABLE `struktura_dds` ;



update misc set value="04.07.15" where what='version';
update misc set value="04.07.19" where what='version';

create table struktura_vir (id integer not null default 0 auto_increment, vir varchar(255) not null, url varchar(255) not null, email varchar(255) not null, slika varchar(255) not null, opomba tinytext, key id(id));

alter table struktura_avtor add fulltext (avtor);
alter table struktura_vir add fulltext (vir);
alter table vodic add column show_date tinyint(1) not null default 1;

alter table data_baze add fulltext (naslov2);
alter table data_baze add fulltext (vir1);
alter table data_baze add fulltext (virx1);
alter table data_baze add fulltext (vir2);
alter table data_baze add fulltext (db1);
alter table data_baze add fulltext (dd1);
alter table data_baze add fulltext (leto);
alter table data_baze add fulltext (published);
alter table data_baze add fulltext (res);
alter table data_baze add fulltext (details);
alter table data_baze add fulltext (desc1);
alter table data_baze add fulltext (desc2);
alter table data_baze add fulltext (char1);
alter table data_baze add fulltext (char2);
alter table data_baze add fulltext (num1);
alter table data_baze add fulltext (num2);

alter table PdfIndex ADD COLUMN sid integer not null default 0;
alter table PdfIndex ADD COLUMN tip integer not null default 0;

update misc set value="04.07.29" where what='version';

ALTER table novice ADD column dostop tinyint(1) not null default 0;
ALTER table mailnovice ADD column dostop tinyint(1) not null default 0;
ALTER table aktualno ADD column dostop tinyint(1) not null default 0;
ALTER table faq ADD column dostop tinyint(1) not null default 0;
ALTER table vodic ADD column dostop tinyint(1) not null default 0;
ALTER table rubrika1 ADD column dostop tinyint(1) not null default 0;
ALTER table rubrika2 ADD column dostop tinyint(1) not null default 0;
ALTER table rubrika3 ADD column dostop tinyint(1) not null default 0;
ALTER table rubrika4 ADD column dostop tinyint(1) not null default 0;

ALTER table new DROP key id;
ALTER table new add column dostop tinyint(1) not null default 0;

CREATE TABLE DeadLinks (title varchar(255) not null default "", id varchar(255) not null default "", location varchar(100) not null default "", link varchar(255) not null default "");

ALTER TABLE new ADD FULLTEXT (name);
ALTER TABLE forum ADD FULLTEXT (naslov);

ALTER TABLE mailnovice ADD COLUMN show_date tinyint(1) not null default 1;
ALTER TABLE faq ADD COLUMN show_date tinyint(1) not null default 1;

ALTER TABLE narocanje ADD COLUMN kdaj date NOT NULL default '2003-01-01';
ALTER TABLE administratorji ADD COLUMN kdaj date NOT NULL default '2003-01-01';

update misc set value="04.08.06" where what='version';
update misc set value="04.08.09" where what='version';

CREATE TABLE obvescanje_tema (
  uid INT NOT NULL,
  tid INT NOT NULL);


CREATE TABLE obvescanje_forum (
uid INT NOT NULL,
fid INT NOT NULL);

INSERT INTO misc VALUES ('forum_alert', NOW());

INSERT INTO misc VALUES ('thread_alert', NOW());

update misc set value="04.09 alpha III" where what="version";

insert into misc (what, value) values ('Skin', 'Default');
insert into misc (what, value) values ('adminskin', 'Default');

ALTER TABLE struktura_baze ADD nivojska INT DEFAULT 0 NOT NULL AFTER naziv;
ALTER TABLE data_baze ADD type INT DEFAULT 0 NOT NULL AFTER bid;
TRUNCATE TABLE data_dds;
ALTER TABLE data_baze ADD disp INT DEFAULT 0 NOT NULL AFTER type ;
ALTER TABLE new ADD type INT DEFAULT 0 NOT NULL ;
ALTER TABLE new CHANGE name name VARCHAR(40) NOT NULL;
ALTER TABLE struktura_baze DROP dd1, DROP dd11, DROP d_1;
ALTER TABLE data_baze DROP dd1;
DROP TABLE data_dd;

update misc set value="04.09 beta I" where what="version";

CREATE TABLE grid (id INT NOT NULL, text TEXT NOT NULL);

ALTER TABLE struktura_baze ADD admin INT DEFAULT 3 NOT NULL AFTER naziv;

ALTER TABLE struktura_avtor ADD adminmail INT DEFAULT 3 NOT NULL, ADD adminopomba MEDIUMTEXT NOT NULL;
ALTER TABLE struktura_avtor CHANGE opomba opomba MEDIUMTEXT NOT NULL ;

ALTER TABLE struktura_db ADD adminmail INT DEFAULT 3 NOT NULL, ADD adminopomba MEDIUMTEXT NOT NULL;
ALTER TABLE struktura_db CHANGE opomba opomba MEDIUMTEXT NOT NULL ;

ALTER TABLE struktura_vir ADD adminmail INT DEFAULT 3 NOT NULL, ADD adminopomba MEDIUMTEXT NOT NULL ;
ALTER TABLE struktura_vir CHANGE opomba opomba MEDIUMTEXT NOT NULL ;

update misc set value="04.09 beta II" where what="version";
update misc set value="04.09 beta III" where what="version";

ALTER TABLE post ADD admin INT DEFAULT 3 NOT NULL ;
ALTER TABLE narocanje ADD showemail INT DEFAULT 1 NOT NULL ;

update misc set value="04.09.20" where what="version";


ALTER TABLE narocanje CHANGE tip tip tinyint(1) not null default 0;

CREATE TABLE forum_menu (mid int(11) NOT NULL default 0, fid int(11) NOT NULL default 0);
DELETE FROM misc WHERE what='ForumAlert';
DELETE FROM misc WHERE what='ForumSubject';
INSERT INTO misc (what, value) VALUES ('ForumAlert', '');
INSERT INTO misc (what, value) VALUES ('ForumSubject', '');

UPDATE misc SET value='04.09.28' WHERE what='version';

ALTER TABLE struktura_avtor ADD showemail INT DEFAULT 1 NOT NULL AFTER opomba;
ALTER TABLE struktura_db ADD showemail INT DEFAULT 1 NOT NULL AFTER opomba;
ALTER TABLE struktura_vir ADD showemail INT DEFAULT 1 NOT NULL AFTER opomba;

ALTER TABLE struktura_baze ADD listyear INT DEFAULT 0 NOT NULL AFTER listchar2;
ALTER TABLE data_baze ADD admin INT DEFAULT 3 NOT NULL AFTER type;

ALTER TABLE struktura_baze ADD res2 TINYINT NOT NULL AFTER re, ADD res21 TINYINT NOT NULL AFTER res2 , ADD re2 VARCHAR(50) NOT NULL AFTER res21;
ALTER TABLE struktura_grid ADD pres2 TINYINT NOT NULL AFTER rres, ADD fres2 TINYINT NOT NULL AFTER pres2, ADD rres2 TINYINT NOT NULL AFTER fres2;
ALTER TABLE data_baze ADD res2 TEXT NOT NULL AFTER res_, ADD res2_ INT DEFAULT -1 NOT NULL AFTER res2;

UPDATE misc SET value='04.09.29' WHERE what='version';
UPDATE misc SET value='04.09.30' WHERE what='version';

ALTER TABLE data_baze ADD masterservant VARCHAR(250) NOT NULL AFTER disp;
ALTER TABLE new ADD list INT DEFAULT 0 NOT NULL ;

UPDATE misc SET value='04.10.01' WHERE what='version';

ALTER TABLE administratorji ADD COLUMN infomail tinyint(1) not null default 0;

UPDATE misc SET value='04.10.04' WHERE what='version';

ALTER TABLE post ADD dispauth INT DEFAULT 0 NOT NULL;
ALTER TABLE narocanje ADD avatar VARCHAR(250) NOT NULL;

UPDATE misc SET value='04.10.05' WHERE what='version';
UPDATE misc SET value='04.10.06' WHERE what='version';

ALTER TABLE neww ADD typ INT NOT NULL AFTER nid;
UPDATE misc SET value='04.10.07' WHERE what='version';

DELETE FROM misc WHERE what='ShowRubrikeAdmin';
INSERT INTO misc (what, value) VALUES ('ShowRubrikeAdmin', '0');

UPDATE misc SET value='04.10.11' WHERE what='version';

DELETE FROM misc WHERE what='SendToAuthorExplain';
INSERT INTO misc (what, value) VALUES ('SendToAuthorExplain', '');

INSERT INTO display (id, val) VALUES ('37', '3');

ALTER TABLE struktura_baze ADD contactinfo TINYINT NOT NULL AFTER nu2, ADD contactinfo1 TINYINT NOT NULL AFTER contactinfo, ADD cti VARCHAR(50) NOT NULL AFTER contactinfo1;
ALTER TABLE struktura_grid ADD pcti TINYINT NOT NULL, ADD fcti TINYINT NOT NULL, ADD rcti TINYINT NOT NULL;
ALTER TABLE data_baze ADD contactinfo INT NOT NULL AFTER num2;

CREATE TABLE anketa (id INT NOT NULL AUTO_INCREMENT, naslov VARCHAR(250) NOT NULL, PRIMARY KEY (id));
CREATE TABLE anketa_opcije (id INT NOT NULL AUTO_INCREMENT, aid INT NOT NULL, text VARCHAR(250) NOT NULL, votes INT NOT NULL, PRIMARY KEY (id));

UPDATE misc SET value='04.10.12' WHERE what='version';
UPDATE misc SET value='04.10.13' WHERE what='version';

alter table narocanje change email email varchar(255) not null default '';

ALTER TABLE data_baze ADD disp_ms INT DEFAULT 0 NOT NULL AFTER masterservant;

ALTER TABLE struktura_baze ADD master_alt VARCHAR(50) NOT NULL AFTER nivojska, ADD servant_alt VARCHAR(50) NOT NULL AFTER master_alt;
ALTER TABLE struktura_grid ADD pcountry TINYINT NOT NULL AFTER ruser2, ADD fcountry TINYINT NOT NULL AFTER pcountry, ADD rcountry TINYINT NOT NULL AFTER fcountry;
ALTER TABLE struktura_baze ADD country TINYINT NOT NULL AFTER pub, ADD country1 TINYINT NOT NULL AFTER country, ADD cou VARCHAR(50) NOT NULL AFTER country1;

CREATE TABLE struktura_country (id int(11) NOT NULL AUTO_INCREMENT, country varchar(255) NOT NULL default '', url varchar(255) NOT NULL default '', email varchar(255) NOT NULL default '', slika varchar(255) NOT NULL default '', opomba mediumtext NOT NULL , showemail int(11) NOT NULL default '1', adminmail int(11) NOT NULL default '3', adminopomba mediumtext NOT NULL , PRIMARY KEY (id) , FULLTEXT KEY country (country) ) TYPE = MYISAM ;
CREATE TABLE data_country (cid int( 11 ) NOT NULL default 0, did int(11) NOT NULL default 0) TYPE = MYISAM ;

ALTER TABLE data_baze ADD country VARCHAR(250) NOT NULL AFTER published;
INSERT INTO display (id, val) VALUES (38, 150);
INSERT INTO display (id, val) VALUES (39, 1);
ALTER TABLE new ADD poll INT DEFAULT 0 NOT NULL ;

UPDATE misc SET value='04.10.19' WHERE what='version';

ALTER TABLE neww ADD poll INT NOT NULL ;
ALTER TABLE struktura_baze ADD users INT DEFAULT 0 NOT NULL AFTER nivojska , ADD multigrid INT DEFAULT 0 NOT NULL AFTER users;
ALTER TABLE data_baze ADD author VARCHAR(255) NOT NULL ;

DELETE FROM misc WHERE what='NoviceChars';
INSERT INTO misc (what, value) VALUES ('NoviceChars', '100');

UPDATE misc SET value='04.10.25' WHERE what='version';

DELETE FROM misc WHERE what='ShowSearchMenu';
INSERT INTO misc (what, value) VALUES ('ShowSearchMenu', '0');
DELETE FROM misc WHERE what='ShowTracking';
INSERT INTO misc (what, value) VALUES ('ShowTracking', '0');

ALTER TABLE narocanje ADD COLUMN groups integer not null default 0;
ALTER TABLE narocanje ADD COLUMN camefrom integer not null default 0;
ALTER TABLE narocanje ADD COLUMN approved integer not null default 0;

CREATE TABLE groups (gid bigint unsigned NOT NULL AUTO_INCREMENT, name VARCHAR(255) NOT NULL, members INTEGER NOT NULL DEFAULT 0, PRIMARY KEY (gid));

INSERT INTO display (id, val) VALUES ('40', '50');

ALTER TABLE struktura_baze ADD aavtor1 TINYINT NOT NULL AFTER avtor1 ;
ALTER TABLE struktura_baze ADD anaslov1 TINYINT NOT NULL AFTER naslov1 ;
ALTER TABLE struktura_baze ADD anaslov21 TINYINT NOT NULL AFTER naslov21 ;
ALTER TABLE struktura_baze ADD avir11 TINYINT NOT NULL AFTER vir11 ;
ALTER TABLE struktura_baze ADD avirx11 TINYINT NOT NULL AFTER virx11 ;
ALTER TABLE struktura_baze ADD avir21 TINYINT NOT NULL AFTER vir21 ;
ALTER TABLE struktura_baze ADD adb11 TINYINT NOT NULL AFTER db11 ;
ALTER TABLE struktura_baze ADD ayear1 TINYINT NOT NULL AFTER year1 ;
ALTER TABLE struktura_baze ADD arecnum1 TINYINT NOT NULL AFTER recnum1 ;
ALTER TABLE struktura_baze ADD adateinsert1 TINYINT NOT NULL AFTER dateinsert1 ;
ALTER TABLE struktura_baze ADD adateedit1 TINYINT NOT NULL AFTER dateedit1 ;
ALTER TABLE struktura_baze ADD adate1 TINYINT NOT NULL AFTER date1 ;
ALTER TABLE struktura_baze ADD adate21 TINYINT NOT NULL AFTER date21 ;
ALTER TABLE struktura_baze ADD apublished1 TINYINT NOT NULL AFTER published1 ;
ALTER TABLE struktura_baze ADD acountry1 TINYINT NOT NULL AFTER country1 ;
ALTER TABLE struktura_baze ADD aabstract1 TINYINT NOT NULL AFTER abstract1 ;
ALTER TABLE struktura_baze ADD aabstract21 TINYINT NOT NULL AFTER abstract21 ;
ALTER TABLE struktura_baze ADD aopis11 TINYINT NOT NULL AFTER opis11 ;
ALTER TABLE struktura_baze ADD aopis21 TINYINT NOT NULL AFTER opis21 ;
ALTER TABLE struktura_baze ADD ares1 TINYINT NOT NULL AFTER res1 ;
ALTER TABLE struktura_baze ADD ares21 TINYINT NOT NULL AFTER res21 ;
ALTER TABLE struktura_baze ADD adetails1 TINYINT NOT NULL AFTER details1 ;
ALTER TABLE struktura_baze ADD adesc11 TINYINT NOT NULL AFTER desc11 ;
ALTER TABLE struktura_baze ADD adesc21 TINYINT NOT NULL AFTER desc21 ;
ALTER TABLE struktura_baze ADD achar11 TINYINT NOT NULL AFTER char11 ;
ALTER TABLE struktura_baze ADD achar21 TINYINT NOT NULL AFTER char21 ;
ALTER TABLE struktura_baze ADD anum11 TINYINT NOT NULL AFTER num11 ;
ALTER TABLE struktura_baze ADD anum21 TINYINT NOT NULL AFTER num21 ;
ALTER TABLE struktura_baze ADD acontactinfo1 TINYINT NOT NULL AFTER contactinfo1 ;

UPDATE misc SET value='04.11.02' WHERE what='version';

ALTER TABLE mailnovice ADD COLUMN mailati tinyint (1) not null default 1;
ALTER TABLE mailnovice ADD COLUMN popravil varchar(255) not null default "Neznan";
ALTER TABLE mailnovice ADD COLUMN datumpopravka varchar(12) not null default "Neznan";
ALTER TABLE mailnovice ADD COLUMN datumvpisa varchar(12) not null default "Neznan";

UPDATE misc SET value='04.11.03' WHERE what='version';

ALTER TABLE administratorji ADD COLUMN passive tinyint(1) not null default 0;

UPDATE misc SET value='Dear colleague:<br><br>On http://www.websm.org site we have included some of your bibliograpy related to Web survey methodology.<br>Recently we received the below-enclosed unquiry for more information.<br><br>If you respond, be aware that we are also highly and permamently interested to include more of your work on out rite, so please let us know.<br><br>Sincerely,<br><br>WebSM team<br><br>To unsubscribe click SFOUT here SFEND.<br>To change/edit your profile click SFCHANGE here SFEND.' WHERE what='OKByeUser';

INSERT INTO misc VALUES ('UploadDummy', '0');

UPDATE misc SET value='04.11.15' WHERE what='version';

INSERT INTO misc VALUES ('FullPageView', '0');

UPDATE misc SET value='04.11.23' WHERE what='version';
CREATE TABLE rss (id bigint unsigned NOT NULL auto_increment, name varchar(255) not null default '', url varchar(255) not null default '', primary key (id));
UPDATE misc SET value='04.11.29' WHERE what='version';
UPDATE misc SET value='04.12.08' WHERE what='version';

UPDATE misc SET value='04.12.08' WHERE what='version';

ALTER TABLE struktura_baze ADD listlimit INT DEFAULT '20' NOT NULL ;
ALTER TABLE forum ADD display INT DEFAULT '1' NOT NULL AFTER opis;

UPDATE misc SET value='04.12.12+1 :-)' WHERE what='version';


CREATE TABLE struktura_companies (
  id int(11) NOT NULL auto_increment,
  companies varchar(255) NOT NULL default '',
  url varchar(255) NOT NULL default '',
  email varchar(255) NOT NULL default '',
  slika varchar(255) NOT NULL default '',
  opomba mediumtext NOT NULL,
  showemail int(11) NOT NULL default '1',
  adminmail int(11) NOT NULL default '3',
  adminopomba mediumtext NOT NULL,
  PRIMARY KEY  (id),
  FULLTEXT KEY companies (companies)
) TYPE=MyISAM ;


CREATE TABLE data_companies (
  coid int(11) NOT NULL default '0',
  did int(11) NOT NULL default '0'
) TYPE=MyISAM;

ALTER TABLE struktura_baze ADD companies1 TINYINT NOT NULL AFTER d1 ,
ADD companies11 TINYINT NOT NULL AFTER companies1 ,
ADD acompanies11 TINYINT NOT NULL AFTER companies11 ,
ADD com1 VARCHAR( 50 ) NOT NULL AFTER acompanies11 ;


ALTER TABLE struktura_grid ADD pcompanies1 TINYINT NOT NULL AFTER rdb1 ,
ADD fcompanies1 TINYINT NOT NULL AFTER pcompanies1 ,
ADD rcompanies1 TINYINT NOT NULL AFTER fcompanies1 ;

ALTER TABLE data_baze ADD companies1 VARCHAR( 250 ) NOT NULL AFTER db1;

UPDATE misc SET value='04.12.17' WHERE what='version';

ALTER TABLE struktura_baze ADD listlimit INT DEFAULT '20' NOT NULL ;
ALTER TABLE forum ADD display INT DEFAULT '1' NOT NULL AFTER opis;

ALTER TABLE post ADD dispthread INT NOT NULL ;
ALTER TABLE struktura_baze ADD listed_servants VARCHAR(50) NOT NULL AFTER servant_alt ;

UPDATE misc SET value='04.12.22' WHERE what='version';

INSERT INTO misc (what, value) VALUES ('AuthorCC', 'Dear visitor,<br><br>Thank you for your interest; your message to author has been sent.<br><br>Message you sent is attached at the bottom of email.<br>');
ALTER TABLE data_baze ADD FULLTEXT (res2);
INSERT INTO misc (what, value) VALUES ('RegName', '1');
INSERT INTO misc (what, value) VALUES ('RegPass', '1');
INSERT INTO misc (what, value) VALUES ('RegAlert', '1');
UPDATE misc SET value='04.12.29' WHERE what='version';

ALTER TABLE data_baze CHANGE masterservant masterservant TEXT NOT NULL;
UPDATE misc SET value='05.01.04' WHERE what='version';

UPDATE misc SET value='05.01.07' WHERE what='version';
UPDATE misc SET value='05.01.10' WHERE what='version';

INSERT INTO misc (what, value) VALUES ('RegAlertOptions', '1');
INSERT INTO misc (what, value) VALUES ('RegEmailOptions', '1');
INSERT INTO misc (what, value) VALUES ('ToBasicUser', '');

ALTER TABLE struktura_avtor ADD FULLTEXT (opomba);
UPDATE misc SET value='05.01.13' WHERE what='version';

UPDATE misc SET value='05.01.19' WHERE what='version';

INSERT INTO misc (what, value) VALUES ('AdminRememberIP', '');
DELETE FROM new WHERE id=2;
INSERT INTO new (id, name, vert, hor, display, width, rows, menu, date, dostop, type, list, poll) values ('2', 'Vodic', '3', '3', '0', '', '5', '1', '1', '0', '0', '0', '0');
drop table vodic;

CREATE TABLE vodic (
  naslov varchar(100) NOT NULL default '',
  vsebina mediumtext NOT NULL,
  datum date NOT NULL default '0000-00-00',
  sid int(10) unsigned NOT NULL auto_increment,
  cid int(11) NOT NULL default '0',
  avtor varchar(100) NOT NULL default 'RIS',
  link varchar(255) default NULL,
  kategorije varchar(255) default NULL,
  email varchar(255) default NULL,
  datoteka varchar(255) default NULL,
  online int(11) NOT NULL default '1',
  thread int(11) NOT NULL default '0',
  comment tinyint(4) NOT NULL default '1',
  naslovnica tinyint(1) NOT NULL default '1',
  mailati tinyint(1) NOT NULL default '1',
  pomembno tinyint(1) NOT NULL default '0',
  vpisal varchar(255) NOT NULL default 'Neznan',
  datumvpisa varchar(12) NOT NULL default 'Neznan',
  popravil varchar(255) NOT NULL default 'Neznan',
  datumpopravka varchar(12) NOT NULL default 'Neznan',
  show_date tinyint(1) NOT NULL default '1',
  dostop tinyint(1) NOT NULL default '0',
  KEY sid (sid),
  FULLTEXT KEY naslov (naslov,vsebina,kategorije)
) TYPE=MyISAM;


UPDATE misc SET value='05.01.24' WHERE what='version';
UPDATE misc SET value='05.01.25' WHERE what='version';
INSERT INTO misc VALUES ('ForumMenus', '0');
UPDATE misc SET value='05.02.03' WHERE what='version';

ALTER TABLE kategorije_baze CHANGE kategorija kategorija VARCHAR(150) NOT NULL;
ALTER TABLE struktura_kategorij ADD click INT DEFAULT '0' NOT NULL AFTER grid ;

UPDATE misc SET value='05.02.03b' WHERE what='version';

alter table menu change news news integer not null default 0;
UPDATE misc SET value='05.02.07' WHERE what='version';

alter table faq add column pomembno tinyint(1) not null default 0 after mailati;

UPDATE misc SET value='05.02.10' WHERE what='version';

ALTER TABLE struktura_baze ADD listed_servants2 VARCHAR(50) NOT NULL AFTER listed_servants;
ALTER TABLE data_baze ADD disp_servant INT DEFAULT 1 NOT NULL AFTER disp;
ALTER TABLE struktura_baze ADD autoupdate INT DEFAULT 0 NOT NULL AFTER dat2;
ALTER TABLE data_baze ADD uinterval INT NOT NULL AFTER userdate2;

UPDATE misc SET value='05.02.15' WHERE what='version';

ALTER TABLE struktura_baze ADD archive INT DEFAULT 0 NOT NULL ,
ADD archive_inserts INT DEFAULT 30 NOT NULL ;

ALTER TABLE neww ADD abc INT DEFAULT 0 NOT NULL ;

UPDATE misc SET value='05.02.17' WHERE what='version';

INSERT INTO misc (what, value) VALUES ('RegEditorOn', '0');
INSERT INTO misc (what, value) VALUES ('RegEditorName', '');
INSERT INTO misc (what, value) VALUES ('RegTextBoxOn', '0');
INSERT INTO misc (what, value) VALUES ('RegTextBoxName', '');

ALTER TABLE narocanje ADD editor mediumtext not null default "";
ALTER TABLE narocanje ADD textarea mediumtext not null default "";


ALTER TABLE selections ADD menu INT DEFAULT 1 NOT NULL ;

CREATE TABLE baza_help (
id INT NOT NULL ,
hnas TINYTEXT NOT NULL ,
hnas2 TINYTEXT NOT NULL ,
havt TINYTEXT NOT NULL ,
hvix1 TINYTEXT NOT NULL ,
hyea TINYTEXT NOT NULL ,
hvi1 TINYTEXT NOT NULL ,
hvi2 TINYTEXT NOT NULL ,
hd1 TINYTEXT NOT NULL ,
hcom1 TINYTEXT NOT NULL ,
hrec TINYTEXT NOT NULL ,
hdat TINYTEXT NOT NULL ,
hins TINYTEXT NOT NULL ,
hedi TINYTEXT NOT NULL ,
hdat2 TINYTEXT NOT NULL ,
hre TINYTEXT NOT NULL ,
hre2 TINYTEXT NOT NULL ,
hcou TINYTEXT NOT NULL ,
habs TINYTEXT NOT NULL ,
habs2 TINYTEXT NOT NULL ,
hopi1 TINYTEXT NOT NULL ,
hopi2 TINYTEXT NOT NULL ,
hpub TINYTEXT NOT NULL ,
hdet TINYTEXT NOT NULL ,
hdes1 TINYTEXT NOT NULL ,
hdes2 TINYTEXT NOT NULL ,
hcha1 TINYTEXT NOT NULL ,
hcha2 TINYTEXT NOT NULL ,
hnu1 TINYTEXT NOT NULL ,
hnu2 TINYTEXT NOT NULL ,
hcti TINYTEXT NOT NULL ,
UNIQUE (
id
)
);



UPDATE misc SET value='05.02.21' WHERE what='version';


CREATE TABLE extra_row (
id INT NOT NULL ,
bid INT NOT NULL ,
type INT NOT NULL ,
val INT NOT NULL
);

CREATE TABLE extra_row_txt (
id INT NOT NULL ,
bid INT NOT NULL ,
type INT NOT NULL ,
text VARCHAR( 200 ) NOT NULL
);

ALTER TABLE extra_row ADD record INT NOT NULL AFTER type;

UPDATE misc SET value='05.02.28' WHERE what='version';

UPDATE misc SET value='05.03.03' WHERE what='version';

INSERT INTO misc (what, value) VALUES ('RegAddArticle', '0');

UPDATE misc SET value='05.03.08' WHERE what='version';
UPDATE misc SET value='05.03.12' WHERE what='version';

INSERT INTO misc (what, value) VALUES ('SendToAuthorSpeech', '');
INSERT INTO misc (what, value) VALUES ('RegAvatarEnable', '1');

CREATE TABLE baza_menu (
  id int(11) NOT NULL default 0,
  bid int(11) NOT NULL default 0,
  mid int(11) NOT NULL default 0
) TYPE=MyISAM;


UPDATE misc SET value='05.03.16' WHERE what='version';

INSERT INTO misc VALUES ('NewChars', '100');
INSERT INTO misc VALUES ('SendToForumSpeech', '');
INSERT INTO misc VALUES ('ArticleDateType', '0');

UPDATE misc SET value='05.03.24' WHERE what='version';

ALTER TABLE struktura_kategorij ADD name VARCHAR(250) NOT NULL ;

CREATE TABLE baza_insert_clani (
  id int(11) NOT NULL default '1',
  cavt tinyint(4) NOT NULL default '1',
  cnas1 tinyint(4) NOT NULL default '1',
  cnas2 tinyint(4) NOT NULL default '1',
  cvir1 tinyint(4) NOT NULL default '1',
  cvirx1 tinyint(4) NOT NULL default '1',
  cvir2 tinyint(4) NOT NULL default '1',
  cdb1 tinyint(4) NOT NULL default '1',
  ccompanies1 tinyint(4) NOT NULL default '1',
  cdd1 tinyint(4) NOT NULL default '1',
  cleto tinyint(4) NOT NULL default '1',
  crec tinyint(4) NOT NULL default '1',
  cdate tinyint(4) NOT NULL default '1',
  cedit tinyint(4) NOT NULL default '1',
  cuser1 tinyint(4) NOT NULL default '1',
  cuser2 tinyint(4) NOT NULL default '1',
  ccountry tinyint(4) NOT NULL default '1',
  cabs1 tinyint(4) NOT NULL default '1',
  cabs2 tinyint(4) NOT NULL default '1',
  copis1 tinyint(4) NOT NULL default '1',
  copis2 tinyint(4) NOT NULL default '1',
  cres tinyint(4) NOT NULL default '1',
  cres2 tinyint(4) NOT NULL default '1',
  cpub tinyint(4) NOT NULL default '1',
  cdet tinyint(4) NOT NULL default '1',
  cdes1 tinyint(4) NOT NULL default '1',
  cdes2 tinyint(4) NOT NULL default '1',
  ccha1 tinyint(4) NOT NULL default '1',
  ccha2 tinyint(4) NOT NULL default '1',
  cnum1 tinyint(4) NOT NULL default '1',
  cnum2 tinyint(4) NOT NULL default '1',
  ccti tinyint(4) NOT NULL default '1'
) TYPE=MyISAM;

UPDATE misc SET value='05.03.30' WHERE what='version';
UPDATE misc SET value='05.03.31' WHERE what='version';
UPDATE misc SET value='05.04.08' WHERE what='version';


# use meta;
# alter table administratorji add ips varchar(255) not null default "";









INSERT INTO misc (what, value) VALUES ('AdminNoBotherIP', '0');
INSERT INTO misc (what, value) VALUES ('RelatedDropdown', 'http://www.sisplet.org,Sisplet CMS');
INSERT INTO misc (what, value) VALUES ('RelatedDropdownActive', '0');
UPDATE misc SET value='05.04.20' WHERE what='version';

UPDATE misc SET value='05.04.28' WHERE what='version';
#
# Table structure for table `kat_avtor`
#

CREATE TABLE kat_avtor (
  did int(11) NOT NULL default '0',
  cid int(10) NOT NULL default '0',
  val tinyint(1) NOT NULL default '0'
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Table structure for table 'kat_companies'
#

CREATE TABLE kat_companies (
  did int(11) NOT NULL default '0',
  cid int(10) NOT NULL default '0',
  val tinyint(1) NOT NULL default '0'
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Table structure for table 'kat_country'
#

CREATE TABLE kat_country (
  did int(11) NOT NULL default '0',
  cid int(10) NOT NULL default '0',
  val tinyint(1) NOT NULL default '0'
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Table structure for table 'kat_db'
#

CREATE TABLE kat_db (
  did int(11) NOT NULL default '0',
  cid int(10) NOT NULL default '0',
  val tinyint(1) NOT NULL default '0'
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Table structure for table 'kat_vir'
#

CREATE TABLE kat_vir (
  did int(11) NOT NULL default '0',
  cid int(10) NOT NULL default '0',
  val tinyint(1) NOT NULL default '0'
) TYPE=MyISAM;

CREATE TABLE teaser (id integer not null auto_increment, name varchar(255) not null default '', content mediumtext, key id(id));
alter table new add column teaser integer not null default 0 after poll;
alter table neww add column teaser integer not null default 0 after poll;

ALTER TABLE novice add column introtype tinyint(1) not null default 0;
ALTER TABLE novice add column introchars integer not null default 150;
ALTER TABLE novice add column introteaser mediumtext not null default '';

ALTER TABLE faq add column introtype tinyint(1) not null default 0;
ALTER TABLE faq add column introchars integer not null default 150;
ALTER TABLE faq add column introteaser mediumtext not null default '';

ALTER TABLE aktualno add column introtype tinyint(1) not null default 0;
ALTER TABLE aktualno add column introchars integer not null default 150;
ALTER TABLE aktualno add column introteaser mediumtext not null default '';

ALTER TABLE vodic add column introtype tinyint(1) not null default 0;
ALTER TABLE vodic add column introchars integer not null default 150;
ALTER TABLE vodic add column introteaser mediumtext not null default '';

ALTER TABLE rubrika1 add column introtype tinyint(1) not null default 0;
ALTER TABLE rubrika1 add column introchars integer not null default 150;
ALTER TABLE rubrika1 add column introteaser mediumtext not null default '';

ALTER TABLE rubrika2 add column introtype tinyint(1) not null default 0;
ALTER TABLE rubrika2 add column introchars integer not null default 150;
ALTER TABLE rubrika2 add column introteaser mediumtext not null default '';

ALTER TABLE rubrika3 add column introtype tinyint(1) not null default 0;
ALTER TABLE rubrika3 add column introchars integer not null default 150;
ALTER TABLE rubrika3 add column introteaser mediumtext not null default '';

ALTER TABLE rubrika4 add column introtype tinyint(1) not null default 0;
ALTER TABLE rubrika4 add column introchars integer not null default 150;
ALTER TABLE rubrika4 add column introteaser mediumtext not null default '';

ALTER TABLE m2w add column introtype tinyint(1) not null default 0;
ALTER TABLE m2w add column introchars integer not null default 150;
ALTER TABLE m2w add column introteaser mediumtext not null default '';

ALTER TABLE struktura_avtor add column avtorji varchar(255) not null default '';
ALTER TABLE struktura_baze add column avtorji varchar(255) not null default '';
ALTER TABLE struktura_companies add column avtorji varchar(255) not null default '';
ALTER TABLE struktura_country add column avtorji varchar(255) not null default '';
ALTER TABLE struktura_db add column avtorji varchar(255) not null default '';
ALTER TABLE struktura_dd add column avtorji varchar(255) not null default '';
ALTER TABLE struktura_grid add column avtorji varchar(255) not null default '';
ALTER TABLE struktura_kategorij add column avtorji varchar(255) not null default '';
ALTER TABLE struktura_vir add column avtorji varchar(255) not null default '';


UPDATE misc SET value='05.05.08' WHERE what='version';

INSERT INTO misc VALUES ('ForumAccessMode', '0');
INSERT INTO misc VALUES ('MenuTriangles', '0');

UPDATE misc SET value='05.05.13' WHERE what='version';

INSERT INTO misc VALUES ('BreadCrumbs', '1');
UPDATE misc SET value='1' WHERE what='BreadCrumbs';

alter table teaser add column border varchar(50) not null default "dotted";
alter table teaser add column showtitle tinyint(1) not null default "0";

UPDATE misc SET value='05.05.23' WHERE what='version';
UPDATE misc SET value='05.06.03' WHERE what='version';

CREATE TABLE sessions (ip varchar(64) not null, lasttime varchar(20), handle varchar(255), registered tinyint(1));
CREATE TABLE registers (ip varchar(64) not null, lasttime varchar(20), handle varchar(255), code varchar(80));
INSERT INTO misc (what, value) VALUES ('BlindEmail', 'info@sisplet.org');
INSERT INTO misc (what, value) VALUES ('ShowOnline', '0');
UPDATE misc SET value='05.06.13' WHERE what='version';
UPDATE misc SET value='05.06.27' WHERE what='version';

ALTER TABLE struktura_dd DROP COLUMN avtorji;
ALTER TABLE struktura_grid DROP COLUMN avtorji;
ALTER TABLE struktura_kategorij DROP COLUMN avtorji;

INSERT INTO misc (what, value) VALUES ('ForumColumns', '0');
CREATE TABLE forum_groups (tid INT NOT NULL ,users MEDIUMTEXT NOT NULL);
ALTER TABLE struktura_baze ADD random INT DEFAULT '0' NOT NULL ;


#
# Table structure for table `active`
#

CREATE TABLE hour_active (
  uid int(10) NOT NULL default '0',
  date datetime NOT NULL default '0000-00-00 00:00:00'
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `comment`
#

CREATE TABLE hour_comment (
  id int(10) NOT NULL auto_increment,
  pid int(10) NOT NULL default '0',
  uid int(10) NOT NULL default '0',
  date date NOT NULL default '0000-00-00',
  comment text NOT NULL,
  PRIMARY KEY  (id)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `comment_project`
#

CREATE TABLE hour_comment_project (
  pid int(10) NOT NULL default '0',
  aid int(10) NOT NULL default '0',
  date date NOT NULL default '0000-00-00',
  comment text NOT NULL
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `hour_comment_user`
#

CREATE TABLE hour_comment_user (
  uid int(10) NOT NULL default '0',
  aid int(10) NOT NULL default '0',
  date date NOT NULL default '0000-00-00',
  comment text NOT NULL
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `middle_project`
#

CREATE TABLE hour_middle_project (
  mid int(10) NOT NULL default '0',
  pid int(10) NOT NULL default '0'
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `middle_user`
#

CREATE TABLE hour_middle_user (
  mid int(10) NOT NULL default '0',
  uid int(10) NOT NULL default '0'
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `name`
#

CREATE TABLE hour_name (
  uid int(20) NOT NULL default '0',
  name tinytext NOT NULL,
  UNIQUE KEY uid (uid)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `paid`
#

CREATE TABLE hour_paid (
  uid int(10) NOT NULL default '0',
  date date NOT NULL default '0000-00-00',
  hour float(3,2) NOT NULL default '0.00',
  settle float(3,2) NOT NULL default '0.00'
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `planned`
#

CREATE TABLE hour_planned (
  uid int(10) NOT NULL default '0',
  pid int(10) NOT NULL default '0',
  date date NOT NULL default '0000-00-00',
  hour float(3,2) NOT NULL default '0.00'
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `project`
#

CREATE TABLE hour_project (
  id int(10) NOT NULL auto_increment,
  name tinytext NOT NULL,
  rank int(1) NOT NULL default '1',
  uid tinytext NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY id (id)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `users`
#

CREATE TABLE hour_users (
  id int(10) NOT NULL auto_increment,
  user varchar(20) NOT NULL default '',
  pass varchar(35) NOT NULL default '',
  salt char(2) NOT NULL default '',
  name tinytext NOT NULL,
  email tinytext NOT NULL,
  tel tinytext NOT NULL,
  rank tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (id),
  UNIQUE KEY id (id,user)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `work`
#

CREATE TABLE hour_work (
  pid int(10) NOT NULL default '0',
  uid int(10) NOT NULL default '0',
  date date NOT NULL default '0000-00-00',
  hour float(3,2) NOT NULL default '0.00'
) TYPE=MyISAM;

ALTER TABLE narocanje ADD hour INT DEFAULT '0' NOT NULL ;

ALTER TABLE hour_users DROP pass, DROP salt, DROP email, DROP tel ;

INSERT INTO misc ( what , value ) VALUES ('TimeLink', '0');

UPDATE misc SET value='05.07.10' WHERE what='version';
UPDATE misc SET value='05.07.13' WHERE what='version';
UPDATE misc SET value='05.07.14' WHERE what='version';

ALTER TABLE struktura_baze ADD master_alt2 VARCHAR( 50 ) NOT NULL AFTER master_alt;

INSERT INTO misc ( what , value ) VALUES ('CreateForum', '0');
INSERT INTO misc ( what , value ) VALUES ('CreateNavigation', '-1');

CREATE TABLE struktura_dodatninapisi (id INT NOT NULL , newid INT NOT NULL , type INT NOT NULL);
ALTER TABLE struktura_dodatninapisi ADD name VARCHAR( 250 ) NOT NULL ;
ALTER TABLE struktura_dodatninapisi ADD sel INT NOT NULL AFTER type ;


UPDATE misc SET value='05.07.25' WHERE what='version';


alter table neww add column vert tinyint(3) not null default -1;
alter table neww add column hor tinyint(3) not null default -1;

ALTER TABLE struktura_baze ADD num3 TINYINT( 1 ) NOT NULL AFTER nu2 ,
ADD num31 TINYINT( 1 ) NOT NULL AFTER num3 ,
ADD anum31 TINYINT( 4 ) NOT NULL AFTER num31 ,
ADD nu3 VARCHAR( 50 ) NOT NULL AFTER anum31 ,
ADD num4 TINYINT( 1 ) NOT NULL AFTER nu3 ,
ADD num41 TINYINT( 1 ) NOT NULL AFTER num4 ,
ADD anum41 TINYINT( 4 ) NOT NULL AFTER num41 ,
ADD nu4 VARCHAR( 50 ) NOT NULL AFTER anum41 ,
ADD num5 TINYINT( 1 ) NOT NULL AFTER nu4 ,
ADD num51 TINYINT( 1 ) NOT NULL AFTER num5 ,
ADD anum51 TINYINT( 4 ) NOT NULL AFTER num51 ,
ADD nu5 VARCHAR( 50 ) NOT NULL AFTER anum51 ,
ADD num6 TINYINT( 1 ) NOT NULL AFTER nu5 ,
ADD num61 TINYINT( 1 ) NOT NULL AFTER num6 ,
ADD anum61 TINYINT( 4 ) NOT NULL AFTER num61 ,
ADD nu6 VARCHAR( 50 ) NOT NULL AFTER anum61 ,
ADD num7 TINYINT( 1 ) NOT NULL AFTER nu6 ,
ADD num71 TINYINT( 1 ) NOT NULL AFTER num7 ,
ADD anum71 TINYINT( 4 ) NOT NULL AFTER num71 ,
ADD nu7 VARCHAR( 50 ) NOT NULL AFTER anum71 ,
ADD num8 TINYINT( 1 ) NOT NULL AFTER nu7 ,
ADD num81 TINYINT( 1 ) NOT NULL AFTER num8 ,
ADD anum81 TINYINT( 4 ) NOT NULL AFTER num81 ,
ADD nu8 VARCHAR( 50 ) NOT NULL AFTER anum81 ,
ADD num9 TINYINT( 1 ) NOT NULL AFTER nu8 ,
ADD num91 TINYINT( 1 ) NOT NULL AFTER num9 ,
ADD anum91 TINYINT( 4 ) NOT NULL AFTER num91 ,
ADD nu9 VARCHAR( 50 ) NOT NULL AFTER anum91 ;

ALTER TABLE data_baze ADD num3 VARCHAR( 255 ) NOT NULL AFTER num2 ,
ADD num4 VARCHAR( 255 ) NOT NULL AFTER num3 ,
ADD num5 VARCHAR( 255 ) NOT NULL AFTER num4 ,
ADD num6 VARCHAR( 255 ) NOT NULL AFTER num5 ,
ADD num7 VARCHAR( 255 ) NOT NULL AFTER num6 ,
ADD num8 VARCHAR( 255 ) NOT NULL AFTER num7 ,
ADD num9 VARCHAR( 255 ) NOT NULL AFTER num8 ;

ALTER TABLE struktura_grid ADD pnum3 TINYINT NOT NULL AFTER rnum2 ,
ADD fnum3 TINYINT NOT NULL AFTER pnum3 ,
ADD rnum3 TINYINT NOT NULL AFTER fnum3 ,
ADD pnum4 TINYINT NOT NULL AFTER rnum3 ,
ADD fnum4 TINYINT NOT NULL AFTER pnum4 ,
ADD rnum4 TINYINT NOT NULL AFTER fnum4 ,
ADD pnum5 TINYINT NOT NULL AFTER rnum4 ,
ADD fnum5 TINYINT NOT NULL AFTER pnum5 ,
ADD rnum5 TINYINT NOT NULL AFTER fnum5 ,
ADD pnum6 TINYINT NOT NULL AFTER rnum5 ,
ADD fnum6 TINYINT NOT NULL AFTER pnum6 ,
ADD rnum6 TINYINT NOT NULL AFTER fnum6 ,
ADD pnum7 TINYINT NOT NULL AFTER rnum6 ,
ADD fnum7 TINYINT NOT NULL AFTER pnum7 ,
ADD rnum7 TINYINT NOT NULL AFTER fnum7 ,
ADD pnum8 TINYINT NOT NULL AFTER rnum7 ,
ADD fnum8 TINYINT NOT NULL AFTER pnum8 ,
ADD rnum8 TINYINT NOT NULL AFTER fnum8 ,
ADD pnum9 TINYINT NOT NULL AFTER rnum8 ,
ADD fnum9 TINYINT NOT NULL AFTER pnum9 ,
ADD rnum9 TINYINT NOT NULL AFTER fnum9 ;

ALTER TABLE baza_help ADD hnu3 TINYTEXT NOT NULL AFTER hnu2 ,
ADD hnu4 TINYTEXT NOT NULL AFTER hnu3 ,
ADD hnu5 TINYTEXT NOT NULL AFTER hnu4 ,
ADD hnu6 TINYTEXT NOT NULL AFTER hnu5 ,
ADD hnu7 TINYTEXT NOT NULL AFTER hnu6 ,
ADD hnu8 TINYTEXT NOT NULL AFTER hnu7 ,
ADD hnu9 TINYTEXT NOT NULL AFTER hnu8 ;

ALTER TABLE baza_insert_clani ADD cnum3 TINYINT DEFAULT '1' NOT NULL AFTER cnum2 ,
ADD cnum4 TINYINT DEFAULT '1' NOT NULL AFTER cnum3 ,
ADD cnum5 TINYINT DEFAULT '1' NOT NULL AFTER cnum4 ,
ADD cnum6 TINYINT DEFAULT '1' NOT NULL AFTER cnum5 ,
ADD cnum7 TINYINT DEFAULT '1' NOT NULL AFTER cnum6 ,
ADD cnum8 TINYINT DEFAULT '1' NOT NULL AFTER cnum7 ,
ADD cnum9 TINYINT DEFAULT '1' NOT NULL AFTER cnum8 ;

ALTER TABLE data_baze ADD flink varchar(255) not null default "" AFTER naslov2;
ALTER TABLE data_baze ADD vpisal varchar(255) not null default "Neznani";
ALTER TABLE data_baze ADD datumvpisa varchar(13) not null default "NP";
ALTER TABLE data_baze ADD popravil varchar(255) not null default "Neznani";
ALTER TABLE data_baze ADD datumpopravka varchar(13) not null default "NP";
ALTER TABLE menu ADD popravil varchar(255) not null default "Neznani";
ALTER TABLE menu ADD datumpopravka varchar(13) not null default "NP";

CREATE TABLE bloggers (
  id integer NOT NULL AUTO_INCREMENT,
  nid integer not null default 0,
  bid integer not null default 0,
  timezone varchar(5) not null default "13",
  timeformat varchar(3) not null default "0",
  dateformat varchar(3) not null default "0",
  city varchar(200) not null default "",
  country varchar(200) not null default "",
  birthday date not null default "0000-00-00",
  homepage varchar(255) not null default "",
  msn varchar(200) not null default "",
  yahoo varchar(200) not null default "",
  aim varchar(200) not null default "",
  icq varchar(200) not null default "",
  work mediumtext not null default "",
  interests mediumtext not null default "",
  description mediumtext not null default "",
  movies mediumtext not null default "",
  music mediumtext not null default "",
  books mediumtext not null default "",
  photo varchar(255) not null default "",
  PRIMARY KEY (id)
);

CREATE TABLE blogs (
  id integer NOT NULL auto_increment,
  uid integer not null default 0,
  name varchar(255) not null default "",
  description mediumtext not null default "",
  public integer not null default "",
  listed integer not null default "",
  created date not null default "2001-01-01",
  PRIMARY KEY (id)
);

CREATE TABLE blog_entries (
  id integer not null auto_increment,
  uid integer not null default 0,
  bid integer not null default 0,
  name varchar(255) not null default "",
  content mediumtext not null default "",
  datum datetime not null default "2001-01-01 01:01:00",
  comments integer not null default 1,
  public integer not null default 1,
  PRIMARY KEY (id)
);

UPDATE misc SET value='05.08.19' WHERE what='version';

ALTER TABLE narocanje ADD COLUMN ehistory mediumtext not null default "";

UPDATE misc SET value='05.08.22' WHERE what='version';

ALTER TABLE menu add showwho tinyint(1) not null default 0;
ALTER TABLE data_baze add showwho tinyint(1) not null default 0;

UPDATE misc SET value='05.08.25' WHERE what='version';

INSERT INTO misc (what, value) VALUES ('NoNUExplain', 'This web page is closed-type so you can not register without invitation');

#Grde besede....

INSERT INTO misc (what, value) VALUES ('BadWords', 'fuck');
INSERT INTO misc (what, value) VALUES ('BadWords', 'kurac');
INSERT INTO misc (what, value) VALUES ('BadWords', 'pizda');
INSERT INTO misc (what, value) VALUES ('BadWords', 'pussy');

#in tako dalje- bo ze kdo drug vnesel :)


INSERT INTO misc (what, value) VALUES ('BadWordsReplacement', '***');

UPDATE misc SET value='05.08.28' WHERE what='version';

ALTER TABLE forum ADD uid INT NOT NULL ;

INSERT INTO misc (what, value) VALUES ('DefaultRootSearch', '0');

UPDATE misc SET value='05.09.06' WHERE what='version';

UPDATE misc SET value='05.09.19' WHERE what='version';

ALTER TABLE menu ADD hour_uid INT DEFAULT 0 NOT NULL ;

UPDATE misc SET value='05.09.26' WHERE what='version';

ALTER TABLE menu ADD hour_uid INT DEFAULT 0 NOT NULL ;

ALTER TABLE data_baze CHANGE avtor avtor TEXT NOT NULL ;

ALTER TABLE struktura_baze CHANGE admin admin0 INT( 11 ) DEFAULT '3' NOT NULL ;
ALTER TABLE struktura_baze ADD admin1 INT DEFAULT '3' NOT NULL AFTER admin0 ;

UPDATE misc SET value='05.09.29' WHERE what='version';

ALTER TABLE struktura_baze ADD pagination INT DEFAULT '0' NOT NULL AFTER listlimit;

ALTER TABLE data_kategorij DROP val;

ALTER TABLE struktura_baze ADD favorites INT DEFAULT '0' NOT NULL AFTER pagination;

CREATE TABLE favorites (
uid INT NOT NULL,
bid INT NOT NULL
);

CREATE TABLE sidelist (id integer not null auto_increment, sidelist mediumtext, time integer, ip varchar(255), key id(id));

UPDATE misc SET value='05.12.03' WHERE what='version';
ALTER TABLE sidelist ADD COLUMN bid integer not null;

# New bullets

UPDATE new SET date=77 where date=3;
UPDATE new SET date=98 where date=5;
UPDATE new SET date=99 where date=6;
UPDATE new SET date=97 where date=7;
UPDATE new SET date=55 where date=8;
UPDATE new SET date=66 where date=9;
UPDATE new SET date=33 where date=10;

UPDATE new SET date=10 where date>10 AND date<15;

UPDATE new SET date=7 WHERE date=77;
UPDATE new SET date=11 WHERE date=98;
UPDATE new SET date=9 WHERE date=99;
UPDATE new SET date=10 WHERE date=97;
UPDATE new SET date=5 WHERE date=55;
UPDATE new SET date=6 WHERE date=66;
UPDATE new SET date=3 WHERE date=33;

UPDATE misc SET value='05.12.18' WHERE what='version';

ALTER TABLE hour_paid CHANGE hour hour FLOAT( 6, 2 ) DEFAULT '0.00' NOT NULL;
ALTER TABLE hour_paid CHANGE settle settle FLOAT( 6, 2 ) DEFAULT '0.00' NOT NULL;
ALTER TABLE hour_planned CHANGE hour hour FLOAT( 6, 2 ) DEFAULT '0.00' NOT NULL;
ALTER TABLE hour_work CHANGE hour hour FLOAT( 6, 2 ) DEFAULT '0.00' NOT NULL;

ALTER TABLE struktura_avtor ADD opomba2 MEDIUMTEXT NOT NULL AFTER opomba ,
ADD opomba3 MEDIUMTEXT NOT NULL AFTER opomba2 ;

ALTER TABLE struktura_companies ADD opomba2 MEDIUMTEXT NOT NULL AFTER opomba ,
ADD opomba3 MEDIUMTEXT NOT NULL AFTER opomba2 ;

ALTER TABLE struktura_country ADD opomba2 MEDIUMTEXT NOT NULL AFTER opomba ,
ADD opomba3 MEDIUMTEXT NOT NULL AFTER opomba2 ;

ALTER TABLE struktura_db ADD opomba2 MEDIUMTEXT NOT NULL AFTER opomba ,
ADD opomba3 MEDIUMTEXT NOT NULL AFTER opomba2 ;

ALTER TABLE struktura_vir ADD opomba2 MEDIUMTEXT NOT NULL AFTER opomba ,
ADD opomba3 MEDIUMTEXT NOT NULL AFTER opomba2 ;

ALTER TABLE selections ADD ime VARCHAR( 200 ) NOT NULL ,
ADD opomba VARCHAR( 200 ) NOT NULL ,
ADD opomba2 VARCHAR( 200 ) NOT NULL ,
ADD opomba3 VARCHAR( 200 ) NOT NULL ,
ADD adminopomba VARCHAR( 200 ) NOT NULL ;

ALTER TABLE selections ADD access INT DEFAULT '3' NOT NULL ;

CREATE TABLE data_companies_avtor (
  companies int(11) NOT NULL default '0',
  aid int(11) NOT NULL default '0'
);

CREATE TABLE data_country_avtor (
  country int(11) NOT NULL default '0',
  aid int(11) NOT NULL default '0'
);

CREATE TABLE data_db_avtor (
  db int(11) NOT NULL default '0',
  aid int(11) NOT NULL default '0'
);

CREATE TABLE data_vir_avtor (
  vir int(11) NOT NULL default '0',
  aid int(11) NOT NULL default '0'
);

ALTER TABLE selections ADD avtoralt VARCHAR( 200 ) NOT NULL AFTER adminopomba ;

UPDATE misc SET value='05.12.28' WHERE what='version';

UPDATE misc SET value='06.01.01' WHERE what='version';

INSERT INTO misc values ('ProfileMenus', '0');

UPDATE misc SET value='06.01.14' WHERE what='version';
UPDATE misc SET value='06.01.19' WHERE what='version';
ALTER TABLE struktura_baze add column sum tinyint(1) not null default 0;
UPDATE misc SET value='06.01.23' WHERE what='version';

INSERT INTO misc values ('NewsColumns', '6040');
ALTER TABLE neww ADD column rows tinyint(2) not null default -1;
UPDATE misc SET value='06.02.03' WHERE what='version';

INSERT INTO misc values ('CookieLife', '3600');

ALTER TABLE novice ADD COLUMN count integer not null default 0;
ALTER TABLE faq ADD COLUMN count integer not null default 0;
ALTER TABLE rubrika1 ADD COLUMN count integer not null default 0;
ALTER TABLE rubrika2 ADD COLUMN count integer not null default 0;
ALTER TABLE rubrika3 ADD COLUMN count integer not null default 0;
ALTER TABLE rubrika4 ADD COLUMN count integer not null default 0;
ALTER TABLE aktualno ADD COLUMN count integer not null default 0;
ALTER TABLE m2w ADD COLUMN count integer not null default 0;
ALTER TABLE vodic ADD COLUMN count integer not null default 0;

INSERT INTO misc values ('ShowCountMenu', '1');
INSERT INTO misc values ('ShowCountRub', '1');

UPDATE misc SET value='06.02.04' WHERE what='version';
UPDATE misc SET value='06.02.09' WHERE what='version';

# BERI
# BERI
# BERI
# V TABELI NEW SE PO NOVEM PRI BAZAH UPORABLJA POLJE TEASER, IN SICER:
#
# 0- NI GRAFKA,
# 1- MALI STOLPCASTI,
# 2- MALI CRSTASTI
#
UPDATE misc SET value='06.02.14' WHERE what='version';
UPDATE misc SET value='06.02.15' WHERE what='version';
UPDATE misc SET value='06.02.20' WHERE what='version';

ALTER TABLE struktura_baze ADD MS_display TINYINT DEFAULT '1' NOT NULL ;

ALTER TABLE struktura_dodatninapisi ADD display TINYINT DEFAULT '1' NOT NULL ;

UPDATE misc SET value='06.02.22' WHERE what='version';

ALTER TABLE struktura_baze ADD COLUMN st_master integer not null default 0;

UPDATE misc SET value='06.02.27' WHERE what='version';
UPDATE misc SET value='06.03.03' WHERE what='version';

ALTER TABLE new CHANGE vert vert integer not null default 0;
ALTER TABLE new CHANGE hor hor integer not null default 0;
ALTER TABLE neww CHANGE vert vert integer not null default -1;
ALTER TABLE neww CHANGE hor hor integer not null default -1;

UPDATE misc SET value='06.03.04' WHERE what='version';


CREATE TABLE chart (
id int(11) NOT NULL default 0 ,   # RES JE; NAMENOMA NI AUTO_INCREMENT!!!!!
type tinyint(2) not null default 0,
interpolate tinyint(2) not null default 0,
dots tinyint(2) not null default 1,
scale tinyint(4) not null default 0
);

UPDATE misc SET value='06.03.06' WHERE what='version';

CREATE TABLE formula (
rid int(11) NOT NULL default 0 ,   # RES JE; NAMENOMA NI AUTO_INCREMENT!!!!!
fid int(11) NOT NULL default 0 ,
formula mediumtext not null default ""
);

UPDATE misc SET value='06.03.14' WHERE what='version';

CREATE TABLE events (
  id integer not null auto_increment,
  uid integer not null,
  uid2 integer not null,
  begin datetime,
  end datetime,
  repeat tinyint(2),
  place varchar(255),
  description varchar(255),
  primary key id (id)
);

DELETE FROM new WHERE id=10;

UPDATE misc SET value='06.03.24' WHERE what='version';
UPDATE misc SET value='06.03.27' WHERE what='version';
UPDATE misc SET value='06.03.28' WHERE what='version';

CREATE TABLE base_formula (
bid int(11) NOT NULL default 0 ,   # RES JE; NAMENOMA NI AUTO_INCREMENT!!!!!
fid int(11) NOT NULL default 0 ,
formula mediumtext not null default ""
);

UPDATE misc SET value='06.03.29' WHERE what='version';
UPDATE misc SET value='06.04.03' WHERE what='version';

alter table new change name name varchar(255) not null default "";

UPDATE misc SET value='06.04.10' WHERE what='version';

INSERT INTO misc (what, value) VALUES ('ForumLongAuthor', '1');

UPDATE misc SET value='06.04.12' WHERE what='version';

CREATE TABLE super_r(
bid int(11) NOT NULL default 0 ,   # RES JE; NAMENOMA NI AUTO_INCREMENT!!!!!
f1f varchar(20) NOT NULL default "",
f2f varchar(20) NOT NULL default "",
f3f varchar(20) NOT NULL default "",
f4f varchar(20) NOT NULL default "",
f5f varchar(20) NOT NULL default "",
f6f varchar(20) NOT NULL default "",
f7f varchar(20) NOT NULL default "",
f8f varchar(20) NOT NULL default "",
f9f varchar(20) NOT NULL default "",
f1s varchar(6) not null default "",
f2s varchar(6) not null default "",
f3s varchar(6) not null default "",
f4s varchar(6) not null default "",
f5s varchar(6) not null default "",
f6s varchar(6) not null default "",
f7s varchar(6) not null default "",
f8s varchar(6) not null default "",
f9s varchar(6) not null default "",
children mediumtext not null default ""
);

UPDATE misc SET value='06.04.13' WHERE what='version';
UPDATE misc SET value='06.04.20' WHERE what='version';

ALTER TABLE chart ADD COLUMN fred tinyint(2) not null default 0;

UPDATE misc SET value='06.04.30' WHERE what='version';
UPDATE misc SET value='06.05.08' WHERE what='version';

ALTER TABLE struktura_avtor ADD COLUMN link varchar(255) not null default "" after avtor;
ALTER TABLE struktura_vir ADD COLUMN link varchar(255) not null default "" after vir;
ALTER TABLE struktura_db ADD COLUMN link varchar(255) not null default "" after db;
ALTER TABLE struktura_country ADD COLUMN link varchar(255) not null default "" after country;
ALTER TABLE struktura_companies ADD COLUMN link varchar(255) not null default "" after company;
UPDATE misc SET value='06.05.11' WHERE what='version';

ALTER TABLE narocanje ADD COLUMN link varchar(255) not null default "";
INSERT INTO misc VALUES ('ProfileLink', '0');
UPDATE misc SET value='06.05.15' WHERE what='version';
UPDATE misc SET value='06.05.19' WHERE what='version';
UPDATE misc SET value='06.05.22' WHERE what='version';

ALTER TABLE chart ADD column percent tinyint(1) not null default 0;
UPDATE misc SET value='06.05.23' WHERE what='version';

ALTER TABLE struktura_baze ADD pag_mas_top TINYINT DEFAULT '1' NOT NULL ,
ADD pag_mas_bot TINYINT DEFAULT '1' NOT NULL ,
ADD pag_ser_top TINYINT DEFAULT '1' NOT NULL ,
ADD pag_ser_bot TINYINT DEFAULT '1' NOT NULL ;

ALTER TABLE struktura_baze ADD MS_current_ontop TINYINT DEFAULT '0' NOT NULL ;

INSERT INTO misc ( what , value ) VALUES ('ForumTopTxt', '1');

ALTER TABLE struktura_baze ADD show_naziv TINYINT DEFAULT '1' NOT NULL ;
ALTER TABLE neww ADD COLUMN chart tinyint(1) not null default -1;

UPDATE misc SET value='06.05.30' WHERE what='version';
UPDATE misc SET value='06.06.01' WHERE what='version';

alter table new add column tabs tinyint(1) not null default 0;

UPDATE misc SET value='06.06.06' WHERE what='version';

ALTER TABLE struktura_baze ADD listed_servants_newwin TINYINT DEFAULT '1' NOT NULL ;

INSERT INTO misc ( what , value ) VALUES ( 'forum_display_column', '1');

INSERT INTO misc ( what , value ) VALUES ( 'forum_display_settings', '1');
CREATE table correlations (id integer auto_increment, name varchar(255), bases mediumtext, field varchar(10), results mediumtext, start varchar(30), end varchar(30), n integer, formula tinyint(2), key id(id));

UPDATE misc SET value='06.06.13' WHERE what='version';

ALTER TABLE menu ADD left_menu INT DEFAULT '0' NOT NULL ;
UPDATE misc SET value='06.06.30' WHERE what='version';
UPDATE misc SET value='06.07.01' WHERE what='version';
ALTER TABLE struktura_baze CHANGE naziv naziv varchar(255) not null default "";
UPDATE misc SET value='06.07.07' WHERE what='version';

INSERT INTO misc (what, value) VALUES ('AdminContent', '0');
INSERT INTO misc (what, value) VALUES ('RegPassDefault', '0');

ALTER TABLE struktura_baze ADD alt_serv_title VARCHAR( 250 ) NOT NULL, ADD alt_serv_author VARCHAR( 250 ) NOT NULL ;

alter table content add column MetaTitle varchar(255) not null default "";
alter table content add column MetaDesc mediumtext not null default "";
alter table content add column MetaKeywords mediumtext not null default "";

CREATE TABLE manager_baza (
baza int( 11 ) NOT NULL default '0',
manager int( 11 ) NOT NULL default '0'
);

CREATE TABLE clan_baza(
baza int( 11 ) NOT NULL default '0',
clan int( 11 ) NOT NULL default '0'
);

UPDATE misc SET value='06.08.11' WHERE what='version';

INSERT INTO misc (what, value) VALUES ('gridopt', '1');

CREATE TABLE forum_group (tid INT NOT NULL, uid INT NOT NULL);
ALTER TABLE forum_group ADD INDEX index_tid (tid);

UPDATE misc SET value='06.08.16' WHERE what='version';

ALTER TABLE selections CHANGE access access_opomba3 INT( 11 ) NOT NULL DEFAULT '3';

ALTER TABLE selections ADD access_ime INT NOT NULL DEFAULT '3' AFTER avtoralt ,
ADD access_opomba INT NOT NULL DEFAULT '3' AFTER access_ime ,
ADD access_opomba2 INT NOT NULL DEFAULT '3' AFTER access_opomba ;

ALTER TABLE selections DROP access_ime ;
UPDATE misc SET value='06.08.18' WHERE what='version';

ALTER TABLE struktura_baze ADD show_middle_servants_onclick TINYINT NOT NULL DEFAULT '0',
ADD show_middle_masters_onclick TINYINT NOT NULL DEFAULT '1';

ALTER TABLE struktura_baze ADD column_width INT NOT NULL DEFAULT '70';

ALTER TABLE selections ADD list_width INT NOT NULL DEFAULT '30', ADD column_width INT NOT NULL DEFAULT '40';


ALTER TABLE struktura_baze ADD yearorder TINYINT NOT NULL DEFAULT '0';

ALTER TABLE new ADD COLUMN border varchar(255) not null default "normal";

UPDATE misc SET value='06.08.23' WHERE what='version';
UPDATE misc SET value='06.09.12' WHERE what='version';
UPDATE misc SET value='06.09.19' WHERE what='version';

ALTER TABLE struktura_dodatninapisi ADD num_records INT NOT NULL DEFAULT '0';

ALTER TABLE selections ADD author_num_records INT NOT NULL AFTER avtoralt ,
ADD author_display INT NOT NULL AFTER author_num_records ,
ADD sourcealt VARCHAR( 200 ) NOT NULL AFTER author_display ,
ADD source_num_records INT NOT NULL AFTER sourcealt ,
ADD source_display INT NOT NULL AFTER source_num_records ,
ADD dbalt VARCHAR( 200 ) NOT NULL AFTER source_display ,
ADD db_num_records INT NOT NULL AFTER dbalt ,
ADD db_display INT NOT NULL AFTER db_num_records ,
ADD countryalt VARCHAR( 200 ) NOT NULL AFTER db_display ,
ADD country_num_records INT NOT NULL AFTER countryalt ,
ADD country_display INT NOT NULL AFTER country_num_records ,
ADD companiesalt VARCHAR( 200 ) NOT NULL AFTER country_display ,
ADD companies_num_records INT NOT NULL AFTER companiesalt ,
ADD companies_display INT NOT NULL AFTER companies_num_records ;

UPDATE misc SET value='06.10.02' WHERE what='version';

ALTER TABLE data_baze ADD frontpage INT NOT NULL DEFAULT '1';

ALTER TABLE struktura_baze ADD cat_txt VARCHAR( 250 ) NOT NULL ,
ADD cat_link VARCHAR( 250 ) NOT NULL ;

ALTER TABLE struktura_baze ADD vir1_indent INT NOT NULL DEFAULT '0' AFTER vi1 ;
ALTER TABLE struktura_baze ADD vir2_indent INT NOT NULL DEFAULT '0' AFTER vi2 ;
ALTER TABLE struktura_baze ADD res_indent INT NOT NULL DEFAULT '0' AFTER re ;
ALTER TABLE struktura_baze ADD res2_indent INT NOT NULL DEFAULT '0' AFTER re2 ;
ALTER TABLE struktura_baze ADD abstract_indent INT NOT NULL DEFAULT '0' AFTER abs ;
ALTER TABLE struktura_baze ADD abstract2_indent INT NOT NULL DEFAULT '0' AFTER abs2 ;
ALTER TABLE struktura_baze ADD opis1_indent INT NOT NULL DEFAULT '0' AFTER opi1 ;
ALTER TABLE struktura_baze ADD opis2_indent INT NOT NULL DEFAULT '0' AFTER opi2 ;
ALTER TABLE struktura_baze ADD published_indent INT NOT NULL DEFAULT '0' AFTER pub ;
ALTER TABLE struktura_baze ADD details_indent INT NOT NULL DEFAULT '0' AFTER det ;


INSERT INTO misc ( what , value ) VALUES ('BreadCrumbsNoFirst', '0');
INSERT INTO misc ( what , value ) VALUES ('BreadCrumbsNoLast', '0');
INSERT INTO misc ( what , value ) VALUES ('BreadCrumbsNoClick', '0');

UPDATE misc SET value='06.10.11' WHERE what='version';
UPDATE misc SET value='06.10.12' WHERE what='version';

ALTER TABLE novice CHANGE kategorije kategorije mediumtext;
ALTER TABLE aktualno CHANGE kategorije kategorije mediumtext;
ALTER TABLE faq CHANGE kategorije kategorije mediumtext;
ALTER TABLE vodic CHANGE kategorije kategorije mediumtext;
ALTER TABLE rubrika1 CHANGE kategorije kategorije mediumtext;
ALTER TABLE rubrika2 CHANGE kategorije kategorije mediumtext;
ALTER TABLE rubrika3 CHANGE kategorije kategorije mediumtext;
ALTER TABLE rubrika4 CHANGE kategorije kategorije mediumtext;

ALTER TABLE forum ADD ocena INT NOT NULL DEFAULT '0';
ALTER TABLE post ADD ocena INT NOT NULL DEFAULT '0';

ALTER TABLE forum ADD dispauth INT NOT NULL DEFAULT '0';

UPDATE misc SET value='06.10.15' WHERE what='version';

ALTER TABLE menu ADD COLUMN meta tinyint(1) not null default 0;

INSERT INTO misc ( what , value ) VALUES ('ForumHourDisplay', '0');
INSERT INTO misc ( what , value ) VALUES ('ForumMarkDisplay', '0');

ALTER TABLE neww ADD ms_active TINYINT NOT NULL DEFAULT '0' AFTER abc ;

ALTER TABLE forum CHANGE dispauth lockedauth INT( 11 ) NOT NULL DEFAULT '0';

INSERT INTO misc ( what , value ) VALUES ('ForumEditPost', '1');
INSERT INTO misc (what, value) VALUES ('MembersFastEdit', '0');

UPDATE misc SET value='06.10.23' WHERE what='version';
UPDATE misc SET value='06.10.26' WHERE what='version';

ALTER TABLE struktura_baze ADD alt_ms_all VARCHAR( 100 ) NOT NULL ;

UPDATE misc SET value='06.10.27' WHERE what='version';

ALTER TABLE struktura_avtor ADD admin INT NOT NULL DEFAULT '3' AFTER id ;
ALTER TABLE struktura_companies ADD admin INT NOT NULL DEFAULT '3' AFTER id ;
ALTER TABLE struktura_country ADD admin INT NOT NULL DEFAULT '3' AFTER id ;
ALTER TABLE struktura_db ADD admin INT NOT NULL DEFAULT '3' AFTER id ;
ALTER TABLE struktura_vir ADD admin INT NOT NULL DEFAULT '3' AFTER id ;



CREATE TABLE manager_avtor (
  avtor int(11) NOT NULL default '0',
  manager int(11) NOT NULL default '0'
);
CREATE TABLE clan_avtor (
  avtor int(11) NOT NULL default '0',
  clan int(11) NOT NULL default '0'
);

CREATE TABLE manager_companies (
  companies int(11) NOT NULL default '0',
  manager int(11) NOT NULL default '0'
);
CREATE TABLE clan_companies (
  companies int(11) NOT NULL default '0',
  clan int(11) NOT NULL default '0'
);

CREATE TABLE manager_country (
  country int(11) NOT NULL default '0',
  manager int(11) NOT NULL default '0'
);
CREATE TABLE clan_country (
  country int(11) NOT NULL default '0',
  clan int(11) NOT NULL default '0'
);

CREATE TABLE manager_db (
  db int(11) NOT NULL default '0',
  manager int(11) NOT NULL default '0'
);
CREATE TABLE clan_db (
  db int(11) NOT NULL default '0',
  clan int(11) NOT NULL default '0'
);

CREATE TABLE manager_vir (
  vir int(11) NOT NULL default '0',
  manager int(11) NOT NULL default '0'
);
CREATE TABLE clan_vir (
  vir int(11) NOT NULL default '0',
  clan int(11) NOT NULL default '0'
);

UPDATE misc SET value='06.11.01' WHERE what='version';
UPDATE misc SET value='06.11.06' WHERE what='version';
UPDATE misc SET value='06.11.08' WHERE what='version';
UPDATE misc SET value='06.11.15' WHERE what='version';


CREATE TABLE srv_anketa (
  id int(11) NOT NULL auto_increment,
  baza_id int(11) default NULL,
  naslov varchar(250) character set utf8 collate utf8_bin NOT NULL default '',
  active tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (id)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE srv_data_text (
  spr_id int(11) NOT NULL default '0',
  `text` text character set utf8 collate utf8_bin NOT NULL,
  usr_id int(11) NOT NULL default '0',
  PRIMARY KEY  (spr_id)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE srv_data_vrednost (
  vre_id int(11) NOT NULL default '0',
  usr_id int(11) NOT NULL default '0',
  PRIMARY KEY  (vre_id)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE srv_grupa (
  id int(11) NOT NULL auto_increment,
  ank_id int(11) NOT NULL default '0',
  naslov varchar(250) character set utf8 collate utf8_bin NOT NULL default '',
  vrstni_red int(11) NOT NULL default '0',
  PRIMARY KEY  (id)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE srv_spremenljivka (
  id int(11) NOT NULL auto_increment,
  gru_id int(11) NOT NULL default '0',
  naslov varchar(250) character set utf8 collate utf8_bin NOT NULL default '',
  tip tinyint(4) NOT NULL default '0',
  vrstni_red int(11) NOT NULL default '0',
  PRIMARY KEY  (id)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE srv_vejitve (
  spr_id int(11) NOT NULL default '0',
  vre_id int(11) NOT NULL default '0',
  PRIMARY KEY  (spr_id,vre_id)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE srv_vrednost (
  id int(11) NOT NULL auto_increment,
  spr_id int(11) NOT NULL default '0',
  naslov varchar(250) character set utf8 collate utf8_bin NOT NULL default '',
  vrstni_red int(11) NOT NULL default '0',
  PRIMARY KEY  (id)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

UPDATE misc SET value='06.11.21' WHERE what='version';
UPDATE misc SET value='06.11.25' WHERE what='version';

CREATE TABLE r_active_cat (nt tinyint(1) not null default 0, dids varchar(255));
UPDATE misc SET value='06.11.30' WHERE what='version';

alter table r_active_cat change dids dids mediumtext;

UPDATE misc SET value='06.12.04' WHERE what='version';

insert into misc (what, value) values ('Financial', '0');
UPDATE misc SET value='06.12.10' WHERE what='version';

CREATE TABLE baza_res_manager (
  bid int(10) NOT NULL default '0',
  manager int(10) NOT NULL default '0'
);

CREATE TABLE baza_res_clan (
  bid int(10) NOT NULL default '0',
  clan int(10) NOT NULL default '0'
);

CREATE TABLE baza_res2_manager (
  bid int(10) NOT NULL default '0',
  manager int(10) NOT NULL default '0'
);

CREATE TABLE baza_res2_clan (
  bid int(10) NOT NULL default '0',
  clan int(10) NOT NULL default '0'
);

ALTER TABLE hour_project ADD paid TINYINT NOT NULL DEFAULT '1';
ALTER TABLE hour_project ADD active TINYINT NOT NULL DEFAULT '1';

INSERT INTO hour_project (id, name, rank, uid, paid, active) VALUES ('-1', 'Bolniska', '1', '', '1', '1');
INSERT INTO hour_project (id, name, rank, uid, paid, active) VALUES ('-2', 'Dopust', '1', '', '1', '1');
INSERT INTO hour_project (id, name, rank, uid, paid, active) VALUES ('-3', 'Ostalo', '1', '', '1', '1');

ALTER TABLE hour_users ADD zaposlen TINYINT NOT NULL DEFAULT '0',
ADD datum DATE NOT NULL;

CREATE TABLE hour_user_dopust (
uid INT NOT NULL ,
dni INT NOT NULL
);

ALTER TABLE hour_user_dopust ADD year INT NOT NULL AFTER uid ;

CREATE TABLE hour_delovneure (
year INT NOT NULL ,
month INT NOT NULL ,
ure INT NOT NULL
);

ALTER TABLE hour_project DROP active;

ALTER TABLE hour_users ADD display TINYINT NOT NULL DEFAULT '1';


CREATE TABLE costs (id integer not null auto_increment, label varchar(255), person varchar(255), financing_project varchar(255), work_project varchar(255), datum varchar(255), details mediumtext, comments varchar(255), database_origin varchar(255), num_internal double, num double, hours double, neto_sit double, vat_sit double, bruto_sit double, bruto_eur double, cost_s_sit double, cost_s_eur double, year varchar(255), month varchar(255), cost_type varchar(255), WP varchar(255), key id(id));

UPDATE misc SET value='06.12.18' WHERE what='version';

ALTER TABLE srv_spremenljivka ADD random TINYINT NOT NULL DEFAULT '0';

UPDATE misc SET value='06.12.19' WHERE what='version';

CREATE TABLE srv_user (
id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
cookie CHAR( 32 ) NOT NULL ,
ip VARCHAR( 20 ) NOT NULL ,
time DATETIME NOT NULL
);

ALTER TABLE srv_data_text DROP PRIMARY KEY;
ALTER TABLE srv_data_vrednost DROP PRIMARY KEY;

ALTER TABLE srv_data_text ADD PRIMARY KEY (spr_id, usr_id);
ALTER TABLE srv_data_vrednost ADD PRIMARY KEY (vre_id, usr_id);
ALTER TABLE srv_user ADD UNIQUE srv_user_cookie (cookie);

ALTER TABLE data_kategorij ADD PRIMARY KEY ( did , kid , cid ) ;
ALTER TABLE data_avtor ADD PRIMARY KEY ( aid , did ) ;
ALTER TABLE data_companies ADD PRIMARY KEY ( coid , did ) ;
ALTER TABLE data_country ADD PRIMARY KEY ( cid , did ) ;
ALTER TABLE data_db ADD PRIMARY KEY ( dbid , did ) ;
ALTER TABLE data_vir ADD PRIMARY KEY ( vid , did ) ;

UPDATE misc SET value='07.01.04' WHERE what='version';

INSERT INTO hour_project ( id , name , rank , uid , paid ) VALUES ('100000', 'Drugo', '1', '', '1');

UPDATE misc SET value='07.01.16' WHERE what='version';

ALTER TABLE data_baze CHANGE virx1 virx1 TEXT NOT NULL ;
ALTER TABLE data_baze CHANGE db1 db1 TEXT NOT NULL ;
ALTER TABLE data_baze CHANGE companies1 companies1 TEXT NOT NULL ;
ALTER TABLE data_baze CHANGE country country TEXT NOT NULL ;

UPDATE misc SET value='07.01.28' WHERE what='version';

ALTER TABLE hour_users CHANGE display display TINYINT( 4 ) NOT NULL DEFAULT '0';
UPDATE hour_users SET display = '0';

UPDATE misc SET value='07.02.08' WHERE what='version';
UPDATE misc SET value='07.02.16' WHERE what='version';

ALTER TABLE hour_paid ADD comment VARCHAR( 250 ) NOT NULL DEFAULT ' ';

UPDATE misc SET value='07.02.28' WHERE what='version';
alter table costs add column servant tinyint(1) not null default 0;
UPDATE misc SET value='07.03.05' WHERE what='version';

ALTER TABLE srv_spremenljivka CHANGE naslov naslov TEXT CHARACTER SET utf8 COLLATE utf8_bin NOT NULL;

ALTER TABLE srv_anketa ADD introduction TEXT NOT NULL ,
ADD conclusion TEXT NOT NULL ;


ALTER TABLE narocanje add column remember tinyint(1) not null default 0;
UPDATE misc SET value='07.03.12' WHERE what='version';

INSERT INTO misc (what, value) VALUES ('SurveyDostop', '0');
UPDATE misc SET value='07.03.19' WHERE what='version';

ALTER TABLE hour_planned ADD comment VARCHAR( 250 ) NOT NULL ;
ALTER TABLE hour_paid ADD comment_admin VARCHAR( 250 ) NOT NULL ;

INSERT INTO misc (what, value) VALUES ('FinCurrency', 'SIT');
UPDATE misc SET value='07.03.22' WHERE what='version';

ALTER TABLE hour_name ADD supervisor VARCHAR( 200 ) NOT NULL ;

UPDATE misc SET value='07.03.30' WHERE what='version';

ALTER TABLE hour_project ADD meta INT NOT NULL ;
UPDATE misc SET value='07.03.31' WHERE what='version';

CREATE TABLE srv_data_grid (
vre_id INT NOT NULL ,
usr_id INT NOT NULL ,
vrednost TINYINT NOT NULL
);

ALTER TABLE srv_data_grid ADD PRIMARY KEY ( vre_id , usr_id ) ;

ALTER TABLE srv_spremenljivka ADD size TINYINT NOT NULL DEFAULT '5';

ALTER TABLE srv_spremenljivka ADD undecided TINYINT NOT NULL DEFAULT '0';

ALTER TABLE srv_data_grid CHANGE vrednost grd_id TINYINT( 4 ) NOT NULL ;

CREATE TABLE srv_grid (
id INT NOT NULL ,
spr_id INT NOT NULL ,
naslov VARCHAR( 250 ) NOT NULL
);

ALTER TABLE srv_grid ADD PRIMARY KEY (id, spr_id);

ALTER TABLE hour_project ADD visible TINYINT NOT NULL DEFAULT '0';

ALTER TABLE  post add column IP varchar(128) not null default "Neznan";

ALTER TABLE menu ADD COLUMN groups integer not null default 0;
ALTER TABLE data_baze ADD COLUMN groups integer not null default 0;

UPDATE misc SET value='07.04.23' WHERE what='version';

CREATE TABLE hour_working_days (
date DATE NOT NULL
);

ALTER TABLE hour_working_days ADD PRIMARY KEY ( date ) ;
ALTER TABLE hour_users ADD procent INT NOT NULL DEFAULT '100' AFTER zaposlen ;
ALTER TABLE hour_work ADD PRIMARY KEY ( pid , uid , date ) ;
ALTER TABLE hour_user_dopust ADD PRIMARY KEY ( uid ,year ) ;
ALTER TABLE hour_planned ADD PRIMARY KEY ( uid , pid , date ) ;
ALTER TABLE hour_paid ADD INDEX indx ( uid , date ) ;
ALTER TABLE hour_middle_user ADD PRIMARY KEY ( mid , uid ) ;
ALTER TABLE hour_middle_project ADD PRIMARY KEY ( mid , pid ) ;
ALTER TABLE hour_delovneure ADD PRIMARY KEY ( year , month ) ;
ALTER TABLE hour_active ADD PRIMARY KEY ( uid ) ;

ALTER TABLE hour_users ADD tarifa FLOAT( 6, 2 ) NOT NULL ;
ALTER TABLE hour_paid ADD tarifa FLOAT( 6, 2 ) NOT NULL ;

ALTER TABLE hour_paid ADD datetime DATETIME NOT NULL AFTER date ;

ALTER TABLE hour_work ADD datetime DATETIME NOT NULL ;

ALTER TABLE narocanje ADD COLUMN telefon varchar(50) not null default '';

UPDATE misc SET value='07.05.02' WHERE what='version';

CREATE TABLE hour_zaposlitev (
uid INT NOT NULL ,
date DATE NOT NULL ,
procent INT NOT NULL
);

ALTER TABLE hour_zaposlitev ADD PRIMARY KEY ( uid , date ) ;

INSERT INTO misc (what, value) VALUES ('ip_yes', '');
INSERT INTO misc (what, value) VALUES ('ip_no', '');
ALTER TABLE menu ADD COLUMN ip_limit longtext not null default "";

UPDATE misc SET value='07.05.14' WHERE what='version';

ALTER TABLE hour_users ADD datum_do DATE NOT NULL AFTER datum ;

create table sum (id integer auto_increment, field mediumtext, key id(id));

ALTER TABLE hour_users CHANGE display display TINYINT( 4 ) NOT NULL DEFAULT '4';
UPDATE hour_users SET display = '4';

ALTER TABLE sum ADD COLUMN timestamp varchar(20);

create table acc_avtor (uid integer not null, eid integer not null);
create table acc_vir (uid integer not null, eid integer not null);
create table acc_db (uid integer not null, eid integer not null);
create table acc_country (uid integer not null, eid integer not null);
create table acc_companies (uid integer not null, eid integer not null);


ALTER TABLE struktura_baze ADD column EFiltering tinyint(1) not null default 0;
UPDATE misc SET value='07.06.11' WHERE what='version';
UPDATE misc SET value='07.06.18' WHERE what='version';

ALTER TABLE hour_project CHANGE uid uid TEXT NOT NULL ;

ALTER TABLE struktura_baze ADD cat_txt1 VARCHAR( 250 ) NOT NULL AFTER cat_link ,
ADD cat_link1 VARCHAR( 250 ) NOT NULL AFTER cat_txt1 ,
ADD cat_txt2 VARCHAR( 250 ) NOT NULL AFTER cat_link1 ,
ADD cat_link2 VARCHAR( 250 ) NOT NULL AFTER cat_txt2 ;

INSERT INTO misc (what, value) VALUES ('TimeTables', '0');

UPDATE misc SET value='07.07.05' WHERE what='version';
CREATE TABLE finance_flags (did integer not null, flag tinyint(1) not null default -1);
UPDATE misc SET value='07.07.09' WHERE what='version';


CREATE TABLE hour_arhiv (
  uid int(11) NOT NULL default '0',
  date date NOT NULL default '0000-00-00',
  vneseno tinyint(4) NOT NULL default '0',
  usrid int(11) NOT NULL default '0',
  datetime datetime NOT NULL default '0000-00-00 00:00:00',
  ip varchar(15) NOT NULL default '',
  PRIMARY KEY  (uid,date,vneseno)
) ;

UPDATE misc SET value='07.08.07' WHERE what='version';

ALTER TABLE hour_users ADD aktiven TINYINT NOT NULL DEFAULT '1' AFTER rank;

ALTER TABLE data_baze CHANGE userdate userdate DATETIME NOT NULL DEFAULT '0000-00-00';
ALTER TABLE data_baze CHANGE userdate2 userdate2 DATETIME NOT NULL DEFAULT '0000-00-00';

ALTER TABLE struktura_baze ADD time TINYINT NOT NULL DEFAULT '0';

UPDATE misc SET value='07.09.28' WHERE what='version';

alter table struktura_avtor add column id_user integer not null default -1;
CREATE TABLE forum_track (uid integer, kdaj varchar(12), type tinyint(1), tid long, ip varchar(100));

UPDATE misc SET value='07.10.15' WHERE what='version';

ALTER TABLE post ADD naslovnica TINYINT NOT NULL DEFAULT '1' AFTER naslov ;

ALTER TABLE data_baze CHANGE dateedit dateedit DATETIME NOT NULL DEFAULT '0000-00-00';

ALTER TABLE data_baze CHANGE date date DATETIME NOT NULL DEFAULT '0000-00-00';

ALTER TABLE data_baze ADD INDEX userdate ( userdate ) ;

ALTER TABLE data_baze ADD INDEX userdate2 ( userdate2 ) ;

ALTER TABLE srv_anketa ADD text VARCHAR( 250 ) NOT NULL ,
ADD url VARCHAR( 250 ) NOT NULL ;

ALTER TABLE srv_anketa ADD insert_user VARCHAR( 50 ) NOT NULL ,
ADD insert_time DATETIME NOT NULL ,
ADD edit_user VARCHAR( 50 ) NOT NULL ,
ADD edit_time DATETIME NOT NULL ;

ALTER TABLE srv_spremenljivka ADD stat INT NOT NULL DEFAULT '0';

CREATE TABLE finance_stats (pid integer, project varchar(255), budget varchar(40), costs varchar(40), income varchar(40), AK varchar(40), budgetYEAR varchar(40), costsYEAR varchar(40), incomeYEAR varchar(40), AKYEAR varchar(40), factor varchar(40));

ALTER TABLE srv_anketa ADD cookie TINYINT NOT NULL DEFAULT '2';

UPDATE misc SET value='07.11.09' WHERE what='version';

ALTER TABLE srv_user CHANGE time time_insert DATETIME NOT NULL ;

ALTER TABLE srv_user ADD time_edit DATETIME NOT NULL ;

ALTER TABLE srv_spremenljivka ADD reminder TINYINT NOT NULL DEFAULT '0';

ALTER TABLE srv_anketa ADD dostop INT NOT NULL DEFAULT '3';

CREATE TABLE srv_dostop (
ank_id INT NOT NULL ,
uid INT NOT NULL ,
PRIMARY KEY ( ank_id , uid )
);

ALTER TABLE narocanje ADD COLUMN gsm varchar(20) after telefon;

UPDATE misc SET value='07.11.19' WHERE what='version';

ALTER TABLE data_baze ADD COLUMN vklop datetime not null default '0000-00-00 00:00:00';
ALTER TABLE data_baze ADD COLUMN izklop datetime not null default '2020-12-31 23:59:00';

UPDATE misc SET value='07.12.10' WHERE what='version';
UPDATE misc SET value='07.12.13' WHERE what='version';

INSERT INTO misc (what, value) VALUES ('SurveyCookie', '2');

ALTER TABLE srv_spremenljivka ADD variable VARCHAR( 15 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL AFTER naslov;

ALTER TABLE srv_vrednost ADD variable VARCHAR( 15 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL AFTER naslov;

UPDATE misc SET value='08.01.03' WHERE what='version';

ALTER TABLE hour_users ADD finance TINYINT NOT NULL DEFAULT '0';

CREATE TABLE hour_dogodek_project (
pid INT NOT NULL ,
bid INT NOT NULL
);


UPDATE misc SET value='08.01.14' WHERE what='version';

ALTER TABLE hour_users ADD status TINYINT NOT NULL ;

UPDATE misc SET value='08.01.24' WHERE what='version';

INSERT INTO misc (what, value) VALUES ('CalendarSubject', 'SFPAGENAME dogodek');
INSERT INTO misc (what, value) VALUES ('CalendarAlert', 'Pozdravljeni, <br><br>Iz spletnega mesta SFPAGENAME vas obvescamo o prihajajocih dogodkih<br><br>SFNEWS<br>Lep pozdrav.');

CREATE TABLE data_alert_avtor (aid int(11) NOT NULL default '0', did int(11) NOT NULL default '0');
CREATE TABLE data_alert_mail (mail VARCHAR(255), did int(11) NOT NULL default '0');
CREATE TABLE data_alert_when (kdaj tinyint(1) NOT NULL default '0', did int(11) NOT NULL default '0');

UPDATE misc SET value='08.01.28' WHERE what='version';

# o, ja :-)
# uporabi skripto utils/TransferUsers.php

CREATE TABLE users (
	id integer NOT NULL AUTO_INCREMENT,
	type TINYINT(1) NOT NULL DEFAULT 3,
	status tinyint(1) NOT NULL DEFAULT 1,
	infomail TINYINT(1) NOT NULL DEFAULT 0,
	approved TINYINT(1) NOT NULL DEFAULT 1,
	hour INTEGER NOT NULL DEFAULT 0,
	email VARCHAR(255) NOT NULL UNIQUE,
	name VARCHAR(50) NOT NULL DEFAULT 'Nepodpisani',
	surname VARCHAR(50) NOT NULL DEFAULT '',
	pass VARCHAR(255),
	came_from TINYINT(1) NOT NULL DEFAULT 0,
	phone1 VARCHAR(25) NOT NULL DEFAULT '',
	phone2 VARCHAR(25) NOT NULL DEFAULT '',
	when_reg DATE NOT NULL DEFAULT '2003-01-01',
	show_email TINYINT(1) NOT NULL DEFAULT 1,
	alert_cats MEDIUMTEXT NOT NULL,
	alert_freq tinyint(1) NOT NULL DEFAULT 0,
	user_groups LONG NOT NULL,
	ehistory MEDIUMTEXT NOT NULL,
	my_url VARCHAR(255) NOT NULL DEFAULT '',
	avatar VARCHAR(255) NOT NULL DEFAULT '',
	about_me1 mediumtext NOT NULL,
	about_me2 mediumtext NOT NULL,
	key id(id)
);

# narocanje in administratorji dropaj rocno potem ko zalaufas skripto za prenos.
# tabelo ali dve imamo obsolete v bazi...

DROP TABLE Testni_forum;
DROP TABLE Testni_forum_bodies;
DROP TABLE avtorji;
DROP TABLE blog_entries;
DROP TABLE bloggers;
DROP TABLE blogs;
DROP TABLE cakajoce;
DROP TABLE forum_auth;
DROP TABLE forum_groups;
DROP TABLE forums;
DROP TABLE forums_auth;
DROP TABLE forums_forum2group;
DROP TABLE forums_groups;
DROP TABLE forums_moderators;
DROP TABLE forums_user2group;

DROP TABLE ip;

DROP TABLE kategorije_novic;
DROP TABLE komentarji;
DROP TABLE komentiranje;
DROP TABLE komentiranje_bodies;

#m2w je ze 100 let v m2w bazi!
DROP TABLE m2w;
DROP TABLE mailkomentarji;
DROP TABLE zacasno;

UPDATE misc SET value='08.02.04' WHERE what='version';

UPDATE misc SET value='08.02.05' WHERE what='version';
UPDATE misc SET value='08.02.07' WHERE what='version';

ALTER TABLE users CHANGE user_groups user_groups integer not null default 0;

UPDATE misc SET value='08.02.11' WHERE what='version';
UPDATE misc SET value='08.02.12' WHERE what='version';


ALTER TABLE srv_anketa ADD user_from_cms INT NOT NULL DEFAULT '0' AFTER cookie ;

ALTER TABLE srv_user ADD user_id INT NOT NULL DEFAULT '0' AFTER cookie ;

UPDATE misc SET value='08.02.18' WHERE what='version';

ALTER TABLE srv_anketa ADD skin VARCHAR( 100 ) NOT NULL DEFAULT 'Simple';

ALTER TABLE srv_spremenljivka ADD visible TINYINT( 4 ) NOT NULL DEFAULT '1';

UPDATE misc SET value='08.02.21' WHERE what='version';
UPDATE misc SET value='08.02.25' WHERE what='version';

ALTER TABLE srv_anketa ADD odgovarja TINYINT NOT NULL DEFAULT '4' AFTER dostop ;

UPDATE misc SET value='08.02.26' WHERE what='version';

CREATE TABLE srv_if (
	id INT NOT NULL AUTO_INCREMENT ,
	spr_id INT NULL ,
	PRIMARY KEY (id) ,
	INDEX (spr_id)
) ENGINE = InnoDB;

CREATE TABLE srv_branch (
	if_id INT NOT NULL ,
	spr_id INT NOT NULL ,
	PRIMARY KEY (if_id, spr_id)
) ENGINE = InnoDB;

CREATE TABLE srv_condition(
	if_id INT NOT NULL ,
	spr_id INT NOT NULL ,
	vre_id INT NOT NULL ,
PRIMARY KEY ( if_id, spr_id, vre_id )
) ENGINE = InnoDB;

UPDATE misc SET value='08.02.28' WHERE what='version';

INSERT INTO misc (what, value) VALUES ('RegSurname', '0');

UPDATE misc SET value='08.03.03' WHERE what='version';

UPDATE misc SET value='08.03.05' WHERE what='version';
UPDATE misc SET value='08.03.06' WHERE what='version';

UPDATE misc SET value='08.03.10' WHERE what='version';

ALTER TABLE post ADD COLUMN locked tinyint(1) not null default 0;

UPDATE misc SET value='08.03.11' WHERE what='version';

CREATE TABLE srv_branching (
    ank_id INT NOT NULL ,
    parent INT NOT NULL ,
    element_spr INT NOT NULL ,
    element_if INT NOT NULL ,
    vrstni_red INT NOT NULL
) ENGINE = InnoDB;

DROP TABLE srv_condition ;

CREATE TABLE srv_condition (
    id INT NOT NULL AUTO_INCREMENT ,
    if_id INT NOT NULL ,
    spr_id INT NOT NULL ,
    conjunction SMALLINT NOT NULL DEFAULT '0',
    negation SMALLINT NOT NULL DEFAULT '0',
    operator SMALLINT NOT NULL DEFAULT '0',
    left_bracket SMALLINT NOT NULL DEFAULT '0',
    right_bracket SMALLINT NOT NULL DEFAULT '0',
    vrstni_red INT NOT NULL ,
    PRIMARY KEY ( id )
) ENGINE = InnoDB;

CREATE TABLE srv_condition_vre (
    cond_id INT NOT NULL ,
    vre_id INT NOT NULL ,
    PRIMARY KEY ( cond_id , vre_id )
) ENGINE = InnoDB;

ALTER TABLE srv_condition ADD vre_id INT NOT NULL AFTER spr_id;

CREATE TABLE srv_condition_grid (
    cond_id INT NOT NULL ,
    grd_id INT NOT NULL ,
    PRIMARY KEY ( cond_id , grd_id )
) ENGINE = InnoDB;

UPDATE misc SET value='08.03.25' WHERE what='version';

ALTER TABLE srv_condition ADD text VARCHAR( 100 ) NOT NULL AFTER operator ;

ALTER TABLE srv_branching ADD pagebreak TINYINT NOT NULL DEFAULT '0';

ALTER TABLE srv_if ADD label VARCHAR( 200 ) NOT NULL ;

ALTER TABLE srv_anketa ADD branching SMALLINT NOT NULL DEFAULT '0';

UPDATE misc SET value='08.04.01' WHERE what='version';


INSERT INTO hour_project (id, name, rank, uid, paid, meta, visible) VALUES ('99999', 'RR', '1', '', '1', '', '0');

UPDATE misc SET value='08.04.09' WHERE what='version';
UPDATE misc SET value='08.04.14' WHERE what='version';

ALTER TABLE srv_data_vrednost ADD spr_id INT NOT NULL FIRST ;

ALTER TABLE srv_data_vrednost DROP PRIMARY KEY ,
ADD PRIMARY KEY ( spr_id , vre_id , usr_id ) ;

ALTER TABLE srv_spremenljivka ADD textfield TINYINT NOT NULL DEFAULT '0';

ALTER TABLE srv_spremenljivka ADD textfield_label VARCHAR( 250 ) NOT NULL ;

UPDATE misc SET value='08.04.21' WHERE what='version';

ALTER TABLE srv_data_grid ADD spr_id INT NOT NULL FIRST ;

ALTER TABLE srv_data_grid DROP PRIMARY KEY ,
ADD PRIMARY KEY ( spr_id , vre_id , usr_id ) ;

ALTER TABLE srv_spremenljivka ADD cela TINYINT NOT NULL DEFAULT '4',
ADD decimalna TINYINT NOT NULL DEFAULT '0';

UPDATE misc SET value='08.05.05' WHERE what='version';

ALTER TABLE hour_users CHANGE display display TINYINT( 4 ) NOT NULL DEFAULT '0';

ALTER TABLE srv_anketa ADD tip TINYINT NOT NULL DEFAULT '0';

ALTER TABLE srv_user ADD recnum INT NOT NULL DEFAULT '0';

ALTER TABLE srv_user ADD ank_id INT NOT NULL AFTER id ;

ALTER TABLE srv_condition ADD modul SMALLINT NOT NULL DEFAULT '2' AFTER text ,
ADD ostanek SMALLINT NOT NULL DEFAULT '0' AFTER modul ;

ALTER TABLE srv_if ADD tip TINYINT NOT NULL DEFAULT '0' AFTER id;

ALTER TABLE hour_project ADD can_insert SMALLINT NOT NULL DEFAULT '3';

ALTER TABLE srv_anketa ADD alert_avtor TINYINT NOT NULL DEFAULT '0',
ADD alert_admin TINYINT NOT NULL DEFAULT '0';

UPDATE misc SET value='08.05.15' WHERE what='version';

CREATE TABLE srv_alert (
    ank_id INT NOT NULL ,
    emails MEDIUMTEXT NOT NULL ,
    PRIMARY KEY ( ank_id )
);

ALTER TABLE srv_anketa CHANGE insert_user insert_uid INT NOT NULL ;

ALTER TABLE srv_anketa CHANGE edit_user edit_uid INT NOT NULL ;

ALTER TABLE srv_anketa ADD user_base TINYINT NOT NULL DEFAULT '0' AFTER user_from_cms ;

UPDATE misc SET value='08.05.19' WHERE what='version';

ALTER TABLE srv_user ADD name VARCHAR( 250 ) NOT NULL AFTER ank_id ;

ALTER TABLE srv_user ADD email VARCHAR( 100 ) NOT NULL AFTER name ;

CREATE TABLE srv_user_grupa (
    gru_id INT NOT NULL ,
    usr_id INT NOT NULL ,
    time_edit DATETIME NOT NULL
);

ALTER TABLE srv_user_grupa ADD PRIMARY KEY ( gru_id , usr_id ) ;

ALTER TABLE srv_user ADD javascript TINYINT NOT NULL ;

ALTER TABLE srv_user ADD useragent VARCHAR( 250 ) NOT NULL ;

UPDATE misc SET value='08.05.20' WHERE what='version';
UPDATE misc SET value='08.05.22' WHERE what='version';
UPDATE misc SET value='08.06.02' WHERE what='version';
UPDATE misc SET value='08.06.04' WHERE what='version';
UPDATE misc SET value='08.06.09' WHERE what='version';

ALTER TABLE srv_branching ADD PRIMARY KEY (ank_id, parent, element_spr, element_if);

ALTER TABLE srv_condition ADD INDEX (if_id);

ALTER TABLE srv_condition ADD INDEX (spr_id, vre_id);

ALTER TABLE srv_grupa ADD INDEX (ank_id);

ALTER TABLE srv_spremenljivka ADD INDEX (gru_id);

ALTER TABLE srv_vrednost ADD INDEX (spr_id);

ALTER TABLE srv_grupa ADD INDEX (ank_id, vrstni_red);

ALTER TABLE srv_spremenljivka ADD INDEX (gru_id, vrstni_red);

ALTER TABLE srv_anketa ADD uporabnost_link VARCHAR( 400 ) NOT NULL ;

ALTER TABLE srv_anketa ADD progressbar TINYINT NOT NULL DEFAULT '1';

ALTER TABLE neww ADD asc2 TINYINT NOT NULL DEFAULT '0';

insert into misc (what, value) values ('preslikavaKAM', '-1');
insert into misc (what, value) values ('preslikavaOSTALI', '0');
insert into misc (what, value) values ('zavzamemenu', '0');

UPDATE misc SET value='08.06.30' WHERE what='version';
ALTER table menu ADD COLUMn nivo integer not null;
ALTER TABLE struktura_baze ADD COLUMN video tinyint(1) not null default 0;

#odkomentiraj ce se nimas spodnje baze

#CREATE DATABASE sispletvideo;
#USE sispletvideo;
#CREATE TABLE videos (id int not null auto_increment, dbname varchar(100), filename varchar(100), full_path varchar(255), resolution varchar(30), fps varchar(8), audiorate varchar(10), channels varchar(2), status tinyint(1) not null default 0, key id(id));

# ne pozabi grantat privilegijev na select, insert, update za zgornjo bazo!


ALTER TABLE srv_if ADD collapsed TINYINT NOT NULL DEFAULT '0';

ALTER TABLE srv_spremenljivka ADD timer MEDIUMINT NOT NULL DEFAULT '0';
UPDATE misc SET value='08.07.29' WHERE what='version';


ALTER TABLE srv_spremenljivka ADD sistem TINYINT NOT NULL DEFAULT '0';
INSERT INTO misc (what, value) VALUES ('AfterReg', '');
ALTER TABLE struktura_avtor ADD COLUMN DisableFinance tinyint(1) not null default 0;


UPDATE misc SET value='08.08.05' WHERE what='version';

ALTER table new ADD column span tinyint(1) not null default 1;
ALTER table neww ADD column span tinyint(1) not null default 1;
ALTER TABLE srv_branching ADD INDEX element_spr (element_spr);

 CREATE TABLE srv_folder (
    id INT NOT NULL AUTO_INCREMENT ,
    naslov VARCHAR( 50 ) NOT NULL ,
    parent INT NOT NULL ,
    PRIMARY KEY (id) ,
    INDEX (parent)
) ENGINE = InnoDB ;

INSERT INTO srv_folder (id, naslov, parent) VALUES ('1', 'OneClick Survey', '0');

ALTER TABLE srv_anketa ADD folder INT NOT NULL DEFAULT '1' AFTER id;

ALTER TABLE srv_anketa ADD backup INT NOT NULL AFTER folder;

ALTER TABLE srv_if DROP spr_id;

ALTER TABLE srv_anketa ADD show_intro TINYINT NOT NULL DEFAULT '1' AFTER conclusion,
ADD show_concl TINYINT NOT NULL DEFAULT '1' AFTER show_intro;

ALTER TABLE srv_anketa ADD concl_link INT NOT NULL DEFAULT '1' AFTER show_concl;

UPDATE misc SET value='08.08.11' WHERE what='version';

ALTER TABLE srv_anketa ADD sidebar TINYINT NOT NULL DEFAULT '1',
ADD collapsed_content TINYINT NOT NULL DEFAULT '1';

ALTER TABLE srv_if ADD number INT NOT NULL DEFAULT '0' AFTER id;

ALTER TABLE srv_anketa ADD intro_opomba VARCHAR( 250 ) NOT NULL AFTER conclusion,
ADD concl_opomba VARCHAR( 250 ) NOT NULL AFTER intro_opomba ;



UPDATE misc SET value='08.08.25' WHERE what='version';

ALTER TABLE srv_data_text ADD id INT UNSIGNED NOT NULL FIRST ;
SET @i = 0;
UPDATE srv_data_text SET id = @i := @i + 1;
ALTER TABLE srv_data_text DROP PRIMARY KEY ,
ADD UNIQUE spr_usr (spr_id, usr_id);
ALTER TABLE srv_data_text ADD PRIMARY KEY (id)  ;
ALTER TABLE srv_data_text CHANGE id id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE srv_data_text ADD INDEX (usr_id);

CREATE TABLE IF NOT EXISTS srv_call_current (
  phone_id int(10) unsigned NOT NULL,
  user_id int(10) unsigned NOT NULL,
  started_time datetime NOT NULL,
  PRIMARY KEY  (phone_id),
  KEY user_id (user_id),
  KEY started_time (started_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS srv_call_history (
  id int(10) unsigned NOT NULL auto_increment,
  survey_id int(10) unsigned NOT NULL default '0',
  user_id int(10) unsigned NOT NULL default '0',
  phone_id int(10) unsigned NOT NULL,
  insert_time datetime NOT NULL,
  status enum('Z','N','R','T','U') NOT NULL,
  PRIMARY KEY  (id),
  KEY phone_id (phone_id),
  KEY time (insert_time),
  KEY status (status),
  KEY survey_id (survey_id),
  KEY user_id (user_id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS srv_call_schedule (
  phone_id int(10) unsigned NOT NULL,
  call_time datetime NOT NULL,
  PRIMARY KEY  (phone_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS srv_call_setting (
  survey_id int(10) unsigned NOT NULL default '0',
  status_z int(10) unsigned NOT NULL default '0',
  status_n int(10) unsigned NOT NULL default '0',
  max_calls int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (survey_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='08.09.02' WHERE what='version';


CREATE TABLE IF NOT EXISTS srv_library_folder (
  id int(11) NOT NULL auto_increment,
  naslov varchar(50) NOT NULL,
  parent int(11) NOT NULL,
  PRIMARY KEY  (id),
  KEY parent (parent)
);

INSERT INTO srv_library_folder (id, naslov, parent) VALUES ('1', 'Library', '0');


ALTER TABLE srv_spremenljivka ADD folder INT NOT NULL DEFAULT '1';



UPDATE misc SET value='08.09.17' WHERE what='version';
ALTER TABLE `srv_call_history` CHANGE `status` `status` ENUM( 'Z', 'N', 'R', 'T', 'P', 'U' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

ALTER TABLE srv_library_folder ADD uid TINYINT NOT NULL DEFAULT '0' AFTER id;

alter table struktura_baze add column intro tinyint(1) not null default 0;
ALTER TABLE struktura_baze add column intro_size varchar(12) not null default '';
alter table data_baze add column intro_pic varchar(255);
alter table data_baze add column intro_text mediumtext not null default '';


ALTER TABLE srv_library_folder ADD tip TINYINT NOT NULL DEFAULT '0' AFTER uid;


ALTER TABLE srv_library_folder ADD tip TINYINT NOT NULL DEFAULT '0' AFTER uid;

INSERT INTO srv_library_folder (uid, tip, naslov, parent) VALUES ('0', '1', 'Library', '0');

ALTER TABLE srv_anketa ADD library TINYINT NOT NULL DEFAULT '0';

CREATE TABLE srv_library_anketa (
ank_id INT NOT NULL ,
uid INT NOT NULL ,
PRIMARY KEY ( ank_id , uid )
);

UPDATE misc SET value='08.09.28' WHERE what='version';
UPDATE misc SET value='08.09.30' WHERE what='version';


ALTER TABLE srv_anketa ADD alert_respondent TINYINT NOT NULL DEFAULT '0' AFTER tip;

ALTER TABLE srv_alert ADD text TEXT NOT NULL ;

ALTER TABLE srv_anketa CHANGE skin skin VARCHAR( 100 ) NOT NULL DEFAULT 'Modern' ;


INSERT INTO misc (what, value) VALUES ('hour_insertproject', '1');

INSERT INTO misc (what, value) VALUES ('register_auto_t', '0');

UPDATE misc SET value='08.10.13' WHERE what='version';


ALTER TABLE srv_anketa CHANGE concl_link concl_link INT( 11 ) NOT NULL DEFAULT '0' ;

ALTER TABLE srv_library_anketa ADD folder INT NOT NULL ;
INSERT INTO misc (what, value) VALUES ('SurveyMetaOnly', '0');


alter table groups change gid gid bigint not null;

UPDATE misc SET value='08.11.03' WHERE what='version';
ALTER TABLE srv_spremenljivka ADD params TEXT CHARACTER SET utf8 COLLATE utf8_bin NOT NULL ;

UPDATE misc SET value='08.11.18' WHERE what='version';

ALTER TABLE srv_library_folder CHANGE uid uid INT( 11 ) NOT NULL DEFAULT '0';
UPDATE misc SET value='08.11.25' WHERE what='version';

ALTER TABLE struktura_baze ADD COLUMN legenda mediumtext not null default '';
INSERT INTO misc (what, value) VALUES ('ShowBookmarks', '1');

UPDATE misc SET value='08.12.01' WHERE what='version';
ALTER TABLE struktura_baze ADD COLUMN time_filter tinyint(1) not null default 0;
UPDATE misc SET value='08.12.05' WHERE what='version';

ALTER TABLE struktura_kategorij ADD COLUMN griddisp tinyint(1) not null default 0;


ALTER TABLE srv_data_grid ADD INDEX ( vre_id , usr_id )  ;

ALTER TABLE srv_data_vrednost ADD INDEX ( spr_id , usr_id );
UPDATE misc SET value='09.01.05' WHERE what='version';
ALTER TABLE data_baze ADD COLUMN intro_size varchar(12) not null default "0";
ALTER TABLE struktura_baze ADD COLUMN intro_no_rec varchar(2) not null default "0";

INSERT INTO misc (what, value) values ('DefaultShowPrintWordPdf', '1');
INSERT INTO misc (what, value) values ('DefaultShowDigg', '1');
ALTER TABLE struktura_baze ADD COLUMN zavihek1 varchar(255) not null default '';
ALTER TABLE struktura_baze ADD COLUMN zavihek1_text varchar(255) not null default '';
ALTER TABLE struktura_baze ADD COLUMN zavihek2 varchar(255) not null default '';
ALTER TABLE struktura_baze ADD COLUMN zavihek2_text varchar(255) not null default '';
ALTER TABLE struktura_baze ADD COLUMN zavihek3 varchar(255) not null default '';
ALTER TABLE struktura_baze ADD COLUMN zavihek3_text varchar(255) not null default '';
ALTER TABLE struktura_baze ADD COLUMN zavihki_kje integer not null default 0;
ALTER TABLE struktura_baze ADD COLUMN zavihki_content integer not null default 0;
ALTER TABLE struktura_baze ADD COLUMN intro_frame tinyint(1) not null default 0;
ALTER TABLE data_baze ADD COLUMN ShowPrintWordPdf tinyint(1) not null default 0;
ALTER TABLE data_baze ADD COLUMN ShowDigg tinyint(1) not null default 0;
ALTER TABLE menu ADD COLUMN ShowPrintWordPdf tinyint(1) not null default 0;
ALTER TABLE menu ADD COLUMN ShowDigg tinyint(1) not null default 0;

CREATE  TABLE  srv_data_imena (id int(10) unsigned NOT NULL auto_increment,
spr_id int(11)  NOT NULL default '0',
text text character  set utf8 collate utf8_bin NOT NULL,
usr_id int(11) NOT NULL default '0',
antonucci int(1) NOT NULL default '0',
emotion tinyint(1) NOT NULL default '0',
social tinyint(1) NOT NULL default '0',
emotionINT tinyint(1) NOT NULL default '0',
socialINT tinyint(1) NOT NULL default '0',
countE int(11) NOT NULL default '0',
countS int(11) NOT NULL default '0',
PRIMARY KEY (id))
ENGINE = InnoDB DEFAULT CHARSET = latin1;

ALTER TABLE srv_spremenljivka ADD COLUMN antonucci tinyint(1) NOT NULL default '0';

ALTER TABLE srv_spremenljivka ADD COLUMN podpora tinyint(1) NOT NULL default '0';

ALTER TABLE struktura_baze ADD COLUMN defa tinyint(1) NOT NULL default 1;
ALTER TABLE struktura_baze ADD COLUMN defb tinyint(1) NOT NULL default 1;
ALTER TABLE struktura_baze ADD COLUMN defc tinyint(1) NOT NULL default 1;
ALTER TABLE struktura_baze ADD COLUMN defd tinyint(1) NOT NULL default 1;
ALTER TABLE struktura_baze ADD COLUMN defe tinyint(1) NOT NULL default 1;
ALTER TABLE struktura_baze ADD COLUMN deff tinyint(1) NOT NULL default 1;
ALTER TABLE struktura_baze ADD COLUMN defg tinyint(1) NOT NULL default 1;
ALTER TABLE struktura_baze ADD COLUMN defh tinyint(1) NOT NULL default 1;
ALTER TABLE struktura_baze ADD COLUMN defi tinyint(1) NOT NULL default 1;
ALTER TABLE struktura_baze ADD COLUMN defj tinyint(1) NOT NULL default 1;
ALTER TABLE struktura_baze ADD COLUMN defk tinyint(1) NOT NULL default 1;
ALTER TABLE struktura_baze ADD COLUMN defl tinyint(1) NOT NULL default 1;
ALTER TABLE struktura_baze ADD COLUMN defm tinyint(1) NOT NULL default 1;
ALTER TABLE struktura_baze ADD COLUMN defn tinyint(1) NOT NULL default 1;
ALTER TABLE struktura_baze ADD COLUMN defo tinyint(1) NOT NULL default 1;
ALTER TABLE struktura_baze ADD COLUMN defp tinyint(1) NOT NULL default 1;
ALTER TABLE struktura_baze ADD COLUMN defq tinyint(1) NOT NULL default 1;
ALTER TABLE struktura_baze ADD COLUMN defr tinyint(1) NOT NULL default 1;
ALTER TABLE struktura_baze ADD COLUMN defs tinyint(1) NOT NULL default 1;
ALTER TABLE struktura_baze ADD COLUMN deft tinyint(1) NOT NULL default 1;
ALTER TABLE struktura_baze ADD COLUMN defu tinyint(1) NOT NULL default 1;
ALTER TABLE struktura_baze ADD COLUMN defv tinyint(1) NOT NULL default 1;
ALTER TABLE struktura_baze ADD COLUMN defw tinyint(1) NOT NULL default 1;
ALTER TABLE struktura_baze ADD COLUMN defx tinyint(1) NOT NULL default 1;
ALTER TABLE struktura_baze ADD COLUMN defy tinyint(1) NOT NULL default 1;
ALTER TABLE struktura_baze ADD COLUMN defz tinyint(1) NOT NULL default 1;
ALTER TABLE struktura_baze ADD COLUMN defaa tinyint(1) NOT NULL default 1;
ALTER TABLE struktura_baze ADD COLUMN defab tinyint(1) NOT NULL default 1;
ALTER TABLE struktura_baze ADD COLUMN defac tinyint(1) NOT NULL default 1;
ALTER TABLE struktura_baze ADD COLUMN defad tinyint(1) NOT NULL default 1;
ALTER TABLE struktura_baze ADD COLUMN defae tinyint(1) NOT NULL default 1;
ALTER TABLE struktura_baze ADD COLUMN defaf tinyint(1) NOT NULL default 1;
ALTER TABLE struktura_baze ADD COLUMN defag tinyint(1) NOT NULL default 1;
ALTER TABLE struktura_baze ADD COLUMN defah tinyint(1) NOT NULL default 1;
ALTER TABLE struktura_baze ADD COLUMN defai tinyint(1) NOT NULL default 1;
ALTER TABLE struktura_baze ADD COLUMN defaj tinyint(1) NOT NULL default 1;
ALTER TABLE struktura_baze ADD COLUMN defak tinyint(1) NOT NULL default 1;

ALTER TABLE neww ADD COLUMN dostop tinyint(1) NOT NULL default 0;
ALTER TABLE menu ADD COLUMN content_span tinyint(1) not null default 1;
ALTER TABLE neww ADD COLUMN width integer not null default 33;

UPDATE misc SET value='09.01.24' WHERE what='version';
ALTER TABLE menu ADD COLUMN show_menu tinyint(1) not null default 1;



ALTER TABLE srv_anketa ADD cookie_return TINYINT NOT NULL DEFAULT '0' AFTER cookie;

# POZOR
# POKLICI SKRIPTO utils/kupcki.php
# RESNO, DRUGACE BO STRAN SESUTA :-)

UPDATE misc SET value='09.02.02' WHERE what='version';

ALTER TABLE struktura_baze ADD COLUMN embed_under_list varchar(255) not null default "";
ALTER TABLE struktura_baze ADD COLUMN embed_under_rec varchar(255) not null default "";

UPDATE misc SET value='09.02.05' WHERE what='version';

ALTER TABLE users ADD COLUMN rubrike mediumtext not null default "";

ALTER TABLE srv_spremenljivka ADD COLUMN enota tinyint(1) NOT NULL default '0' AFTER decimalna;
ALTER TABLE struktura_baze ADD COLUMN tab1_default tinyint(1) not null default 0;

CREATE  TABLE  srv_data_number (
spr_id int(11) NOT NULL default '0',
vre_id int(11) NOT NULL default '0',
usr_id int(11) NOT NULL default '0',
text text character  set utf8 collate utf8_bin NOT NULL,
PRIMARY KEY (spr_id, vre_id, usr_id))
ENGINE = InnoDB DEFAULT CHARSET = latin1;

ALTER TABLE srv_vrednost ADD COLUMN naslov2 varchar(250) character set utf8 collate utf8_bin NOT NULL AFTER naslov;
ALTER TABLE users ADD COLUMN pay_n mediumtext not null;
ALTER TABLE users ADD COLUMN pay_bid mediumtext not null;
ALTER TABLE data_baze ADD COLUMN pay_att varchar(255) not null default "";
UPDATE misc SET value='09.02.23' WHERE what='version';

INSERT INTO misc (what, value) VALUES ('RegShowInterval', '1');
INSERT INTO misc (what, value) VALUES ('RegShowGroups', '1');
INSERT INTO misc (what, value) VALUES ('RegShowColumns', '1');
INSERT INTO misc (what, value) VALUES ('RegShowN', '1');

ALTER TABLE srv_spremenljivka ADD COLUMN design tinyint(1) NOT NULL default '0' AFTER antonucci;

ALTER TABLE srv_data_vrednost ADD INDEX vre_usr (vre_id, usr_id);
ALTER TABLE srv_branching ADD INDEX element_spr_if (element_spr, element_if);
ALTER TABLE srv_branching ADD INDEX (parent);
ALTER TABLE srv_branching ADD INDEX (element_if);
ALTER TABLE srv_data_vrednost ADD INDEX (usr_id);
ALTER TABLE srv_data_grid ADD INDEX (usr_id);

INSERT INTO misc (what, value) VALUES ('WhenSendCustomMail', 'never / nikoli');
UPDATE misc SET value='09.03.02' WHERE what='version';

ALTER TABLE srv_data_text ADD COLUMN text2 text character set utf8 collate utf8_bin NOT NULL AFTER text;

ALTER TABLE srv_user_grupa ADD INDEX (usr_id);

ALTER TABLE struktura_baze ADD COLUMN zavihek1_nobid tinyint(1) not null default 0;
ALTER TABLE struktura_baze ADD COLUMN zavihek2_nobid tinyint(1) not null default 0;
ALTER TABLE struktura_baze ADD COLUMN zavihek3_nobid tinyint(1) not null default 0;
ALTER TABLE neww ADD COLUMN nointro tinyint(1) not null default 0;

ALTER TABLE struktura_baze ADD COLUMN rss tinyint(1) not null default 0;

UPDATE misc SET value='09.03.08' WHERE what='version';

ALTER TABLE srv_alert ADD subject VARCHAR( 250 ) NOT NULL AFTER emails;

ALTER TABLE srv_spremenljivka ADD COLUMN grids tinyint(4) NOT NULL default '5';

ALTER TABLE struktura_kategorij ADD COLUMN gridadm tinyint(1) not null default 0;

ALTER TABLE srv_spremenljivka ADD variable_custom TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER variable;

ALTER TABLE srv_vrednost ADD variable_custom TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER variable;

ALTER TABLE srv_grid ADD COLUMN vrstni_red int(11) NOT NULL default '0';

ALTER TABLE srv_vrednost ADD random TINYINT( 1 ) NOT NULL DEFAULT '0';

UPDATE srv_vrednost v, srv_spremenljivka s SET v.random = '1' WHERE v.spr_id = s.id AND s.random = '1' ;

ALTER TABLE srv_data_text ADD vre_id INT NOT NULL DEFAULT '0' AFTER spr_id;

ALTER TABLE srv_data_text DROP INDEX spr_usr, ADD UNIQUE spr_usr (spr_id, usr_id, vre_id);

ALTER TABLE srv_vrednost ADD other TINYINT NOT NULL DEFAULT '0';

ALTER TABLE srv_grid ADD COLUMN variable varchar(15) character set utf8 collate utf8_bin NOT NULL;

UPDATE srv_grid SET vrstni_red=id, variable=id WHERE vrstni_red='0' AND variable='';

UPDATE srv_grid SET variable=vrstni_red WHERE variable='';

ALTER TABLE srv_spremenljivka ADD COLUMN grids_edit tinyint(4) NOT NULL default '0' AFTER grids;

UPDATE misc SET value='09.03.31' WHERE what='version';

#ALTER TABLE srv_spremenljivka ADD COLUMN grids_edit tinyint(4) NOT NULL default '0' AFTER grids;

# Za update strani, ki uporabljajo 1KA:
# NUJNO pozeni utils/1ka_other_prenos.php !!!!!!!!

UPDATE misc SET value='09.03.31' WHERE what='version';

ALTER TABLE srv_folder ADD creator_uid INT NOT NULL DEFAULT '0';


ALTER TABLE srv_call_current  CHANGE phone_id usr_id INT(10) UNSIGNED NOT NULL;
ALTER TABLE srv_call_history  CHANGE phone_id usr_id INT(10) UNSIGNED NOT NULL;
ALTER TABLE srv_call_schedule CHANGE phone_id usr_id INT(10) UNSIGNED NOT NULL;

TRUNCATE TABLE srv_call_current;
TRUNCATE TABLE srv_call_history;
TRUNCATE TABLE srv_call_schedule;

ALTER TABLE srv_anketa ADD expire DATE NOT NULL AFTER active;
UPDATE srv_anketa SET expire = NOW() + INTERVAL 30 DAY;


CREATE TABLE srv_userbase_setting (
ank_id INT NOT NULL ,
subject VARCHAR( 200 ) NOT NULL ,
text TEXT NOT NULL ,
PRIMARY KEY ( ank_id )
);

# tip=0 pomeni, da je bil takrat dodan v bazo
# tip>0 pomeni zaporedno stevilko posiljanja mailov (1. val, 2. val, ...)
CREATE TABLE srv_userbase (
usr_id INT NOT NULL ,
tip TINYINT NOT NULL ,
datetime DATETIME NOT NULL ,
admin_id INT NOT NULL ,
PRIMARY KEY (usr_id, tip)
);


ALTER TABLE srv_data_number ADD COLUMN text2 text character set utf8 collate utf8_bin NOT NULL;

#damo polje alert_more, za možnost nastavljanja checkboxa za več prejemnikov
ALTER TABLE srv_anketa ADD alert_more TINYINT(1) NOT NULL DEFAULT '0' AFTER alert_admin;
#če smo pri srv_alert imeli več prejemnikov nastavimo alert_more na 1
UPDATE srv_anketa san LEFT JOIN srv_alert as sal ON sal.ank_id = san.id SET san.alert_more = '1' WHERE sal.emails is not null;

ALTER TABLE menu ADD COLUMN NiceLink varchar(255) not null default "";

UPDATE misc SET value='09.04.14' WHERE what='version';
ALTER TABLE srv_anketa ADD akronim VARCHAR( 40 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL AFTER naslov;

ALTER TABLE struktura_baze ADD COLUMN NiceLink varchar(255) not null default "";
ALTER TABLE data_baze ADD COLUMN NiceLink varchar(255) not null default "";

ALTER TABLE srv_anketa ADD starts DATE NOT NULL AFTER active;
UPDATE srv_anketa SET starts = NOW() - INTERVAL 30 DAY;


ALTER table srv_data_grid MODIFY grd_id int(11) NOT NULL;

UPDATE srv_spremenljivka SET variable_custom='1' WHERE variable NOT LIKE 'V%' AND gru_id='-1';

CREATE TABLE srv_userstatus (usr_id INT NOT NULL, tip TINYINT NOT NULL DEFAULT '0', status TINYINT NOT NULL DEFAULT '0', datetime DATETIME NOT NULL);
ALTER TABLE srv_userstatus ADD PRIMARY KEY (usr_id, tip);
# status:
# 0 - nismo se poslali maila
# 1 - mail je bil poslan
# 2 - pri posiljanju je prislo do napake
# 3 - kliknil je na link v mailu
# 4 - izpolnil je anketo (ne do konca)
# 5 - do konca je izpolnil anketo

CREATE TABLE srv_help (
what VARCHAR(50) NOT NULL,
help TEXT NOT NULL,
PRIMARY KEY (what)
);

CREATE TABLE srv_tracking (
datetime DATETIME NOT NULL ,
ip VARCHAR( 16 ) NOT NULL ,
user VARCHAR( 20 ) NOT NULL ,
get TEXT NOT NULL ,
post TEXT NOT NULL
);

ALTER TABLE srv_tracking ADD ank_id INT NOT NULL FIRST ;
ALTER TABLE srv_tracking CHANGE user user INT NOT NULL ;
ALTER TABLE srv_tracking ADD INDEX ( ank_id) ;
ALTER TABLE srv_tracking ADD INDEX ( datetime );

# za uporabnikove nastavitve za posamezno anketo
CREATE TABLE srv_userSurveySetting (
id int(11) NOT NULL AUTO_INCREMENT,
sid int(11) NOT NULL,
uid int(11) NOT NULL,
stevilciPdf tinyint(1) not null default 0,
stevilciRtf tinyint(1) not null default 0,
stevilciFrekvence tinyint(1) not null default 0,
stevilciOpisne tinyint(1) not null default 0,
PRIMARY KEY (id)
);

# dodano polje status
# 0 => default - sprememba nastavitev ankete
# 1 => klik na linke Vnosi
# 2 => klik na linke Analiza
# 3 => klik na linke Porocila
# 4 => kliki na folderjih
ALTER TABLE srv_tracking ADD status TINYINT( 1 ) NOT NULL DEFAULT '0';

INSERT INTO misc (what, value) VALUES ('SurveyExport', '2');

ALTER TABLE srv_spremenljivka ADD info TEXT NOT NULL AFTER naslov;

#tega ne rabimo ker je po novem na nivoju ankete
ALTER TABLE srv_userSurveySetting ADD stevilciType tinyint(1) not null default 0  after uid;

ALTER TABLE srv_spremenljivka ADD orientation TINYINT( 1 ) NOT NULL DEFAULT '1' AFTER stat;

ALTER TABLE srv_anketa ADD countType tinyint(1) not null default 1;


ALTER TABLE srv_anketa ADD question_comment TINYINT NOT NULL DEFAULT '0';
ALTER TABLE srv_userSurveySetting DROP stevilciType ;

ALTER TABLE srv_spremenljivka ADD rejected TINYINT NOT NULL DEFAULT '0' AFTER undecided;
ALTER TABLE srv_spremenljivka ADD inappropriate TINYINT NOT NULL DEFAULT '0' AFTER rejected;

UPDATE misc SET value='09.05.18' where what="version";

ALTER TABLE srv_if ADD folder INT NOT NULL DEFAULT '0';

# tabela sistemskih filtrov
CREATE TABLE srv_sys_filters (
id int(11) NOT NULL AUTO_INCREMENT ,
filter varchar(255) NOT NULL ,
text varchar(255) NOT NULL ,
uid int(11) NOT NULL,
PRIMARY KEY (id),
UNIQUE (filter, text)
) DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

INSERT INTO srv_sys_filters (id, filter, text, uid) VALUES (1, '-1', 'Ni odgovoril', 0);
INSERT INTO srv_sys_filters (id, filter, text, uid) VALUES (2, '-2', 'Preskok (if)', 0);
INSERT INTO srv_sys_filters (id, filter, text, uid) VALUES (3, '-4', 'Naknadno vprasanje', 0);
INSERT INTO srv_sys_filters (id, filter, text, uid) VALUES (4, '99', 'Ne vem', 0);
INSERT INTO srv_sys_filters (id, filter, text, uid) VALUES (5, '98', 'Zavrnil', 0);
INSERT INTO srv_sys_filters (id, filter, text, uid) VALUES (6, '97', 'Neustrezno', 0);
INSERT INTO srv_sys_filters (id, filter, text, uid) VALUES (7, '96', 'Drugo', 0);

ALTER TABLE srv_spremenljivka ADD checkboxhide TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER orientation;

CREATE TABLE user_tracker (uid varchar(10), timestamp long, what varchar(254));
UPDATE misc SET value='09.05.28' where what="version";

INSERT INTO misc (what, value) values ('utrack_acc', '0');
ALTER TABLE struktura_baze ADD COLUMN grid_hint integer not null default 0;

ALTER TABLE forum ADD COLUMN NiceLink varchar(255) not null default "";
ALTER TABLE post ADD COLUMN NiceLink varchar(255) not null default "";

CREATE TABLE srv_specialdata_vrednost (
  spr_id int(11) NOT NULL default '0',
  vre_id int(11) NOT NULL default '0',
  usr_id int(11) NOT NULL default '0',
  PRIMARY KEY ( spr_id, usr_id ),
  UNIQUE (spr_id , usr_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE novice ADD COLUMN NiceLink varchar(255) not null default "";
ALTER TABLE aktualno ADD COLUMN NiceLink varchar(255) not null default "";
ALTER TABLE faq ADD COLUMN NiceLink varchar(255) not null default "";
ALTER TABLE vodic ADD COLUMN NiceLink varchar(255) not null default "";
ALTER TABLE rubrika1 ADD COLUMN NiceLink varchar(255) not null default "";
ALTER TABLE rubrika2 ADD COLUMN NiceLink varchar(255) not null default "";
ALTER TABLE rubrika3 ADD COLUMN NiceLink varchar(255) not null default "";
ALTER TABLE rubrika4 ADD COLUMN NiceLink varchar(255) not null default "";
ALTER TABLE new ADD COLUMN NiceLink varchar(255) not null default "";

UPDATE misc SET value='09.05.31' where what="version";

DROP TABLE srv_userSurveySetting ;
CREATE TABLE srv_userSurveySetting (
sid int(11) NOT NULL,
uid int(11) NOT NULL,
use_if_in_report tinyint(1) not null default 0,
PRIMARY KEY ( sid, uid ),
UNIQUE (sid , uid)
);
UPDATE misc SET value='09.06.01' where what="version";

ALTER TABLE srv_user ADD COLUMN referer varchar(255) not null default "";

# za shranjevanje nastavitev sistema
DROP TABLE IF EXISTS srv_misc;
CREATE TABLE srv_misc (
  what varchar(255) NOT NULL default '',
  value longtext,
  UNIQUE KEY what ( what )
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
INSERT INTO srv_misc (what, value) VALUES ('question_comment_text', 'Va&#154; komentar k vpra&#154;anju');

# za shranjevanje nastavitev ankete
DROP TABLE IF EXISTS srv_survey_misc;
CREATE TABLE srv_survey_misc(sid int( 11 ) NOT NULL ,what varchar( 255 ) NOT NULL default '',value longtext,UNIQUE (sid, what)) ENGINE = InnoDB DEFAULT CHARSET = utf8;
UPDATE misc SET value='09.06.04' where what="version";


CREATE TABLE srv_user_setting_misc( uid int( 11 ) NOT NULL , what varchar( 255 ) NOT NULL default '', value longtext, UNIQUE (uid, what)) ENGINE = InnoDB DEFAULT CHARSET = utf8;

UPDATE misc SET value='09.06.04' where what="version";

ALTER TABLE srv_anketa ADD return_finished TINYINT NOT NULL DEFAULT '1' AFTER cookie_return;
ALTER TABLE srv_anketa ADD block_ip TINYINT NOT NULL DEFAULT '0' AFTER user_base;

UPDATE misc SET value='09.06.16' where what="version";

ALTER TABLE srv_vrednost ADD if_id INT NOT NULL DEFAULT '0';

# dodamo polje fid, ki bo fiksno in polje type, ki predstavlja tip (1-missing, 2-neopredeljen, 3-uporabniškonastavljen)
ALTER TABLE srv_sys_filters ADD fid varchar(255) NOT NULL AFTER id;
ALTER TABLE srv_sys_filters ADD type TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER fid;
UPDATE srv_sys_filters SET fid = '-1', type='1' WHERE filter = -1;
UPDATE srv_sys_filters SET fid = '-2', type='1' WHERE filter = -2;
UPDATE srv_sys_filters SET fid = '-3', type='1' WHERE filter = -3;
UPDATE srv_sys_filters SET fid = '-4', type='1' WHERE filter = -4;
UPDATE srv_sys_filters SET fid = '99', type='2' WHERE filter = 99 OR filter = -99;
UPDATE srv_sys_filters SET fid = '98', type='2' WHERE filter = 98 OR filter = -98;
UPDATE srv_sys_filters SET fid = '97', type='2' WHERE filter = 97 OR filter = -97;
UPDATE srv_sys_filters SET fid = '96', type='2' WHERE filter = 96 OR filter = -96;

UPDATE misc SET value='09.06.23' where what="version";

# popravek sys_filters zaradi errorja na pizzi 	
# tabela sistemskih filtrov
DROP TABLE IF EXISTS srv_sys_filters;
CREATE TABLE srv_sys_filters (
id int(11) NOT NULL AUTO_INCREMENT ,
fid varchar(100) NOT NULL ,
type TINYINT( 1 ) NOT NULL DEFAULT '0' ,
filter varchar(100) NOT NULL ,
text varchar(200) NOT NULL ,
uid int(11) NOT NULL,
PRIMARY KEY (id),
UNIQUE (filter, text)
) DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
INSERT INTO srv_sys_filters (id, fid, type, filter, text, uid) VALUES (1, '-1', '1', '-1', 'Ni odgovoril', 0);
INSERT INTO srv_sys_filters (id, fid, type, filter, text, uid) VALUES (2, '-2', '1', '-2', 'Preskok (if)', 0);
INSERT INTO srv_sys_filters (id, fid, type, filter, text, uid) VALUES (3, '-3', '1', '-3', 'Prekinjeno', 0);
INSERT INTO srv_sys_filters (id, fid, type, filter, text, uid) VALUES (4, '-4', '1', '-4', 'Naknadno vprasanje', 0);
INSERT INTO srv_sys_filters (id, fid, type, filter, text, uid) VALUES (5, '99', '2', '-99', 'Ne vem', 0);
INSERT INTO srv_sys_filters (id, fid, type, filter, text, uid) VALUES (6, '98', '2', '-98', 'Zavrnil', 0);
INSERT INTO srv_sys_filters (id, fid, type, filter, text, uid) VALUES (7, '97', '2', '-97', 'Neustrezno', 0);

UPDATE misc SET value='09.06.24' where what="version";

DROP TABLE IF EXISTS srv_userSurveySetting ;
CREATE TABLE srv_userSurveySetting (
sid int(11) NOT NULL,
uid int(11) NOT NULL,
what varchar(200) NOT NULL,
value varchar(255) NOT NULL ,
PRIMARY KEY ( sid, uid),
UNIQUE (sid , uid, what)
) DEFAULT CHARSET=utf8 ;

# seznam profilov manjkajocih vrednosti in njihove nastavitve
DROP TABLE IF EXISTS srv_mising_profiles ;
CREATE TABLE srv_mising_profiles (
id int(11) NOT NULL AUTO_INCREMENT ,
uid int(11) NOT NULL default 0,
name varchar(200) NOT NULL ,
system TINYINT(1) not null default 0,
PRIMARY KEY (id),
UNIQUE (uid, name)
) DEFAULT CHARSET=utf8 ;

# nastavitve posameznih profilov
DROP TABLE IF EXISTS srv_mising_profiles_values ;
CREATE TABLE srv_mising_profiles_values (
pid int(11) NOT NULL,
fid int(11) NOT NULL,
means TINYINT(1) not null default 0,
crosstab TINYINT(1) not null default 0,
frequencies TINYINT(1) not null default 0,
descriptives TINYINT(1) not null default 0,
PRIMARY KEY (pid, fid),
UNIQUE (pid, fid)
) DEFAULT CHARSET=utf8 ;

# vstavimo privzeti filter (pid = 1)
INSERT INTO srv_mising_profiles_values (pid, fid, means, crosstab, frequencies, descriptives)
VALUES ('1', '-1', '0', '0', '0', '1'),
	   ('1', '-2', '0', '0', '0', '1'),
	   ('1', '-3', '0', '0', '0', '1'),
	   ('1', '-4', '0', '0', '0', '1'),
	   ('1', '99', '0', '0', '0', '1'),
	   ('1', '98', '0', '0', '0', '1'),
	   ('1', '97', '0', '0', '0', '1');
	   
INSERT INTO srv_mising_profiles(id, uid, name, system) VALUES ('1', 0, 'privzeti profil', '1');
UPDATE misc SET value='09.06.28' where what="version";
INSERT INTO srv_mising_profiles_values (pid, fid, means, crosstab, frequencies, descriptives)
VALUES ('1', '96', '0', '0', '0', '1');

UPDATE srv_mising_profiles_values SET means = '1', crosstab = '1' WHERE pid = 1 AND fid = -4 LIMIT 1;
UPDATE srv_mising_profiles_values SET means = '1', crosstab = '1' WHERE pid = 1 AND fid = -3 LIMIT 1;
UPDATE srv_mising_profiles_values SET means = '1', crosstab = '1' WHERE pid = 1 AND fid = -2 LIMIT 1;
UPDATE srv_mising_profiles_values SET means = '1', crosstab = '1' WHERE pid = 1 AND fid = -1 LIMIT 1;
UPDATE srv_mising_profiles_values SET means = '1'  WHERE pid = 1 AND fid = 99 LIMIT 1;
UPDATE srv_mising_profiles_values SET means = '1'  WHERE pid = 1 AND fid = 98 LIMIT 1;
UPDATE srv_mising_profiles_values SET means = '1'  WHERE pid = 1 AND fid = 97 LIMIT 1;
UPDATE srv_mising_profiles_values SET means = '1'  WHERE pid = 1 AND fid = 96 LIMIT 1;
UPDATE misc SET value='09.06.29' where what="version";

CREATE TABLE IF NOT EXISTS srv_calculation (
  id int(11) NOT NULL auto_increment,
  expression tinytext NOT NULL,
  PRIMARY KEY  (id)
);
UPDATE misc SET value='09.07.02' where what="version";


DROP TABLE IF EXISTS srv_calculation;
CREATE TABLE IF NOT EXISTS srv_calculation (
  id int(11) NOT NULL auto_increment,
  cnd_id int(11) NOT NULL,
  spr_id int(11) NOT NULL,
  vre_id int(11) NOT NULL,
  operator smallint(6) NOT NULL,
  left_bracket smallint(6) NOT NULL,
  right_bracket smallint(6) NOT NULL,
  vrstni_red int(11) NOT NULL,
  PRIMARY KEY  (id),
  KEY cnd_id (cnd_id),
  KEY spr_id (spr_id,vre_id)
) ;

UPDATE misc SET value='09.07.06' where what="version";

ALTER TABLE srv_calculation ADD number INT NOT NULL DEFAULT '0' AFTER operator;

UPDATE misc SET value='09.07.07' where what="version";


ALTER TABLE srv_anketa ADD phone TINYINT NOT NULL DEFAULT '0' AFTER user_base,
ADD email TINYINT NOT NULL DEFAULT '0' AFTER phone;

UPDATE misc SET value='09.07.08' where what="version";

ALTER TABLE srv_anketa ADD usercode_skip TINYINT NOT NULL DEFAULT '0' AFTER email,
ADD usercode_required TINYINT NOT NULL DEFAULT '0' AFTER usercode_skip,
ADD usercode_text varchar(255) not null AFTER usercode_required;

ALTER TABLE srv_mising_profiles ADD status0 TINYINT NOT NULL DEFAULT '0',
ADD status1 TINYINT NOT NULL DEFAULT '0', ADD status2 TINYINT NOT NULL DEFAULT '0',
ADD status3 TINYINT NOT NULL DEFAULT '0', ADD status4 TINYINT NOT NULL DEFAULT '0',
ADD status5 TINYINT NOT NULL DEFAULT '0';
UPDATE misc SET value='09.07.08' where what="version"; # datum 10.07.08 je tukaj napačen

CREATE TABLE srv_data_checkgrid (
 spr_id int(11) NOT NULL,
 vre_id int(11) NOT NULL,
 usr_id int(11) NOT NULL,
 grd_id int(11) NOT NULL,
 PRIMARY KEY (spr_id, vre_id, usr_id, grd_id)
);
UPDATE misc SET value='09.07.20' where what="version";

# zamenjamo [url] z #url#
UPDATE srv_userbase_setting SET text = replace(text, "[URL]", "#URL#");
#tabela z shranjenimi e-mail nagovori za userbase
DROP TABLE IF EXISTS srv_userbase_invitations;
CREATE TABLE srv_userbase_invitations (
id int(11) NOT NULL AUTO_INCREMENT ,
name varchar(100) NOT NULL,
subject varchar(200) NOT NULL,
text mediumtext NOT NULL,
PRIMARY KEY (id),
UNIQUE (name)
) DEFAULT CHARSET=utf8 ;
ALTER TABLE srv_userbase_invitations ADD INDEX ( name );
 
INSERT INTO srv_userbase_invitations (id, name, subject, text) VALUES
(1, 'Privzet text', 'Spletna anketa', '<p>Prosimo &#269;e si vzamete nekaj minut in izpolnite spodnjo anketo.</p><p>Hvala.</p><p>#URL#</p>');

UPDATE misc SET value='09.07.23' where what="version";

DROP TABLE IF EXISTS srv_userbase_respondents_lists;
CREATE TABLE srv_userbase_respondents_lists (
id int(11) NOT NULL AUTO_INCREMENT,
name varchar(100) NOT NULL,
variables text NOT NULL,
PRIMARY KEY (id),
UNIQUE (name)
) DEFAULT CHARSET=utf8 ;

DROP TABLE IF EXISTS srv_userbase_respondents;
CREATE TABLE srv_userbase_respondents (
id int(11) NOT NULL AUTO_INCREMENT,
list_id int(11) NOT NULL,
line text NOT NULL,
PRIMARY KEY (id)
) DEFAULT CHARSET=utf8 ;

ALTER TABLE srv_userbase_respondents ADD INDEX list_id (list_id);
UPDATE misc SET value='09.07.25' where what="version";

ALTER TABLE srv_mising_profiles CHANGE status4 status4 TINYINT NOT NULL DEFAULT '1',
CHANGE status5 status5 TINYINT NOT NULL DEFAULT '1';

UPDATE srv_mising_profiles SET status4 = '1', status5 = '1' WHERE id = 1;

CREATE TABLE srv_data_rating (
 spr_id int(11) NOT NULL,
 vre_id int(11) NOT NULL,
 usr_id int(11) NOT NULL,
 vrstni_red int(11) NOT NULL,
 PRIMARY KEY (spr_id, vre_id, usr_id, vrstni_red)
);

ALTER TABLE struktura_baze ADD COLUMN video_cols tinyint(1) not null default 2;
ALTER TABLE new ADD COLUMN video_cols tinyint(1) not null default 0;
UPDATE misc SET value='09.08.10' where what="version";

ALTER TABLE srv_data_rating DROP PRIMARY KEY,
ADD PRIMARY KEY (spr_id, vre_id, usr_id);

UPDATE misc SET value='09.09.01' where what="version";

ALTER TABLE srv_mising_profiles ADD statusnull TINYINT NOT NULL DEFAULT '1';
UPDATE misc SET value='09.09.07' where what="version";

#tipi ankete
# 0 -> Glasovanja
# 1 -> Enostavna anketa, Forma
# 2 -> Anketa na vec straneh
# 3 -> Anketa s pogoji
ALTER TABLE srv_anketa ADD survey_type TINYINT NOT NULL DEFAULT '2';
UPDATE srv_anketa SET survey_type = '3' WHERE branching = '1';
UPDATE misc SET value='09.09.09' where what="version";
#polje tip zamenjamo z poljem survey_type

UPDATE srv_anketa SET survey_type = '2' WHERE tip = '0';
UPDATE srv_anketa SET survey_type = '5' WHERE tip = '1';
UPDATE srv_anketa SET survey_type = '6' WHERE tip = '2';
UPDATE srv_anketa SET survey_type = '1' WHERE tip = '3';
UPDATE srv_anketa SET survey_type = '4' WHERE tip = '4';
UPDATE srv_anketa SET survey_type = '2' WHERE tip = '5';
#popravimo tam ko mamo branching
UPDATE srv_anketa SET survey_type = '3' WHERE branching = '1';

ALTER TABLE srv_anketa drop tip;
UPDATE misc SET value='09.09.11' where what="version";

ALTER TABLE srv_mising_profiles ADD merge_missing TINYINT( 1 ) not null default 0; 
ALTER TABLE srv_mising_profiles ADD show_zerro TINYINT( 1 ) not null default 0;
UPDATE misc SET value='09.09.22' where what="version";

ALTER TABLE srv_anketa ADD social_network tinyint(4) NOT NULL default 0 AFTER email;
UPDATE misc SET value='09.09.25' where what="version";

ALTER TABLE srv_spremenljivka ADD ranking_k tinyint(4) NOT NULL default 0;
UPDATE misc SET value='09.09.28' where what="version";

Alter Table users add column autologin tinyint(1) not null default 0;
UPDATE misc SET value='09.10.05' where what="version";

ALTER TABLE srv_mising_profiles CHANGE merge_missing merge_missing TINYINT( 1 ) NOT NULL DEFAULT '1',
CHANGE show_zerro show_zerro TINYINT( 1 ) NOT NULL DEFAULT '1';
UPDATE srv_mising_profiles SET merge_missing = '1', show_zerro = '1' WHERE id = 1;
UPDATE misc SET value='09.10.09' where what="version";

ALTER TABLE users ADD COLUMN lost_password varchar(255) not null default "";
ALTER TABLE users ADD COLUMN lost_password_code varchar(255) not null default "";

INSERT INTO misc (what, value) VALUES ('RegEmailActivate', '0');

CREATE TABLE users_to_be (
	id integer NOT NULL AUTO_INCREMENT,
	type TINYINT(1) NOT NULL DEFAULT 3,
	status tinyint(1) NOT NULL DEFAULT 1,
	infomail TINYINT(1) NOT NULL DEFAULT 0,
	approved TINYINT(1) NOT NULL DEFAULT 1,
	hour INTEGER NOT NULL DEFAULT 0,
	email VARCHAR(255) NOT NULL,
	name VARCHAR(50) NOT NULL DEFAULT 'Nepodpisani',
	surname VARCHAR(50) NOT NULL DEFAULT '',
	pass VARCHAR(255),
	came_from TINYINT(1) NOT NULL DEFAULT 0,
	phone1 VARCHAR(25) NOT NULL DEFAULT '',
	phone2 VARCHAR(25) NOT NULL DEFAULT '',
	when_reg DATE NOT NULL DEFAULT '2003-01-01',
	show_email TINYINT(1) NOT NULL DEFAULT 1,
	alert_cats MEDIUMTEXT NOT NULL,
	alert_freq tinyint(1) NOT NULL DEFAULT 0,
	user_groups integer NOT NULL,
	ehistory MEDIUMTEXT NOT NULL,
	my_url VARCHAR(255) NOT NULL DEFAULT '',
	avatar VARCHAR(255) NOT NULL DEFAULT '',
	about_me1 mediumtext NOT NULL,
	about_me2 mediumtext NOT NULL,
	rubrike mediumtext not null default "",
	pay_n mediumtext not null,
	pay_bid mediumtext not null,
	autologin tinyint(1) not null default 0,
	timecode varchar(15) not null default '',
	code varchar(255) not null default '',
	key id(id)
);




UPDATE misc SET value='09.10.11' where what="version";

ALTER TABLE srv_spremenljivka ADD vsota varchar(255) NOT NULL default '';

ALTER TABLE srv_spremenljivka ADD vsota_limit int(11) NOT NULL default 0;

 CREATE TABLE srv_data_textgrid (
 spr_id int(11) NOT NULL,
 vre_id int(11) NOT NULL,
 usr_id int(11) NOT NULL,
 grd_id int(11) NOT NULL,
 text varchar(255) NOT NULL default '',
 PRIMARY KEY (spr_id, vre_id, usr_id, grd_id)
);


ALTER TABLE mailnovice ADD COLUMN NiceLink varchar(255) not null default "";

UPDATE misc SET value='09.11.05' where what="version";

INSERT INTO srv_mising_profiles (id, uid, name, system, status0, status1, status2, status3, status4, status5, statusnull, merge_missing, show_zerro)
 VALUES(2, 0, 'Brez manjkajo&#269;ih vrednosti', 1, 0, 0, 0, 0, 1, 1, 1, 1, 1)
 ON DUPLICATE KEY UPDATE uid='0', name='Brez manjkajo&#269;ih vrednosti', system='1', 
 status0='0', status1='0', status2='0', status3='0', status4='1', status5='1', statusnull='1', merge_missing='1', show_zerro='1';

INSERT INTO srv_mising_profiles_values VALUES ('2', '-4', '1', '1', '1', '1')
 ON DUPLICATE KEY UPDATE means = '1', crosstab = '1', frequencies = '1', descriptives = '1';
INSERT INTO srv_mising_profiles_values VALUES ('2', '-3', '1', '1', '1', '1')
 ON DUPLICATE KEY UPDATE means = '1', crosstab = '1', frequencies = '1', descriptives = '1';
INSERT INTO srv_mising_profiles_values VALUES ('2', '-2', '1', '1', '1', '1')
 ON DUPLICATE KEY UPDATE means = '1', crosstab = '1', frequencies = '1', descriptives = '1';
INSERT INTO srv_mising_profiles_values VALUES ('2', '-1', '1', '1', '1', '1')
 ON DUPLICATE KEY UPDATE means = '1', crosstab = '1', frequencies = '1', descriptives = '1';
INSERT INTO srv_mising_profiles_values VALUES ('2', '99', '1', '1', '1', '1')
 ON DUPLICATE KEY UPDATE means = '1', crosstab = '1', frequencies = '1', descriptives = '1';
INSERT INTO srv_mising_profiles_values VALUES ('2', '98', '1', '1', '1', '1')
 ON DUPLICATE KEY UPDATE means = '1', crosstab = '1', frequencies = '1', descriptives = '1';
INSERT INTO srv_mising_profiles_values VALUES ('2', '97', '1', '1', '1', '1')
 ON DUPLICATE KEY UPDATE means = '1', crosstab = '1', frequencies = '1', descriptives = '1';
  
ALTER TABLE srv_spremenljivka ADD skala TINYINT NOT NULL DEFAULT '0';

UPDATE misc SET value='09.11.09' where what="version";

ALTER TABLE srv_anketa ADD form_open tinyint(4) NOT NULL default 0;


UPDATE misc SET value='09.11.14' where what="version";

DROP table IF EXISTS srv_variable_profiles;
CREATE TABLE srv_variable_profiles (
 id int(11) NOT NULL AUTO_INCREMENT ,
 sid int(11) NOT NULL default 0,
 uid int(11) NOT NULL default 0,
 name varchar(200) NOT NULL ,
 variables text NOT NULL,
 PRIMARY KEY (id),
 UNIQUE (sid, uid, name)
)  AUTO_INCREMENT = 1, DEFAULT CHARSET=utf8 ;

DROP TABLE IF EXISTS srv_userSurveySetting ;
CREATE TABLE srv_user_setting_for_survey (
sid int(11) NOT NULL,
uid int(11) NOT NULL,
what varchar(200) NOT NULL,
value varchar(255) NOT NULL ,
UNIQUE (sid , uid, what)
) DEFAULT CHARSET=utf8 ;

UPDATE misc SET value='09.11.16' where what="version";

ALTER TABLE srv_spremenljivka ADD vsota_min int(11) NOT NULL default 0 AFTER vsota_limit;
 
ALTER TABLE srv_spremenljivka ADD vsota_reminder tinyint(4) NOT NULL default 0;

UPDATE misc SET value='09.11.21' where what="version";

UPDATE srv_mising_profiles SET name = 'Brez m.v.' where name = 'Brez manjkajo&#269;ih vrednosti';
UPDATE srv_mising_profiles SET statusnull='0' where system = '1';

DROP table IF EXISTS srv_variable_profiles;
CREATE TABLE srv_variable_profiles (
 id int(11) NOT NULL,
 sid int(11) NOT NULL default 0,
 uid int(11) NOT NULL default 0,
 name varchar(200) NOT NULL ,
 system TINYINT(1) not null default 0,
 variables text NOT NULL,
 PRIMARY KEY (id,sid,uid),
 UNIQUE (id, sid, uid, name)
) DEFAULT CHARSET=utf8 ;

UPDATE misc SET value='09.11.27' where what="version";

CREATE TABLE srv_glasovanje (
 ank_id int(11) NOT NULL,
 spr_id int(11) NOT NULL,
 show_results tinyint(4) NOT NULL default 0,
 spol tinyint(4) NOT NULL default 0,
 PRIMARY KEY (ank_id, spr_id)
) DEFAULT CHARSET=utf8 ;

CREATE TABLE hour_views (uid integer not null default -900, pid integer not null default -900, lact integer not null default 1, viewer integer not null default 0, timestamp datetime not null, ip varchar(100) not null default '');

ALTER TABLE srv_dostop ADD alert_author TINYINT(1) NOT NULL DEFAULT 0;
UPDATE misc SET value='09.12.02' where what="version";

CREATE TABLE srv_data_glasovanje (
 spr_id int(11) NOT NULL,
 usr_id int(11) NOT NULL,
 spol int(11) NOT NULL,
 PRIMARY KEY (spr_id, usr_id)
);
UPDATE misc SET value='09.12.03' where what="version";
UPDATE srv_mising_profiles SET statusnull='1' where system = '1';
UPDATE srv_mising_profiles_values SET fid = '-99' WHERE fid = 99;
UPDATE srv_mising_profiles_values SET fid = '-98' WHERE fid = 98;
UPDATE srv_mising_profiles_values SET fid = '-97' WHERE fid = 97;
UPDATE misc SET value='09.12.08' where what="version";

ALTER table data_baze add column meta_pagename varchar(255) not null default "";
ALTER table data_baze add column meta_keywords mediumtext not null default "";
ALTER table data_baze add column meta_desc mediumtext not null default "";

INSERT INTO misc (what, value) VALUES ('analitics', '');

UPDATE misc SET value='09.12.15' where what="version";

ALTER TABLE srv_spremenljivka ADD vsota_limittype tinyint(4) NOT NULL default 0 AFTER vsota_reminder;

DROP TABLE IF EXISTS srv_respondent_profiles;
CREATE TABLE srv_respondent_profiles (
id int(11) NOT NULL AUTO_INCREMENT,
uid int(11) NOT NULL,
name varchar(100) NOT NULL,
variables text NOT NULL,
PRIMARY KEY (id),
UNIQUE (uid, name)
) DEFAULT CHARSET=utf8 ;


DROP TABLE IF EXISTS srv_respondents;
CREATE TABLE srv_respondents (
pid int(11) NOT NULL,
line text NOT NULL
) DEFAULT CHARSET=utf8 ;

CREATE TABLE srv_filter_profiles (
id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
ank_id INT NOT NULL ,
uid INT NOT NULL ,
name VARCHAR( 250 ) NOT NULL ,
if_id INT NOT NULL
) ;

INSERT INTO misc (what, value) VALUES ('user_see_hour_views', '0');

UPDATE misc SET value='09.12.17' where what="version";

INSERT INTO misc (what, value) VALUES ('ShortPageName', '');


ALTER TABLE srv_filter_profiles CHANGE ank_id sid INT( 11 ) NOT NULL;
ALTER TABLE srv_filter_profiles CHANGE id id INT( 11 ) NOT NULL;
ALTER TABLE srv_filter_profiles DROP PRIMARY KEY ;

DELETE FROM srv_mising_profiles_values WHERE pid='1';
DELETE FROM srv_mising_profiles_values WHERE pid='2';
INSERT INTO srv_mising_profiles_values (pid, fid, means, crosstab, frequencies, descriptives)
VALUES ('1', '-1', '1', '0', '0', '1'),
	   ('1', '-2', '1', '0', '0', '1'),
	   ('1', '-3', '1', '0', '0', '1'),
	   ('1', '-4', '1', '0', '0', '1'),
	   ('1', '-99', '1', '0', '0', '1'),
	   ('1', '-98', '1', '0', '0', '1'),
	   ('1', '-97', '1', '0', '0', '1'),
	   ('1', '-96', '1', '0', '0', '1');
INSERT INTO srv_mising_profiles_values (pid, fid, means, crosstab, frequencies, descriptives)
VALUES ('2', '-1', '1', '1', '1', '1'),
	   ('2', '-2', '1', '1', '1', '1'),
	   ('2', '-3', '1', '1', '1', '1'),
	   ('2', '-4', '1', '1', '1', '1'),
	   ('2', '-99', '1', '1', '1', '1'),
	   ('2', '-98', '1', '1', '1', '1'),
	   ('2', '-97', '1', '1', '1', '1'),
	   ('2', '-96', '1', '1', '1', '1');

DROP TABLE IF EXISTS srv_invitations_profiles;
CREATE TABLE srv_invitations_profiles (
id int(11) NOT NULL AUTO_INCREMENT,
uid int(11) NOT NULL,
name varchar(100) NOT NULL,
subject varchar(100) NOT NULL,
content text NOT NULL,
PRIMARY KEY (id),
UNIQUE (uid, name)
) DEFAULT CHARSET=utf8 ;

ALTER TABLE srv_filter_profiles ADD UNIQUE (id ,sid ,uid);

ALTER TABLE srv_filter_profiles CHANGE id id INT( 11 ) NOT NULL AUTO_INCREMENT ;

INSERT INTO srv_invitations_profiles (id, uid, name, subject, content) VALUES
(1, 0, 'Privzet profil', 'Spletna anketa', '<p>Prosimo &#269;e si vzamete nekaj minut in izpolnite spodnjo anketo.</p><p>Hvala.</p><p>#URL#</p>');
UPDATE misc SET value='09.12.18' where what="version";

ALTER table srv_user Add column last_status tinyint(4) NOT NULL DEFAULT -1;

# Potrebno zagnati skripto: utils/setLastStatus.php

ALTER TABLE srv_glasovanje ADD stat_count tinyint(4) NOT NULL default 0;
ALTER TABLE srv_glasovanje ADD stat_time tinyint(4) NOT NULL default 0;

UPDATE misc SET value='09.12.21' where what="version";

ALTER TABLE srv_anketa ADD statistics TEXT NOT NULL AFTER conclusion;

INSERT INTO misc (what, value) VALUES ('SurveyForum', '0');

UPDATE misc SET value='09.12.23' where what="version";

ALTER TABLE srv_spremenljivka ADD thread INT NOT NULL DEFAULT '0';
ALTER TABLE srv_anketa ADD forum INT NOT NULL DEFAULT '0';

ALTER TABLE srv_anketa ADD user_from_cms_email INT NOT NULL DEFAULT '0' AFTER user_from_cms;

ALTER TABLE srv_glasovanje ADD show_graph tinyint(4) NOT NULL default 0 AFTER show_results;
ALTER TABLE srv_glasovanje ADD show_percent tinyint(4) NOT NULL default 0 AFTER show_results;
ALTER TABLE srv_glasovanje ADD embed tinyint(4) NOT NULL default 0;
ALTER TABLE srv_glasovanje ADD show_title tinyint(4) NOT NULL default 0;

ALTER TABLE srv_anketa ADD thread INT NOT NULL;

# Tole odkomentiraj samo za enkrat da ne bo sranja
# use sispletvideo
# ALTER TABLE videos ADD COLUMN screenie varchar(9) not null default '00:00:05';

ALTER TABLE srv_anketa ADD survey_comment_show INT NOT NULL DEFAULT '0' AFTER countType;

UPDATE misc SET value='10.01.05' where what="version";

# A je blo tole ponesreci zbrisano?
ALTER TABLE srv_dostop ADD alert_author_expire TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE srv_anketa ADD alert_admin_expire TINYINT(1) NOT NULL DEFAULT '0' AFTER alert_more;
ALTER TABLE srv_anketa ADD alert_more_expire TINYINT(1) NOT NULL DEFAULT '0' AFTER alert_admin_expire;
ALTER TABLE srv_anketa ADD alert_expire_days INT NOT NULL DEFAULT '3' AFTER alert_more_expire;

CREATE TABLE srv_alert_expire (
    ank_id INT NOT NULL ,
    emails MEDIUMTEXT NOT NULL ,
    text TEXT NOT NULL ,
    subject VARCHAR( 250 ) NOT NULL , 
    PRIMARY KEY ( ank_id )
);
UPDATE misc SET value='10.01.07' where what="version";
# / A je blo tole ponesreci zbrisano?

# Ja tudi May-u se zgodi da ob komitanju pobriše kaj kar nebi smel :)
# Itak, nalašč ni bilo brisano nič...

INSERT INTO misc (what, value) VALUES ('user_see_hour_views_meta', '0');
ALTER TABLE hour_views ADD COLUMN meta tinyint(1) not null default 0;

DROP table IF EXISTS srv_zanka_profiles;
CREATE TABLE srv_zanka_profiles (
 id int(11) NOT NULL,
 sid int(11) NOT NULL default 0,
 uid int(11) NOT NULL default 0,
 name varchar(200) NOT NULL ,
 system TINYINT(1) not null default 0,
 variables text NOT NULL,
 PRIMARY KEY (id,sid,uid),
 UNIQUE (id, sid, uid, name)
) DEFAULT CHARSET=utf8 ;


INSERT INTO misc (what, value) VALUES ('user_see_forum_views', '0');

ALTER TABLE srv_anketa ADD vote_limit tinyint(4) NOT NULL default 0;
ALTER TABLE srv_anketa ADD vote_count int(11) NOT NULL default 0;

alter table forum add fulltext index (NiceLink);
alter table post add fulltext index (NiceLink);
alter table data_baze add fulltext index (NiceLink);
alter table novice add fulltext index (NiceLink);
alter table aktualno add fulltext index (NiceLink);
alter table faq add fulltext index (NiceLink);
alter table vodic add fulltext index (NiceLink);
alter table rubrika1 add fulltext index (NiceLink);
alter table rubrika2 add fulltext index (NiceLink);
alter table rubrika3 add fulltext index (NiceLink);
alter table rubrika4 add fulltext index (NiceLink);
alter table forum add fulltext index (NiceLink);

UPDATE misc SET value='10.01.10' where what="version";

ALTER TABLE srv_zanka_profiles ADD mnozenje INT NOT NULL DEFAULT '0';

# spodnje pozeni po potrebi (mogoce najprej preveri ali sploh je kak zapis: SELECT * FROM srv_variable_profiles WHERE variables REGEXP '_')
# za odpravo profilov variabel ki imajo '_' med variablami zaradi bug-a  
#DELETE FROM srv_variable_profiles WHERE variables REGEXP '_'

ALTER TABLE srv_spremenljivka ADD text_kosov tinyint(4) NOT NULL default 1;

ALTER TABLE srv_anketa ADD question_administration_comment TINYINT NOT NULL DEFAULT '0' AFTER survey_comment_show;

ALTER TABLE srv_anketa ADD comment_view_adminonly TINYINT NOT NULL DEFAULT '0' AFTER countType;

ALTER TABLE srv_anketa ADD survey_comment TINYINT NOT NULL DEFAULT '0' AFTER survey_comment_show;

ALTER TABLE srv_anketa ADD lang_admin TINYINT NOT NULL , ADD lang_resp TINYINT NOT NULL ;

UPDATE srv_anketa SET lang_admin = (SELECT val FROM display WHERE id = '5'), lang_resp = (SELECT val FROM display WHERE id = '5');

UPDATE misc SET value='10.01.13' where what="version";

ALTER TABLE srv_spremenljivka ADD text_orientation tinyint(4) NOT NULL default 0;

ALTER TABLE srv_anketa CHANGE lang_admin lang_admin TINYINT( 4 ) NOT NULL DEFAULT '1';
ALTER TABLE srv_anketa CHANGE lang_resp lang_resp TINYINT( 4 ) NOT NULL DEFAULT '1';

DROP TABLE IF EXISTS srv_vejitve;

# polja so bila prenesena v srv_alert
drop TABLE srv_alert_expire;
ALTER TABLE srv_anketa drop alert_admin_expire;
ALTER TABLE srv_anketa drop alert_more_expire;
ALTER TABLE srv_anketa drop alert_expire_days;

# polja za obvestila ob izteku ankete (skupaj z alert_author_expire v srv_dostop ki je že dodano)
ALTER TABLE srv_alert ADD expire_days INT NOT NULL DEFAULT '3' ;
ALTER TABLE srv_alert ADD expire_author TINYINT(1) NOT NULL DEFAULT '0' ;
ALTER TABLE srv_alert ADD expire_other TINYINT(1) NOT NULL DEFAULT '0' ;
ALTER TABLE srv_alert ADD expire_other_emails MEDIUMTEXT NOT NULL;
ALTER TABLE srv_alert ADD expire_text TEXT NOT NULL;
ALTER TABLE srv_alert ADD expire_subject VARCHAR( 250 ) NOT NULL;

# polja za obvestila ob brisanju  ankete 
ALTER TABLE srv_dostop ADD alert_author_delete TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE srv_alert ADD delete_author TINYINT(1) NOT NULL DEFAULT '0' ;
ALTER TABLE srv_alert ADD delete_other TINYINT(1) NOT NULL DEFAULT '0' ;
ALTER TABLE srv_alert ADD delete_other_emails MEDIUMTEXT NOT NULL;
ALTER TABLE srv_alert ADD delete_text TEXT NOT NULL;
ALTER TABLE srv_alert ADD delete_subject VARCHAR( 250 ) NOT NULL;

# polja za obvestila ob spremembi aktivnosti ankete 
ALTER TABLE srv_dostop ADD alert_author_active TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE srv_alert ADD active_author TINYINT(1) NOT NULL DEFAULT '0' ;
ALTER TABLE srv_alert ADD active_other TINYINT(1) NOT NULL DEFAULT '0' ;
ALTER TABLE srv_alert ADD active_other_emails MEDIUMTEXT NOT NULL;
ALTER TABLE srv_alert ADD active_text0 TEXT NOT NULL;
ALTER TABLE srv_alert ADD active_subject0 VARCHAR( 250 ) NOT NULL;
ALTER TABLE srv_alert ADD active_text1 TEXT NOT NULL;
ALTER TABLE srv_alert ADD active_subject1 VARCHAR( 250 ) NOT NULL;

UPDATE misc SET value='10.01.14' where what="version";

ALTER TABLE srv_dostop 
CHANGE alert_author alert_complete TINYINT( 1 ) NOT NULL DEFAULT '0',
CHANGE alert_author_expire alert_expire TINYINT( 1 ) NOT NULL DEFAULT '0',
CHANGE alert_author_delete alert_delete TINYINT( 1 ) NOT NULL DEFAULT '0',
CHANGE alert_author_active alert_active TINYINT( 1 ) NOT NULL DEFAULT '0';

#odkomentiraj ce se nimas spodnje baze
#CREATE DATABASE surveycrontab CHARSET = utf8;
#USE surveycrontab;
#CREATE TABLE IF NOT EXISTS srv_alert ( id int(11) NOT NULL AUTO_INCREMENT, dbname varchar(100) DEFAULT NULL, sid int(11) DEFAULT NULL, emails mediumtext NOT NULL, text text NOT NULL, subject varchar(250) NOT NULL, send_date date NOT NULL, status tinyint(1) NOT NULL DEFAULT '0', emails_success mediumtext NOT NULL, emails_failed mediumtext NOT NULL, KEY id (id) ) ENGINE = InnoDB DEFAULT CHARSET = utf8;
# ne pozabi grantat privilegijev na select, insert, update za zgornjo bazo!

# nova polja, bodo zamenjala tista v srv_anketa 
ALTER TABLE srv_alert ADD finish_respondent TINYINT(1) NOT NULL DEFAULT '0' AFTER ank_id;
ALTER TABLE srv_alert ADD finish_respondent_cms TINYINT(1) NOT NULL DEFAULT '0' AFTER finish_respondent;
ALTER TABLE srv_alert ADD finish_author TINYINT(1) NOT NULL DEFAULT '0' AFTER finish_respondent_cms;
ALTER TABLE srv_alert ADD finish_other TINYINT(1) NOT NULL DEFAULT '0' AFTER finish_author;
ALTER TABLE srv_alert 
CHANGE emails finish_other_emails MEDIUMTEXT NOT NULL,
CHANGE text finish_text TEXT NOT NULL,
CHANGE subject finish_subject VARCHAR( 250 );

# updejtamo polja iz srv_anketa v srv_alert
INSERT INTO srv_alert (ank_id, finish_respondent) SELECT id, alert_respondent FROM srv_anketa WHERE alert_respondent = 1 ON DUPLICATE KEY UPDATE finish_respondent='1';
INSERT INTO srv_alert (ank_id, finish_respondent_cms) SELECT id, alert_avtor FROM srv_anketa WHERE alert_avtor = 1 ON DUPLICATE KEY UPDATE finish_respondent_cms='1';
INSERT INTO srv_alert (ank_id, finish_author) SELECT id, alert_admin FROM srv_anketa WHERE alert_admin = 1 ON DUPLICATE KEY UPDATE finish_author='1';
INSERT INTO srv_alert (ank_id, finish_other) SELECT id, alert_more FROM srv_anketa WHERE alert_more = 1 ON DUPLICATE KEY UPDATE finish_other='1';




# od zdaj naprej naj bodo vse tabele za anketo (srv_*) InnoDB
ALTER TABLE srv_alert ENGINE = InnoDB;
ALTER TABLE srv_anketa ENGINE = InnoDB;
ALTER TABLE srv_branching ENGINE = InnoDB;
ALTER TABLE srv_calculation ENGINE = InnoDB;
ALTER TABLE srv_call_current ENGINE = InnoDB;
ALTER TABLE srv_call_history ENGINE = InnoDB;
ALTER TABLE srv_call_schedule ENGINE = InnoDB;
ALTER TABLE srv_call_setting ENGINE = InnoDB;
ALTER TABLE srv_condition ENGINE = InnoDB;
ALTER TABLE srv_condition_grid ENGINE = InnoDB;
ALTER TABLE srv_condition_vre ENGINE = InnoDB;
ALTER TABLE srv_data_checkgrid ENGINE = InnoDB;
ALTER TABLE srv_data_glasovanje ENGINE = InnoDB;
ALTER TABLE srv_data_grid ENGINE = InnoDB;
ALTER TABLE srv_data_imena ENGINE = InnoDB;
ALTER TABLE srv_data_number ENGINE = InnoDB;
ALTER TABLE srv_data_rating ENGINE = InnoDB;
ALTER TABLE srv_data_text ENGINE = InnoDB;
ALTER TABLE srv_data_textgrid ENGINE = InnoDB;
ALTER TABLE srv_data_vrednost ENGINE = InnoDB;
ALTER TABLE srv_dostop ENGINE = InnoDB;
ALTER TABLE srv_filter_profiles ENGINE = InnoDB;
ALTER TABLE srv_folder ENGINE = InnoDB;
ALTER TABLE srv_glasovanje ENGINE = InnoDB;
ALTER TABLE srv_grid ENGINE = InnoDB;
ALTER TABLE srv_grupa ENGINE = InnoDB;
ALTER TABLE srv_help ENGINE = InnoDB;
ALTER TABLE srv_if ENGINE = InnoDB;
ALTER TABLE srv_invitations_profiles ENGINE = InnoDB;
ALTER TABLE srv_library_anketa ENGINE = InnoDB;
ALTER TABLE srv_library_folder ENGINE = InnoDB;
ALTER TABLE srv_misc ENGINE = InnoDB;
ALTER TABLE srv_mising_profiles ENGINE = InnoDB;
ALTER TABLE srv_mising_profiles_values ENGINE = InnoDB;
ALTER TABLE srv_respondents ENGINE = InnoDB;
ALTER TABLE srv_respondent_profiles ENGINE = InnoDB;
ALTER TABLE srv_specialdata_vrednost ENGINE = InnoDB;
ALTER TABLE srv_spremenljivka ENGINE = InnoDB;
ALTER TABLE srv_survey_misc ENGINE = InnoDB;
ALTER TABLE srv_sys_filters ENGINE = InnoDB;
ALTER TABLE srv_tracking ENGINE = InnoDB;
ALTER TABLE srv_user ENGINE = InnoDB;
ALTER TABLE srv_userbase ENGINE = InnoDB;
ALTER TABLE srv_userbase_invitations ENGINE = InnoDB;
ALTER TABLE srv_userbase_respondents ENGINE = InnoDB;
ALTER TABLE srv_userbase_respondents_lists ENGINE = InnoDB;
ALTER TABLE srv_userbase_setting ENGINE = InnoDB;
ALTER TABLE srv_userstatus ENGINE = InnoDB;
ALTER TABLE srv_user_grupa ENGINE = InnoDB;
ALTER TABLE srv_user_setting_for_survey ENGINE = InnoDB;
ALTER TABLE srv_user_setting_misc ENGINE = InnoDB;
ALTER TABLE srv_variable_profiles ENGINE = InnoDB;
ALTER TABLE srv_vrednost ENGINE = InnoDB;
ALTER TABLE srv_zanka_profiles ENGINE = InnoDB;

CREATE TABLE srv_data_grid_active (
spr_id int( 11 ) NOT NULL ,
vre_id int( 11 ) NOT NULL ,
usr_id int( 11 ) NOT NULL ,
grd_id int( 11 ) NOT NULL ,
PRIMARY KEY ( spr_id, vre_id , usr_id ) ,
KEY vre_id ( vre_id , usr_id ) ,
KEY usr_id ( usr_id )
) ENGINE = InnoDB DEFAULT CHARSET = latin1;

CREATE TABLE srv_data_vrednost_active (
spr_id int( 11 ) NOT NULL ,
vre_id int( 11 ) NOT NULL DEFAULT '0',
usr_id int( 11 ) NOT NULL DEFAULT '0',
PRIMARY KEY ( spr_id , vre_id , usr_id ) ,
KEY spr_id ( spr_id , usr_id ) ,
KEY vre_usr ( vre_id , usr_id ) ,
KEY usr_id ( usr_id )
) ENGINE = InnoDB DEFAULT CHARSET = latin1;

CREATE TABLE srv_user_grupa_active (
gru_id int( 11 ) NOT NULL ,
usr_id int( 11 ) NOT NULL ,
time_edit datetime NOT NULL ,
PRIMARY KEY ( gru_id , usr_id ) ,
KEY usr_id ( usr_id )
) ENGINE = InnoDB DEFAULT CHARSET = latin1;

ALTER TABLE srv_anketa ADD db_table TINYINT NOT NULL DEFAULT '0' AFTER active;

UPDATE misc SET value='10.01.18' where what="version";

ALTER TABLE srv_anketa CHANGE countType countType TINYINT( 1 ) NOT NULL DEFAULT '0';

INSERT INTO misc (what, value) VALUES ('ForumNewInicialke', '0'); 

ALTER TABLE srv_anketa CHANGE comment_view_adminonly survey_comment_view_adminonly TINYINT( 4 ) NOT NULL DEFAULT '0';

ALTER TABLE srv_anketa DROP survey_comment_view_adminonly,
DROP survey_comment_show,
DROP survey_comment,
DROP question_administration_comment,
DROP question_comment;

ALTER TABLE srv_glasovanje ALTER show_graph SET DEFAULT 1;
ALTER TABLE srv_glasovanje ALTER show_percent SET DEFAULT 1;

ALTER TABLE srv_glasovanje ALTER stat_time SET DEFAULT 1;
ALTER TABLE srv_glasovanje ALTER stat_count SET DEFAULT 1;

# Odkomentiraj za updejt v bazi: surveycrontab 
# USE surveycrontab;
# ALTER TABLE srv_alert ADD MailFrom varchar(100) DEFAULT NULL;
# ALTER TABLE srv_alert ADD MailReply varchar(100) DEFAULT NULL;

ALTER TABLE srv_tracking DROP INDEX ank_id;
ALTER TABLE srv_tracking DROP INDEX datetime;
ALTER TABLE srv_user ADD INDEX (ank_id) ;

CREATE TABLE direct_access (id integer not null auto_increment, ip varchar(65), browser_id varchar(10), browser_ver varchar(4), as_user integer, key id(id));

UPDATE misc SET value='10.01.25' where what="version";

CREATE INDEX bid_index ON data_baze(bid);
CREATE INDEX admin_index ON data_baze(admin);
CREATE INDEX parent_index ON menu(parent);
CREATE INDEX admin_index ON menu(admin);
CREATE INDEX user_index ON menu(user);
CREATE INDEX admin_index ON forum(admin);
CREATE INDEX user_index ON forum(user);
CREATE INDEX fid_index ON post(fid);
CREATE INDEX tid_index ON post(tid);
CREATE INDEX admin_index ON post(admin);

ALTER TABLE srv_spremenljivka CHANGE skala skala TINYINT NOT NULL DEFAULT '1';
ALTER TABLE srv_vrednost ADD size int(11) NOT NULL default 0;
UPDATE srv_invitations_profiles SET content = '<p>Prosimo &#269;e si vzamete nekaj minut in izpolnite spodnjo anketo.</p><p>Hvala.</p><p>#URL#</p>' WHERE id = 1 LIMIT 1 ;

UPDATE misc SET value='10.01.28' where what="version";

ALTER TABLE srv_anketa ADD concl_back_button TINYINT NOT NULL DEFAULT '1' AFTER concl_link;

UPDATE misc SET value='10.01.29' where what="version";

alter table misc change value value mediumtext;
CREATE INDEX uid_index ON hour_work(uid);
CREATE INDEX pid_index ON hour_work(pid);
CREATE INDEX uid_index ON hour_paid(uid);

ALTER TABLE srv_spremenljivka CHANGE skala skala TINYINT NOT NULL DEFAULT '-1';
CREATE INDEX id_index ON hour_users(id);
CREATE INDEX id_index ON hour_project(id);

alter table post drop index user;
CREATE INDEX time2_index ON post(time2);
CREATE INDEX date_index ON hour_work(date);
CREATE INDEX uid_index ON views_forum(uid);

CREATE TABLE db_index (id long, dbid integer, naslov varchar(255), vsebina mediumtext, NiceLink varchar(255), factor tinyint(1), datum date, admin tinyint(1), fulltext(vsebina), index(dbid), index(datum));
CREATE TABLE forum_index (fid long, tid integer, admin tinyint(1), datum date, factor tinyint(1), vsebina mediumtext, naslov varchar(255), NiceLink varchar(255), fulltext(vsebina), index(datum));

UPDATE misc SET value='10.02.07' where what="version";

ALTER TABLE data_baze DROP index vir1;
ALTER TABLE data_baze DROP index virx1;
ALTER TABLE data_baze DROP index vir2;
ALTER TABLE data_baze DROP index db1;
ALTER TABLE data_baze DROP index leto;
ALTER TABLE data_baze DROP index published;
ALTER TABLE data_baze DROP index abstract;
ALTER TABLE data_baze DROP index abstract2;
ALTER TABLE data_baze DROP index opis1;
ALTER TABLE data_baze DROP index opis2;
ALTER TABLE data_baze DROP index res;
ALTER TABLE data_baze DROP index res2;
ALTER TABLE data_baze DROP index details;
ALTER TABLE data_baze DROP index desc1;
ALTER TABLE data_baze DROP index desc2;
ALTER TABLE data_baze DROP index char1;
ALTER TABLE data_baze DROP index char2;
ALTER TABLE post DROP index vsebina;
OPTIMIZE TABLE data_baze;
OPTIMIZE TABLE post;

ALTER TABLE srv_glasovanje ADD stat_archive tinyint(4) NOT NULL default 0;

# tabela z uporabniškimi nastavitvami sistema
CREATE TABLE srv_user_setting (
	usr_id int( 11 ) NOT NULL, 
	survey_list_order varchar( 255 ) NOT NULL default '',
	survey_list_order_by varchar( 20 ) NOT NULL default '',
	survey_list_rows_per_page INT NOT NULL DEFAULT '25',
	survey_list_visible varchar( 255 ) NOT NULL default '',
	survey_list_widths varchar( 255 ) NOT NULL default '',
	icons_always_on tinyint(1) not null default 0,
	full_screen_edit tinyint(1) not null default 0,
	PRIMARY KEY ( usr_id )
) ENGINE = InnoDB DEFAULT CHARSET = utf8;

UPDATE misc SET value='10.02.14' where what="version";
# CREATE TABLE menu_index (id long, user tinyint(1), admin tinyint(1), clan tinyint(1), factor tinyint(1), vsebina mediumtext, naslov varchar(255), NiceLink varchar(255), dbname varchar(255), fulltext(vsebina), index(dbname));

#
# Tole je za remote search, sicer polaufaj bolj ali manj samo na remote strezniku kjer se bo izvajal
#
# odkomentiraj po potrebi
# CREATE DATABASE sisplet_search;
# USE sisplet_search;
# CREATE TABLE db_index (id long, dbid integer, naslov varchar(255), vsebina mediumtext, NiceLink varchar(255), factor tinyint(1), datum date, admin tinyint(1), dbname varchar(255), fulltext(vsebina), index(dbid), index(datum), index(dbname));
# CREATE TABLE forum_index (fid long, tid integer, admin tinyint(1), datum date, factor tinyint(1), vsebina mediumtext, naslov varchar(255), NiceLink varchar(255), dbname varchar(255), fulltext(vsebina), index(datum), index(dbname));
# CREATE TABLE menu_index (id long, user tinyint(1), admin tinyint(1), clan tinyint(1), factor tinyint(1), vsebina mediumtext, naslov varchar(255), NiceLink varchar(255), dbname varchar(255), fulltext(vsebina), index(dbname));
# CREATE TABLE r_index (sid integer, nt integer, dostop tinyint(1), factor tinyint(1), vsebina mediumtext, naslov varchar(255), NiceLink varchar(255), dbname varchar(255), fulltext(vsebina), index(dbname));
# CREATE TABLE file_index (id integer auto_increment, filename varchar(255) not null default '', vsebina mediumtext, povezava varchar(255) not null default '', dbname varchar(255) not null default '', fulltext(vsebina), index(dbname), key id(id));


UPDATE srv_mising_profiles SET statusnull='0' where system = '1';

CREATE TABLE srv_activity (
	sid int(11) NOT NULL,
	starts DATE NOT NULL,
	expire DATE NOT NULL,
	uid int(11) NOT NULL,
	UNIQUE (sid, starts, expire,uid)
) ENGINE = InnoDB DEFAULT CHARSET = utf8;
UPDATE misc SET value='10.02.18' where what="version";

ALTER TABLE srv_glasovanje ADD skin varchar(100) NOT NULL default 'Modern';

ALTER TABLE srv_anketa ADD uporabnost TINYINT NOT NULL DEFAULT '0' AFTER alert_more;


UPDATE misc SET value='10.02.25' WHERE what="version";

ALTER TABLE srv_mising_profiles ADD status6 TINYINT NOT NULL DEFAULT '1' AFTER status5;

ALTER TABLE srv_mising_profiles CHANGE status4 status4 TINYINT NOT NULL DEFAULT '0',
CHANGE status5 status5 TINYINT NOT NULL DEFAULT '1';

UPDATE misc SET value='10.02.28' where what="version";

# sprememba statusov, ce se bo se kje poganjalo, sicer pa ni treba (zatem je treba zagnati se utils/setLastStatus.php)
#UPDATE srv_userstatus SET `status` = '7' WHERE `status` = '6';
#UPDATE srv_userstatus SET `status` = `status` +1 WHERE (`status` = '4' OR `status` = '5');
#UPDATE srv_userstatus SET `status` = '4' WHERE `status` = '7' ;

UPDATE srv_mising_profiles set status4 = '0', statusnull = '0' WHERE system=1;
ALTER TABLE srv_mising_profiles CHANGE status4 status4 TINYINT NOT NULL DEFAULT '0',
CHANGE statusnull statusnull TINYINT NOT NULL DEFAULT '0';

ALTER TABLE srv_dostop ADD aktiven TINYINT NOT NULL DEFAULT '1' AFTER uid;

UPDATE misc SET value='10.03.09' where what="version";

ALTER TABLE srv_anketa ADD quiz TINYINT NOT NULL DEFAULT '0' AFTER social_network;

#pocistimo tabele z mv profili
TRUNCATE TABLE srv_mising_profiles; 
TRUNCATE TABLE srv_mising_profiles_values; 
ALTER TABLE srv_mising_profiles ADD shrink_mv TINYINT NOT NULL DEFAULT '1';

INSERT INTO srv_mising_profiles (id, uid, name, system, status0, status1, status2, status3, status4, status5,  status6, statusnull, merge_missing, show_zerro, shrink_mv)
 VALUES(1, 0, 'Privzet profil', 1, 0, 0, 0, 0, 0, 1, 1, 0, 1, 0, 1)
 ON DUPLICATE KEY UPDATE uid='0', name='Privzet profil', system='1', 
 status0='0', status1='0', status2='0', status3='0', status4='0', status5='1', status6='1', statusnull='0', merge_missing='1', show_zerro='0', shrink_mv='1';

INSERT INTO srv_mising_profiles_values VALUES ('1', '-4', '1', '0', '0', '1') ON DUPLICATE KEY UPDATE means = '1', crosstab = '0', frequencies = '0', descriptives = '1';
INSERT INTO srv_mising_profiles_values VALUES ('1', '-3', '1', '0', '0', '1') ON DUPLICATE KEY UPDATE means = '1', crosstab = '0', frequencies = '0', descriptives = '1';
INSERT INTO srv_mising_profiles_values VALUES ('1', '-2', '1', '0', '0', '1') ON DUPLICATE KEY UPDATE means = '1', crosstab = '0', frequencies = '0', descriptives = '1';
INSERT INTO srv_mising_profiles_values VALUES ('1', '-1', '1', '0', '0', '1') ON DUPLICATE KEY UPDATE means = '1', crosstab = '0', frequencies = '0', descriptives = '1';
INSERT INTO srv_mising_profiles_values VALUES ('1', '-99', '1', '0', '0', '1') ON DUPLICATE KEY UPDATE means = '1', crosstab = '0', frequencies = '0', descriptives = '1';
INSERT INTO srv_mising_profiles_values VALUES ('1', '-98', '1', '0', '0', '1') ON DUPLICATE KEY UPDATE means = '1', crosstab = '0', frequencies = '0', descriptives = '1';
INSERT INTO srv_mising_profiles_values VALUES ('1', '-97', '1', '0', '0', '1') ON DUPLICATE KEY UPDATE means = '1', crosstab = '0', frequencies = '0', descriptives = '1';
INSERT INTO srv_mising_profiles_values VALUES ('1', '-96', '1', '0', '0', '1') ON DUPLICATE KEY UPDATE means = '1', crosstab = '0', frequencies = '0', descriptives = '1';

INSERT INTO srv_mising_profiles (id, uid, name, system, status0, status1, status2, status3, status4, status5,  status6, statusnull, merge_missing, show_zerro,shrink_mv)
 VALUES(2, 0, 'Brez manjkajo&#269;ih vrednosti', 1, 0, 0, 0, 0, 0, 1, 1, 0, 1, 0, 0)
 ON DUPLICATE KEY UPDATE uid='0', name='Brez manjkajo&#269;ih vrednosti', system='1', 
 status0='0', status1='0', status2='0', status3='0', status4='0', status5='1', status6='1', statusnull='0', merge_missing='1', show_zerro='0', shrink_mv='0';

INSERT INTO srv_mising_profiles_values VALUES ('2', '-4', '1', '0', '0', '1') ON DUPLICATE KEY UPDATE means = '1', crosstab = '0', frequencies = '0', descriptives = '1';
INSERT INTO srv_mising_profiles_values VALUES ('2', '-3', '1', '0', '0', '1') ON DUPLICATE KEY UPDATE means = '1', crosstab = '0', frequencies = '0', descriptives = '1';
INSERT INTO srv_mising_profiles_values VALUES ('2', '-2', '1', '0', '0', '1') ON DUPLICATE KEY UPDATE means = '1', crosstab = '0', frequencies = '0', descriptives = '1';
INSERT INTO srv_mising_profiles_values VALUES ('2', '-1', '1', '0', '0', '1') ON DUPLICATE KEY UPDATE means = '1', crosstab = '0', frequencies = '0', descriptives = '1';
INSERT INTO srv_mising_profiles_values VALUES ('2', '-99', '1', '0', '0', '1') ON DUPLICATE KEY UPDATE means = '1', crosstab = '0', frequencies = '0', descriptives = '1';
INSERT INTO srv_mising_profiles_values VALUES ('2', '-98', '1', '0', '0', '1') ON DUPLICATE KEY UPDATE means = '1', crosstab = '0', frequencies = '0', descriptives = '1';
INSERT INTO srv_mising_profiles_values VALUES ('2', '-97', '1', '0', '0', '1') ON DUPLICATE KEY UPDATE means = '1', crosstab = '0', frequencies = '0', descriptives = '1';
INSERT INTO srv_mising_profiles_values VALUES ('2', '-96', '1', '0', '0', '1') ON DUPLICATE KEY UPDATE means = '1', crosstab = '0', frequencies = '0', descriptives = '1';

UPDATE misc SET value='10.03.11' where what="version";

UPDATE srv_mising_profiles SET show_zerro = '1' WHERE id = 1;
UPDATE srv_mising_profiles SET show_zerro = '0' WHERE id = 2;
UPDATE srv_mising_profiles SET merge_missing = '1' WHERE id = 1;
UPDATE srv_mising_profiles SET merge_missing = '0' WHERE id = 2;

ALTER table struktura_baze ADD COLUMN PastEventsInt integer not null default 7;

UPDATE srv_mising_profiles SET show_zerro = '0' WHERE id = 1;
UPDATE srv_mising_profiles SET merge_missing = '0' WHERE id = 1;

UPDATE misc SET value='10.03.22' where what="version";

ALTER TABLE srv_user ADD preview TINYINT NOT NULL DEFAULT '0' AFTER ank_id; 

CREATE TABLE srv_statistic_profile (
 id int(11) NOT NULL auto_increment,
 uid int(11) NOT NULL default 0,
 name varchar(200) NOT NULL ,
 starts datetime DEFAULT NULL ,
 ends datetime DEFAULT NULL ,
 interval_txt varchar(100) DEFAULT NULL,
 PRIMARY KEY (id,uid),
 UNIQUE (id, uid, name)
) DEFAULT CHARSET=utf8 ;


INSERT INTO misc (what, value) SELECT 'SurveyLang_admin', val FROM display WHERE id='5';
INSERT INTO misc (what, value) SELECT 'SurveyLang_resp', val FROM display WHERE id='5';

UPDATE misc SET value='10.03.23' where what="version";

ALTER TABLE users add column remember_default tinyint(1) not null default '0';

UPDATE misc SET value='10.03.27' where what="version";
UPDATE srv_mising_profiles SET name = 'Brez m.v.' where name = 'Brez manjkajo&#269;ih vrednosti';


INSERT INTO srv_misc (what, value) VALUES
('timing_kategorija_1', '5'),
('timing_kategorija_16', '10'),
('timing_kategorija_17', '5'),
('timing_kategorija_18', '5'),
('timing_kategorija_19', '20'),
('timing_kategorija_2', '5'),
('timing_kategorija_20', '20'),
('timing_kategorija_3', '5'),
('timing_kategorija_6', '10'),
('timing_stran', '5'),
('timing_vprasanje_1', '10'),
('timing_vprasanje_16', '10'),
('timing_vprasanje_17', '10'),
('timing_vprasanje_18', '10'),
('timing_vprasanje_19', '10'),
('timing_vprasanje_2', '10'),
('timing_vprasanje_20', '10'),
('timing_vprasanje_21', '20'),
('timing_vprasanje_3', '10'),
('timing_vprasanje_4', '20'),
('timing_vprasanje_5', '10'),
('timing_vprasanje_6', '10'),
('timing_vprasanje_7', '10'),
('timing_vprasanje_8', '10');

UPDATE misc SET value='10.04.19' where what="version";

CREATE TABLE srv_status_profile (
 id int(11) NOT NULL auto_increment,
 uid int(11) NOT NULL default 0,
 name varchar(200) NOT NULL ,
 system  TINYINT(1) not null DEFAULT 0,
 statusnull TINYINT(1) not null DEFAULT 0,
 status0 TINYINT(1) NOT NULL DEFAULT 0,
 status1 TINYINT(1) NOT NULL DEFAULT 0, 
 status2 TINYINT(1) NOT NULL DEFAULT 0,
 status3 TINYINT(1) NOT NULL DEFAULT 0, 
 status4 TINYINT(1) NOT NULL DEFAULT 0,
 status5 TINYINT(1) NOT NULL DEFAULT 0, 
 status6 TINYINT(1) NOT NULL DEFAULT 0, 
 PRIMARY KEY (id,uid),
 UNIQUE (id, uid, name)
) DEFAULT CHARSET=utf8 ;

INSERT INTO srv_status_profile (id, uid, name, system, statusnull, status0, status1, status2, status3, status4, status5, status6 ) 
VALUES ('1', '0', 'Vsi statusi', 1, 1, 1, 1, 1, 1, 1, 1, 1);

UPDATE misc SET value='10.04.21' where what="version";

ALTER TABLE srv_user ADD testdata TINYINT NOT NULL DEFAULT '0' AFTER preview;

UPDATE misc SET value='10.04.30' where what="version";

ALTER TABLE data_baze change naslov naslov varchar(500);
ALTER TABLE novice change naslov naslov varchar(500);
ALTER TABLE aktualno change naslov naslov varchar(500);
ALTER TABLE vodic change naslov naslov varchar(500);
ALTER TABLE faq change naslov naslov varchar(500);
ALTER TABLE rubrika1 change naslov naslov varchar(500);
ALTER TABLE rubrika2 change naslov naslov varchar(500);
ALTER TABLE rubrika3 change naslov naslov varchar(500);
ALTER TABLE rubrika4 change naslov naslov varchar(500);

UPDATE misc SET value='10.05.04' where what="version";

ALTER TABLE forum_index ADD COLUMN pid  bigint  not null default 0;

UPDATE misc SET value='10.05.06' where what="version";

ALTER TABLE srv_user_grupa ADD preskocena TINYINT NOT NULL DEFAULT '0';
ALTER TABLE srv_user_grupa_active ADD preskocena TINYINT NOT NULL DEFAULT '0';

# pozeni utils/1ka_grupa_preskocena.php !!!

UPDATE misc SET value='10.05.07' where what="version";

CREATE TABLE srv_status_casi (
 id int(11) NOT NULL auto_increment,
 uid int(11) NOT NULL default 0,
 name varchar(200) NOT NULL ,
 system  TINYINT(1) not null DEFAULT 0,
 statusnull TINYINT(1) not null DEFAULT 0,
 status0 TINYINT(1) NOT NULL DEFAULT 0,
 status1 TINYINT(1) NOT NULL DEFAULT 0, 
 status2 TINYINT(1) NOT NULL DEFAULT 0,
 status3 TINYINT(1) NOT NULL DEFAULT 0, 
 status4 TINYINT(1) NOT NULL DEFAULT 0,
 status5 TINYINT(1) NOT NULL DEFAULT 0, 
 status6 TINYINT(1) NOT NULL DEFAULT 0, 
 PRIMARY KEY (id,uid),
 UNIQUE (id, uid, name)
) DEFAULT CHARSET=utf8 ;

INSERT INTO srv_status_casi (id, uid, name, system, statusnull, status0, status1, status2, status3, status4, status5, status6 ) 
VALUES ('1', '0', 'Koncal anketo', 1, 0, 0, 0, 0, 0, 0, 0, 1);
INSERT INTO srv_status_casi (id, uid, name, system, statusnull, status0, status1, status2, status3, status4, status5, status6 ) 
VALUES ('2', '0', 'Vsi statusi', 1, 1, 1, 1, 1, 1, 1, 1, 1);

UPDATE misc SET value='10.05.11' where what="version";

CREATE TABLE srv_analysis_archive (
 id int(11) NOT NULL auto_increment,
 sid int(11) NOT NULL default 0,
 uid int(11) NOT NULL default 0,
 name varchar(200) NOT NULL,
 filename varchar(50) NOT NULL,
 date datetime NOT NULL,
 PRIMARY KEY (id,sid)
);
UPDATE misc SET value='10.05.11a' where what="version";

ALTER TABLE struktura_avtor ADD COLUMN NiceLink varchar(255) not null default '';
ALTER TABLE struktura_vir ADD COLUMN NiceLink varchar(255) not null default '';
ALTER TABLE struktura_db ADD COLUMN NiceLink varchar(255) not null default '';
ALTER TABLE struktura_country ADD COLUMN NiceLink varchar(255) not null default '';
ALTER TABLE struktura_companies ADD COLUMN NiceLink varchar(255) not null default '';

#
# HTACCESS REMINDER
# v root vpisi
# RewriteRule ^dba/(.*?[^/])/(.*)                     /index.php?fl=2&lact=4&avtor=$1&%{QUERY_STRING}
# RewriteRule ^dbv/(.*?[^/])/(.*)                     /index.php?fl=2&lact=8&vir=$1&%{QUERY_STRING}
# RewriteRule ^dbdb/(.*?[^/])/(.*)                     /index.php?fl=2&lact=7&db=$1&%{QUERY_STRING}
# RewriteRule ^dbcm/(.*?[^/])/(.*)                     /index.php?fl=2&lact=5&companies=$1&%{QUERY_STRING}
# RewriteRule ^dbct/(.*?[^/])/(.*)                     /index.php?fl=2&lact=6&country=$1&%{QUERY_STRING}

ALTER TABLE srv_analysis_archive ADD note varchar(200) NOT NULL;
ALTER TABLE srv_analysis_archive ADD access TINYINT NOT NULL DEFAULT 0; # 0 - vidijo vsi, 1 - vidijo samo uporabniki z dostopom
ALTER TABLE srv_analysis_archive ADD type TINYINT NOT NULL DEFAULT 0; # 0 - sumarnik, 1 - opisne, 2 - frekvence, 3 - crostabi

UPDATE misc SET value='10.05.12' where what="version";

# samo za index bazo
# CREATE TABLE ank_index (id bigint, naslov varchar(255), vsebina mediumtext fulltext index, factor integer, dbname varchar(255));
# pozabil....
# ALTER TABLE ank_index add fulltext index(vsebina);
# ALTER TABLE ank_index add fulltext index(naslov);


ALTER TABLE srv_user ADD lurker TINYINT NOT NULL DEFAULT '1';
UPDATE srv_user SET lurker = '0'; # <-- ne pozabi podpicja :)

ALTER TABLE srv_status_profile ADD statuslurker TINYINT( 1 ) NOT NULL DEFAULT '0';
UPDATE srv_status_profile SET statuslurker='1' WHERE id='1' AND uid='0';

ALTER TABLE srv_status_casi ADD statuslurker TINYINT( 1 ) NOT NULL DEFAULT '0';
UPDATE srv_status_casi SET statuslurker='0' WHERE id='1' AND uid='0';
UPDATE srv_status_casi SET statuslurker='1' WHERE id='2' AND uid='0';

ALTER TABLE srv_analysis_archive ADD duration date NOT NULL;
UPDATE srv_analysis_archive SET duration = DATE_ADD(NOW(),INTERVAL 3 MONTH) WHERE duration = '0000-00-00';

UPDATE misc SET value='10.05.18' where what="version";

ALTER TABLE srv_analysis_archive ADD editid int(11) NOT NULL default 0;
UPDATE srv_analysis_archive SET editid = uid;
UPDATE misc SET value='10.05.19' where what="version";

ALTER TABLE srv_anketa CHANGE akronim akronim VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL;

UPDATE misc SET value='10.05.26' where what="version";

UPDATE srv_mising_profiles SET name = 'Privzet' WHERE id=1;
UPDATE srv_zanka_profiles SET name = 'Brez' WHERE id=1;
UPDATE srv_variable_profiles SET name = 'Vse' WHERE id=1;
UPDATE srv_filter_profiles SET name = 'Brez' WHERE id=1;

ALTER TABLE menu ADD COLUMN p1 integer not null default 0;
ALTER TABLE menu ADD COLUMN p2 integer not null default 0;
ALTER TABLE menu ADD COLUMN p3 integer not null default 0;
ALTER TABLE menu ADD COLUMN p4 integer not null default 0;
ALTER TABLE menu ADD COLUMN p5 integer not null default 0;
ALTER TABLE menu ADD COLUMN p6 integer not null default 0;
ALTER TABLE menu ADD COLUMN p7 integer not null default 0;
ALTER TABLE menu ADD COLUMN p8 integer not null default 0;
ALTER TABLE menu ADD COLUMN p9 integer not null default 0;
ALTER TABLE menu ADD COLUMN ps tinyint(1) not null default 0;

UPDATE misc SET value='10.05.31' where what="version";

ALTER TABLE srv_anketa CHANGE usercode_skip usercode_skip TINYINT NOT NULL DEFAULT '2';
UPDATE srv_zanka_profiles SET name = 'Brez' WHERE id=1;

INSERT INTO srv_status_profile (id, uid, name, system, statusnull, status0, status1, status2, status3, status4, status5, status6, statuslurker ) 
VALUES ('2', '0', 'Ustrezni', 1, 0, 0, 0, 0, 0, 0, 1, 1, 0) 
ON DUPLICATE KEY UPDATE uid = '0', name='Ustrezni', system='1', statusnull='0', status0='0', status1='0', status2='0', status3='0', status4='0', status5='1', status6='1', statuslurker='0';

UPDATE misc SET value='10.06.02' where what="version";

# opazil sem, da na www1kasi niso vse srv_* tabele InnoDB
# to je zato, ker je na strezniku ni nujno default storage engine InnoDB!
# pri kreiranju novih tabel je zato treba eksplicitno nastaviti storage engine!

ALTER TABLE srv_analysis_archive ENGINE = InnoDB;
ALTER TABLE srv_statistic_profile ENGINE = InnoDB;
ALTER TABLE srv_status_casi ENGINE = InnoDB;
ALTER TABLE srv_status_profile ENGINE = InnoDB;

UPDATE misc SET value='10.06.08' where what="version";

ALTER TABLE srv_vrednost CHANGE naslov naslov varchar(2000) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';

# UPDATE baze za 1ka anketo !!!
# Pozeni datoteki update2_cleanup.sql in update2_FK.sql in se pripravi na cakanje :)

UPDATE misc SET value='10.06.17' where what="version";

ALTER TABLE srv_anketa ADD expanded TINYINT NOT NULL DEFAULT '1';

ALTER TABLE srv_anketa ADD flat TINYINT NOT NULL DEFAULT '1';

ALTER TABLE srv_anketa ADD toolbox TINYINT NOT NULL DEFAULT '1';

UPDATE misc SET value='10.06.24' where what="version";

ALTER TABLE srv_anketa ADD popup TINYINT NOT NULL DEFAULT '1';

UPDATE misc SET value='10.06.28' where what="version";

ALTER TABLE srv_tracking DROP FOREIGN KEY fk_srv_tracking_ank_id;
ALTER TABLE srv_tracking DROP INDEX fk_srv_tracking_ank_id;
ALTER TABLE srv_tracking ENGINE = MyISAM;

UPDATE misc SET value='10.07.01' where what="version";

# USE sisplet_search;
# ALTER TABLE menu_index ADD COLUMN datum date;

# srv_data_files shrani datum nastanka header in podatkovne datoteke
CREATE TABLE IF NOT EXISTS srv_data_files (
  sid int(11) NOT NULL ,
  head_file_time datetime NOT NULL,
  data_file_time datetime NOT NULL,
  PRIMARY KEY (sid),
  UNIQUE (sid)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE srv_data_files
  ADD CONSTRAINT fk_srv_data_files_sid FOREIGN KEY (sid) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE;
UPDATE misc SET value='10.07.08' where what="version"; 

CREATE TABLE IF NOT EXISTS srv_log_collect_data (
	date datetime NOT NULL,
	automatic TINYINT( 1 ) not null default 1,
	has_error TINYINT( 1 ) not null default 0,
	log mediumtext NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

UPDATE misc SET value='10.07.12' where what="version";

INSERT INTO srv_grupa (id, ank_id, naslov) VALUES ('-1', '0', 'system');

UPDATE misc SET value='10.08.03' where what="version";

INSERT INTO srv_anketa (id, naslov) VALUES ('-1', 'system');

UPDATE misc SET value='10.08.05' where what="version";

ALTER TABLE srv_grid ADD INDEX ( spr_id ); 

UPDATE misc SET value='10.08.10' where what="version";

ALTER TABLE srv_specialdata_vrednost DROP INDEX spr_id;

UPDATE misc SET value='10.08.11' where what="version";

CREATE TABLE IF NOT EXISTS srv_data_spremenljivka_missing (
  ank_id int(11) NOT NULL default '0',
  usr_id int(11) NOT NULL default '0',
  spr_id int(11) NOT NULL default '0',
  vrednost int(11) NOT NULL default '0',
  UNIQUE (ank_id, usr_id, spr_id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE srv_data_spremenljivka_missing
	ADD CONSTRAINT fk_srv_data_spremenljivka_missing_ank_id FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE srv_data_spremenljivka_missing
	ADD CONSTRAINT fk_srv_data_spremenljivka_missing_usr_id FOREIGN KEY (usr_id) REFERENCES srv_user (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE srv_data_spremenljivka_missing
	ADD CONSTRAINT fk_srv_data_spremenljivka_missing_spr_id FOREIGN KEY (spr_id) REFERENCES srv_spremenljivka (id) ON DELETE CASCADE ON UPDATE CASCADE;

UPDATE misc SET value='10.08.16' where what="version";

DROP TABLE srv_data_spremenljivka_missing;

delete from srv_data_vrednost where vre_id='-1';
delete from srv_data_vrednost where vre_id='-3';
delete from srv_data_vrednost_active where vre_id='-1';
delete from srv_data_vrednost_active where vre_id='-3';

delete from srv_data_grid where grd_id='-1';
delete from srv_data_grid where grd_id='-3';
delete from srv_data_grid_active where grd_id='-1';
delete from srv_data_grid_active where grd_id='-3';

UPDATE misc SET value='10.08.17' where what="version";

INSERT IGNORE INTO srv_data_vrednost (spr_id, vre_id, usr_id) SELECT spr_id, '-2', usr_id FROM srv_data_grid WHERE grd_id='-2';
DELETE FROM srv_data_grid WHERE grd_id = '-2';

INSERT IGNORE INTO srv_data_vrednost_active (spr_id, vre_id, usr_id) SELECT spr_id, '-2', usr_id FROM srv_data_grid_active WHERE grd_id='-2';
DELETE FROM srv_data_grid_active WHERE grd_id = '-2';

# insertamo kar v obe tabeli isto, ker je checkgridov itak ful mal..
INSERT IGNORE INTO srv_data_vrednost (spr_id, vre_id, usr_id) SELECT spr_id, '-2', usr_id FROM srv_data_checkgrid WHERE grd_id='-2';
INSERT IGNORE INTO srv_data_vrednost_active (spr_id, vre_id, usr_id) SELECT spr_id, '-2', usr_id FROM srv_data_checkgrid WHERE grd_id='-2';
DELETE FROM srv_data_checkgrid WHERE grd_id = '-2';

delete from srv_data_checkgrid where grd_id='-1';
delete from srv_data_checkgrid where grd_id='-3';

UPDATE misc SET value='10.08.18' where what="version";

INSERT INTO misc (what, value) VALUES ('RegCustomGroupsText', '');
UPDATE misc SET value='10.09.03' where what="version";

DROP table DeadLinks;
CREATE TABLE DeadLinks (tbl varchar(255), title varchar(255), NiceLink varchar(255), target varchar(255), inside varchar(255));
UPDATE misc SET value='10.09.06' where what="version";

INSERT INTO misc (what, value) VALUES ('RegAgreement', '');

ALTER TABLE srv_data_files ADD collect_all_status TINYINT NOT NULL DEFAULT '0';
ALTER TABLE srv_data_files ADD collect_full_meta TINYINT NOT NULL DEFAULT '0';
UPDATE srv_invitations_profiles SET content = '<p>Prosimo, &#269;e si vzamete nekaj minut in izpolnite spodnjo anketo.</p><p>Hvala.</p><p>#URL#</p>' WHERE id = 1 LIMIT 1;
UPDATE srv_userbase_invitations SET text = '<p>Prosimo, &#269;e si vzamete nekaj minut in izpolnite spodnjo anketo.</p><p>Hvala.</p><p>#URL#</p>' WHERE id = 1 LIMIT 1;
UPDATE misc SET value='10.09.13' where what="version";

ALTER TABLE srv_glasovanje change skin skin varchar(100) NOT NULL default 'Classic';

CREATE TABLE srv_language_spremenljivka (
spr_id INT NOT NULL ,
naslov TEXT NOT NULL ,
info TEXT NOT NULL ,
vsota VARCHAR( 255 ) NOT NULL ,
PRIMARY KEY ( spr_id ),
CONSTRAINT fk_srv_language_spremenljivka_spr_id FOREIGN KEY (spr_id) REFERENCES srv_spremenljivka (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;


CREATE TABLE srv_language_vrednost (
vre_id INT NOT NULL ,
naslov TEXT NOT NULL ,
PRIMARY KEY ( vre_id ),
CONSTRAINT fk_srv_language_vrednost_vre_id FOREIGN KEY (vre_id) REFERENCES srv_vrednost (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE TABLE srv_language_grid (
spr_id INT NOT NULL ,
grd_id INT NOT NULL ,
naslov TEXT NOT NULL ,
PRIMARY KEY ( spr_id, grd_id ),
CONSTRAINT fk_srv_language_grid_spr_id_grd_id FOREIGN KEY (spr_id, grd_id) REFERENCES srv_grid (spr_id, id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

UPDATE misc SET value='10.09.14' where what="version";

ALTER TABLE srv_language_spremenljivka ADD ank_id INT NOT NULL FIRST ;
ALTER TABLE srv_language_spremenljivka ADD CONSTRAINT fk_srv_language_spremenljivka_ank_id FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE srv_anketa CHANGE usercode_skip usercode_skip TINYINT NOT NULL DEFAULT '1';

ALTER TABLE srv_anketa ADD multilang TINYINT NOT NULL DEFAULT '0' AFTER lang_resp;

UPDATE misc SET value='10.09.15' where what="version";

DROP TABLE srv_language_spremenljivka;
DROP TABLE srv_language_vrednost;
DROP TABLE srv_language_grid;

CREATE TABLE srv_language (
ank_id INT NOT NULL ,
lang_id INT NOT NULL ,
language VARCHAR(255) NOT NULL,
PRIMARY KEY ( ank_id , lang_id ) ,
CONSTRAINT fk_srv_language_ank_id FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE TABLE srv_language_spremenljivka (
ank_id INT NOT NULL,
spr_id INT NOT NULL ,
lang_id INT NOT NULL,
naslov TEXT NOT NULL ,
info TEXT NOT NULL ,
vsota VARCHAR( 255 ) NOT NULL ,
PRIMARY KEY ( ank_id, spr_id, lang_id ),
CONSTRAINT fk_srv_language_spremenljivka_spr_id FOREIGN KEY (spr_id) REFERENCES srv_spremenljivka (id) ON DELETE CASCADE ON UPDATE CASCADE,
CONSTRAINT fk_srv_language_spremenljivka_ank_id_lang_id FOREIGN KEY (ank_id, lang_id) REFERENCES srv_language (ank_id,lang_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE TABLE srv_language_vrednost (
ank_id INT NOT NULL,
vre_id INT NOT NULL ,
lang_id INT NOT NULL,
naslov TEXT NOT NULL ,
PRIMARY KEY ( vre_id ),
CONSTRAINT fk_srv_language_vrednost_vre_id FOREIGN KEY (vre_id) REFERENCES srv_vrednost (id) ON DELETE CASCADE ON UPDATE CASCADE,
CONSTRAINT fk_srv_language_vrednost_ank_id_lang_id FOREIGN KEY (ank_id, lang_id) REFERENCES srv_language (ank_id, lang_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE TABLE srv_language_grid (
ank_id INT NOT NULL,
spr_id INT NOT NULL ,
grd_id INT NOT NULL ,
lang_id INT NOT NULL,
naslov TEXT NOT NULL ,
PRIMARY KEY ( spr_id, grd_id ),
CONSTRAINT fk_srv_language_grid_spr_id_grd_id FOREIGN KEY (spr_id, grd_id) REFERENCES srv_grid (spr_id, id) ON DELETE CASCADE ON UPDATE CASCADE,
CONSTRAINT fk_srv_language_grid_ank_id_lang_id FOREIGN KEY (ank_id, lang_id) REFERENCES srv_language (ank_id, lang_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

UPDATE misc SET value='10.09.16' where what="version";

ALTER TABLE srv_anketa DROP form_open;
ALTER TABLE srv_anketa ADD form_settings_obvescanje TINYINT NOT NULL DEFAULT '0';
ALTER TABLE srv_anketa ADD form_settings_vabila TINYINT NOT NULL DEFAULT '0';

# OBVEZNO UPGRADAJ 09.06 in novejši zaradi buga v registraciji!!! (function/ProfileClass.php)
UPDATE misc SET value='10.09.19' WHERE what="version";

ALTER TABLE srv_data_files CHANGE collect_all_status collect_all_status TINYINT NOT NULL DEFAULT '1';

UPDATE misc SET value='10.09.20' WHERE what="version";

ALTER TABLE srv_sys_filters ADD UNIQUE (type,filter);
UPDATE misc SET value='10.09.21' WHERE what="version";

ALTER TABLE srv_grid ADD other TINYINT NOT NULL DEFAULT '0';
# popravimo gride za stare missinge other = 99,98,97
UPDATE srv_grid SET other = '-99' WHERE vrstni_red = '99' and id = '99';
UPDATE srv_grid SET other = '-98' WHERE vrstni_red = '98' and id = '98';
UPDATE srv_grid SET other = '-97' WHERE vrstni_red = '97' and id = '97';
UPDATE misc SET value='10.09.22' WHERE what="version";

#
#
# (par vrstic da ne spregledam...)
#
#
# dodaj RewriteRule:
#
# RewriteRule ^sitemap                     /index.php?fl=16

UPDATE misc SET value='10.09.23' WHERE what="version";
CREATE TABLE UlCounter (filename varchar(255) not null default '', timestamp datetime);

#
# Dodaj se RewriteRule:
#
# RewriteRule ^ul/(.*)                     /uploadi/counter.php?fn=$1
#
#


# Preverit ali se podvajajo missingi v gridih
# SELECT CONCAT(spr_id, other), count(*) FROM srv_grid WHERE other!=0 group by CONCAT(spr_id,other) HAVING COUNT(*) >1;
UPDATE srv_grid SET id = '-99', variable = '-99' WHERE other = '-99' AND id!='-99';
UPDATE srv_grid SET id = '-98', variable = '-98' WHERE other = '-98' AND id!='-98';
UPDATE srv_grid SET id = '-97', variable = '-97' WHERE other = '-97' AND id!='-97';

DROP TABLE IF EXISTS srv_loop;

CREATE TABLE srv_loop (
if_id INT NOT NULL PRIMARY KEY,
spr_id INT NOT NULL
) ENGINE = InnoDB;

ALTER TABLE srv_loop ADD CONSTRAINT fk_srv_loop_if_id FOREIGN KEY (if_id) REFERENCES srv_if (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE srv_loop ADD CONSTRAINT fk_srv_loop_spr_id FOREIGN KEY (spr_id) REFERENCES srv_spremenljivka (id) ON DELETE CASCADE ON UPDATE CASCADE;

DROP TABLE IF EXISTS srv_loop_vre;

CREATE TABLE srv_loop_vre (
if_id INT NOT NULL ,
vre_id INT NOT NULL ,
PRIMARY KEY ( if_id , vre_id )
) ENGINE = InnoDB;

ALTER TABLE srv_loop_vre ADD CONSTRAINT fk_srv_loop_vre_if_id FOREIGN KEY (if_id) REFERENCES srv_loop (if_id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE srv_loop_vre ADD CONSTRAINT fk_srv_loop_vre_vre_id FOREIGN KEY (vre_id) REFERENCES srv_vrednost (id) ON DELETE CASCADE ON UPDATE CASCADE;

UPDATE misc SET value='10.09.24' WHERE what="version";

CREATE TABLE srv_loop_data (
id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
vre_id INT NOT NULL
) ENGINE = InnoDB;

ALTER TABLE srv_loop_data ADD CONSTRAINT fk_srv_loop_data_vre_id FOREIGN KEY (vre_id) REFERENCES srv_loop_vre (vre_id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE srv_loop_data ADD if_id INT NOT NULL AFTER id;
ALTER TABLE srv_loop_data ADD UNIQUE (if_id, vre_id);
ALTER TABLE srv_loop_data ADD CONSTRAINT fk_srv_loop_data_if_id FOREIGN KEY (if_id) REFERENCES srv_loop_vre (if_id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE srv_data_checkgrid ADD loop_id INT NULL DEFAULT NULL ;
ALTER TABLE srv_data_checkgrid DROP PRIMARY KEY , ADD UNIQUE INDEX ( spr_id , vre_id , usr_id , grd_id , loop_id ) ;
ALTER TABLE srv_data_checkgrid ADD CONSTRAINT fk_srv_data_checkgrid_loop_id FOREIGN KEY (loop_id) REFERENCES srv_loop_data (id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE srv_data_grid ADD loop_id INT NULL DEFAULT NULL ;
ALTER TABLE srv_data_grid DROP PRIMARY KEY , ADD UNIQUE INDEX ( spr_id , vre_id , usr_id , loop_id ) ;
ALTER TABLE srv_data_grid ADD CONSTRAINT fk_srv_data_grid_loop_id FOREIGN KEY (loop_id) REFERENCES srv_loop_data (id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE srv_data_grid_active ADD loop_id INT NULL DEFAULT NULL ;
ALTER TABLE srv_data_grid_active DROP PRIMARY KEY , ADD UNIQUE INDEX ( spr_id , vre_id , usr_id , loop_id ) ;
ALTER TABLE srv_data_grid_active ADD CONSTRAINT fk_srv_data_grid_active_loop_id FOREIGN KEY (loop_id) REFERENCES srv_loop_data (id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE srv_data_number ADD loop_id INT NULL DEFAULT NULL ;
ALTER TABLE srv_data_number DROP PRIMARY KEY , ADD UNIQUE INDEX ( spr_id , vre_id , usr_id , loop_id ) ;
ALTER TABLE srv_data_number ADD CONSTRAINT fk_srv_data_number_loop_id FOREIGN KEY (loop_id) REFERENCES srv_loop_data (id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE srv_data_rating ADD loop_id INT NULL DEFAULT NULL ;
ALTER TABLE srv_data_rating DROP PRIMARY KEY , ADD UNIQUE INDEX ( spr_id , vre_id , usr_id , loop_id ) ;
ALTER TABLE srv_data_rating ADD CONSTRAINT fk_srv_data_rating_loop_id FOREIGN KEY (loop_id) REFERENCES srv_loop_data (id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE srv_data_text ADD loop_id INT NULL DEFAULT NULL ;
ALTER TABLE srv_data_text DROP INDEX spr_usr , ADD UNIQUE INDEX ( spr_id , vre_id , usr_id , loop_id ) ;
ALTER TABLE srv_data_text ADD CONSTRAINT fk_srv_data_text_loop_id FOREIGN KEY (loop_id) REFERENCES srv_loop_data (id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE srv_data_textgrid ADD loop_id INT NULL DEFAULT NULL ;
ALTER TABLE srv_data_textgrid DROP PRIMARY KEY , ADD UNIQUE INDEX ( spr_id , vre_id , usr_id , grd_id , loop_id ) ;
ALTER TABLE srv_data_textgrid ADD CONSTRAINT fk_srv_data_textgrid_loop_id FOREIGN KEY (loop_id) REFERENCES srv_loop_data (id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE srv_data_vrednost ADD loop_id INT NULL DEFAULT NULL ;
ALTER TABLE srv_data_vrednost DROP PRIMARY KEY , ADD UNIQUE INDEX ( spr_id , vre_id , usr_id , loop_id ) ;
ALTER TABLE srv_data_vrednost ADD CONSTRAINT fk_srv_data_vrednost_loop_id FOREIGN KEY (loop_id) REFERENCES srv_loop_data (id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE srv_data_vrednost_active ADD loop_id INT NULL DEFAULT NULL ;
ALTER TABLE srv_data_vrednost_active DROP PRIMARY KEY , ADD UNIQUE INDEX ( spr_id , vre_id , usr_id , loop_id ) ;
ALTER TABLE srv_data_vrednost_active ADD CONSTRAINT fk_srv_data_vrednost_active_loop_id FOREIGN KEY (loop_id) REFERENCES srv_loop_data (id) ON DELETE CASCADE ON UPDATE CASCADE;

UPDATE misc SET value='10.09.28' WHERE what="version";

ALTER TABLE srv_spremenljivka ADD note TEXT NOT NULL ;

UPDATE misc SET value='10.09.29' WHERE what="version";

ALTER TABLE srv_status_profile ADD merge_missing TINYINT( 1 ) not null default 0; 
ALTER TABLE srv_status_profile ADD show_zerro TINYINT( 1 ) not null default 0;

DROP TABLE IF EXISTS srv_mising_profiles_values;
DROP TABLE IF EXISTS srv_mising_profiles;


CREATE TABLE IF NOT EXISTS srv_missing_profiles (
  id int(11) NOT NULL AUTO_INCREMENT,
  uid int(11) NOT NULL DEFAULT '0',
  name varchar(200) NOT NULL,
  system tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (id),
  UNIQUE KEY uid (uid,name)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS srv_missing_profiles_values (
  missing_pid int(11) NOT NULL,
  missing_value int(11) NOT NULL,
  type int(2) NOT NULL,
  PRIMARY KEY (missing_pid,missing_value,type),
  UNIQUE KEY pid (missing_pid,missing_value,type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
	
ALTER TABLE srv_missing_profiles_values
  ADD CONSTRAINT fk_srv_missing_profiles_id FOREIGN KEY (missing_pid) REFERENCES srv_missing_profiles (id) ON DELETE CASCADE ON UPDATE CASCADE;
 
UPDATE misc SET value='10.09.30' WHERE what="version";

UPDATE srv_invitations_profiles SET content = '<p>Prosimo, &#269;e si vzamete nekaj minut in izpolnite spodnjo anketo.</p><p>Hvala.</p><p>#URL#</p><p>#UNSUBSCRIBE#</p>' WHERE id =1;
# pobrišemo stare profile variable, ker ne deluje več zaradi loopov
DELETE FROM srv_variable_profiles WHERE NOT system = 1;

UPDATE misc SET value='10.10.01' WHERE what="version";

# pobrišemo datume datotek, da vsilimo novo kreacijo 
TRUNCATE TABLE srv_data_files;

ALTER TABLE srv_loop_data DROP FOREIGN KEY fk_srv_loop_data_vre_id;
ALTER TABLE srv_loop_data DROP FOREIGN KEY fk_srv_loop_data_if_id;

ALTER TABLE srv_loop_data ADD CONSTRAINT fk_srv_loop_data_if_id_vre_id FOREIGN KEY (if_id, vre_id) REFERENCES srv_loop_vre (if_id, vre_id) ON DELETE CASCADE ON UPDATE CASCADE;

UPDATE misc SET value='10.10.04' WHERE what="version";

ALTER TABLE srv_anketa ADD missing_values_type TINYINT NOT NULL DEFAULT '0';

CREATE TABLE IF NOT EXISTS srv_missing_values (
  sid int(11) NOT NULL,
  type tinyint(1) NOT NULL DEFAULT '3',
  value varchar(10) COLLATE utf8_bin NOT NULL DEFAULT '-99',
  text varchar(255) COLLATE utf8_bin NOT NULL DEFAULT 'Ne vem',
  active tinyint(1) NOT NULL DEFAULT '1',
  UNIQUE KEY sid (sid,type,value)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE srv_missing_values
  ADD CONSTRAINT fk_srv_missing_values_sid FOREIGN KEY (sid) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE;
UPDATE misc SET value='10.10.05' WHERE what="version";

TRUNCATE TABLE srv_missing_profiles; 

CREATE TABLE srv_dostop_language (
ank_id INT NOT NULL ,
uid INT NOT NULL ,
lang_id INT NOT NULL
) ENGINE = InnoDB;

ALTER TABLE srv_dostop_language ADD PRIMARY KEY ( ank_id , uid , lang_id ); 
ALTER TABLE srv_dostop_language ADD CONSTRAINT fk_srv_dostop_language_ank_id_uid FOREIGN KEY (ank_id, uid) REFERENCES srv_dostop (ank_id, uid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE srv_dostop_language ADD CONSTRAINT fk_srv_dostop_language_ank_id_lang_id FOREIGN KEY (ank_id, lang_id) REFERENCES srv_language (ank_id, lang_id) ON DELETE CASCADE ON UPDATE CASCADE;

UPDATE misc SET value='10.10.08' WHERE what="version";

TRUNCATE TABLE srv_missing_profiles;
ALTER TABLE srv_missing_profiles ADD display_mv_type TINYINT NOT NULL DEFAULT '0';
UPDATE misc SET value='10.10.08a' WHERE what="version";

ALTER TABLE srv_missing_profiles ADD merge_missing TINYINT( 1 ) not null default 0; 
ALTER TABLE srv_missing_profiles ADD show_zerro TINYINT( 1 ) not null default 0;

ALTER TABLE srv_status_profile DROP merge_missing;
ALTER TABLE srv_status_profile DROP show_zerro;

INSERT INTO srv_help (what, help) VALUES
('srv_collect_all_status_1', '[6] Končal anketo\n[5] Delno izpolnjena\n[4] Klik na anketo\n[3] Klik na nagovor\n[2] Napaka pri poA!iljanju e-poA!te\n[1] E-poA!ta poslana (neodgovor)\n[0] E-poA!ta A!e ni bila poslana\n[-1] Neznan status'),
('srv_collect_all_status_0', 'Statusi ustrezni so:\n[6] KonÄ?al anketo\n[5] Delno izpolnjena');
UPDATE misc SET value='10.10.10' WHERE what="version";

ALTER TABLE srv_spremenljivka ADD upload TINYINT NOT NULL DEFAULT 0;

UPDATE misc SET value='10.10.11' WHERE what="version";

CREATE TABLE srv_data_upload (
ank_id INT NOT NULL ,
usr_id INT NOT NULL ,
code CHAR( 13 ) NOT NULL ,
filename VARCHAR( 50 ) NOT NULL
) ENGINE = InnoDB;

ALTER TABLE srv_data_upload ADD CONSTRAINT srv_data_upload_ank_id FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE srv_data_upload ADD CONSTRAINT srv_data_upload_usr_id FOREIGN KEY (usr_id) REFERENCES srv_user (id) ON DELETE CASCADE ON UPDATE CASCADE;

UPDATE misc SET value='10.10.11a' WHERE what="version";

ALTER TABLE srv_invitations_profiles ADD replyto VARCHAR( 30 ) NOT NULL ;

ALTER TABLE srv_userbase_setting ADD replyto VARCHAR( 30 ) NOT NULL ;

UPDATE misc SET value='10.10.12' WHERE what="version";
TRUNCATE TABLE srv_missing_profiles;

ALTER TABLE srv_loop_vre ADD tip TINYINT NOT NULL;

UPDATE misc SET value='10.10.15' WHERE what="version";

ALTER TABLE srv_loop ADD max INT NOT NULL;

UPDATE misc SET value='10.10.15a' WHERE what="version";

ALTER TABLE srv_spremenljivka ADD vsota_show TINYINT NOT NULL DEFAULT '1' AFTER vsota_limittype;

UPDATE misc SET value='10.10.19' WHERE what="version";

ALTER TABLE srv_anketa DROP form_settings_obvescanje;

ALTER TABLE srv_anketa DROP form_settings_vabila;

ALTER TABLE srv_user ADD pass VARCHAR( 20 ) NULL DEFAULT NULL AFTER cookie;

ALTER TABLE srv_user ADD UNIQUE (ank_id, pass);

UPDATE misc SET value='10.10.19a' WHERE what="version";

INSERT INTO srv_sys_filters (id, fid, type, filter, text, uid) VALUES (NULL, '-5', '1', '-5', 'Prazna enota', 0);

UPDATE misc SET value='10.10.19b' WHERE what="version";

######
######
###### Tukaj je potrebno paziti ce se pojavijo duplicate keyi!!! v tem primeru se naj spremeni enega od prvih 6ih znakov
######
###### Za lažje iskanje podvojenih zapisov (sem dal se having zraven, da ne bo se koga kap - tko k mene zdele):
###### SELECT count(*) AS count, cookie, SUBSTRING( cookie, 1, 6 ), ank_id FROM srv_user GROUP by ank_id, SUBSTRING( cookie, 1, 6 ) HAVING count >=2 ORDER BY count DESC, ank_id DESC
######
UPDATE srv_user SET pass = SUBSTRING( cookie, 1, 6 ) ;

UPDATE misc SET value='10.10.19c' WHERE what="version";

UPDATE misc SET value='10.10.20' WHERE what="version";

ALTER TABLE srv_anketa ADD concl_end_button TINYINT NOT NULL DEFAULT '1' AFTER concl_back_button ;

UPDATE misc SET value='10.10.22' WHERE what="version";

ALTER TABLE srv_user ADD unsubscribed TINYINT NOT NULL DEFAULT '0';

UPDATE misc SET value='10.11.02' WHERE what="version";

ALTER TABLE srv_anketa ADD thread_intro INT NOT NULL DEFAULT '0' AFTER thread , ADD thread_concl INT NOT NULL DEFAULT '0' AFTER thread_intro ;

ALTER TABLE srv_anketa ADD intro_note TEXT NOT NULL AFTER thread_concl ,ADD concl_note TEXT NOT NULL AFTER intro_note ;

UPDATE misc SET value='10.11.03' WHERE what="version";

INSERT INTO srv_help (what, help) VALUES ('srv_collect_data_setting', 'Generiranje tabele s podatki:\n\nS poljem \"le ustrezni\" izbiramo med statusi enot, ki se bodo generirali kot potencialni za analize, izvoz podatkov in prikaz vnosov.\nKadar je polje \"le ustrezni\" izbrano se upo&#353;tevajo samo enote z statusom: 6 - Kon&#269;al anketo in 5 - Delno izpolnjena.\n\nS poljem \"meta podatki\" izbiramo ali se generirajo tudi meta podatki kot so: lastnosti ra&#269;unalika, podrobni podatki o e-po&#353;tnih vabilih in telefonskih klicih.')
ON DUPLICATE KEY UPDATE help = 'Generiranje tabele s podatki:\n\nS poljem \"le ustrezni\" izbiramo med statusi enot, ki se bodo generirali kot potencialni za analize, izvoz podatkov in prikaz vnosov.\nKadar je polje \"le ustrezni\" izbrano se upo&#353;tevajo samo enote z statusom: 6 - Kon&#269;al anketo in 5 - Delno izpolnjena.\n\nS poljem \"meta podatki\" izbiramo ali se generirajo tudi meta podatki kot so: lastnosti ra&#269;unalika, podrobni podatki o e-po&#353;tnih vabilih in telefonskih klicih.';

update srv_survey_misc SET value='4' where what='survey_comment_viewadminonly' AND value ='1';
update srv_survey_misc SET value='' where what='survey_comment_viewadminonly' AND value ='0';

update srv_survey_misc SET value='4' where what='survey_comment' AND value ='1';
update srv_survey_misc SET value='' where what='survey_comment' AND value ='0';

update srv_survey_misc SET value='4' where what='question_comment_viewadminonly' AND value ='1';
update srv_survey_misc SET value='' where what='question_comment_viewadminonly' AND value ='0';

update srv_survey_misc SET value='4' where what='question_comment' AND value ='1';
update srv_survey_misc SET value='' where what='question_comment' AND value ='0';

update srv_survey_misc SET value='4' where what='question_resp_comment_viewadminonly' AND value ='1';
update srv_survey_misc SET value='' where what='question_resp_comment_viewadminonly' AND value ='0';

UPDATE misc SET value='10.11.05' WHERE what="version";

# nova tabela za profile pogojev se imenuje srv_condition_profiles
DROP TABLE IF EXISTS srv_filter_profiles;
CREATE TABLE IF NOT EXISTS srv_condition_profiles (
id int(11) NOT NULL AUTO_INCREMENT,
sid int(11) NOT NULL,
uid int(11) NOT NULL,
name varchar(250) NOT NULL,
if_id int(11) NOT NULL,
UNIQUE KEY id (id,sid,uid)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

UPDATE misc SET value='10.11.05a' WHERE what="version";

INSERT INTO misc (what, value) values ('DefaultShowLike', '0');
ALTER TABLE data_baze add column ShowLike tinyint(1) not null default 0;
ALTER TABLE menu add column ShowLike tinyint(1) not null default 0;

UPDATE misc SET value='10.11.06' WHERE what="version";

ALTER TABLE srv_condition_profiles add column condition_label TEXT NOT NULL;

UPDATE misc SET value='10.11.08' WHERE what="version";

ALTER TABLE srv_condition_profiles add column condition_error tinyint(1) not null default 0;
UPDATE misc SET value='10.11.15' WHERE what="version";

ALTER TABLE srv_spremenljivka ADD dostop TINYINT NOT NULL DEFAULT '4';

UPDATE misc SET value='10.11.24' WHERE what="version";

ALTER TABLE users ADD lang INT NOT NULL;

UPDATE users SET lang = (SELECT value FROM misc WHERE what = "SurveyLang_admin");

UPDATE misc SET value='10.11.26' WHERE what="version";
INSERT INTO misc (what, value) values ('srv_maxDashboardChacheFiles', '200');
UPDATE misc SET value='10.11.27' WHERE what="version";

ALTER TABLE srv_tracking ADD time_seconds FLOAT NOT NULL DEFAULT '0';

UPDATE misc SET value='10.12.01' WHERE what="version";

INSERT INTO srv_help (what, help) VALUES ('displaydata_pdftype', 'Dolg izpis pomeni izpis oblike kakr&#353;ne je vpra&#353;alnik, kraj&#353;i izpis pa izpi&#353;e vpra&#353;alnik z rezultati v skraj&#353;ani obliki.');

# Te tabele v bistvu ne rabimo -  je par vrstic nižje izbrisana 
# CREATE TABLE srv_data_sn_imena (
#	ank_id INT NOT NULL ,
#	spr_id INT NOT NULL ,
#	max_sn_imen INT DEFAULT 0,
#	UNIQUE ank_id (ank_id, spr_id)
# ) ENGINE = InnoDB;

INSERT INTO misc (what, value) VALUES ('DefaultShowLikeAbove', '0');
ALTER TABLE data_baze ADD COLUMN ShowLikeAbove tinyint(1) not null default 0;
ALTER TABLE menu ADD COLUMN ShowLikeAbove tinyint(1) not null default 0;

UPDATE misc SET value='10.12.05' WHERE what="version";

# te tabele ne rabimo :) (nastala je v trenutku nepozornosti :) )
DROP TABLE IF EXISTS srv_data_sn_imena;

UPDATE misc SET value='10.12.06' WHERE what="version";

ALTER TABLE srv_grid ADD part TINYINT NOT NULL DEFAULT '1';

ALTER TABLE srv_data_files ADD last_update datetime NOT NULL;
UPDATE misc SET value='10.12.07' WHERE what="version";

ALTER TABLE srv_spremenljivka ADD grid_subtitle1 TEXT NOT NULL AFTER grids_edit, ADD grid_subtitle2 TEXT NOT NULL AFTER grid_subtitle1;

UPDATE misc SET value='10.12.09' WHERE what="version";

ALTER TABLE users CHANGE lang lang INT( 11 ) NOT NULL DEFAULT '1';

UPDATE misc SET value='10.12.12' WHERE what="version";

UPDATE srv_library_folder SET naslov = 'Moja vpra&#154;anja' WHERE uid>0 AND tip=0 AND parent=0 AND naslov='My library';
UPDATE srv_library_folder SET naslov = 'Moje ankete' WHERE uid>0 AND tip=1 AND parent=0 AND naslov='My library';

UPDATE misc SET value='10.12.21' WHERE what="version";

INSERT INTO srv_help (what, help) VALUES ('toolbox_advanced', "Pri anketi z ve&#269;jim &#353;tevilom vpra&#353;anj vam priporo&#269;amo, da anketo razdelite na vsebinsko smiselne bloke. \n\nBloke lahko po potrebi zapirate in razpirate ter si s tem omogo&#269;ite bolj&#353;i pregled nad anketo.");

UPDATE misc SET value='10.12.30' WHERE what="version";

# izpise ife, ki se nanasajo na spremenljivko, pa niso v srv_branchingu
#
# select * from srv_condition where 
#	spr_id in 
#		(select element_spr from srv_branching where ank_id = 4156 and element_spr > 0) 
#	AND id not in
#		(select id from srv_condition where if_id in 
#			(select element_if from srv_branching where ank_id = 4156 and element_if > 0)
#		);


ALTER TABLE new ADD p1 integer not null default 0, add p2 integer not null default 0;


CREATE TABLE srv_time_profile (
 id int(11) NOT NULL auto_increment,
 uid int(11) NOT NULL default 0,
 name varchar(200) NOT NULL ,
 type tinyint(1) NOT NULL ,
 starts datetime DEFAULT NULL ,
 ends datetime DEFAULT NULL ,
 interval_txt varchar(100) DEFAULT NULL,
 PRIMARY KEY (id,uid),
 UNIQUE (id, uid, name)
) ENGINE = InnoDB DEFAULT CHARSET=utf8 ;

UPDATE misc SET value='11.01.14' WHERE what="version";

ALTER TABLE srv_spremenljivka ADD inline_edit TINYINT NOT NULL DEFAULT '0';

UPDATE misc SET value='11.01.17' WHERE what="version";

ALTER TABLE new ADD p3 integer not null default 0;

ALTER TABLE srv_anketa ADD slideshow TINYINT NOT NULL DEFAULT '0' AFTER multilang;

UPDATE misc SET value='11.01.24' WHERE what="version";

CREATE TABLE srv_slideshow_settings (
 ank_id int(11) not null,
 fixed_interval tinyint(1) not null default 0,
 timer mediumint not null default 5,
 PRIMARY KEY (ank_id),
 UNIQUE (ank_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='11.01.25' WHERE what="version";

ALTER TABLE srv_slideshow_settings ADD autostart TINYINT NOT NULL DEFAULT '0' AFTER timer;

UPDATE misc SET value='11.01.26' WHERE what="version";

ALTER TABLE srv_slideshow_settings ADD pause_btn TINYINT NOT NULL DEFAULT '0' AFTER autostart;

UPDATE misc SET value='11.01.27' WHERE what="version";

ALTER TABLE srv_slideshow_settings ADD next_btn TINYINT NOT NULL DEFAULT '1' AFTER autostart;
ALTER TABLE srv_slideshow_settings ADD back_btn TINYINT NOT NULL DEFAULT '1' AFTER next_btn;

UPDATE misc SET value='11.01.30' WHERE what="version";

ALTER TABLE srv_slideshow_settings ADD save_entries TINYINT NOT NULL DEFAULT '0' AFTER timer;

UPDATE misc SET value='11.01.31' WHERE what="version";

alter table neww add column border varchar(255) not null default 'SAME';

ALTER TABLE srv_spremenljivka ADD onchange_submit TINYINT NOT NULL DEFAULT '0';

UPDATE misc SET value='11.02.09' WHERE what="version";

ALTER TABLE srv_spremenljivka ADD hidden_default TINYINT NOT NULL DEFAULT '0';

UPDATE misc SET value='11.02.10' WHERE what="version";

ALTER TABLE srv_anketa ADD locked TINYINT NOT NULL DEFAULT '0' AFTER active ;

UPDATE misc SET value='11.02.10a' WHERE what="version";

CREATE TABLE srv_captcha (
ank_id INT NOT NULL ,
usr_id INT NOT NULL ,
text CHAR( 5 ) NOT NULL ,
code CHAR( 40 ) NOT NULL
) ENGINE = InnoDB;

ALTER TABLE srv_captcha ADD spr_id INT NOT NULL AFTER ank_id ;

ALTER TABLE srv_captcha ADD PRIMARY KEY ( ank_id , spr_id , usr_id) ;

UPDATE misc SET value='11.02.11' WHERE what="version";

ALTER TABLE struktura_baze add column copykeywords varchar(10) not null default "";

ALTER TABLE srv_captcha ADD CONSTRAINT srv_captcha_ank_id FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE srv_captcha ADD CONSTRAINT srv_captcha_usr_id FOREIGN KEY (usr_id) REFERENCES srv_user (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE srv_captcha ADD CONSTRAINT srv_captcha_spr_id FOREIGN KEY (spr_id) REFERENCES srv_spremenljivka (id) ON DELETE CASCADE ON UPDATE CASCADE;

UPDATE misc SET value='11.02.14' WHERE what="version";

ALTER TABLE srv_condition_profiles ADD CONSTRAINT srv_condition_profiles_sid FOREIGN KEY (sid) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE srv_slideshow_settings ADD CONSTRAINT srv_slideshow_settings_ank_id FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE;

UPDATE misc SET value='11.02.14a' WHERE what="version";

ALTER TABLE struktura_baze add column copydesc varchar(10) not null default "";

UPDATE misc SET value='11.02.16' WHERE what="version";

ALTER TABLE srv_condition ADD grd_id INT NOT NULL AFTER vre_id;

UPDATE misc SET value='11.02.24' WHERE what="version";

CREATE TABLE srv_datasetting_profile (
 id int(11) NOT NULL auto_increment,
 uid int(11) NOT NULL default 0,
 name varchar(200) NOT NULL ,
 PRIMARY KEY (id,uid),
 UNIQUE (id, uid, name)
) ENGINE = InnoDB DEFAULT CHARSET=utf8 ;

ALTER TABLE srv_datasetting_profile ADD dsp_ndp TINYINT NOT NULL default 1 AFTER name ;
ALTER TABLE srv_datasetting_profile ADD dsp_nda TINYINT NOT NULL default 2 AFTER dsp_ndp ;
ALTER TABLE srv_datasetting_profile ADD dsp_ndd TINYINT NOT NULL default 2 AFTER dsp_nda ;

UPDATE misc SET value='11.02.27' WHERE what="version";

ALTER TABLE srv_datasetting_profile ADD dsp_sep TINYINT NOT NULL default 0 AFTER dsp_ndd ;
ALTER TABLE srv_datasetting_profile ADD dsp_res TINYINT NOT NULL default 3 AFTER dsp_ndd ;
UPDATE misc SET value='11.02.28' WHERE what="version";

CREATE TABLE srv_survey_list (
 id int(11) NOT NULL,
 lib_glb ENUM('0','1') NOT NULL DEFAULT '0',
 lib_usr ENUM('0','1') NOT NULL,
 answers int NOT NULL DEFAULT 0,
 variables int NOT NULL DEFAULT 0,
 approp int NOT NULL DEFAULT 0,
 i_name varchar(255) NOT NULL default '',
 i_surname varchar(255) NOT NULL default '',
 i_email varchar(255) NOT NULL default '',
 e_name varchar(255) NOT NULL default '',
 e_surname varchar(255) NOT NULL default '',
 e_email varchar(255) NOT NULL default '',
 a_first DATETIME DEFAULT NULL ,
 a_last DATETIME DEFAULT NULL,
 updated  ENUM('0','1') NOT NULL DEFAULT '0',
 PRIMARY KEY (id),
 UNIQUE (id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8 ;

ALTER TABLE srv_survey_list ADD CONSTRAINT srv_survey_list_id FOREIGN KEY (id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE srv_survey_list ADD last_updated DATETIME default null AFTER updated ;

UPDATE misc SET value='11.03.07' WHERE what="version";

ALTER TABLE srv_datasetting_profile ADD crossChk0 ENUM('0','1') NOT NULL DEFAULT '1';
ALTER TABLE srv_datasetting_profile ADD crossChk1 ENUM('0','1') NOT NULL DEFAULT '0';
ALTER TABLE srv_datasetting_profile ADD crossChk2 ENUM('0','1') NOT NULL DEFAULT '0';
ALTER TABLE srv_datasetting_profile ADD crossChk3 ENUM('0','1') NOT NULL DEFAULT '0';
ALTER TABLE srv_datasetting_profile ADD crossChkEC ENUM('0','1') NOT NULL DEFAULT '0';
ALTER TABLE srv_datasetting_profile ADD crossChkRE ENUM('0','1') NOT NULL DEFAULT '0';
ALTER TABLE srv_datasetting_profile ADD crossChkSR ENUM('0','1') NOT NULL DEFAULT '0';
ALTER TABLE srv_datasetting_profile ADD crossChkAR ENUM('0','1') NOT NULL DEFAULT '0';
ALTER TABLE srv_datasetting_profile ADD doColor ENUM('0','1') NOT NULL DEFAULT '0';

UPDATE misc SET value='11.03.09' WHERE what="version";

ALTER TABLE struktura_baze ADD COLUMN ArchiveDate tinyint(1) not null default '-1';


ALTER TABLE srv_calculation ADD grd_id INT NOT NULL AFTER vre_id;

UPDATE misc SET value='11.03.17' WHERE what="version";

alter table selections add column ShowCat tinyint(1) not null default 1;

UPDATE misc SET value='11.03.24' WHERE what="version";

ALTER TABLE hour_users ADD skupina INT NOT NULL ;

CREATE TABLE hour_skupina (
id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
ime VARCHAR( 200 ) NOT NULL
) ENGINE = InnoDB;

UPDATE misc SET value='11.04.05' WHERE what="version";

CREATE  TABLE IF NOT EXISTS srv_invitations (
  id INT(11) NOT NULL AUTO_INCREMENT ,
  sid INT(11) NOT NULL ,
  type ENUM('0','1') DEFAULT 0 ,
  status ENUM('0','1') DEFAULT 1 ,
  name VARCHAR(100) NOT NULL ,
  recipients INT NOT NULL DEFAULT 0 ,
  responses INT NOT NULL DEFAULT 0 ,
  deleted ENUM('0','1') DEFAULT 0 ,
  PRIMARY KEY (id)
) 
ENGINE = InnoDB 
DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS srv_invitations_recipients (
  id INT NOT NULL AUTO_INCREMENT ,
  uid VARCHAR(10) NOT NULL,
  invitations_id INT NOT NULL ,
  email VARCHAR(100) NULL ,
  firstname VARCHAR(45) NULL ,
  lastname VARCHAR(45) NULL ,
  password VARCHAR(45) NULL ,
  salutation VARCHAR(45) NULL ,
  phone VARCHAR(45) NULL ,
  custom VARCHAR(45) NULL ,
  sent ENUM('0','1') DEFAULT 1 ,
  responded ENUM('0','1') DEFAULT 1 ,
  optedout ENUM('0','1') DEFAULT 1 ,
  deleted ENUM('0','1') DEFAULT 0 ,
  PRIMARY KEY (id) ,
  UNIQUE (uid) ,
  UNIQUE (invitations_id,email) ,
  CONSTRAINT fk_srv_invitations_recipients_srv_invitations
    FOREIGN KEY (invitations_id)
    REFERENCES srv_invitations (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
)
ENGINE = InnoDB 
DEFAULT CHARSET=utf8 ;
CREATE INDEX fk_srv_invitations_recipients_srv_invitations ON srv_invitations_recipients (invitations_id ASC) ;

CREATE TABLE IF NOT EXISTS srv_invitations_messages (
  id INT NOT NULL AUTO_INCREMENT ,
  invitations_id INT NOT NULL ,
  recipients_type ENUM('0','1','2','3') DEFAULT '0' ,
  subject_set ENUM('0','1') DEFAULT '0' ,
  subject_text VARCHAR(100) NOT NULL ,
  body_text MEDIUMTEXT NULL,
  drafts ENUM('0','1') DEFAULT '1' ,
  mailed ENUM('0','1') DEFAULT '0' ,
  reply_to VARCHAR(100) NOT NULL ,
  PRIMARY KEY (id) ,
  CONSTRAINT fk_srv_invitations_messages_srv_invitations
    FOREIGN KEY (invitations_id)
    REFERENCES srv_invitations (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
)
ENGINE = InnoDB 
DEFAULT CHARSET=utf8 ;
CREATE INDEX fk_srv_invitations_messages_srv_invitations ON srv_invitations_messages (invitations_id ASC) ;

UPDATE misc SET value='11.04.06' WHERE what="version";

ALTER TABLE srv_invitations CHANGE 
	type type ENUM( '0', '1', '2' ) DEFAULT '0';

ALTER TABLE srv_anketa ADD mass_insert ENUM( '0', '1' ) NOT NULL ;

UPDATE misc SET value='11.04.11' WHERE what="version";

ALTER TABLE srv_anketa CHANGE naslov naslov VARCHAR( 40 ) NOT NULL; 

UPDATE misc SET value='11.04.14' WHERE what="version";

ALTER TABLE srv_spremenljivka ADD naslov_graf VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE srv_vrednost ADD naslov_graf VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE srv_grid ADD naslov_graf VARCHAR(255) NOT NULL DEFAULT '';

UPDATE misc SET value='11.04.19' WHERE what="version";

ALTER TABLE srv_survey_list DROP INDEX id ;

ALTER TABLE users ADD COLUMN last_login datetime not null default '0000-00-00 00:00:01';


UPDATE misc SET value='11.05.05' WHERE what="version";

CREATE TABLE srv_nice_links (
id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
ank_id INT NOT NULL ,
link VARCHAR( 30 ) NOT NULL ,
UNIQUE (link)
) ENGINE = InnoDB;

ALTER TABLE srv_nice_links ADD CONSTRAINT srv_nice_links_ank_id FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE;

# zato, da imamo lahko case-sensitive lepe linke
ALTER TABLE srv_nice_links CHANGE link link VARBINARY( 30 ) NOT NULL ;

#
#	ko se bo updatal 1ko je treba vse lepe linke iz htaccess prepisat se v tabelo srv_nice_links !!!
#

UPDATE misc SET value='11.05.13' WHERE what="version";

ALTER TABLE srv_anketa CHANGE flat flat TINYINT NOT NULL DEFAULT '0';

UPDATE misc SET value='11.05.15' WHERE what="version";

CREATE TABLE srv_password (
ank_id INT NOT NULL ,
password VARCHAR( 20 ) NOT NULL ) ENGINE = InnoDB;

ALTER TABLE srv_password ADD CONSTRAINT srv_password_ank_id FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE;

UPDATE misc SET value='11.05.16' WHERE what="version";

CREATE TABLE fb_users (id integer not null auto_increment, uid integer not null default 0, first_name varchar(255), last_name varchar(255), gender varchar(2), timezone varchar(5), profile_link varchar(255), key id(id));

ALTER TABLE srv_nice_links CHANGE link link VARBINARY( 50 ) NOT NULL ;

UPDATE misc SET value='11.05.19' WHERE what="version";

ALTER TABLE srv_spremenljivka ADD edit_graf TINYINT NOT NULL DEFAULT '0' AFTER naslov_graf;

# da se resi priblem label grafov pri kopiranu vprasanj
UPDATE srv_spremenljivka SET naslov_graf = naslov WHERE naslov_graf != naslov;
UPDATE srv_grid SET naslov_graf = naslov WHERE naslov_graf != naslov;
UPDATE srv_vrednost SET naslov_graf = naslov WHERE naslov_graf != naslov;

UPDATE misc SET value='11.05.22' WHERE what="version";

ALTER TABLE srv_datasetting_profile ADD dovalues ENUM('0','1') NOT NULL DEFAULT '1';

UPDATE misc SET value='11.05.23' WHERE what="version";

ALTER TABLE srv_user ADD INDEX ( preview ) ;

UPDATE misc SET value='11.05.30' WHERE what="version";

ALTER TABLE srv_anketa ADD INDEX ( active );

UPDATE misc SET value='11.05.30a' WHERE what="version";

ALTER TABLE srv_anketa ADD monitoring ENUM( '0', '1' ) NOT NULL ;

UPDATE misc SET value='11.06.01' WHERE what="version";

ALTER TABLE srv_anketa ADD show_email ENUM( '0', '1' ) NOT NULL ;

UPDATE misc SET value='11.06.03' WHERE what="version";

ALTER TABLE srv_spremenljivka ADD wide_graf TINYINT NOT NULL DEFAULT '0' AFTER edit_graf;

UPDATE misc SET value='11.06.08' WHERE what="version";

CREATE TABLE srv_recode (
	ank_id INT NOT NULL ,
	spr_id INT NOT NULL ,
	search VARCHAR( 15 ) NOT NULL ,
	value VARCHAR( 15 ) NOT NULL ,
	UNIQUE (ank_id,spr_id,search)
) ENGINE = InnoDB;

ALTER TABLE srv_spremenljivka ADD coding ENUM( '0', '1' ) NOT NULL ;

UPDATE misc SET value='11.06.08a' WHERE what="version";

ALTER TABLE srv_recode ADD operator ENUM( '0', '1', '2', '3', '4', '5' ) DEFAULT '0';
ALTER TABLE srv_recode DROP INDEX ank_id; 
ALTER TABLE srv_recode ADD UNIQUE (ank_id,spr_id,search,operator);

UPDATE misc SET value='11.06.09' WHERE what="version";

ALTER TABLE srv_datasetting_profile ADD doValues ENUM('0','1') NOT NULL DEFAULT '0';
ALTER TABLE srv_datasetting_profile ADD showCategories ENUM('0','1') NOT NULL DEFAULT '1';
ALTER TABLE srv_datasetting_profile ADD showOther ENUM('0','1') NOT NULL DEFAULT '1';
ALTER TABLE srv_datasetting_profile ADD showNumbers ENUM('0','1') NOT NULL DEFAULT '1';
ALTER TABLE srv_datasetting_profile ADD showText ENUM('0','1') NOT NULL DEFAULT '1';

ALTER TABLE srv_if ADD enabled ENUM( '0', '1', '2' ) NOT NULL ;

UPDATE misc SET value='11.06.15' WHERE what="version";

ALTER TABLE srv_datasetting_profile ADD chartNumbering ENUM('0','1') NOT NULL DEFAULT '0';
ALTER TABLE srv_datasetting_profile ADD chartFP ENUM('0','1') NOT NULL DEFAULT '0';

UPDATE misc SET value='11.06.20' WHERE what="version";

DROP TABLE IF EXISTS srv_invitations; # ta query ne gre skoz
DROP TABLE IF EXISTS srv_invitations_profiles;

DROP TABLE IF EXISTS srv_invitations_archive;
CREATE TABLE IF NOT EXISTS srv_invitations_archive (
  id int(11) NOT NULL AUTO_INCREMENT,
  ank_id int(11) NOT NULL,
  date_send datetime DEFAULT NULL,
  subject_text varchar(100) NOT NULL,
  body_text mediumtext,
  cnt_succsess int(11) NOT NULL,
  cnt_error int(11) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS srv_invitations_archive_recipients;
CREATE TABLE IF NOT EXISTS srv_invitations_archive_recipients (
  arch_id int(11) NOT NULL,
  rec_id int(11) NOT NULL,
  success enum('0','1') NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS srv_invitations_messages;
CREATE TABLE IF NOT EXISTS srv_invitations_messages (
  id int(11) NOT NULL AUTO_INCREMENT,
  ank_id int(11) NOT NULL,
  subject_text varchar(100) NOT NULL,
  body_text mediumtext,
  reply_to varchar(100) NOT NULL,
  isdefault enum('0','1') DEFAULT '0',
  PRIMARY KEY (id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS srv_invitations_recipients;
CREATE TABLE IF NOT EXISTS srv_invitations_recipients (
  id int(11) NOT NULL AUTO_INCREMENT,
  ank_id int(11) NOT NULL,
  email varchar(100) DEFAULT NULL,
  firstname varchar(45) DEFAULT NULL,
  lastname varchar(45) DEFAULT NULL,
  password varchar(45) DEFAULT NULL,
  salutation varchar(45) DEFAULT NULL,
  phone varchar(45) DEFAULT NULL,
  custom varchar(45) DEFAULT NULL,
  sent enum('0','1') DEFAULT '1',
  responded enum('0','1') DEFAULT '1',
  unsubscribed enum('0','1') DEFAULT '1',
  deleted enum('0','1') DEFAULT '0',
  date_inserted datetime NOT NULL,
  date_sent datetime NOT NULL,
  date_responded datetime NOT NULL,
  date_unsubscribed datetime NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY ank_id (ank_id,email)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

UPDATE misc SET value='11.07.22' WHERE what="version";

ALTER TABLE menu ADD COLUMN ShowPlusAbove tinyint(1) not null default 0;
ALTER TABLE menu ADD COLUMN ShowPlusBelow tinyint(1) not null default 0;

UPDATE misc SET value='11.07.26' WHERE what="version";

# OK, kr so ble težave s ključi za datoteke pošiljanja sporočil, gremo jovo na novo :)
DROP TABLE IF EXISTS srv_invitations_archive_recipients;
DROP TABLE IF EXISTS srv_invitations_archive;
DROP TABLE IF EXISTS srv_invitations_messages;
DROP TABLE IF EXISTS srv_invitations_recipients;
DROP TABLE IF EXISTS srv_invitations_profiles;
DROP TABLE IF EXISTS srv_invitations;

CREATE TABLE IF NOT EXISTS srv_invitations_archive (
  id int(11) NOT NULL AUTO_INCREMENT,
  ank_id int(11) NOT NULL,
  date_send datetime DEFAULT NULL,
  subject_text varchar(100) NOT NULL,
  body_text mediumtext,
  cnt_succsess int(11) NOT NULL,
  cnt_error int(11) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS srv_invitations_archive_recipients (
  arch_id int(11) NOT NULL,
  rec_id int(11) NOT NULL,
  success enum('0','1') NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS srv_invitations_messages (
  id int(11) NOT NULL AUTO_INCREMENT,
  ank_id int(11) NOT NULL,
  subject_text varchar(100) NOT NULL,
  body_text mediumtext,
  reply_to varchar(100) NOT NULL,
  isdefault enum('0','1') DEFAULT '0',
  PRIMARY KEY (id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS srv_invitations_recipients (
  id int(11) NOT NULL AUTO_INCREMENT,
  ank_id int(11) NOT NULL,
  email varchar(100) DEFAULT NULL,
  firstname varchar(45) DEFAULT NULL,
  lastname varchar(45) DEFAULT NULL,
  password varchar(45) DEFAULT NULL,
  salutation varchar(45) DEFAULT NULL,
  phone varchar(45) DEFAULT NULL,
  custom varchar(45) DEFAULT NULL,
  sent enum('0','1') DEFAULT '1',
  responded enum('0','1') DEFAULT '1',
  unsubscribed enum('0','1') DEFAULT '1',
  deleted enum('0','1') DEFAULT '0',
  date_inserted datetime NOT NULL,
  date_sent datetime NOT NULL,
  date_responded datetime NOT NULL,
  date_unsubscribed datetime NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY ank_id (ank_id,email)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS srv_invitations_recipients_profiles;
CREATE TABLE IF NOT EXISTS srv_invitations_recipients_profiles (
  pid int(11) NOT NULL AUTO_INCREMENT,
  name varchar(100) DEFAULT NULL,
  uid int(11) NOT NULL,
  fields varchar(100) DEFAULT NULL,
  respondents TEXT NOT NULL,
  PRIMARY KEY (pid)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

UPDATE misc SET value='11.08.02' WHERE what="version";

ALTER TABLE srv_invitations_recipients ADD cookie CHAR( 32 ) NOT NULL AFTER password;

UPDATE misc SET value='11.08.04' WHERE what="version";

ALTER TABLE struktura_avtor ADD COLUMN mtime datetime not null default '0000-00-00 00:00:00';
ALTER TABLE struktura_avtor ADD COLUMN muid datetime not null default '0000-00-00 00:00:00';
ALTER TABLE struktura_vir ADD COLUMN mtime datetime not null default '0000-00-00 00:00:00';
ALTER TABLE struktura_vir ADD COLUMN muid datetime not null default '0000-00-00 00:00:00';
ALTER TABLE struktura_db ADD COLUMN mtime datetime not null default '0000-00-00 00:00:00';
ALTER TABLE struktura_db ADD COLUMN muid datetime not null default '0000-00-00 00:00:00';
ALTER TABLE struktura_companies ADD COLUMN mtime datetime not null default '0000-00-00 00:00:00';
ALTER TABLE struktura_companies ADD COLUMN muid datetime not null default '0000-00-00 00:00:00';
ALTER TABLE struktura_country ADD COLUMN mtime datetime not null default '0000-00-00 00:00:00';
ALTER TABLE struktura_country ADD COLUMN muid datetime not null default '0000-00-00 00:00:00';

UPDATE misc SET value='11.08.10' WHERE what="version";

ALTER TABLE srv_invitations_recipients_profiles ADD insert_time DATETIME NOT NULL;
ALTER TABLE srv_invitations_recipients_profiles ADD comment CHAR( 100 ) NOT NULL;

ALTER TABLE srv_invitations_messages ADD uid int(11) NOT NULL;
ALTER TABLE srv_invitations_messages ADD insert_time DATETIME NOT NULL;
ALTER TABLE srv_invitations_messages ADD comment CHAR( 100 ) NOT NULL;
ALTER TABLE srv_invitations_messages ADD edit_uid int(11) NOT NULL;
ALTER TABLE srv_invitations_messages ADD edit_time DATETIME NOT NULL;

UPDATE misc SET value='11.08.18' WHERE what="version";

ALTER TABLE srv_invitations_archive ADD uid int(11) NOT NULL;
ALTER TABLE srv_invitations_archive ADD comment CHAR( 100 ) NOT NULL;

UPDATE misc SET value='11.08.21' WHERE what="version";

ALTER TABLE srv_invitations_recipients_profiles ADD from_survey int(11) NOT NULL;
UPDATE misc SET value='11.08.22' WHERE what="version";

INSERT INTO srv_help (what, help) VALUES
('srv_diag_time', 'Predviden čas izpolnjevanja:\ndo 2 min => zelo kratka anketa\n2-5 min => kratka anketa\n5-10 min => srednje dolga anketa\n10-15 min => dolga anketa\n15-30 min => zelo dolga anketa\n30-45 min => obsežna anketa\nveč kot 45 min => zelo obsežna anketa');
INSERT INTO srv_help (what, help) VALUES
('srv_diag_complexity', 'Kompleksnost:\nbrez pogojev ali blokov => zelo enostavna anketa\n1 pogoj ali blok => enostavna anketa\n1-10 pogojev ali blokov => zahtevna anketa\n10-50 pogojev ali blokov => kompleksna anketa \nveč kot 50 pogojev ali blokov => zelo kompleksna anketa');

ALTER TABLE srv_spremenljivka ADD dynamic_mg ENUM('0','1') NOT NULL DEFAULT '0';

UPDATE misc SET value='11.09.21' WHERE what="version";

CREATE TABLE IF NOT EXISTS srv_survey_skins_groups (
  id int(11) NOT NULL AUTO_INCREMENT,
  name varchar(100) DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS srv_survey_skins (
  id int(11) NOT NULL AUTO_INCREMENT,
  gid int(11) NOT NULL,
  name varchar(100) DEFAULT NULL,
  filename varchar(100) DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

UPDATE misc SET value='11.09.21a' WHERE what="version";

ALTER TABLE srv_vrednost CHANGE naslov naslov TEXT CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
ALTER TABLE srv_vrednost CHANGE naslov2 naslov2 TEXT CHARACTER SET utf8 COLLATE utf8_bin NOT NULL ;

UPDATE misc SET value='11.09.26' WHERE what="version";

TRUNCATE TABLE srv_survey_skins_groups;
INSERT INTO srv_survey_skins_groups (id ,name) VALUES (1 , 'Classic');
INSERT INTO srv_survey_skins_groups (id ,name) VALUES (2 , 'Classic shade');
INSERT INTO srv_survey_skins_groups (id ,name) VALUES (3 , 'Clear');
INSERT INTO srv_survey_skins_groups (id ,name) VALUES (4 , 'Colorful');
INSERT INTO srv_survey_skins_groups (id ,name) VALUES (5 , 'Elegant');
INSERT INTO srv_survey_skins_groups (id ,name) VALUES (6 , 'Embed');
INSERT INTO srv_survey_skins_groups (id ,name) VALUES (7 , 'Matrix');
INSERT INTO srv_survey_skins_groups (id ,name) VALUES (8 , 'Modern');
INSERT INTO srv_survey_skins_groups (id ,name) VALUES (9 , 'Relief');
INSERT INTO srv_survey_skins_groups (id ,name) VALUES (10 , 'Safe');
INSERT INTO srv_survey_skins_groups (id ,name) VALUES (11 , 'Strips');
INSERT INTO srv_survey_skins_groups (id ,name) VALUES (12 , 'Tailored');
INSERT INTO srv_survey_skins_groups (id ,name) VALUES (13 , 'test_Mobile');	

UPDATE misc SET value='11.09.27' WHERE what="version";

ALTER TABLE srv_invitations_recipients DROP INDEX ank_id;
ALTER TABLE srv_invitations_recipients ADD UNIQUE ank_id_unique (ank_id,email,firstname,lastname,salutation,phone,custom); 

UPDATE misc SET value='11.09.29' WHERE what="version";

#dodamo na kak način prikazujemo email vabila 0 = nov način, 1 = star način
ALTER TABLE srv_anketa ADD old_email_style ENUM( '0', '1' ) NOT NULL DEFAULT '0';

UPDATE srv_anketa SET old_email_style = '1' WHERE id NOT IN ( SELECT DISTINCT ank_id FROM srv_invitations_recipients GROUP BY ank_id );

UPDATE misc SET value='11.10.03' WHERE what="version";

CREATE TABLE IF NOT EXISTS srv_survey_unsubscribe (
  id int(11) NOT NULL AUTO_INCREMENT,
  ank_id int(11) NOT NULL,
  email varchar(100) DEFAULT NULL,
  unsubscribe_time DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY ank_id (ank_id,email)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE srv_survey_unsubscribe ADD CONSTRAINT srv_survey_unsubscribe_ank_id FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE;

UPDATE misc SET value='11.10.04' WHERE what="version";

ALTER TABLE srv_spremenljivka CHANGE wide_graf wide_graf TINYINT NOT NULL DEFAULT '1';

ALTER TABLE srv_user ADD inv_res_id int(11) default NULL;
ALTER TABLE srv_user ADD INDEX index_inv_res_id (inv_res_id);

ALTER table srv_invitations_recipients ADD column last_status tinyint(4) NOT NULL DEFAULT 0;

UPDATE misc SET value='11.10.06' WHERE what="version";

INSERT INTO srv_help (what, help) VALUES ('displaychart_settings', '&#268;e &#382;elite urediti manjkajo&#269;e vrednosti ali za grafe prilagoditi text vpra&#353;anja, potem to spremenite v naprednih nastavitvah.');

UPDATE misc SET value='11.10.08' WHERE what="version";

ALTER TABLE srv_invitations_archive ADD CONSTRAINT srv_invitations_archive_ank_id FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE srv_invitations_archive_recipients ADD CONSTRAINT srv_invitations_archive_recipients_arch_id FOREIGN KEY (arch_id) REFERENCES srv_invitations_archive (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE srv_invitations_archive_recipients ADD CONSTRAINT srv_invitations_archive_recipients_rec_id FOREIGN KEY (rec_id) REFERENCES srv_invitations_recipients (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE srv_invitations_messages ADD CONSTRAINT srv_invitations_messages_ank_id FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE srv_invitations_recipients ADD CONSTRAINT srv_invitations_recipients_ank_id FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE;

UPDATE misc SET value='11.10.09' WHERE what="version";

CREATE TABLE srv_spremenljivka_tracking (
ank_id INT NOT NULL ,
spr_id INT NOT NULL ,
tracking_id INT NOT NULL ,
tracking_uid INT NOT NULL ,
tracking_time DATETIME NOT NULL
) ENGINE = InnoDB;

ALTER TABLE srv_spremenljivka_tracking ADD CONSTRAINT srv_spremenljivka_tracking_ank_id FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE srv_spremenljivka_tracking ADD CONSTRAINT srv_spremenljivka_tracking_spr_id FOREIGN KEY (spr_id) REFERENCES srv_spremenljivka (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE srv_spremenljivka_tracking ADD CONSTRAINT srv_spremenljivka_tracking_tracking_id FOREIGN KEY (spr_id) REFERENCES srv_spremenljivka (id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE srv_anketa ADD vprasanje_tracking ENUM( '0', '1' ) NOT NULL DEFAULT '0';

UPDATE misc SET value='11.10.12' WHERE what="version";

ALTER TABLE srv_anketa CHANGE vprasanje_tracking vprasanje_tracking ENUM( '0', '1', '2' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '0';

UPDATE misc SET value='11.10.14' WHERE what="version";

DROP TABLE IF EXISTS srv_condition_profiles;
CREATE TABLE IF NOT EXISTS srv_condition_profiles (
	id int(11) NOT NULL AUTO_INCREMENT,
	sid int(11) NOT NULL,
	uid int(11) NOT NULL,
	name varchar(250) NOT NULL,
	if_id int(11) NOT NULL,
	condition_label TEXT NOT NULL,
	condition_error tinyint(1) not null default 0,
	PRIMARY KEY (id),
	UNIQUE KEY (sid,uid,if_id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
ALTER TABLE srv_condition_profiles ADD FOREIGN KEY (sid) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE srv_condition_profiles ADD FOREIGN KEY (if_id) REFERENCES srv_if (id) ON DELETE CASCADE ON UPDATE CASCADE;

UPDATE misc SET value='11.10.14a' WHERE what="version";

ALTER TABLE srv_datasetting_profile ADD hideEmpty ENUM('0','1') NOT NULL DEFAULT '0';

UPDATE misc SET value='11.10.15' WHERE what="version";

ALTER TABLE srv_spremenljivka ADD label VARCHAR( 50 ) NOT NULL AFTER variable_custom;

UPDATE misc SET value='11.10.17' WHERE what="version";

ALTER TABLE srv_anketa CHANGE vprasanje_tracking vprasanje_tracking ENUM( '0', '1', '2', '3' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '0';

UPDATE misc SET value='11.10.18' WHERE what="version";
INSERT INTO misc (what, value) VALUES ('RegLang', '0');
ALTER TABLE users_to_be ADD lang INT NOT NULL;
UPDATE users_to_be SET lang = (SELECT value FROM misc WHERE what = "SurveyLang_admin");

UPDATE misc SET value='11.10.25' WHERE what="version";

ALTER TABLE srv_datasetting_profile ADD dataPdfType ENUM('0','1','2') NOT NULL DEFAULT '0';

UPDATE misc SET value='11.10.26' WHERE what="version";

ALTER TABLE srv_datasetting_profile ADD enableInspect ENUM('0','1') NOT NULL DEFAULT '0';

UPDATE misc SET value='11.10.26a' WHERE what="version";

ALTER TABLE srv_datasetting_profile ADD dataShowIcons ENUM('0','1') NOT NULL DEFAULT '1';

UPDATE misc SET value='11.10.26b' WHERE what="version";

ALTER TABLE srv_dostop ADD alert_complete_if INT NULL;
ALTER TABLE srv_dostop ADD FOREIGN KEY (alert_complete_if) REFERENCES srv_if (id) ON DELETE SET NULL ON UPDATE CASCADE;

UPDATE misc SET value='11.11.02' WHERE what="version";

ALTER TABLE srv_alert ADD finish_respondent_if INT NULL DEFAULT NULL ,
ADD finish_respondent_cms_if INT NULL DEFAULT NULL; 

ALTER TABLE srv_alert ADD FOREIGN KEY (finish_respondent_if) REFERENCES srv_if (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE srv_alert ADD FOREIGN KEY (finish_respondent_cms_if) REFERENCES srv_if (id) ON DELETE SET NULL ON UPDATE CASCADE;

UPDATE misc SET value='11.11.03' WHERE what="version";

ALTER TABLE srv_spremenljivka CHANGE dynamic_mg dynamic_mg ENUM('0','1','2','3','4') NOT NULL DEFAULT '0';

# 08.11.11
# !!! POZOR !!!
# NE UPGRADAJ SE SISPETA NA PRODUKCIJSKIH SAJTIH!!!
#
# (survey del nadgradi ce mislis da je OK)
#
# naceloma dela (prvi testi), ampak je sprememb enostavno prevec da bi sli upgradat brez testov pravih userjev!!!!
#
# povezane komponente: prijava, profil, alert, baze, rubrike, navigacije, forum ipd.
# !!! POZOR !!!


INSERT INTO srv_help (what, help) VALUES ('srv_crosstab_inspect', 'Kdo je to -> Inspect');
update srv_help set help='Mo&#382;nost "Kdo je to" omogo&#269;a da s klikom na &#382;eleno frekvenco, ogledamo katere enote se v njej nahajajo.<a href="http://www.1ka.si/c/836/Kdojeto/?preid=789" target="_blank">ve&#269;</a>' where what='srv_crosstab_inspect';

UPDATE misc SET value='11.11.20' WHERE what="version";

ALTER TABLE srv_alert ADD finish_other_if INT NULL DEFAULT NULL ;

ALTER TABLE srv_alert ADD FOREIGN KEY (finish_other_if) REFERENCES srv_if (id) ON DELETE SET NULL ON UPDATE CASCADE;

UPDATE misc SET value='11.11.22' WHERE what="version";

#
# UPGRADE SISTEMA DOSTOPA DO MENUJA
# Za uporabo poglej admin->navigacija->menu_content_edit.php, del okoli extended pravic
# Na kratko- omogocen je finetuning kaj lahko kdo dela
# !!! POCAKAJ do miklavza pred upgradom produkcije, da se 1x pretestiram!!!

ALTER TABLE menu ADD COLUMN extended_perms integer not null default 0;

# konverzija
# M-A-C-U -> ext_perm
# 0 0 0 0 -> 3222222
# 0 0 0 1 -> 3222220
# 0 0 1 x -> 3222200
# 0 1 x x -> 3202000
# 0 2 x x -> 3222211
# 1 x x x -> 0000000

UPDATE menu SET extended_perms=CONV(3222222,4,10) WHERE meta=0 AND admin=0 AND clan=0 AND user=0;
UPDATE menu SET extended_perms=CONV(3222220,4,10) WHERE meta=0 AND admin=0 AND clan=0 AND user=1;
UPDATE menu SET extended_perms=CONV(3222200,4,10) WHERE meta=0 AND admin=0 AND clan=1;
UPDATE menu SET extended_perms=CONV(3202000,4,10) WHERE meta=0 AND admin=1;
UPDATE menu SET extended_perms=CONV(3222211,4,10) WHERE meta=0 AND admin=2;
UPDATE menu SET extended_perms=CONV(0000000,4,10) WHERE meta=1;

UPDATE misc SET value='11.12.03' WHERE what="version";

ALTER TABLE srv_condition_profiles ADD COLUMN type ENUM('default','inspect') NOT NULL DEFAULT 'default';

UPDATE misc SET value='11.12.06' WHERE what="version";

# ko se bo updatal skine:
ALTER TABLE srv_anketa CHANGE skin skin VARCHAR( 100 ) NOT NULL DEFAULT 'Default';

UPDATE misc SET value='11.12.06a' WHERE what="version";

# za evidenco komu je bilo poslano navadno vabilo 
CREATE TABLE IF NOT EXISTS srv_simple_mail_invitation (
	id int(11) NOT NULL AUTO_INCREMENT,
	ank_id int(11) NOT NULL,
	email varchar(250) NOT NULL,
	send_time DATETIME NOT NULL,	
	state ENUM('ok','error','quota_exceeded'),
	usr_id int(11) NOT NULL,
	PRIMARY KEY (id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

UPDATE misc SET value='11.12.07' WHERE what="version";

CREATE TABLE msn_users (id integer auto_increment, uid integer, key id(id));

TRUNCATE TABLE srv_survey_skins_groups ;

UPDATE misc SET value='11.12.08' WHERE what="version";

# popravi stari .htaccess za lepe linke v 1ki, ker se je spremenil nacin zapisovanja!

UPDATE srv_anketa SET skin = CASE skin

WHEN 'Default' THEN 'Default'

WHEN 'Colorful' THEN 'Colorful'
WHEN 'Classic - grey&blue' THEN 'Colorful'
WHEN 'Classic shade - green' THEN 'Colorful'
WHEN 'Classic shade - blue' THEN 'Colorful'
WHEN 'Classic - orange' THEN 'Colorful'
WHEN '3122_Colorful_2' THEN 'Colorful'
WHEN '106_1987_Colorful_2' THEN 'Colorful'
WHEN 'Classic - green' THEN 'Colorful'
WHEN '370_Classic - grey&blue' THEN 'Colorful'
WHEN 'Colorful' THEN 'Colorful'
WHEN 'Colorful' THEN 'Colorful'

WHEN 'Modern' THEN 'Modern'
WHEN '1425_FSD' THEN 'Modern'
WHEN '3268_DIASOCKS' THEN 'Modern'

WHEN 'Relief' THEN 'Relief'
WHEN 'Relief - blue' THEN 'Relief'
WHEN 'Relief - blue&yellow' THEN 'Relief'
WHEN 'Relief - green&blue' THEN 'Relief'

WHEN 'Safe' THEN 'Safe'
WHEN 'Safe - green' THEN 'Safe'
WHEN 'Safe - orange' THEN 'Safe'
WHEN 'Safe - yellow' THEN 'Safe'
WHEN 'SafeNajstniki' THEN 'Safe'
WHEN 'SafeNone' THEN 'Safe'
WHEN 'SafeOtroci' THEN 'Safe'
WHEN 'SafeStarsi' THEN 'Safe'
WHEN 'SafeUcitelji' THEN 'Safe'
WHEN '2065_SafeNone' THEN 'Safe'

WHEN 'Uni' THEN 'Uni'
WHEN 'uni' THEN 'Uni'
WHEN '72_uni' THEN 'Uni'
WHEN 'FDVinfo' THEN 'Uni'
WHEN '3202_UNI' THEN 'Uni'
WHEN '1556_uni' THEN 'Uni'
WHEN '72_uni_1' THEN 'Uni'
WHEN '3231_uni' THEN 'Uni'

ELSE 'Default' END;

INSERT INTO srv_help (what,help) VALUES 
('displaydata_data', 'Kadar je opcija izbrana, se v tabeli prika&#382;ejo podatki respondentov'),
('displaydata_status', 'status (6-kon&#269;al anketo, 5-delno izpolnjena, 4-klik na anketo, 3-klik na nagovor, 2-epo&#353;ta-napaka, 1-epo&#353;ta-neodgovor, 0-epo&#353;ta-ni poslana),<br>lurker - prazna anketa (1 = da, 0 = ne),<br>Zaporedna &#353;tevilka vnosa'),
('displaydata_meta', 'Priak&#382;e meta podatke uporabnika: datum vnosa, datum popravljanja, &#269;ase po straneh, IP, JavaScript, podatke brskalnika'),
('displaydata_system', 'Prika&#382;e sistemske podatke respondenta: ime, priimek, email....')
ON DUPLICATE KEY UPDATE help = VALUES (help);

UPDATE misc SET value='11.12.12' WHERE what="version";

update srv_anketa set skin='Default' where skin='Embed';

UPDATE misc SET value='11.12.13' WHERE what="version";

ALTER TABLE struktura_kategorij ADD COLUMN keywords tinyint(1) not null default 0;

CREATE TABLE kat_keywords (bid integer, cid integer);


# zaradi primerjave tekstov v pogojih je potrebno povečati dolžino polja (ker je prej rezalo pri 100-tem zanaku
ALTER TABLE srv_condition CHANGE text text TEXT CHARACTER SET utf8 COLLATE utf8_bin NOT NULL; 

ALTER table srv_invitations_recipients ADD column inserted_uid int(11) NOT NULL DEFAULT 0;
ALTER table srv_invitations_recipients ADD column list_id int(11) NOT NULL DEFAULT 0;

UPDATE misc SET value='11.12.29' WHERE what="version";

CREATE TABLE srv_alert_custom (
ank_id INT NOT NULL ,
type ENUM( 'respondent', 'respondent_cms', 'author', 'other' ) NOT NULL ,
uid INT NOT NULL ,
subject VARCHAR( 250 ) NOT NULL ,
text TEXT NOT NULL 
) ENGINE = InnoDB DEFAULT CHARSET = utf8;


ALTER TABLE srv_alert_custom ADD FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE srv_alert_custom ADD UNIQUE (ank_id, type, uid);

UPDATE misc SET value='11.12.30' WHERE what="version";

CREATE table oid_users (uid integer);

UPDATE misc SET value='12.01.04' WHERE what="version";

ALTER table srv_invitations_recipients ADD column date_deleted datetime NOT NULL AFTER date_unsubscribed; 
ALTER table srv_invitations_recipients ADD column uid_deleted int(11) NOT NULL AFTER date_deleted; 

UPDATE misc SET value='12.01.04a' WHERE what="version";

ALTER TABLE srv_condition_profiles CHANGE type type ENUM('default','inspect','zoom') NOT NULL DEFAULT 'default';

UPDATE misc SET value='12.01.06' WHERE what="version";

CREATE TABLE IF NOT EXISTS srv_zoom_profiles (
id int(11) NOT NULL AUTO_INCREMENT,
sid int(11) NOT NULL,
uid int(11) NOT NULL,
name varchar(250) NOT NULL,
vars TEXT NOT NULL,
conditions TEXT NOT NULL,
if_id int(11) NOT NULL,
PRIMARY KEY (id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

UPDATE misc SET value='12.01.09' WHERE what="version";


# Ne pustimo brisat IDjev manjsih od 0, ker se uporabljajo za sistemske zadeve...

DELIMITER $$
DROP TRIGGER IF EXISTS srv_anketa_zero $$
CREATE TRIGGER srv_anketa_zero
BEFORE DELETE on srv_anketa
FOR EACH ROW
BEGIN
DECLARE dummy INTEGER;
IF OLD.id <= 0 THEN
SELECT Cannot_delete_IDs_smaller_than_zero INTO dummy FROM srv_anketa;
END IF;
END $$
DELIMITER ;


DELIMITER $$
DROP TRIGGER IF EXISTS srv_grupa_zero $$
CREATE TRIGGER srv_grupa_zero
BEFORE DELETE on srv_grupa
FOR EACH ROW
BEGIN
DECLARE dummy INTEGER;
IF OLD.id <= 0 THEN
SELECT Cannot_delete_IDs_smaller_than_zero INTO dummy FROM srv_grupa;
END IF;
END $$
DELIMITER ;


DELIMITER $$
DROP TRIGGER IF EXISTS srv_spremenljivka_zero $$
CREATE TRIGGER srv_spremenljivka_zero
BEFORE DELETE on srv_spremenljivka
FOR EACH ROW
BEGIN
DECLARE dummy INTEGER;
IF OLD.id <= 0 THEN
SELECT Cannot_delete_IDs_smaller_than_zero INTO dummy FROM srv_spremenljivka;
END IF;
END $$
DELIMITER ;


DELIMITER $$
DROP TRIGGER IF EXISTS srv_vrednost_zero $$
CREATE TRIGGER srv_vrednost_zero
BEFORE DELETE on srv_vrednost
FOR EACH ROW
BEGIN
DECLARE dummy INTEGER;
IF OLD.id <= 0 THEN
SELECT Cannot_delete_IDs_smaller_than_zero INTO dummy FROM srv_vrednost;
END IF;
END $$
DELIMITER ;


DELIMITER $$
DROP TRIGGER IF EXISTS srv_if_zero $$
CREATE TRIGGER srv_if_zero
BEFORE DELETE on srv_if
FOR EACH ROW
BEGIN
DECLARE dummy INTEGER;
IF OLD.id <= 0 THEN
SELECT Cannot_delete_IDs_smaller_than_zero INTO dummy FROM srv_if;
END IF;
END $$
DELIMITER ;

UPDATE misc SET value='12.01.13' WHERE what="version";

INSERT INTO srv_help (what ,help) VALUES ( 'displaydata_checkboxes', '<ul style="padding-left:15px"> <li>Kadar je izbrana opcija "<b>Podatki</b>", se v tabeli prikazujejo podatki respondentov.</li> <li>"<b>status</b>"<dl><dt>&nbsp;&nbsp;6-kon&#269;al anketo,</dt><dt>&nbsp;&nbsp;5-delno izpolnjena,</dt><dt>&nbsp;&nbsp;4-klik na anketo,</dt><dt>&nbsp;&nbsp;3-klik na nagovor,</dt><dt>&nbsp;&nbsp;2-epo&#353;ta-napaka,</dt><dt>&nbsp;&nbsp;1-epo&#353;ta-neodgovor,</dt><dt>&nbsp;&nbsp;0-epo&#353;ta-ni poslana),</dt><dt>&nbsp;&nbsp;lurker - prazna anketa (1 = da, 0 = ne),</dt><dt>&nbsp;&nbsp;Zaporedna &#353;tevilka vnosa</dt></dl></li> <li>Kadar je izbrana opcija "<b>Meta podatki</b>" prikazujemo meta podatke uporabnika: datum vnosa, datum popravljanja, &#269;ase po straneh, IP, JavaScript, podatke brskalnika</li> <li>Kadar je izbrana opcija "<b>Sistemski podatki</b>" prikazujemo sistemske podatke respondenta: ime, priimek, email....</li> </ul>');
UPDATE misc SET value='12.01.17' WHERE what="version";

CREATE TABLE IF NOT EXISTS srv_survey_unsubscribe_codes (
  ank_id int(11) NOT NULL,
  email varchar(100) DEFAULT NULL,
  code CHAR( 32 ) NOT NULL ,
  UNIQUE KEY ank_id (ank_id,email)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

UPDATE misc SET value='23.01.17' WHERE what="version";

INSERT INTO srv_help VALUES('srv_skala_edit', 'Ali je spremenljivka zvezna in se v analizah lahko racunajo povprecja.');
INSERT INTO srv_help VALUES('srv_statistika', 'Opcija ki se sicer redko uporablja, prikaze rezultate odgovora na naslednji strani.');

UPDATE misc SET value='12.01.24' WHERE what="version";

INSERT INTO srv_help VALUES('srv_data_only_valid', 'Prikaze samo respondente, ki so uspesno zakljucili anketo (status 5 in 6). Ce je uporabnik zgolj klikal preko ankete in si jo ogledoval, ne da bi kdaj kaj odgovoril (ogledovalec - lurker), njegov vnos ni prikazan.');
INSERT INTO srv_help VALUES('srv_data_onlyMySurvey', 'Kadar anketo resujete kot uporabnik Sispleta in imate vklopljeno opcijo da anketa prepozna responmdenta iz CMS, Lahko z enostavnim klikom pregledate le vase ankete.');

UPDATE misc SET value='12.01.25' WHERE what="version";

ALTER TABLE srv_invitations_messages ADD naslov varchar(100) NOT NULL AFTER ank_id;
UPDATE srv_invitations_messages SET naslov = subject_text;

UPDATE misc SET value='12.01.29' WHERE what="version";

ALTER TABLE srv_anketa ADD parapodatki ENUM( '0', '1' ) NOT NULL ;

UPDATE misc SET value='12.01.30' WHERE what="version";

CREATE TABLE srv_parapodatki (
ank_id INT NOT NULL ,
usr_id INT NOT NULL ,
datetime DATETIME NOT NULL ,
what VARCHAR( 150 ) NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8;

ALTER TABLE srv_parapodatki ADD CONSTRAINT fk_srv_parapodatki_ank_id FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE srv_parapodatki ADD CONSTRAINT fk_srv_parapodatki_usr_id FOREIGN KEY (usr_id) REFERENCES srv_user (id) ON DELETE CASCADE ON UPDATE CASCADE;

UPDATE misc SET value='12.01.31' WHERE what="version";

ALTER TABLE srv_datasetting_profile ADD chartTableAlign ENUM('0','1') NOT NULL DEFAULT '0' AFTER chartFP;
ALTER TABLE srv_datasetting_profile ADD chartTableMore ENUM('0','1') NOT NULL DEFAULT '0' AFTER chartTableAlign;

UPDATE misc SET value='12.01.31a' WHERE what="version";

INSERT INTO srv_help VALUES('usercode_skip', 'Dostop brez gesla:<br/><ul><li><b>Ne</b> - za izpolnjevanje ankete je nujno potreno vnesti geslo</li><li><b>Da</b> - anketo lahko izpolnjujemo tudi kadar ne vnesemo gesla</li><li><b>Samo avtor</b> - samo avtor ankete lahko izpolnjuje anketo brez vna&#353;anja gesla. Prijavljen mora biti v sisplet.</li></ul>');
INSERT INTO srv_help VALUES('usercode_required', '<ul><li><b>"Avtomatsko"</b> - geslo se samodejno prenese iz povezave email vabila</li><li><b>"Ro&#269;no"</b> uporabnik mora ro&#269;no vnesti geslo</li><ul/>');

UPDATE misc SET value='12.02.06' WHERE what="version";

ALTER TABLE srv_datasetting_profile ADD analysisGoTo ENUM('0','1') NOT NULL DEFAULT '0';

UPDATE misc SET value='12.02.07' WHERE what="version";

#ALTER TABLE srv_user ADD INDEX recnum ( recnum ) ;

ALTER TABLE srv_user DROP INDEX recnum;
ALTER TABLE srv_user DROP INDEX index_inv_res_id;

alter table srv_user add index recnum (ank_id, recnum);

    DELIMITER $$
    CREATE FUNCTION MAX_RECNUM (aid INT(11))
    RETURNS INT(11)
    DETERMINISTIC
    BEGIN
     DECLARE max INT(11);
     SELECT MAX(recnum) INTO max FROM srv_user WHERE ank_id = aid AND preview='0';
     IF max IS NULL THEN SET max = '0' ;
     END IF;
     RETURN max+1;
    END$$
    DELIMITER ;


UPDATE misc SET value='12.02.14' WHERE what="version";

ALTER TABLE srv_invitations_archive ADD naslov CHAR( 100 ) NOT NULL;
UPDATE srv_invitations_archive SET naslov = subject_text;

UPDATE misc SET value='12.02.14a' WHERE what="version";


ALTER TABLE srv_datasetting_profile ADD chartNumerusText ENUM('0','1') NOT NULL DEFAULT '0' AFTER chartTableMore;
ALTER TABLE srv_datasetting_profile ADD chartPieZeros ENUM('0','1') NOT NULL DEFAULT '1' AFTER chartNumerusText;
ALTER TABLE srv_datasetting_profile ADD exportNumbering ENUM('0','1') NOT NULL DEFAULT '1' AFTER dataPdfType;
ALTER TABLE srv_datasetting_profile ADD exportShowIf ENUM('0','1') NOT NULL DEFAULT '1' AFTER exportNumbering;
ALTER TABLE srv_datasetting_profile ADD exportFontSize TINYINT NOT NULL DEFAULT '10' AFTER exportShowIf;

UPDATE misc SET value='12.02.19' WHERE what="version";

CREATE TABLE srv_dostop_manage (
manager INT NOT NULL ,
user INT NOT NULL
) ENGINE = InnoDB;

ALTER TABLE srv_dostop_manage ADD CONSTRAINT fk_srv_dostop_manage_manager FOREIGN KEY (manager) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE srv_dostop_manage ADD CONSTRAINT fk_srv_dostop_manage_user FOREIGN KEY (user) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE srv_dostop_manage ADD PRIMARY KEY ( manager , user );

UPDATE misc SET value='12.02.21' WHERE what="version";

ALTER TABLE srv_data_files CHANGE collect_full_meta collect_full_meta TINYINT NOT NULL DEFAULT '1';
ALTER TABLE srv_data_files ADD dashboard_file_time INT(11);

UPDATE misc SET value='12.02.26' WHERE what="version";

# za inkremental pobrišemo še podatke o predhodnih datotekah, da zgeneriramo na novo
TRUNCATE TABLE srv_data_files;

ALTER TABLE srv_datasetting_profile ADD exportShowIntro ENUM('0','1') NOT NULL DEFAULT '0' AFTER exportFontSize;
ALTER TABLE srv_datasetting_profile ADD exportDataNumbering ENUM('0','1') NOT NULL DEFAULT '1' AFTER dataPdfType;
ALTER TABLE srv_datasetting_profile ADD exportDataShowIf ENUM('0','1') NOT NULL DEFAULT '1' AFTER exportDataNumbering;
ALTER TABLE srv_datasetting_profile ADD exportDataFontSize TINYINT NOT NULL DEFAULT '10' AFTER exportDataShowIf;

UPDATE srv_help SET help = 'Dostop brez gesla:<br/><ul><li><b>Ne</b> - Za izpolnjevanje ankete je potrebno bodisi pridobiti email vabilo kjer kliknemo na povezavo s katero se koda avtomatsko prenese v anketo, bodisi pa mora respondent poznati kodo in jo ro&#269;no vnesti v anketo.</li><li><b>Da</b> - Anketo lahko izpolnjujejo tudi uporabniki kateri niso prejeli email vabila - kode.</li><li><b>Samo avtor</b> - Polek uporabnikov, ki imajo kodo lahko anketo brez vabila (brez vnosa kode) izpolnjujejo tudi avtorji ankete (predvsem v testne namene).</li></ul>' WHERE what = 'usercode_skip';

ALTER TABLE srv_dostop ADD dostop SET( 'mail', 'link' ) NOT NULL ;

UPDATE misc SET value='12.03.01' WHERE what="version";

ALTER TABLE srv_datasetting_profile ADD chartFontSize TINYINT NOT NULL DEFAULT '8' AFTER chartNumerusText;

ALTER TABLE srv_dostop DROP dostop;

ALTER TABLE srv_dostop ADD dostop SET( 'edit', 'data', 'export', 'link', 'mail' ) NOT NULL DEFAULT 'edit,data,export';

ALTER TABLE srv_user_setting ADD showAnalizaPreview ENUM('0','1') NOT NULL DEFAULT '1';


UPDATE misc SET value='12.03.05' WHERE what="version";


#
# sprememba NULL polj na loop-ih
#

# na data damo vre_id na NULL
ALTER TABLE srv_loop_data CHANGE vre_id vre_id INT( 11 ) NULL ;

# na vre pobrisemo FK
ALTER TABLE srv_loop_vre DROP FOREIGN KEY fk_srv_loop_vre_if_id ;
ALTER TABLE srv_loop_vre DROP FOREIGN KEY fk_srv_loop_vre_vre_id ;
ALTER TABLE srv_loop_vre DROP KEY fk_srv_loop_vre_vre_id ;

# na data pobrisemo FK
ALTER TABLE srv_loop_data DROP FOREIGN KEY fk_srv_loop_data_if_id_vre_id ;
ALTER TABLE srv_loop_data DROP KEY fk_srv_loop_data_vre_id;

# na data zamenjamo UNIQUE index za navadnega
ALTER TABLE srv_loop_data DROP KEY if_id;
ALTER TABLE srv_loop_data ADD INDEX (if_id, vre_id);

# odstranimo PK na vre
ALTER TABLE srv_loop_vre DROP PRIMARY KEY;

# na vre damo vre_id na NULL
ALTER TABLE srv_loop_vre CHANGE vre_id vre_id INT( 11 ) NULL ;

# postavimo nazaj index, ki bo zdaj samo unique namesto primary
ALTER TABLE srv_loop_vre ADD UNIQUE (if_id, vre_id);

# damo nazaj FK na data tabelo
ALTER TABLE srv_loop_data ADD CONSTRAINT fk_srv_loop_data_if_id_vre_id FOREIGN KEY (if_id, vre_id) REFERENCES srv_loop_vre (if_id, vre_id) ON DELETE CASCADE ON UPDATE CASCADE;

# damo nazaj FK na vre tabelo
ALTER TABLE srv_loop_vre ADD CONSTRAINT fk_srv_loop_vre_if_id FOREIGN KEY (if_id) REFERENCES srv_loop (if_id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE srv_loop_vre ADD CONSTRAINT fk_srv_loop_vre_vre_id FOREIGN KEY (vre_id) REFERENCES srv_vrednost (id) ON DELETE CASCADE ON UPDATE CASCADE;


UPDATE misc SET value='12.03.07' WHERE what="version";


ALTER TABLE srv_user ADD deleted ENUM( '0', '1' ) NOT NULL DEFAULT '0';

UPDATE misc SET value='12.03.09' WHERE what="version";

ALTER TABLE srv_analysis_archive ADD settings mediumtext default '';

UPDATE misc SET value='12.03.18' WHERE what="version";

UPDATE srv_help SET help = 'Ustrezne enote so tiste ankete, kjer je respondent odgovoril vsaj na eno vpra&#353;anje. V vseh analizah so privzeto (default) vklju&#269;ene le ustrezne enote. Ostale - za vsebinske analize neustrezne enote – namre&#269; vklju&#269;ujejo prazne ankete (npr. anketirance, ki so zgolj kliknili na nagovor) in so zanimive predvsem za analizo procesa odgovarjanja – njihov sumarni pregled pa je v zavihku STATUS.<br><a href="http://www.1ka.si/c/837/Statusi_in_manjkajoce_vrednosti/" target="_blank">Podrobnosti</a>' WHERE what = 'srv_data_only_valid';

INSERT INTO srv_help (help, what) VALUES ('Prikazovanje samo se neresenih komentarjev.', 'srv_comments_only_unresolved');


UPDATE misc SET value='12.03.20' WHERE what="version";

ALTER TABLE srv_datasetting_profile CHANGE analysisGoTo analysisGoTo ENUM( '0', '1' ) NOT NULL DEFAULT '1';

ALTER TABLE srv_if ADD tab ENUM( '0', '1' ) NOT NULL ;

UPDATE misc SET value='12.03.22' WHERE what="version";

ALTER TABLE srv_alert ADD reply_to VARCHAR( 250 ) NOT NULL ;

UPDATE misc SET value='12.03.27' WHERE what="version";

# ker so bila dodana nova polja vsilimo novo generacijo datotek
TRUNCATE TABLE srv_data_files;

UPDATE misc SET value='12.03.28' WHERE what="version";

# ker je prebilo int, damo brez predznaka, ker je čas tak vedno večji od 0
ALTER TABLE srv_data_files CHANGE dashboard_file_time dashboard_file_time INT( 11 ) UNSIGNED NOT NULL;

# zaradi sprememb v datoteki s podatki vsilimo novo generacijo datotek
TRUNCATE TABLE srv_data_files;

UPDATE misc SET value='12.03.29' WHERE what="version";

ALTER TABLE srv_data_files ADD dashboard_update_time datetime NOT NULL;

# zaradi sprememb v datoteki s podatki vsilimo novo generacijo datotek
TRUNCATE TABLE srv_data_files;

UPDATE misc SET value='12.04.01' WHERE what="version";

ALTER TABLE srv_spremenljivka ADD num_useMax ENUM( '0', '1' ) NOT NULL DEFAULT '0' AFTER vsota_show;
ALTER TABLE srv_spremenljivka ADD num_useMin ENUM( '0', '1' ) NOT NULL DEFAULT '0' AFTER num_useMax;

UPDATE misc SET value='12.04.05' WHERE what="version";

ALTER TABLE srv_datasetting_profile CHANGE chartNumerusText chartNumerusText ENUM( '0', '1', '2' ) NOT NULL DEFAULT '0';

UPDATE srv_spremenljivka SET num_useMax = '1' WHERE vsota_limit != 0;
UPDATE srv_spremenljivka SET num_useMin = '1' WHERE vsota_min != 0;

UPDATE misc SET value='12.04.17' WHERE what="version";

CREATE TABLE srv_validation (spr_id INT NOT NULL, if_id INT NOT NULL) ENGINE = InnoDB;
ALTER TABLE srv_validation ADD PRIMARY KEY (spr_id, if_id);
ALTER TABLE srv_validation ADD CONSTRAINT fk_srv_validation_spr_id FOREIGN KEY (spr_id) REFERENCES srv_spremenljivka (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE srv_validation ADD CONSTRAINT fk_srv_validation_if_id FOREIGN KEY (if_id) REFERENCES srv_if (id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE srv_validation ADD reminder ENUM( '0', '1', '2' ) NOT NULL,
ADD reminder_text VARCHAR( 250 ) NOT NULL ;

UPDATE misc SET value='12.04.18' WHERE what="version";

ALTER TABLE srv_user_setting ADD lockSurvey ENUM('0','1') NOT NULL DEFAULT '1';

UPDATE misc SET value='12.04.18a' WHERE what="version";

ALTER TABLE srv_data_files ADD updateInProgress ENUM('0','1') NOT NULL DEFAULT '0';
ALTER TABLE srv_data_files ADD updateStartTime datetime NOT NULL;

UPDATE misc SET value='12.04.19' WHERE what="version";

ALTER TABLE srv_datasetting_profile CHANGE chartNumerusText chartNumerusText ENUM( '0', '1', '2' , '3' ) NOT NULL DEFAULT '0';

ALTER TABLE srv_anketa ADD individual_invitation ENUM('0','1') NOT NULL DEFAULT '1';

UPDATE misc SET value='12.04.24' WHERE what="version";

ALTER TABLE srv_anketa ADD email_to_list ENUM('0','1') NOT NULL DEFAULT '0';

UPDATE misc SET value='12.05.14' WHERE what="version";

ALTER TABLE srv_recode CHARACTER SET utf8 collate utf8_bin;
ALTER TABLE srv_recode CHANGE search search VARCHAR( 15 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL;
ALTER TABLE srv_recode CHANGE value value VARCHAR( 15 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL ;
ALTER TABLE srv_recode CHANGE operator operator ENUM( '0', '1', '2', '3', '4', '5', '6' ) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT '0';
ALTER TABLE srv_recode ADD vrstni_red INT( 11 ) NOT NULL AFTER spr_id; 

UPDATE misc SET value='12.05.15' WHERE what="version";

ALTER TABLE srv_status_profile ADD statustestni TINYINT( 1 ) NOT NULL DEFAULT '2';

UPDATE misc SET value='12.05.16' WHERE what="version";

ALTER TABLE srv_parapodatki ADD id INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST ;

UPDATE misc SET value='12.05.21' WHERE what="version";

UPDATE srv_invitations_archive SET naslov = concat('mailing_',DATE_FORMAT(date_send,'%d.%m.%Y, %T'));

INSERT INTO srv_help (help, what) VALUES ('V anketo mora biti dodano sistemsko vpra&#353;anje "email". Prav tako mora biti tudi vidno.', 'srv_email_to_list');

CREATE TABLE srv_custom_report (
id int(11) NOT NULL AUTO_INCREMENT,
ank_id int(11) NOT NULL default '0',
spr1 varchar(255) NOT NULL default '',
spr2 varchar(255) NOT NULL default '',
type tinyint(1) not null default '0',
sub_type tinyint(1) not null default '0',
vrstni_red tinyint(1) not null default '0',
PRIMARY KEY (id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE srv_recode ADD to_spr_id INT NOT NULL;
UPDATE srv_recode SET to_spr_id = spr_id;

UPDATE misc SET value='12.05.29' WHERE what="version";

ALTER TABLE srv_anketa ADD logo VARCHAR( 50 ) NOT NULL ;

UPDATE misc SET value='12.05.31' WHERE what="version";

ALTER TABLE srv_recode DROP COLUMN to_spr_id;

CREATE TABLE srv_recode_spremenljivka (
	ank_id INT NOT NULL ,
	spr_id INT NOT NULL ,
	recode_type tinyint(1) not null default 0,
	to_spr_id INT(11) NOT NULL default 0,
	UNIQUE (ank_id,spr_id)
) ENGINE = InnoDB;

CREATE TABLE srv_recode_vrednost (
	ank_id INT(11) NOT NULL ,
	spr1 INT(11) NOT NULL ,
	vre1 INT(11) NOT NULL,
	spr2 INT(11) NOT NULL ,
	vre2 int(11) NOT NULL
) ENGINE = InnoDB;

UPDATE misc SET value='12.06.03' WHERE what="version";

ALTER TABLE srv_spremenljivka CHANGE coding coding INT( 11 ) NOT NULL DEFAULT '0';
UPDATE srv_spremenljivka SET coding = '0';

UPDATE misc SET value='12.06.05' WHERE what="version";

ALTER TABLE srv_invitations_recipients_profiles ADD edit_uid int(11) NOT NULL;
ALTER TABLE srv_invitations_recipients_profiles ADD edit_time DATETIME NOT NULL;
UPDATE srv_invitations_recipients_profiles SET edit_uid = uid, edit_time = insert_time;

UPDATE misc SET value='12.06.06' WHERE what="version";

ALTER TABLE srv_datasetting_profile ADD chartAvgText ENUM('0','1') NOT NULL DEFAULT '1' AFTER chartNumerusText;


ALTER TABLE srv_invitations_recipients_profiles ADD access text default '';

ALTER TABLE srv_invitations_recipients_profiles DROP access;

CREATE TABLE srv_invitations_recipients_profiles_access (
  pid int(11) NOT NULL,
  uid int(11) NOT NULL
) ENGINE=InnoDB;

UPDATE misc SET value='12.06.07' WHERE what="version";

ALTER TABLE srv_spremenljivka ADD showOnAllPages ENUM( '0', '1' ) NOT NULL DEFAULT '0';

UPDATE misc SET value='12.06.10' WHERE what="version";

ALTER TABLE srv_language_vrednost DROP FOREIGN KEY fk_srv_language_vrednost_vre_id;
ALTER TABLE srv_language_vrednost DROP PRIMARY KEY;
ALTER TABLE srv_language_vrednost DROP FOREIGN KEY fk_srv_language_vrednost_vre_id;

ALTER TABLE srv_language_vrednost ADD PRIMARY KEY (ank_id, vre_id, lang_id);

UPDATE misc SET value='15.06.10' WHERE what="version";

ALTER TABLE srv_language_grid DROP PRIMARY KEY , ADD PRIMARY KEY (spr_id, grd_id, lang_id) ;

ALTER TABLE srv_language_vrednost ADD CONSTRAINT fk_srv_language_vrednost_vre_id FOREIGN KEY (vre_id) REFERENCES srv_vrednost (id) ON DELETE CASCADE ON UPDATE CASCADE;

UPDATE misc SET value='18.06.10' WHERE what="version";

ALTER TABLE srv_custom_report ADD usr_id int(11) NOT NULL default '0' AFTER ank_id;

ALTER TABLE srv_dostop CHANGE dostop dostop SET( 'edit', 'test', 'publish', 'data', 'analyse', 'export', 'link', 'mail' ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'edit,test,publish,data,analyse,export';

UPDATE srv_dostop SET dostop = CONCAT(dostop, ',test,publish,analyse');

UPDATE misc SET value='12.06.30' WHERE what="version";

INSERT INTO srv_help (what, help) VALUES ('srv_inv_cnt_by_sending', 'Tabela pove, koliko enotam email še ni bil poslan (0), koliko enot je dobilo email enkrat (1), koliko dvakrat (2), koliko trikrat (3) ….');


ALTER TABLE content ADD COLUMN admincontent mediumtext not null default '';

UPDATE srv_invitations_archive SET naslov = concat('mailing_',DATE_FORMAT(date_send,'%d.%m.%Y'));

ALTER TABLE srv_datasetting_profile ADD analiza_legenda ENUM('0','1') NOT NULL DEFAULT '0';

INSERT INTO srv_help (what, help) VALUES ('srv_podatki_urejanje_inline', 'Vkljucili ste tudi neposredno urejanje v pregledovalniku');

UPDATE misc SET value='12.07.04' WHERE what="version";

ALTER TABLE srv_help ADD lang TINYINT NOT NULL DEFAULT '1' AFTER what;

ALTER TABLE srv_help DROP PRIMARY KEY , ADD PRIMARY KEY ( what , lang ) ;

INSERT INTO srv_help (what, lang, help) VALUES ('srv_podatki_urejanje_inline', '2', 'You have also enabled inline editing in the table');

UPDATE misc SET value='12.07.05' WHERE what="version";

ALTER TABLE srv_anketa ADD invisible ENUM( '0', '1' ) NOT NULL DEFAULT '0';

UPDATE misc SET value='12.07.05a' WHERE what="version";

ALTER TABLE srv_custom_report ADD text TEXT CHARACTER SET utf8 COLLATE utf8_bin NOT NULL;

ALTER TABLE srv_user ADD language TINYINT NOT NULL ;

UPDATE misc SET value='12.07.06' WHERE what="version";

ALTER TABLE srv_invitations_messages ADD url varchar(200) default NULL;

UPDATE misc SET value='12.07.08' WHERE what="version";

ALTER TABLE srv_recode ADD enabled ENUM( '0', '1' ) NOT NULL DEFAULT '1';
ALTER TABLE srv_recode_spremenljivka ADD enabled ENUM( '0', '1' ) NOT NULL DEFAULT '1';

UPDATE misc SET value='12.07.09' WHERE what="version";

CREATE TABLE srv_theme_editor (
  ank_id int(11) NOT NULL,
  id int(11) NOT NULL,
  type int(11) NOT NULL,
  value varchar(100) NOT NULL,
  PRIMARY KEY (ank_id,id,type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE srv_theme_editor ADD CONSTRAINT fk_srv_theme_editor_ank_id FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE;

UPDATE misc SET value='12.07.13' WHERE what="version";

DROP TABLE IF EXISTS srv_recode_number;
CREATE TABLE srv_recode_number (
	ank_id INT NOT NULL ,
	spr_id INT NOT NULL ,
	vrstni_red INT( 11 ) NOT NULL,
	search VARCHAR( 15 ) NOT NULL ,
	operator ENUM( '0', '1', '2', '3', '4', '5', '6' ) DEFAULT '0',
	vred_id INT NOT NULL ,
	UNIQUE (ank_id,spr_id,search,operator)
) ENGINE = InnoDB DEFAULT CHARACTER SET utf8 collate utf8_bin;

UPDATE misc SET value='12.07.13a' WHERE what="version";

ALTER TABLE srv_library_folder ADD lang INT NOT NULL DEFAULT '1';

INSERT INTO srv_library_folder (id,uid,tip,naslov,parent,lang) VALUES (NULL , '0', '1', 'Public surveys', '0', '2');

UPDATE misc SET value='12.07.16' WHERE what="version";

INSERT INTO srv_library_folder (id,uid,tip ,naslov ,parent,lang) VALUES (NULL , '0', '0', 'Public questions', '0', '2');

UPDATE misc SET value='12.07.17' WHERE what="version";

ALTER TABLE srv_anketa ADD skin_profile INT NOT NULL AFTER skin;
ALTER TABLE srv_theme_editor DROP FOREIGN KEY fk_srv_theme_editor_ank_id;
TRUNCATE TABLE srv_theme_editor;
ALTER TABLE srv_theme_editor CHANGE ank_id profile_id INT( 11 ) NOT NULL ;

CREATE TABLE IF NOT EXISTS srv_theme_profiles (
  id int(11) NOT NULL AUTO_INCREMENT,
  usr_id int(11) NOT NULL,
  skin varchar(100) NOT NULL,
  name varchar(100) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

UPDATE misc SET value='12.08.14' WHERE what="version";

DROP TABLE IF EXISTS srv_testdata_archive;

UPDATE misc SET value='12.08.15' WHERE what="version";

INSERT INTO srv_help (what, lang, help) VALUES ('srv_dostop_password', '1', 'Vsi respondenti morajo vnesti isto geslo.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_dostop_password', '2', 'All respondents are asked to insert the same password.');

UPDATE misc SET value='12.08.16' WHERE what="version";

CREATE TABLE IF NOT EXISTS srv_testdata_archive (
  ank_id int(11) NOT NULL,
  add_date datetime DEFAULT NULL,
  add_uid int(11) NOT NULL,
  usr_id int(11) NOT NULL,
  UNIQUE ank_usr_id (ank_id, usr_id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

UPDATE misc SET value='12.08.16a' WHERE what="version";

CREATE TABLE srv_chart_skin (
 id int(11) NOT NULL auto_increment,
 usr_id int(11) NOT NULL default 0,
 name varchar(200) NOT NULL,
 colors varchar(200) NOT NULL,
 PRIMARY KEY (id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='12.08.19' WHERE what="version";

ALTER TABLE srv_anketa ADD continue_later ENUM( '1', '0' ) NOT NULL DEFAULT '1';

UPDATE misc SET value='12.08.20' WHERE what="version";

CREATE TABLE IF NOT EXISTS srv_invitations_tracking (
  inv_arch_id int(11) NOT NULL,
  time_insert datetime DEFAULT NULL, 
  res_id int(11) NOT NULL,
  status int(11) NOT NULL,
  UNIQUE arc_res_status (inv_arch_id, res_id,status)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE srv_invitations_tracking ADD CONSTRAINT srv_invitations_tracking_arch_id FOREIGN KEY (inv_arch_id) REFERENCES srv_invitations_archive (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE srv_invitations_tracking ADD CONSTRAINT srv_invitations_tracking_res_id FOREIGN KEY (res_id) REFERENCES srv_invitations_recipients (id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE srv_invitations_archive ADD rec_in_db int(11) NOT NULL;

UPDATE misc SET value='12.08.20a' WHERE what="version";

INSERT INTO misc VALUES ('invitationTrackingStarted', NOW());

UPDATE misc SET value='12.08.20b' WHERE what="version";

ALTER TABLE srv_invitations_tracking ADD uniq MEDIUMINT NOT NULL AUTO_INCREMENT KEY;

UPDATE misc SET value='12.08.20c' WHERE what="version";

UPDATE srv_help SET help = 'Tabela pove, koliko enotam email &#353;e ni bil poslan (0), koliko enot je dobilo email enkrat (1), koliko dvakrat (2), koliko trikrat (3) ...' WHERE what='srv_inv_cnt_by_sending' AND lang ='1';

INSERT INTO srv_help (what,help) VALUES ('srv_recode_h_actions', 'Funkcije rekodiranja so: <ul><li>Dodaj - odpre okno za dodajanje rekodiranje za posamezno variablo</li><li>Uredi - prikaže okno za urejane rekodiranja posamezne variable</li><li>Odstrani - odstrano oziroma v celoti izbri&#353;e rekodiranje posamezne variable</li><li>Omogo&#269;eno - trenutno omogo&#269;i oziroma onemogo&#269;i rekodiranje posamezne variable</li><li>Vidna - nastavi variablo vidno oziroma nevidno v vpra&#353;alniku</li></ul>');

UPDATE misc SET value='12.08.21' WHERE what="version";

ALTER TABLE srv_theme_editor ADD CONSTRAINT fk_srv_theme_editor_profile_id FOREIGN KEY (profile_id) REFERENCES srv_theme_profiles (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE srv_theme_profiles ADD CONSTRAINT fk_srv_theme_profiles_usr_id FOREIGN KEY (usr_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE;

UPDATE misc SET value='12.08.22' WHERE what="version";

ALTER TABLE srv_theme_profiles ADD logo VARCHAR( 250 ) NOT NULL ;

ALTER TABLE srv_anketa DROP logo;

UPDATE misc SET value='12.08.23' WHERE what="version";

ALTER TABLE srv_anketa ADD js_tracking TEXT NOT NULL ;

UPDATE misc SET value='12.08.24' WHERE what="version";

UPDATE srv_user_setting_for_survey SET value = 'lively' WHERE what = 'default_chart_profile_skin' AND value = '0';
UPDATE srv_user_setting_for_survey SET value = 'mild' WHERE what = 'default_chart_profile_skin' AND value = '1';
UPDATE srv_user_setting_for_survey SET value = 'green' WHERE what = 'default_chart_profile_skin' AND value = '2';
UPDATE srv_user_setting_for_survey SET value = 'blue' WHERE what = 'default_chart_profile_skin' AND value = '3';
UPDATE srv_user_setting_for_survey SET value = 'red' WHERE what = 'default_chart_profile_skin' AND value = '4';
UPDATE srv_user_setting_for_survey SET value = 'multi' WHERE what = 'default_chart_profile_skin' AND value = '5';
UPDATE srv_user_setting_for_survey SET value = 'office' WHERE what = 'default_chart_profile_skin' AND value = '6';

UPDATE misc SET value='12.08.27' WHERE what="version";

ALTER TABLE srv_anketa ADD concl_PDF_link TINYINT NOT NULL DEFAULT '0';

UPDATE misc SET value='12.08.30' WHERE what="version";

ALTER TABLE users ADD editing_mode ENUM( '0', '1' ) NOT NULL DEFAULT '0';

UPDATE users SET editing_mode = '1';

UPDATE misc SET value='12.08.31' WHERE what="version";

ALTER TABLE srv_anketa CHANGE continue_later continue_later ENUM( '1', '0' ) NOT NULL DEFAULT '0';

UPDATE misc SET value='12.09.03' WHERE what="version";

INSERT INTO srv_help (what, help) VALUES ('srv_creport', 'V prilagojenem poro&#269;ilu lahko:<ul style="padding-left:15px"><li>naredite poljuben izbor spremenljivk</li><li>jih urejate v poljubnem vrstnem redu</li><li>kombinirate grafe, frekvence, povpre&#269;ja...</li><li>dodajate komentarje</li></ul>');

INSERT INTO srv_help (what, help) VALUES ('srv_recode_chart_advanced', 'Osnovno rekodiranje je primerno, da se starost, katera je večja od 100 rekodira v -97 katero je neustrezno. Oziroma da se odgovori 9 - ne vem rekodirajo v neustrezno.');

ALTER TABLE srv_custom_report ADD profile int(11) NOT NULL DEFAULT 0;

CREATE TABLE srv_custom_report_profiles (
 id int(11) NOT NULL auto_increment,
 ank_id int(11) NOT NULL default 0,
 usr_id int(11) NOT NULL default 0,
 name varchar(200) NOT NULL,
 PRIMARY KEY (id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE srv_status_profile ADD ank_id int( 11 ) not null default 0 after uid;

UPDATE misc SET value='12.09.12' WHERE what="version";

INSERT INTO srv_help (what, help) VALUES ('srv_concl_PDF_link', 'Na koncu ankete prikaže ikono s povezavo do PDF dokumenta z odgovori respondenta.');


INSERT INTO srv_status_profile (id, uid, name, system, statusnull, status0, status1, status2, status3, status4, status5, status6, statuslurker ) 
VALUES ('3', '0', 'Koncani', 1, 0, 0, 0, 0, 0, 0, 0, 1, 0) 
ON DUPLICATE KEY UPDATE uid = '0', name='Koncani', system='1', statusnull='0', status0='0', status1='0', status2='0', status3='0', status4='0', status5='0', status6='1', statuslurker='0';

UPDATE misc SET value='12.09.17' WHERE what="version";

# zaradi sprememb v datoteki s podatki vsilimo novo generacijo datotek
TRUNCATE TABLE srv_data_files;

ALTER TABLE srv_anketa ADD defValidProfile TINYINT NOT NULL DEFAULT '2';
ALTER TABLE srv_anketa ADD showItime TINYINT NOT NULL DEFAULT '0';

UPDATE misc SET value='12.09.19' WHERE what="version";

ALTER TABLE srv_user_setting ADD autoActiveSurvey ENUM('0','1') NOT NULL DEFAULT '0';

UPDATE misc SET value='12.09.24' WHERE what="version";

INSERT INTO srv_help (what, help) VALUES ('srv_data_print_preview', 'Hiter seznam omogoča hiter pregled odgovorov na prvih 5 vpra&#154;anj.');

ALTER TABLE srv_datasetting_profile ADD exportDataShowRecnum ENUM('0','1') NOT NULL DEFAULT '1' AFTER exportDataFontSize;
ALTER TABLE srv_datasetting_profile ADD exportDataPB ENUM('0','1') NOT NULL DEFAULT '0' AFTER exportDataShowRecnum;

CREATE TABLE srv_invitations_mapping (
 sid int(11) NOT NULL,
 spr_id int(11) NOT NULL default 0,
 field VARCHAR(45) NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET=utf8;


UPDATE misc SET value='12.10.01' WHERE what="version";

ALTER TABLE srv_datasetting_profile ADD numOpenAnswers int(11) NOT NULL DEFAULT 10 AFTER hideEmpty;

UPDATE misc SET value='12.10.02' WHERE what="version";

ALTER TABLE srv_user_setting CHANGE autoActiveSurvey autoActiveSurvey ENUM( '0', '1' ) NOT NULL DEFAULT '0';


UPDATE misc SET value='12.10.09' WHERE what="version";

ALTER TABLE srv_missing_values ADD systemValue int(11) default NULL;
#prilagodimo sistemsko vrednost
UPDATE srv_missing_values SET systemValue = value;
ALTER TABLE srv_vrednost CHANGE other other INT(11) NOT NULL DEFAULT '0';

UPDATE misc SET value='12.10.10' WHERE what="version";

UPDATE srv_help SET help = 'Hiter seznam omogo&#269;a hiter pregled odgovorov na prvih 5 vpra&#154;anj.<br><br><a href="http://www.1ka.si/db/19/381/Pogosta%20vprašanja/Hitra_izdelava_seznama_odgovorov/?&cat=292&p1=226&p2=735&p3=789&p4=801&p5=856&id=856" target="_blank">Podrobnosti</a>' WHERE what = 'srv_data_print_preview';

UPDATE srv_help SET help = 'V prilagojenem poro&#269;ilu lahko:<ul style="padding-left:15px"><li>naredite poljuben izbor spremenljivk</li><li>jih urejate v poljubnem vrstnem redu</li><li>kombinirate grafe, frekvence, povpre&#269;ja...</li><li>dodajate komentarje</li></ul><a href="http://www.1ka.si/db/19/427/Pogosta%20vprašanja/Porocila_po_meri/?&cat=286&p1=226&p2=735&p3=789&p4=794&p5=865&id=865" target="_blank">Podrobnosti</a>' WHERE what = 'srv_creport';

UPDATE srv_user_setting SET autoActiveSurvey = '0';

UPDATE misc SET value='12.10.12' WHERE what="version";

ALTER TABLE srv_datasetting_profile ADD exportDataSkipEmpty ENUM('0','1') NOT NULL DEFAULT '0' AFTER exportDataPB;
ALTER TABLE srv_datasetting_profile ADD exportDataSkipEmptySub ENUM('0','1') NOT NULL DEFAULT '0' AFTER exportDataSkipEmpty;
ALTER TABLE srv_datasetting_profile ADD exportDataLandscape ENUM('0','1') NOT NULL DEFAULT '0' AFTER exportDataSkipEmptySub;

UPDATE srv_help SET help = 'V hitrem seznamu je izpisanih prvih 5 spremenljivk. Dodaten izbor spremenljivk lahko naredite v opciji "Spremenljivke".<br><br><a href="http://www.1ka.si/db/19/381/Pogosta%20vprašanja/Hitra_izdelava_seznama_odgovorov/?&cat=292&p1=226&p2=735&p3=789&p4=801&p5=856&id=856" target="_blank">Podrobnosti</a>' WHERE what = 'srv_data_print_preview';

UPDATE misc SET value='12.10.15' WHERE what="version";

CREATE TABLE IF NOT EXISTS srv_grid_multiple (
  ank_id int(11) NOT NULL,
  parent int(11) NOT NULL,
  spr_id int(11) NOT NULL,
  vrstni_red smallint(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='12.10.16' WHERE what="version";

INSERT INTO srv_help (what,help) VALUES ('srv_recode_advanced_edit','Napredno urejanje, kot je dodajanje, preimenovanje in brisanje kategorij je na voljo v urejanju vpra&#353;alnika.');

ALTER TABLE srv_recode_spremenljivka ADD usr_id int(11) NOT NULL;
ALTER TABLE srv_recode_spremenljivka ADD rec_date datetime NOT NULL;

UPDATE misc SET value='12.10.22' WHERE what="version";

ALTER TABLE srv_grid_multiple ADD CONSTRAINT srv_grid_multiple_ank_id FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE srv_grid_multiple ADD CONSTRAINT srv_grid_multiple_parent FOREIGN KEY (parent) REFERENCES srv_spremenljivka (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE srv_grid_multiple ADD CONSTRAINT srv_grid_multiple_spr_id FOREIGN KEY (spr_id) REFERENCES srv_spremenljivka (id) ON DELETE CASCADE ON UPDATE CASCADE;

UPDATE misc SET value='12.10.24' WHERE what="version";

ALTER TABLE srv_custom_report ADD time_edit DATETIME NOT NULL;
ALTER TABLE srv_custom_report_profiles ADD time_created DATETIME NOT NULL;

UPDATE misc SET value='12.10.30' WHERE what="version";

ALTER TABLE srv_call_history CHANGE status status ENUM( 'A', 'Z', 'N', 'R', 'T', 'P', 'U' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

# zaradi sprememb v datoteki s podatki vsilimo novo generacijo datotek
TRUNCATE TABLE srv_data_files;

UPDATE misc SET value='12.11.05' WHERE what="version";

CREATE TABLE IF NOT EXISTS srv_call_comment (
  usr_id int(11) unsigned NOT NULL,
  comment_time datetime NOT NULL,
  comment text NOT NULL,
  PRIMARY KEY  (usr_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='12.11.06' WHERE what="version";

UPDATE srv_help SET help = 'V hitrem seznamu je izpisanih prvih 5 spremenljivk. Primeren je za hiter izpis prijavnic in form. Za podrobne izpise uporabite obstoječe izvoze. Dodaten izbor spremenljivk lahko naredite v opciji "Spremenljivke".<br><br><a href="http://www.1ka.si/db/19/381/Pogosta%20vprašanja/Hitra_izdelava_seznama_odgovorov/?&cat=292&p1=226&p2=735&p3=789&p4=801&p5=856&id=856" target="_blank">Podrobnosti</a>' WHERE what = 'srv_data_print_preview';

ALTER TABLE srv_dostop CHANGE dostop dostop SET('edit', 'test', 'publish', 'data', 'analyse', 'export', 'link', 'mail', 'dashboard', 'phone' ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'edit,test,publish,data,analyse,export,dashboard';

UPDATE srv_dostop SET dostop = CONCAT(dostop, ',dashboard');

UPDATE misc SET value='12.11.19' WHERE what="version";

INSERT INTO srv_help (what,help) VALUES ('srv_crosstab_residual','Obarvane celice - glede na prilagojene vrednosti rezidualov (Z) - ka&#382;ejo, ali in koliko je v celici vec ali manj enot v primerjavi z razmerami, ko celici nista povezani. Bolj temna barva (rdeca ali modra) torej pomeni, da se v celici nekaj dogaja. Natancne vrednosti residualov dobimo, ce tako izberemo  v NASTAVITVAH. Nadaljnje podrobnosti o izracunavanja in interpetaciji rezidualov so <a href="https://www.1ka.si/db/24/308/Priro%C4%8Dniki/Kaj_pomenijo_residuali/" target="_blank">tukaj</a>');

UPDATE srv_user_setting SET survey_list_order = CONCAT(survey_list_order, ',22') WHERE survey_list_order IS NOT NULL AND survey_list_order != '';
UPDATE srv_user_setting SET survey_list_visible = CONCAT(survey_list_visible, ',22') WHERE survey_list_visible IS NOT NULL AND survey_list_visible != '';

UPDATE misc SET value='12.11.20' WHERE what="version";

CREATE TABLE IF NOT EXISTS srv_telephone_current (
  rec_id int(10) unsigned NOT NULL,
  user_id int(10) unsigned NOT NULL,
  started_time datetime NOT NULL,
  PRIMARY KEY  (rec_id),
  KEY user_id (user_id),
  KEY started_time (started_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS srv_telephone_history (
  id int(10) unsigned NOT NULL auto_increment,
  survey_id int(10) unsigned NOT NULL default '0',
  user_id int(10) unsigned NOT NULL default '0',
  rec_id int(10) unsigned NOT NULL,
  insert_time datetime NOT NULL,
  status ENUM( 'A', 'Z', 'N', 'R', 'T', 'P', 'U' ) NOT NULL,
  PRIMARY KEY  (id),
  KEY rec_id (rec_id),
  KEY time (insert_time),
  KEY status (status),
  KEY survey_id (survey_id),
  KEY user_id (user_id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS srv_telephone_schedule (
  rec_id int(10) unsigned NOT NULL,
  call_time datetime NOT NULL,
  PRIMARY KEY  (rec_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS srv_telephone_setting (
  survey_id int(10) unsigned NOT NULL default '0',
  status_z int(10) unsigned NOT NULL default '0',
  status_n int(10) unsigned NOT NULL default '0',
  max_calls int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (survey_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS srv_telephone_comment (
  rec_id int(11) unsigned NOT NULL,
  comment_time datetime NOT NULL,
  comment text NOT NULL,
  PRIMARY KEY  (rec_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='12.12.03' WHERE what="version";

# ne vem se a nej bo novo polje, al se bo uporabilo sistemske spremenljivke...
#ALTER TABLE srv_spremenljivka ADD connect_identifikator ENUM( '0', '1' ) NOT NULL DEFAULT '0';
#UPDATE misc SET value='12.12.11' WHERE what="version";

CREATE TABLE IF NOT EXISTS srv_profileManager (
	id int(11) NOT NULL auto_increment,
	ank_id int(11) NOT NULL default 0,
  	name text NOT NULL,
  	comment text NOT NULL,
  	ssp int(11) default NULL,
  	svp int(11) default NULL,
  	scp int(11) default NULL,
  	stp int(11) default NULL,
  	PRIMARY KEY  (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='12.12.18' WHERE what="version";

ALTER TABLE srv_anketa ADD dostop_admin DATE NOT NULL AFTER dostop ;

UPDATE misc SET value='12.12.27' WHERE what="version";

ALTER TABLE srv_anketa ADD showLineNumber TINYINT NOT NULL DEFAULT '0';

INSERT INTO srv_help (what,lang,help) VALUES('srv_skala_edit', '1', '<b>Ordinalna skala:</b> Kategorije odgovorov je mogoce primerjati; racunamo lahko tudi povprecje. Npr. lestvice na skalah (strinjanje, zadovoljstvo,…)</br><b>Nominalna skala:</b> Kategorij odgovorov ni mogoce primerjati niti ni mogoce racunati povprecij. Npr. spol, barva, regija, država.') ON DUPLICATE KEY UPDATE help=VALUES(help);

CREATE TABLE srv_tracking_incremental (
	anketa INT( 11 ) NOT NULL ,
	datetime DATETIME NOT NULL ,
	message TEXT NOT NULL
) ENGINE = MyISAM;

UPDATE misc SET value='13.01.08' WHERE what="version"

INSERT INTO srv_help (what,lang,help) VALUES('help-centre', '1', '1KA center za pomoc uporabnikom brez vasega dovoljenja nima dostopa do vase ankete. <br><br><a href="https://www.1ka.si/db/24/439/Priro%C4%8Dniki/Dostop_do_moje_ankete_za__1KA_center_za_pomoc_uporabnikom/?&cat=309&p1=226&p2=735&p3=867&p4=0&id=867&from1ka=1" target="_blank">Vec</a>') ON DUPLICATE KEY UPDATE help=VALUES(help);

UPDATE misc SET value='13.01.10' WHERE what="version";

INSERT INTO srv_grupa (id, ank_id, naslov, vrstni_red) VALUES (-2, 0, 'system', 0);

UPDATE misc SET value='13.01.10a' WHERE what="version";

CREATE TABLE srv_analysis_session (
	ank_id int(11) NOT NULL default 0,
  	usr_id int(11) NOT NULL default 0,
	data mediumtext NOT NULL,
  	PRIMARY KEY (ank_id, usr_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='13.01.16' WHERE what="version";


CREATE TABLE srv_survey_session (
	ank_id int(11) NOT NULL,
  	what varchar(255) NOT NULL,
	value longtext NOT NULL,
  	PRIMARY KEY (ank_id, what)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='13.01.22' WHERE what="version";

CREATE TABLE srv_lock (
	lock_key varchar(32) NOT NULL,
	locked ENUM('0','1') NOT NULL DEFAULT '0',
	usr_id INT(11) NOT NULL DEFAULT 0,
	last_lock_date DATETIME NOT NULL ,
	last_unlock_date DATETIME NOT NULL ,
  	PRIMARY KEY (lock_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='13.01.25' WHERE what="version";

RENAME TABLE srv_analysis_session TO srv_user_session;

UPDATE misc SET value='13.02.12' WHERE what="version";

alter table hour_users change user user varchar(255) not null default "";

UPDATE misc SET value='13.02.26' WHERE what="version";


CREATE TABLE srv_survey_conditions (
	ank_id int(11) NOT NULL default '0',
	if_id int(11) NOT NULL default '0',
	name VARCHAR(45) NOT NULL
) ENGINE = InnoDB;

ALTER TABLE srv_survey_conditions 
	ADD CONSTRAINT srv_survey_conditions_ank_id 
	FOREIGN KEY (ank_id) 
	REFERENCES srv_anketa (id) 
	ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE srv_survey_conditions 
	ADD CONSTRAINT srv_survey_conditions_if_id 
	FOREIGN KEY (if_id) 
	REFERENCES srv_if (id) 
	ON DELETE CASCADE ON UPDATE CASCADE;
	
UPDATE misc SET value='13.03.08' WHERE what="version";

#####
# Za katjo za projekt RC33
CREATE TABLE rc33_misc (
  what varchar(255) NOT NULL default '',
  value longtext,
  UNIQUE KEY what ( what )
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO rc33_misc (what, value) VALUES ('expire_in', '+4 year');
INSERT INTO rc33_misc (what, value) VALUES ('member_check_message', 'Vaš email: #EMAIL# <br/>Vaša registracija: #REGDATE#<br/>Potece: #EXPIREDATE#<br/>Že clan: #ISMEMBER#');

#####

UPDATE misc SET value='18.03.08' WHERE what="version";

INSERT INTO srv_help (what,help) VALUES('inv_recipiens_from_system', 'Prejemniki bodo dodani iz obstoječih podatkov v bazi, pri čemer mora vprašalnik vsebovati sistemsko spremenljivko email') ON DUPLICATE KEY UPDATE help=VALUES(help);

UPDATE misc SET value='13.04.01' WHERE what="version";

CREATE TABLE srv_mc_table (
 id int(11) NOT NULL AUTO_INCREMENT,
 ank_id int(11) NOT NULL default 0,
 usr_id int(11) NOT NULL default 0,
 PRIMARY KEY (id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE srv_mc_element (
 id int(11) NOT NULL AUTO_INCREMENT,
 table_id int(11) NOT NULL DEFAULT '0',
 spr varchar(255) NOT NULL DEFAULT '',
 parent varchar(255) NOT NULL DEFAULT '',
 vrstni_red int(11) NOT NULL DEFAULT '0',
 position ENUM('0','1') NOT NULL DEFAULT '0',
 PRIMARY KEY (id),
 FOREIGN KEY (table_id) REFERENCES srv_mc_table (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

UPDATE misc SET value='13.04.09' WHERE what="version";

DROP table IF EXISTS srv_variable_profiles;
CREATE TABLE srv_variable_profiles (
 id int(11) NOT NULL AUTO_INCREMENT,
 sid int(11) NOT NULL DEFAULT '0',
 name varchar(200) NOT NULL,
 variables text NOT NULL,
 PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


UPDATE misc SET value='13.04.10' WHERE what="version";

#####
##### na testu je update na 13.04.11 ????????
#####


DROP table IF EXISTS srv_hash_url;
CREATE TABLE srv_hash_url (
 hash varchar(32)  NOT NULL,
 anketa int(11) NOT NULL DEFAULT '0',
 properties text NOT NULL,
 PRIMARY KEY (hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE srv_hash_url ADD UNIQUE (hash,anketa);

ALTER TABLE srv_hash_url ADD CONSTRAINT FK_srv_hash_url FOREIGN KEY (anketa) REFERENCES srv_anketa(id) ON UPDATE CASCADE ON DELETE CASCADE;

UPDATE misc SET value='13.04.15' WHERE what="version";

ALTER TABLE srv_hash_url ADD comment varchar( 256 ) NOT NULL;

# v .htaccess je potrebno dodati spodnje zadeve:
#
# RewriteRule ^podatki/(.*)?/(.*)?	admin/survey/public.php?anketa=$1&urlhash=$2&%{QUERY_STRING}
# RewriteRule ^podatki/(.*)			admin/survey/public.php%{QUERY_STRING}

UPDATE misc SET value='13.04.18' WHERE what="version";

ALTER TABLE srv_tracking ADD INDEX ( ank_id , datetime , user ) ;

UPDATE misc SET value='13.05.07' WHERE what="version";

UPDATE srv_help SET help = 'Vklju&#269;ili ste tudi neposredno urejanje v pregledovalniku. <br/>V kolikor &#382;elite vrednosti vpra&#353;anja izbirati iz rolete lahko to nastavite v urejanju kot napredno nastavitev vpra&#353;anja.' WHERE what = 'srv_podatki_urejanje_inline' ;

INSERT INTO srv_help (what, help) VALUES ('edit_date_range','Datum lahko navzdol omejimo z letnico, naprimer: 1951 ali kot obdobje -70, kar pomeni zadnjih 70 let. Podobno lahko omejimo datum tudi navzgor. Naprimer: 2013 ali kot obdobje +10, kar pomeni naslednjih 10 let');

UPDATE misc SET value='13.05.08' WHERE what="version";

ALTER TABLE srv_mc_table ADD name varchar(255) NOT NULL;
ALTER TABLE srv_mc_table ADD time_created DATETIME NOT NULL;
ALTER TABLE srv_mc_table ADD title text NOT NULL;
ALTER TABLE srv_mc_table ADD numerus ENUM('0','1') NOT NULL DEFAULT '1';
ALTER TABLE srv_mc_table ADD percent ENUM('0','1') NOT NULL DEFAULT '0';
ALTER TABLE srv_mc_table ADD navVsEno ENUM('0','1') NOT NULL DEFAULT '1';
ALTER TABLE srv_mc_table ADD avgVar varchar(255) NOT NULL DEFAULT '';
ALTER TABLE srv_mc_table ADD delezVar varchar(255) NOT NULL DEFAULT '';
ALTER TABLE srv_mc_table ADD delez varchar(255) NOT NULL DEFAULT '';

UPDATE misc SET value='13.05.20' WHERE what="version";

ALTER TABLE srv_mc_table ADD sums ENUM('0','1') NOT NULL DEFAULT '0' AFTER percent;

INSERT INTO srv_help (what, help) VALUES ('exportSettings','Kadar izberete "Izvozi samo identifikatorje" se bodo izvozili samo identifikatorji (sistemski podatki repondenta), brez katerikoli drugih podatkov.<br>Kadar pa ne izvažate identifikatorjev pa lahko izvozite posamezne para podatke respondenta.');


# v .htaccess je potrebno odstranit spodnje zadeve:
#
# RewriteRule ^podatki/(.*)?/(.*)?	admin/survey/public.php?anketa=$1&urlhash=$2&%{QUERY_STRING}
# RewriteRule ^podatki/(.*)			admin/survey/public.php%{QUERY_STRING}
#
# in dodat nov pogoj:
# RewriteRule ^podatki/(.*?[^/])/(.*[^/])?	admin/survey/public.php?anketa=$1&urlhash=$2&%{QUERY_STRING}

ALTER TABLE srv_if ADD INDEX ( folder ) ;

alter table users add column LastLP integer unsigned not null default 0;

UPDATE misc SET value='13.05.27' WHERE what="version";

ALTER TABLE srv_hash_url ADD page enum('data','analysis') default 'data'; 
ALTER TABLE srv_hash_url ADD add_date datetime DEFAULT NULL;
ALTER TABLE srv_hash_url ADD add_uid int(11) NOT NULL;
 
UPDATE misc SET value='13.06.07' WHERE what="version";
  
INSERT INTO srv_help (what, help) VALUES ('srv_item_nonresponse','Neodgovor spremenljivke pomeni koliko ustreznih odgovorov smo dobili glede na skupno število enot.');
  
UPDATE misc SET value='30.06.07' WHERE what="version";

INSERT INTO srv_help (what, help) VALUES ('srv_invitation_rename_profile','Vsak vnešen email se privzeto shrani v začasen seznam, katerega pa lahko preimenujete tudi drugače. Nove emaile pa lahko dodate tudi v obstoječe sezname.');  

UPDATE misc SET value='05.05.07' WHERE what="version";

# kaj so to zaeni versioini :) 
# prestopna leta pa tak pa to. :)
UPDATE misc SET value='13.07.08' WHERE what="version";

ALTER TABLE srv_parapodatki ADD gru_id VARCHAR( 50 ) NOT NULL ,
ADD item TEXT NOT NULL ;

ALTER TABLE srv_parapodatki ADD spr_id VARCHAR( 50 ) NOT NULL AFTER gru_id;

UPDATE misc SET value='13.08.06' WHERE what="version";

ALTER TABLE srv_spremenljivka ADD num_min2 int(11) NOT NULL default 0 AFTER num_useMin;
ALTER TABLE srv_spremenljivka ADD num_max2 int(11) NOT NULL default 0 AFTER num_min2;
ALTER TABLE srv_spremenljivka ADD num_useMax2 ENUM( '0', '1' ) NOT NULL DEFAULT '0' AFTER num_max2;
ALTER TABLE srv_spremenljivka ADD num_useMin2 ENUM( '0', '1' ) NOT NULL DEFAULT '0' AFTER num_useMax2;

UPDATE misc SET value='13.09.03' WHERE what="version";

ALTER TABLE srv_anketa ADD thread_resp INT NOT NULL AFTER thread;

INSERT INTO srv_help (what, help) VALUES ('srv_mail_mode','TODO: Podrobnejši opis za posamezne nastavitve... + link na FAQ.');

UPDATE misc SET value='13.09.10' WHERE what="version";

ALTER TABLE srv_anketa DROP COLUMN thread_resp;

CREATE TABLE srv_comment_resp (
  id int(11) NOT NULL auto_increment,
  ank_id int(11) NOT NULL default '0',
  usr_id int(11) NOT NULL default '0',
  comment_time datetime NOT NULL,
  comment text NOT NULL,
  ocena enum('0','1','2','3') NOT NULL DEFAULT '0',
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='13.09.19' WHERE what="version";

update srv_help set help='Neodgovor spremenljivke pomeni koliko ustreznih odgovorov smo dobili glede na skupno stevilo enot.<br> Izracunan je po formuli: enot(-1) * 100 / (enot(veljavni) + enot(-1) + enot(-97) + enot(-98) + enot(-99))' where what='srv_item_nonresponse';

UPDATE misc SET value='13.09.25' WHERE what="version";

ALTER TABLE srv_spremenljivka ADD alert_show_99 enum('0','1') NOT NULL DEFAULT '0' AFTER reminder;

UPDATE misc SET value='13.10.09' WHERE what="version";

INSERT INTO srv_help (what, help) VALUES('individual_invitation', 'Individualiziran URL');
INSERT INTO srv_help (what, help) VALUES('srv_email_with_data', 'Povezava emailov s podatki');
INSERT INTO srv_help (what, help) VALUES('srv_email_server_settings', 'Nastvaitve poštnega strežnika');

UPDATE misc SET value='13.10.26' WHERE what="version";

ALTER TABLE srv_spremenljivka ADD skupine enum('0','1','2') NOT NULL DEFAULT '0';

UPDATE misc SET value='13.11.04' WHERE what="version";

ALTER TABLE srv_anketa ALTER COLUMN show_email SET DEFAULT '1';

UPDATE misc SET value='13.11.18' WHERE what="version";

INSERT INTO srv_help (what, help) VALUES ('srv_sistemska_edit','Sistemska spremenljivka');

UPDATE misc SET value='13.11.20' WHERE what="version";

UPDATE misc SET value='<p>Spo&#353;tovani,</p><p>Uspe&#353;no ste se odjavili iz spletnega mesta www.1ka.si.</p><p>Veseli nas, da ste preizkusili orodje 1ka.</p><p>SFPAGENAME ekipa</p>' WHERE what="ByeEmail";

UPDATE misc SET value='13.11.22' WHERE what="version";

INSERT INTO srv_help (what, help) VALUES ('edit_variable','Urejanje variable');

UPDATE misc SET value='13.11.29' WHERE what="version";

UPDATE srv_help SET 
help = 'Neodgovor spremenljivke pomeni koliko ustreznih odgovorov smo dobili glede na skupno &#353;tevilo enot, ki so dobile dolo&#269;eno vpra&#353;anje. Ali druga&#269;e: od vseh ustreznih enot, ki so dobile to vpra&#353;anje, so izlo&#269;eni statusi (-1).<br> Izra&#269;unan je po formuli: (-1) * 100 / ( (veljavni) + (-1) + (-97) + (-98) + (-99) )'
WHERE what = 'srv_item_nonresponse' AND lang = 1;

INSERT INTO srv_help (what, lang, help) 
VALUES ('srv_item_nonresponse', 2, 'Neodgovor spremenljivke pomeni koliko ustreznih odgovorov smo dobili glede na skupno &#353;tevilo enot, ki so dobile dolo&#269;eno vpra&#353;anje. Ali druga&#269;e: od vseh ustreznih enot, ki so dobile to vpra&#353;anje, so izlo&#269;eni statusi (-1).<br> Izra&#269;unan je po formuli: (-1) * 100 / ( (veljavni) + (-1) + (-97) + (-98) + (-99) )')
ON DUPLICATE KEY UPDATE help = VALUES(help);

UPDATE misc SET value='13.12.01' WHERE what="version";

ALTER TABLE srv_user ADD device enum('0','1','2','3') NOT NULL DEFAULT '0' AFTER useragent;
ALTER TABLE srv_user ADD browser VARCHAR(250) NOT NULL AFTER device;
ALTER TABLE srv_user ADD os VARCHAR(250) NOT NULL AFTER browser;

UPDATE misc SET value='13.12.10' WHERE what="version";
 
INSERT INTO srv_help (what, help) VALUES ('srv_nice_url','Lepi link');
INSERT INTO srv_help (what, help) VALUES ('srv_para_graph_link','Parapodatki');
INSERT INTO srv_help (what, help) VALUES ('srv_para_neodgovori_link','Neodgovor spremenljivke');
INSERT INTO srv_help (what, help) VALUES ('srv_aapor_link','AAPOR kalkulacije');
INSERT INTO srv_help (what, help) VALUES ('srv_moje_ankete_setting','Moje ankete');
INSERT INTO srv_help (what, help) VALUES ('srv_privacy_setting','Politika zasebnosti');
INSERT INTO srv_help (what, help) VALUES ('srv_continue_later_setting','Opcija za nadaljevanje kasenje');
INSERT INTO srv_help (what, help) VALUES ('srv_namig_setting','Namig');
INSERT INTO srv_help (what, help) VALUES ('srv_window_help','Urejanje oken s pomočjo');

UPDATE misc SET value='13.12.17' WHERE what="version";

INSERT INTO srv_help (what, help) VALUES ('srv_vprasanje_tracking_setting','Arhiviranje vprašanj');

UPDATE misc SET value='13.12.18' WHERE what="version";

CREATE TABLE srv_nice_links_skupine (
 id int(11) NOT NULL AUTO_INCREMENT,
 ank_id int(11) NOT NULL,
 nice_link_id int(11) NOT NULL,
 vre_id int(11) NOT NULL,
 link VARBINARY(30) NOT NULL,
 PRIMARY KEY (id)
) ENGINE = InnoDB;

UPDATE misc SET value='14.01.12' WHERE what="version";

INSERT INTO srv_help (what, help) VALUES ('srv_skupine','Skupine');
ALTER TABLE srv_datasetting_profile ADD hideAllSystem ENUM('0','1') NOT NULL DEFAULT '0';

UPDATE misc SET value='14.01.21' WHERE what="version";

CREATE TABLE srv_data_vrednost_cond (
	id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	spr_id INT(11) NOT NULL DEFAULT '0',
	vre_id INT(11) NOT NULL DEFAULT '0',
	text TEXT NOT NULL COLLATE 'utf8_bin',
	usr_id INT(11) NOT NULL DEFAULT '0',
	loop_id INT(11) NULL DEFAULT NULL,
	PRIMARY KEY (id),
	UNIQUE INDEX spr_id (spr_id, vre_id, usr_id, loop_id),
	INDEX usr_id (usr_id),
	INDEX fk_srv_data_vrednost_cond_id (vre_id),
	INDEX fk_srv_data_vrednost_cond_loop_id (loop_id),
	CONSTRAINT fk_srv_data_vrednost_cond_loop_id FOREIGN KEY (loop_id) REFERENCES srv_loop_data (id) ON UPDATE CASCADE ON DELETE CASCADE,
	CONSTRAINT fk_srv_data_vrednost_cond_spr_id FOREIGN KEY (spr_id) REFERENCES srv_spremenljivka (id) ON UPDATE CASCADE ON DELETE CASCADE,
	CONSTRAINT fk_srv_data_vrednost_cond_usr_id FOREIGN KEY (usr_id) REFERENCES srv_user (id) ON UPDATE CASCADE ON DELETE CASCADE
)
COLLATE='utf8_bin'
ENGINE=InnoDB;

UPDATE misc SET value='14.04.06' WHERE what="version";

INSERT INTO srv_help (what, help) VALUES ('srv_choose_skin','Izbira predloge');
INSERT INTO srv_help (what, help) VALUES ('srv_missing_values','Manjkajoce vrednosti');

UPDATE misc SET value='14.04.17' WHERE what="version";

INSERT INTO srv_help (what, help) VALUES ('srv_create_survey', 'Ustvari navadno anketo');
INSERT INTO srv_help (what, help) VALUES ('srv_create_form', 'Ustvari formo na eni strani');
INSERT INTO srv_help (what, help) VALUES ('srv_create_poll', 'Ustvari glasovanje z enim vprašanjem');

UPDATE misc SET value='14.05.09' WHERE what="version";

alter table new change rows rows integer not null default 5;
alter table new change rows rows integer not null default -1;

INSERT INTO srv_help (what, help) VALUES ('srv_data_filter', 'Filtriranje');
INSERT INTO srv_help (what, help) VALUES ('srv_toolbox_add_advanced', 'Dodaj tip vprašanja');

UPDATE misc SET value='14.05.15' WHERE what="version";

ALTER TABLE srv_user_setting ADD advancedMySurveys ENUM('0','1') NOT NULL DEFAULT '0';

ALTER TABLE srv_dostop CHANGE dostop dostop SET('edit', 'test', 'publish', 'data', 'analyse', 'export', 'link', 'mail', 'dashboard', 'phone', 'mail_server') CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'edit,test,publish,data,analyse,export,dashboard';

UPDATE misc SET value='14.07.17' WHERE what="version";

INSERT INTO srv_help (what, help) VALUES ('srv_show_progressbar', 'Prikae indikator napredka na vrhu ankete. Vklop je možen samo, če ima anketa več strani.');

ALTER TABLE srv_spremenljivka ADD locked ENUM( '0', '1' ) NOT NULL DEFAULt '0' AFTER visible;

INSERT INTO srv_help (what, help) VALUES ('srv_spremenljivka_lock', 'Zaklenjeno vprašanje lahko ureja samo avtor ankete.');

UPDATE misc SET value='14.09.01' WHERE what="version";
UPDATE misc SET value='14.09.07' WHERE what="version";
UPDATE misc SET value='14.09.08' WHERE what="version";
UPDATE misc SET value='14.09.09' WHERE what="version";

ALTER TABLE srv_spremenljivka CHANGE skupine skupine enum('0','1','2','3') NOT NULL DEFAULT '0';

UPDATE misc SET value='14.09.21' WHERE what="version";

ALTER TABLE srv_anketa CHANGE progressbar progressbar TINYINT NOT NULL DEFAULT '0';

UPDATE misc SET value='14.09.25' WHERE what="version";
UPDATE misc SET value='14.09.28' WHERE what="version";

INSERT INTO misc (what, value) VALUES ('UnregisterEmbed', '');
UPDATE misc SET value='14.10.04' WHERE what="version";

ALTER TABLE srv_status_profile ADD statusnonusable TINYINT(1) NOT NULL DEFAULT '1';
ALTER TABLE srv_status_profile ADD statuspartusable TINYINT(1) NOT NULL DEFAULT '1';
ALTER TABLE srv_status_profile ADD statususable TINYINT(1) NOT NULL DEFAULT '1';

UPDATE misc SET value='14.10.10' WHERE what="version";

INSERT INTO srv_help (what, help) VALUES ('srv_skala_text_ord', 'Ordinalna skala');
INSERT INTO srv_help (what, help) VALUES ('srv_skala_text_nom', 'Nominalna skala');
INSERT INTO srv_help (what, help) VALUES ('srv_dropdown_quickedit', 'Hitro urejanje');

UPDATE misc SET value='14.10.25' WHERE what="version";

INSERT INTO srv_help (what, help) VALUES ('srv_concl_deactivation_text', 'Obvestilo pri deaktivaciji');

UPDATE misc SET value='14.10.28' WHERE what="version";

ALTER TABLE srv_user_setting ADD showIntro ENUM('0','1') NOT NULL DEFAULT '1';
ALTER TABLE srv_user_setting ADD showConcl ENUM('0','1') NOT NULL DEFAULT '1';
ALTER TABLE srv_user_setting ADD showSurveyTitle ENUM('0','1') NOT NULL DEFAULT '1';

UPDATE misc SET value='14.11.17' WHERE what="version";

ALTER TABLE srv_user_setting ADD oneclickCreateMySurveys ENUM('0','1') NOT NULL DEFAULT '0';

UPDATE misc SET value='14.11.21' WHERE what="version";

ALTER TABLE srv_dostop CHANGE dostop dostop SET('edit', 'test', 'publish', 'data', 'analyse', 'export', 'link', 'mail', 'dashboard', 'phone', 'mail_server', 'lock') CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'edit,test,publish,data,analyse,export,dashboard';

UPDATE misc SET value='14.12.15' WHERE what="version";

ALTER TABLE srv_spremenljivka ADD signature TINYINT NOT NULL DEFAULT 0 AFTER upload;

UPDATE misc SET value='14.12.26' WHERE what="version";

#
# logiram stevce da 10 minut isti IP ne bo poveceval counta....
#

CREATE TABLE view_log (id integer not null primary key auto_increment, ip varchar(20), type varchar(20) COMMENT 'kaj', rid integer COMMENT 'id zapisa v bazi', cas datetime);

ALTER TABLE srv_anketa CHANGE return_finished return_finished TINYINT NOT NULL DEFAULT '0' AFTER cookie_return;

UPDATE misc SET value='15.01.19' WHERE what="version";

UPDATE users SET email=REPLACE(email, 'D3LMD-', 'UNSU8MD-');

UPDATE misc SET value='15.01.25' WHERE what="version";

INSERT INTO srv_help (what, help) VALUES('srv_skins_Fdv', 'Samo za uporabnike, ki imajo dovoljenje s strani FDV.');
INSERT INTO srv_help (what, help) VALUES('srv_skins_Uni', 'Samo za uporabnike, ki imajo dovoljenje s strani FDV.');
INSERT INTO srv_help (what, help) VALUES('srv_skins_Embed', 'Za ankete, ki so vključene v drugo spletno stran.');
INSERT INTO srv_help (what, help) VALUES('srv_skins_Embed2', 'Za ankete, ki so vključene v drugo spletno stran (ožja različica).');
INSERT INTO srv_help (what, help) VALUES('srv_skins_Slideshow', 'Za prezentacijo.');

ALTER table srv_anketa MODIFY block_ip int(11) NOT NULL DEFAULT '0';

UPDATE misc SET value='15.01.27' WHERE what="version";

ALTER TABLE srv_anketa CHANGE usercode_skip usercode_skip TINYINT NOT NULL DEFAULT '0';

UPDATE misc SET value='15.02.02' WHERE what="version";

ALTER TABLE srv_spremenljivka CHANGE dynamic_mg dynamic_mg ENUM('0','1','2','3','4','5','6') NOT NULL DEFAULT '0';

UPDATE misc SET value='15.02.06' WHERE what="version";

ALTER TABLE users ADD COLUMN manuallyApproved ENUM('Y','N') NOT NULL DEFAULT 'N';

INSERT INTO srv_help (what, help) VALUES('srv_alert_show_99', 'Funkcija prikaz "Ne vem" ob opozorilu, da se respondentu prikaže odgovor "Ne vem" šele po tem, ko ta ni odgovoril na vprašanje. Vprašanje mora biti obvezno ali imeti opozorilo. <a href="https://www.1ka.si/a/57119">Primer ankete >></a>');
INSERT INTO srv_help (what, help) VALUES('srv_alert_show_98', 'Funkcija prikaz "Zavrnil" ob opozorilu, da se respondentu prikaže odgovor "Zavrnil" šele po tem, ko ta ni odgovoril na vprašanje. Vprašanje mora biti obvezno ali imeti opozorilo.');
INSERT INTO srv_help (what, help) VALUES('srv_alert_show_97', 'Funkcija prikaz "Neustrezno" ob opozorilu, da se respondentu prikaže odgovor "Neustrezno" šele po tem, ko ta ni odgovoril na vprašanje. Vprašanje mora biti obvezno ali imeti opozorilo.');

ALTER TABLE srv_spremenljivka ADD alert_show_98 enum('0','1') NOT NULL DEFAULT '0' AFTER alert_show_99;
ALTER TABLE srv_spremenljivka ADD alert_show_97 enum('0','1') NOT NULL DEFAULT '0' AFTER alert_show_98;

UPDATE misc SET value='15.02.25' WHERE what="version";

CREATE TABLE srv_mysurvey_folder (
	id int(11) NOT NULL auto_increment,
	usr_id int(11) NOT NULL default '0',
	parent int(11) NOT NULL default '0',
	naslov varchar(50) NOT NULL default '',
	PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE srv_mysurvey_anketa (
	ank_id int(11) NOT NULL,
	usr_id int(11) NOT NULL,
	folder int(11) NOT NULL default '0',
	PRIMARY KEY (ank_id, usr_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='15.03.02' WHERE what="version";

ALTER TABLE srv_mysurvey_folder ADD open enum('0','1') NOT NULL DEFAULT '0';

UPDATE misc SET value='15.03.03' WHERE what="version";

ALTER TABLE srv_user_setting ADD survey_list_folders ENUM('0','1') NOT NULL DEFAULT '0';

UPDATE misc SET value='15.03.04' WHERE what="version";

CREATE TABLE ip_allow (id integer not null primary key auto_increment, ip varchar(255) not null default '');
UPDATE misc SET value='15.03.08' WHERE what="version";

ALTER TABLE srv_alert_custom CHANGE type type varchar(20) NOT NULL;

UPDATE misc SET value='15.03.10' WHERE what="version";

ALTER TABLE srv_vrednost ADD hidden ENUM('0', '1') NOT NULL DEFAULT '0';

UPDATE misc SET value='15.03.13' WHERE what="version";

INSERT INTO srv_help (what,help) VALUES ('srv_crosstab_residual2','Reziduali');

UPDATE misc SET value='15.04.10' WHERE what="version";

INSERT INTO srv_misc (what, value) VALUES ('mobile_friendly', '1');
INSERT INTO srv_misc (what, value) VALUES ('mobile_tables', '1');

UPDATE misc SET value='15.04.17' WHERE what="version";

ALTER TABLE srv_if ADD horizontal ENUM('0','1') NOT NULL DEFAULT '0';

UPDATE misc SET value='15.04.20' WHERE what="version";

CREATE TABLE srv_notifications (
	id int(11) NOT NULL AUTO_INCREMENT,
	author int(11) NOT NULL default '0',
	recipient int(11) NOT NULL default '0',
	date date NOT NULL default '0000-00-00',
	title varchar(50) NOT NULL default '',
	text mediumtext NOT NULL default '',
	viewed ENUM('0','1') NOT NULL DEFAULT '0',
	PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='15.05.05' WHERE what="version";

ALTER TABLE srv_vrednost CHANGE hidden hidden ENUM('0','1','2') NOT NULL DEFAULT '0';

UPDATE misc SET value='15.05.08' WHERE what="version";

ALTER TABLE srv_anketa ADD skin_checkbox TINYINT(4)  NOT NULL DEFAULT 0 AFTER skin_profile;

UPDATE misc SET value='15.05.21' WHERE what="version";

# prej je bila omejitev 250 znakov in je dolge odgovore odrezalo
ALTER TABLE srv_data_textgrid MODIFY text TEXT character set utf8 collate utf8_bin NOT NULL; 

UPDATE misc SET value='15.05.25' WHERE what="version";

INSERT INTO srv_help (what, help) VALUES('srv_activity_quotas', 'Več o trajanju ankete in kvotah si lahko preberete <a href="https://www.1ka.si/db/24/493/Priročniki/Trajanje_ankete_glede_na_datum_ali_stevilo_odgovorov_kvote/" target="_blank">tukaj</a>.');
INSERT INTO srv_help (what, help) VALUES('srv_activity_quotas_valid', 'Več o statusih enot si lahko preberete <a href="https://www.1ka.si/db/24/328/Prirocniki/Statusi_enot_ustreznost_veljavnost_in_manjkajoce_vrednosti/" target="_blank">tukaj</a>.');

UPDATE misc SET value='15.07.17' WHERE what="version";

# dovolimo null, ker imamo noter tudi komentarje, ki morajo ostat pri brisanju userjev
ALTER TABLE srv_data_text MODIFY usr_id int(11) default '0'; 

UPDATE misc SET value='15.08.11' WHERE what="version";

ALTER TABLE neww change rows rows integer not null default -1;

ALTER TABLE srv_telephone_setting ADD call_order int(11) NOT NULL default '0' AFTER max_calls;

UPDATE misc SET value='15.08.25' WHERE what="version";

ALTER TABLE srv_anketa ADD 360_stopinj TINYINT NOT NULL DEFAULT '0' AFTER social_network;

UPDATE misc SET value='15.09.29' WHERE what="version";

ALTER TABLE srv_if ADD random INT(11) NOT NULL DEFAULT '-1';

UPDATE misc SET value='15.10.14' WHERE what="version";

INSERT INTO srv_help (what, help) VALUES('srv_telephone_help', 'Ve&#269; o telefonski anketi si lahko preberete v priro&#269;niku. <a href=\"http://www.1ka.si/c/834/Telefonska_anketa/?preid=824&from1ka=1\" target=\"_blank\">Ve&#269; >></a>');

UPDATE misc SET value='15.10.15' WHERE what="version";

ALTER TABLE srv_telephone_setting ADD status_d int(10) NOT NULL default '0' AFTER status_n;
ALTER TABLE srv_telephone_history CHANGE status status ENUM( 'A', 'Z', 'N', 'R', 'T', 'P', 'U', 'D' ) NOT NULL;

UPDATE misc SET value='15.10.22' WHERE what="version";

ALTER TABLE srv_invitations_recipients MODIFY custom VARCHAR(100) DEFAULT NULL;

UPDATE misc SET value='15.12.08' WHERE what="version";

ALTER TABLE srv_user_setting ADD manage_domain VARCHAR(50) NOT NULL DEFAULT '';

UPDATE misc SET value='15.12.09' WHERE what="version";

CREATE TABLE srv_quota (
  id int(11) NOT NULL auto_increment,
  cnd_id int(11) NOT NULL,
  spr_id int(11) NOT NULL,
  vre_id int(11) NOT NULL,
  grd_id int(11) NOT NULL,
  operator smallint(6) NOT NULL,
  value int(11) NOT NULL DEFAULT '0',
  left_bracket smallint(6) NOT NULL,
  right_bracket smallint(6) NOT NULL,
  vrstni_red int(11) NOT NULL,
  PRIMARY KEY (id),
  KEY cnd_id (cnd_id),
  KEY spr_id (spr_id, vre_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='15.12.15' WHERE what="version";


# spregledal... odstranim, se nuca spodnja...
ALTER TABLE users DROP COLUMN aai;
ALTER TABLE users ADD COLUMN eduroam ENUM ('1', '0');

UPDATE misc SET value='15.12.31' WHERE what="version";

# rabimo dodatno vrednost za kvote - drugace ne dela zaradi FK
INSERT INTO srv_spremenljivka (id, gru_id, naslov) VALUES ('-3', '0', 'system');

UPDATE misc SET value='16.01.05' WHERE what="version";

CREATE TABLE user_login_tracker (id bigint unsigned primary key auto_increment, uid integer unsigned, IP varchar(255) not null default "N/A", kdaj datetime);

INSERT INTO srv_help (help, what) VALUES ('Tukaj lahko odstranjujete celotni nivo ali pa s klikom na checkbox izberete, &#269;e so &#353;ifranti znotraj polja unikatni.', 'srv_hierarchy_admin_help');

UPDATE misc SET value='16.01.08' WHERE what="version";

INSERT INTO srv_misc (what, value) VALUES ('export_numbering', '1');
INSERT INTO srv_misc (what, value) VALUES ('export_show_if', '1');
INSERT INTO srv_misc (what, value) VALUES ('export_font_size', '10');

INSERT INTO srv_misc (what, value) VALUES ('export_data_numbering', '1');
INSERT INTO srv_misc (what, value) VALUES ('export_data_show_recnum', '1');
INSERT INTO srv_misc (what, value) VALUES ('export_data_show_if', '1');
INSERT INTO srv_misc (what, value) VALUES ('export_data_font_size', '10');

UPDATE misc SET value='16.01.11' WHERE what="version";

DROP TABLE srv_notifications;

CREATE TABLE srv_notifications (
	id int(11) NOT NULL AUTO_INCREMENT,
	message_id int(11) NOT NULL,
	recipient int(11) NOT NULL default '0',
	viewed ENUM('0','1') NOT NULL DEFAULT '0',
	PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE srv_notifications_messages (
	id int(11) NOT NULL AUTO_INCREMENT,
	author int(11) NOT NULL default '0',
	date date NOT NULL default '0000-00-00',
	title varchar(50) NOT NULL default '',
	text mediumtext NOT NULL default '',
	PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='16.02.04' WHERE what="version";

ALTER TABLE srv_user_setting ADD activeComments ENUM('0','1') NOT NULL DEFAULT '0';

ALTER TABLE srv_user DROP INDEX recnum;
ALTER TABLE srv_user ADD INDEX recnum (ank_id, recnum, preview);

UPDATE misc SET value='16.03.29' WHERE what="version";

# tole je za "varno" prijavo prek AAI na daljavo
CREATE TABLE aai_prenosi (timestamp integer unsigned, moja varchar(500), njegova varchar(500));

UPDATE srv_dostop d, srv_alert al, srv_anketa an SET d.alert_complete='1' WHERE al.finish_author='1' AND an.id=al.ank_id AND al.ank_id=d.ank_id AND d.uid=an.insert_uid;

UPDATE misc SET value='16.04.11' WHERE what="version";

ALTER TABLE srv_if ADD thread INT NOT NULL DEFAULT '0';

UPDATE misc SET value='16.04.18' WHERE what="version";

INSERT INTO srv_misc (what, value) VALUES ('export_show_intro', '1');
UPDATE srv_misc SET value='0' WHERE what='export_data_numbering';
UPDATE srv_misc SET value='0' WHERE what='export_data_show_recnum';
UPDATE srv_misc SET value='0' WHERE what='export_data_show_if';
INSERT INTO srv_misc (what, value) VALUES ('export_data_skip_empty', '1');
INSERT INTO srv_misc (what, value) VALUES ('export_data_skip_empty_sub', '1');
INSERT INTO srv_misc (what, value) VALUES ('export_data_PB', '1');

UPDATE misc SET value='16.04.22' WHERE what="version";

CREATE TABLE srv_hotspot_regions (
	id int(11) NOT NULL AUTO_INCREMENT,
	vre_id int(11) NOT NULL default '0',
	spr_id int(11) NOT NULL default '0',
	region_name text NOT NULL default '',
	region_coords text NOT NULL default '',
	region_index int(11) NOT NULL,
	variable varchar(15) NOT NULL default '',
	vrstni_red int(11) NOT NULL default '0',
	PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='16.04.26' WHERE what="version";

CREATE TABLE srv_data_map (
  id int(11) NOT NULL auto_increment,
  usr_id int(11) NOT NULL,
  spr_id int(11) NOT NULL,
  ank_id int(11) NOT NULL,
  loop_id int(11) DEFAULT NULL,
  lat float(19,15) NOT NULL,
  lng float(19,15) NOT NULL,
  address varchar(255) character set utf8 collate utf8_bin default '',
  PRIMARY KEY (id),
  KEY usr_id (usr_id),
  KEY loop_id (loop_id),
  KEY ank_id (ank_id),
  KEY spr_id (spr_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE srv_data_map ADD CONSTRAINT fk_srv_data_map_ank_id FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE srv_data_map ADD CONSTRAINT fk_srv_data_map_usr_id FOREIGN KEY (usr_id) REFERENCES srv_user (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE srv_data_map ADD CONSTRAINT fk_srv_data_map_loop_id FOREIGN KEY (loop_id) REFERENCES srv_loop_data (id) ON DELETE CASCADE ON UPDATE CASCADE;

UPDATE misc SET value='16.05.06' WHERE what="version";

CREATE TABLE srv_module (
	id int(11) NOT NULL AUTO_INCREMENT,
	module_name text NOT NULL default '',
	active ENUM('0','1') NOT NULL DEFAULT '0',
	PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO srv_module (module_name, active) VALUES ('hierarhija', '0');
INSERT INTO srv_module (module_name, active) VALUES ('evalvacija', '0');
INSERT INTO srv_module (module_name, active) VALUES ('360', '0');
INSERT INTO srv_module (module_name, active) VALUES ('evoli', '0');

UPDATE misc SET value='16.05.13' WHERE what="version";

ALTER TABLE srv_hotspot_regions ADD CONSTRAINT fk_srv_hotspot_regions_spr_id FOREIGN KEY (spr_id) REFERENCES srv_spremenljivka (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE srv_hotspot_regions ADD CONSTRAINT fk_srv_hotspot_regions_vre_id FOREIGN KEY (vre_id) REFERENCES srv_vrednost (id) ON DELETE CASCADE ON UPDATE CASCADE;

UPDATE misc SET value='16.05.16' WHERE what="version";

ALTER TABLE srv_anketa ADD evoli TINYINT NOT NULL DEFAULT '0' AFTER 360_stopinj;

UPDATE misc SET value='16.05.16' WHERE what="version";

ALTER TABLE srv_data_map ADD text TEXT CHARACTER SET utf8 COLLATE utf8_bin DEFAULT ''; 

## NEVARNO NA VELIKI BAZI KER ZABLOKIRA STREZNIK
## ALTER table srv_user MODIFY pass VARCHAR(50) NULL DEFAULT NULL;

UPDATE misc SET value='16.05.17' WHERE what="version";

## TUDI TOLE ZNA TRAJAT
ALTER TABLE srv_invitations_recipients ADD relation int(11) DEFAULT NULL AFTER custom;

UPDATE misc SET value='16.05.24' WHERE what="version";

INSERT INTO srv_module (module_name, active) VALUES ('gfksurvey', '0');

UPDATE misc SET value='16.05.27' WHERE what="version";

INSERT INTO srv_help (help, what) VALUES ('Izberite mo&#382;nosti prikazovanja namigov z imeni obmo&#269;ij.

Prika&#382;i ob mouseover: namig je viden, ko je kurzor mi&#353;ke nad obmo&#269;jem;
Skrij: namig ni viden;
', 'srv_hotspot_tooltip');

INSERT INTO srv_help (help, what) VALUES ('Izberite mo&#382;nosti prikazovanja namigov s kategorijami odgovorov.

Prika&#382;i ob mouseover: kategorije odgovorov so vidne, ko je kurzor mi&#353;ke nad obmo&#269;jem;
Prika&#382;i ob kliku  mi&#353;ke na obmo&#269;je: kategorije odgovorov so vidne, ko se klikne na obmo&#269;je;
', 'srv_hotspot_tooltip_grid');

INSERT INTO srv_help (help, what) VALUES ('Izberite tip osvetlitve oz. kako, so obmo&#269;ja vidna ali nevidna respondentom.

Skrij: obmo&#269;je ni vidno;
Prika&#382;i: obmo&#269;je je vidno;
Prika&#382;i ob mouseover: obmo&#269;je je vidno, ko je kurzor mi&#353;ke nad obmo&#269;jem;
', 'srv_hotspot_visibility');


UPDATE misc SET value='16.06.20' WHERE what="version";

ALTER TABLE srv_anketa ADD concl_return_edit ENUM('0','1') NOT NULL DEFAULT '0' AFTER concl_PDF_link;

UPDATE misc SET value='16.06.22' WHERE what="version";

CREATE TABLE srv_api_auth (
	usr_id int(11) NOT NULL DEFAULT '0',
	identifier text NOT NULL DEFAULT '',
	private_key text NOT NULL DEFAULT '',
	PRIMARY KEY (usr_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='16.06.22' WHERE what="version";

CREATE TABLE srv_quiz_vrednost (
  spr_id int(11) NOT NULL default '0',
  vre_id int(11) NOT NULL default '0',
  PRIMARY KEY  (spr_id, vre_id),
  CONSTRAINT fk_srv_quiz_vrednost_spr_id FOREIGN KEY (spr_id) REFERENCES srv_spremenljivka (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_srv_quiz_vrednost_vre_id FOREIGN KEY (vre_id) REFERENCES srv_vrednost (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

UPDATE misc SET value='16.06.28' WHERE what="version";

INSERT INTO srv_help (help, what) VALUES ('&#352;tevilo najve&#269; mo&#382;nih oddanih odgovorov/markerjev na zemljevidu', 'srv_vprasanje_max_marker_map');
INSERT INTO srv_help (help, what) VALUES ('Besedilo podvpra&#353;anja v obla&#269;ku markerja', 'naslov_podvprasanja_map');
INSERT INTO srv_help (help, what) VALUES ('Brskalnik bo poskusil ugotoviti trenutno lokacijo respondenta. Respondenta bo brskalnik najprej vpra&#353;al za dovoljenje deljenja njegove lokacije.', 'user_location_map');
INSERT INTO srv_help (help, what) VALUES ('V obla&#269;ek markerja dodaj podvpra&#353;anje', 'marker_podvprasanje');
INSERT INTO srv_help (help, what) VALUES ('V zemljevid vklju&#269;i tudi iskalno okno, preko katerega lahko respondent tudi opisno poi&#353;&#269;e lokacijo na zemljevidu', 'dodaj_searchbox');

UPDATE misc SET value='16.07.8' WHERE what="version";

INSERT INTO misc VALUES ("mobileApp_version", "16.5.30");

INSERT INTO srv_module (module_name, active) VALUES ('mfdps', '0');

UPDATE misc SET value='16.07.8' WHERE what="version";

## MODULI KI SO LAHKO VKLOPLJENI PRI ANKETI SE PRENESEJO V NOVO TABELO (KER JIH JE POCASI ZE PREVEC)
CREATE TABLE srv_anketa_module (
	ank_id int(11) NOT NULL,
	modul VARCHAR(100) NOT NULL DEFAULT '',
	PRIMARY KEY (ank_id, modul)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE srv_anketa_module ADD CONSTRAINT fk_srv_anketa_module_ank_id FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE;

## KOPIRAMO VSE NASTAVLJENE MODULE NA OBSTOJECIH ANKETAH
INSERT INTO srv_anketa_module (ank_id, modul) SELECT id, 'email' FROM srv_anketa WHERE email='1';
INSERT INTO srv_anketa_module (ank_id, modul) SELECT id, 'phone' FROM srv_anketa WHERE phone='1';
INSERT INTO srv_anketa_module (ank_id, modul) SELECT id, 'slideshow' FROM srv_anketa WHERE slideshow='1';
INSERT INTO srv_anketa_module (ank_id, modul) SELECT id, 'social_network' FROM srv_anketa WHERE social_network='1';
INSERT INTO srv_anketa_module (ank_id, modul) SELECT id, 'quiz' FROM srv_anketa WHERE quiz='1';
INSERT INTO srv_anketa_module (ank_id, modul) SELECT id, 'uporabnost' FROM srv_anketa WHERE uporabnost='1';

INSERT INTO srv_anketa_module (ank_id, modul) SELECT id, '360_stopinj' FROM srv_anketa WHERE 360_stopinj='1';
INSERT INTO srv_anketa_module (ank_id, modul) SELECT id, 'evoli' FROM srv_anketa WHERE evoli='1';
INSERT INTO srv_anketa_module (ank_id, modul) SELECT id, 'hierarhija' FROM srv_anketa WHERE hierarhija='1';

INSERT INTO srv_anketa_module (ank_id, modul, vrednost) SELECT id, 'hierarhija', hierarhija FROM srv_anketa WHERE hierarhija='2';

## POBRISEMO STARA POLJA IZ srv_anketa
ALTER TABLE srv_anketa DROP COLUMN email;
ALTER TABLE srv_anketa DROP COLUMN phone;
ALTER TABLE srv_anketa DROP COLUMN slideshow;
ALTER TABLE srv_anketa DROP COLUMN social_network;
ALTER TABLE srv_anketa DROP COLUMN quiz;
ALTER TABLE srv_anketa DROP COLUMN uporabnost;

ALTER TABLE srv_anketa DROP COLUMN 360_stopinj;
ALTER TABLE srv_anketa DROP COLUMN evoli;
ALTER TABLE srv_anketa DROP COLUMN hierarhija;

ALTER TABLE srv_anketa_module ADD vrednost TINYINT NOT NULL DEFAULT '1';

UPDATE misc SET value='16.08.05' WHERE what="version";

## ZE IZVEDENO NA www.1ka.si!!
ALTER TABLE srv_user DROP INDEX recnum;
ALTER TABLE srv_user ADD INDEX recnum (ank_id, recnum, preview, deleted);

UPDATE misc SET value='16.08.22' WHERE what="version";

ALTER TABLE srv_invitations_archive ADD tip TINYINT(4) NOT NULL DEFAULT '0' AFTER body_text;

UPDATE misc SET value='16.08.23' WHERE what="version";

INSERT INTO srv_help (help, what) VALUES ('&#268;e &#382;elite dodati enote, ki nimajo emailov, naredite lo&#269;en seznam.', 'srv_inv_recipiens_add_invalid_note');

UPDATE misc SET value='16.08.23' WHERE what="version";

ALTER TABLE srv_anketa ADD intro_static TINYINT(4) NOT NULL DEFAULT '0' AFTER show_intro;
ALTER TABLE srv_anketa ADD mobile_created ENUM('0','1') NOT NULL DEFAULT '0';

UPDATE misc SET value='16.08.29' WHERE what="version";

INSERT INTO srv_help (help, what) VALUES ('Z izbiro "email vabil z individualiziranim URL" se avtomati&#269;no vklopi opcija "Da" za individualizirana vabila, za vnos kode pa "Avtomatsko v URL". Respondentom bo sistem 1KA lahko poslal email, v katerem bo individualiziran URL naslovom ankete. &#268;im bo respondent na URL kliknil, bo sistem 1KA sledil respondenta.', 'srv_inv_activate_1');
INSERT INTO srv_help (help, what) VALUES ('INSERT INTO srv_anketa_module (ank_id, modul) SELECT id, 'quiz' FROM srv_anketa WHERE quiz='1';Z izbiro "ro&#269;ni vnos individualizirane kode" se avtomati&#269;no vklopi opcija "Da" za individulaizirana vabila ter opcija "Ro&#269;ni vnos" za vnos kode. Respondenti bodo prejeli enak URL, na za&#269;etku pa bodo morali ro&#269;no vnesti svojo individualno kodo. Vabilo s kodo se lahko respondentu po&#353;lje z emailom preko sistemom 1KA. Lahko pa se po&#353;lje  tudi eksterno (izven sistema 1KA): z dopisom preko po&#353;te, s SMS sporo&#269;ilom kako druga&#269;e; v takem primeru sistem 1KA zgolj zabele&#382;i kdo, kdaj in kako je poslal vabilo.', 'srv_inv_activate_2');
INSERT INTO srv_help (help, what) VALUES ('Z izbiro "uporabe splo&#353;nih vabil brez individulaizirane kode" opcija "email vabila z individualiziranim URL" ostaja izklopljena ("Ne"). Sistem 1KA bo respondenom lahko poslal emaile, ki pa ne bo imeli individulaizranega URL oziroma individualizirane kode.', 'srv_inv_activate_3');

UPDATE misc SET value='16.09.02' WHERE what="version";

INSERT INTO srv_help (help, what) VALUES ('Zabele&#382;imo lahko kako posebnost ali zna&#269;ilnost (npr. preliminarno obvestilo, prvo vabilo, opomnik ipd). V primeru ro&#269;nega po&#353;iljanja je priporo&#269;ljivo navesti dejanski dan odpo&#353;iljanja (npr. preko po&#353;te), saj se lahko razlikuje od datuma priprave seznama.', 'srv_inv_sending_comment');
INSERT INTO srv_help (help, what) VALUES ('Odstranjujejo se podvojeni zapisi glede na email', 'srv_inv_sending_double');
INSERT INTO srv_help (help, what) VALUES ('Klik na &#353;tevilo poslanih vabil prika&#382;e podroben pregled poslanih vabil', 'srv_inv_archive_sent');

UPDATE misc SET value='16.09.05' WHERE what="version";

##TOLE ZNA BITI POCASNO NA WWW!!
##ALTER TABLE srv_vrednost ADD INDEX (if_id);

INSERT INTO srv_module (module_name, active) VALUES ('360_1ka', '0');

UPDATE misc SET value='16.09.09' WHERE what="version";

ALTER TABLE srv_parapodatki ADD spr_id_variable INT NOT NULL AFTER item;

UPDATE misc SET value='16.09.10' WHERE what="version";

INSERT INTO srv_help (help, what) VALUES ('Z izbiro "Email vabila z ro&#269;nim vnosom kode" se vklopi opcija "Da" za individualizirana vabila, za vnos kode pa "Ro&#269;ni vnos". Respondentom bo sistem 1KA lahko poslal email, v katerem bo individualizirana koda, URL naslov ankete pa bo enoten. Ko bo respondent kliknil na URL kliknil, se bo prikazal zahtevek za vnos kode, ki jo je prejel po emailu.', 'srv_inv_activate_4');

UPDATE misc SET value='16.09.11' WHERE what="version";

ALTER TABLE srv_invitations_archive MODIFY COLUMN tip TINYINT(4) NOT NULL DEFAULT '-1';
## Popravimo ker je default '-1' po novem email
UPDATE srv_invitations_archive SET tip='-1' WHERE tip='0';

UPDATE misc SET value='16.09.12' WHERE what="version";

INSERT INTO srv_help (help, what) VALUES ('Sporo&#269;ilo, ki bo poslano po emailu', 'srv_inv_message_title');
INSERT INTO srv_help (help, what) VALUES ('Sporo&#269;ilo, ki bo poslano po navadni po&#353;ti ali SMS-u in bo v 1ki dokumentirano', 'srv_inv_message_title_noEmail');

UPDATE misc SET value='16.09.14' WHERE what="version";

## rabimo dodatno vrednost za napravo - drugace ne dela zaradi FK
INSERT INTO srv_spremenljivka (id, gru_id, naslov) VALUES ('-4', '0', 'system');

UPDATE misc SET value='16.09.16' WHERE what="version";

INSERT INTO srv_help (help, what) VALUES ('Individualizirana vabila omogo&#269;ajo sledenje respondentom.', 'srv_user_base_individual_invitaition_note');
INSERT INTO srv_help (help, what) VALUES ('Z izbiro "Ne" je modul individualiziranih vabil izklopljen. Anketira se lahko vsak, ki vidi ali pozna URL naslov. Respondentov v takem primeru ne moremo slediti; ne vemo kdo je odgovoril in kdo ne.<br /><br />Sistem 1KA lahko kljub temu po&#353;lje (email) oziroma dokumentira (po&#353;ta, SMS, drugo) po&#353;iljanje splo&#353;nega ne-individualiziranega vabila, kjer vsi respondenti prejmejo enotni URL. To pomeni, da se zabele&#382;ilo, komu, kdaj in kako je bilo vabilo poslano, ne bo pa ozna&#269;eno, kdo je odgovoril in kdo ne.', 'srv_user_base_individual_invitaition_note2');

UPDATE misc SET value='16.09.17' WHERE what="version";

CREATE TABLE srv_fieldwork (id integer unsigned auto_increment primary key, terminal_id varchar(255), sid_terminal integer unsigned, sid_server integer unsigned, secret varchar(500)); 
ALTER TABLE srv_fieldwork add column lastnum integer not null default 0;
 insert into srv_help values ('fieldwork_devices', '1', 'blablabla');
 insert into srv_help values ('fieldwork_devices', '2', 'blablabla');

UPDATE misc SET value='16.09.26' WHERE what="version";

INSERT INTO srv_help (help, what) VALUES ('Splosne nastavitve vabil', 'srv_inv_general_settings');
INSERT INTO srv_help (help, what) VALUES ('Nacin posiljanja', 'srv_inv_sending_type');
INSERT INTO srv_help (help, what) VALUES ('Poslji brez kode', 'srv_inv_no_code');

UPDATE misc SET value='16.10.02' WHERE what="version";

ALTER TABLE srv_notifications_messages ADD force_show ENUM('0','1') NOT NULL DEFAULT '0';

UPDATE misc SET value='16.10.12' WHERE what="version";

ALTER TABLE srv_language_vrednost ADD naslov2 TEXT NOT NULL AFTER naslov;

UPDATE misc SET value='16.10.18' WHERE what="version";

CREATE TABLE srv_chat_settings (
	ank_id INT(11) NOT NULL,
	code TEXT NOT NULL,
	PRIMARY KEY (ank_id) ,
	CONSTRAINT fk_srv_chat_settings_ank_id FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='16.10.31' WHERE what="version";

ALTER TABLE srv_chat_settings ADD chat_type enum('0','1','2') NOT NULL DEFAULT '0';

UPDATE misc SET value='16.11.04' WHERE what="version";

## se ne uporablja vec
DROP TABLE srv_survey_skins;
DROP TABLE srv_survey_skins_groups;

ALTER TABLE srv_anketa ADD mobile_skin VARCHAR( 100 ) NOT NULL DEFAULT 'Mobile' AFTER skin;

UPDATE misc SET value='16.12.06' WHERE what="version";

## se ne uporablja vec
DROP TABLE srv_branch;
DROP TABLE srv_call_comment;

UPDATE misc SET value='16.12.14' WHERE what="version";

INSERT INTO srv_module (module_name, active) VALUES ('mju', '0');

UPDATE misc SET value='16.12.20' WHERE what="version";

INSERT INTO srv_help (what, lang, help) VALUES ('srv_hotspot_region_color', '1', 'Omogo&#269;a urejanje barve obmo&#269;ja, ko bo to izbrano.');

INSERT INTO srv_help (what, lang, help) VALUES ('srv_hotspot_visibility_color', '1', 'Omogo&#269;a urejanje barve osvetlitve obmo&#269;ja.');

UPDATE misc SET value='16.12.22' WHERE what="version";

INSERT INTO srv_help (help, what) VALUES ('Missing kot 0', 'srv_calculation_missing');

UPDATE misc SET value='17.01.15' WHERE what="version";

UPDATE srv_anketa SET skin='1kaGrey' WHERE skin='1kaNew';

UPDATE misc SET value='17.01.27' WHERE what="version";

ALTER TABLE srv_data_map ADD vrstni_red INT;

## Dodamo timestamp
ALTER TABLE srv_branching ADD timestamp TIMESTAMP on update CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER pagebreak;
ALTER TABLE srv_grupa ADD timestamp TIMESTAMP on update CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER vrstni_red;
ALTER TABLE srv_spremenljivka ADD timestamp TIMESTAMP on update CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER skupine;
ALTER TABLE srv_vrednost ADD timestamp TIMESTAMP on update CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER hidden;

UPDATE misc SET value='17.01.31' WHERE what="version";

INSERT INTO srv_module (module_name, active) VALUES ('evoli_teammeter', '0');

CREATE TABLE srv_evoli_teammeter (
	id INT(11) NOT NULL AUTO_INCREMENT,
	ank_id INT(11) NOT NULL DEFAULT '0',
	skupina_id int(11) NOT NULL DEFAULT '0',	
	email VARCHAR(100) DEFAULT '',
	lang_id INT(11) NOT NULL DEFAULT '1',
	url VARCHAR(255) DEFAULT '',	
	kvota_max INT(11) NOT NULL default '0',
	kvota_val INT(11) NOT NULL default '0',
	PRIMARY KEY (id),
	CONSTRAINT fk_srv_evoli_teammeter_ank_id FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT fk_srv_evoli_teammeter_skupina_id FOREIGN KEY (skupina_id) REFERENCES srv_vrednost (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='17.02.01' WHERE what="version";

## se ne uporablja vec
ALTER TABLE srv_anketa DROP COLUMN baza_id;

ALTER TABLE srv_anketa ADD skin_profile_mobile INT(11) NOT NULL AFTER skin_profile;

CREATE TABLE srv_theme_profiles_mobile (
  id int(11) NOT NULL AUTO_INCREMENT,
  usr_id int(11) NOT NULL,
  skin varchar(100) NOT NULL,
  name varchar(100) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

ALTER TABLE srv_theme_profiles_mobile ADD logo VARCHAR(250) NOT NULL;

CREATE TABLE srv_theme_editor_mobile (
  profile_id int(11) NOT NULL,
  id int(11) NOT NULL,
  type int(11) NOT NULL,
  value varchar(100) NOT NULL,
  PRIMARY KEY (profile_id, id, type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE srv_theme_editor_mobile ADD CONSTRAINT fk_srv_theme_editor_mobile_profile_id FOREIGN KEY (profile_id) REFERENCES srv_theme_profiles_mobile (id) ON DELETE CASCADE ON UPDATE CASCADE;

UPDATE misc SET value='17.02.13' WHERE what="version";

## se ne uporablja vec - ze narejeno na www1kasi!!!
ALTER TABLE srv_user DROP COLUMN name;

## da vidimo ce bo tole kaj pomaglao pri hitrosti... - ze narejeno na www1kasi!!!
ALTER TABLE srv_user ADD INDEX test (ank_id, testdata, preview, last_status);

## PO NOVEM ARHIVIRAMO TUDI SPODNJE TABELE S PODATKI
CREATE TABLE srv_data_text_active LIKE srv_data_text; 
CREATE TABLE srv_data_textgrid_active LIKE srv_data_textgrid; 
CREATE TABLE srv_data_checkgrid_active LIKE srv_data_checkgrid; 

## ZA KOMPATIBILNOST ZA NAZAJ JE POTREBNO NAMESTO TEGA SPODAJ POGANJATI SKRIPTO utils/1kaUtils/1ka_ankete_deactive_part2.php (naceloma samo na www.1ka.si, ker drugje nicesar ne arhiviramo)
## PRI OSTALIH MANJSIH INSTALACIJAH JE MOGOCE LAZJE CE SE KAR VSE SKOPIRA (3 SPODNJI QUERIJI)
INSERT srv_data_text_active SELECT * FROM srv_data_text;
INSERT srv_data_textgrid_active SELECT * FROM srv_data_textgrid;
INSERT srv_data_checkgrid_active SELECT * FROM srv_data_checkgrid;

UPDATE misc SET value='17.02.26' WHERE what="version";

## ze narejeno na www1kasi!!!
ALTER TABLE srv_tracking ENGINE=InnoDB;
ALTER TABLE srv_tracking_incremental ENGINE=InnoDB;
ALTER TABLE sessions ENGINE=InnoDB;
ALTER TABLE user_tracker ENGINE=InnoDB;

# tole je samo za debugging, kar je starejše od 2016, je itak v logih
delete from user_tracker WHERE FROM_UNIXTIME(timestamp) < '2016-06-01 00:00:01'; 
ALTER TABLE users ENGINE=InnoDB;

UPDATE misc SET value='17.02.27' WHERE what="version";

## ze narejeno na www1kasi!!!
ALTER TABLE srv_user DROP INDEX test;
ALTER TABLE srv_user ADD INDEX response (ank_id, testdata, preview, last_status, time_edit, time_insert);

UPDATE misc SET value='17.02.28' WHERE what="version";

## PO NOVEM ARHIVIRAMO TUDI srv_tracking
CREATE TABLE srv_tracking_active LIKE srv_tracking;

## ZA KOMPATIBILNOST ZA NAZAJ JE POTREBNO NAMESTO TEGA SPODAJ POGANJATI SKRIPTO utils/1kaUtils/1ka_ankete_deactive_part3.php (naceloma samo na www.1ka.si, ker drugje nicesar ne arhiviramo)
## PRI OSTALIH MANJSIH INSTALACIJAH JE MOGOCE LAZJE CE SE KAR VSE SKOPIRA
INSERT srv_tracking_active SELECT * FROM srv_tracking;

RENAME TABLE 	srv_dataSetting_profile TO 	srv_datasetting_profile;

UPDATE misc SET value='17.03.03' WHERE what="version";

ALTER TABLE srv_data_map ADD vre_id int(11) AFTER loop_id;
ALTER TABLE srv_data_map ADD INDEX (vre_id);
ALTER TABLE srv_data_map ADD CONSTRAINT fk_srv_data_map_vre_id FOREIGN KEY (vre_id) REFERENCES srv_vrednost (id) ON DELETE CASCADE ON UPDATE CASCADE;

CREATE TABLE srv_vrednost_map (
  id INT NOT NULL auto_increment,
  vre_id INT NOT NULL,
  spr_id INT NOT NULL,
  overlay_id int(11) default '0',
  overlay_type VARCHAR(25) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  lat float(19,15) NOT NULL,
  lng float(19,15) NOT NULL,
  address varchar(255) character set utf8 collate utf8_bin default '',
  vrstni_red int(11) NOT NULL default '0',
  timestamp TIMESTAMP on update CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
ALTER TABLE srv_vrednost_map ADD CONSTRAINT fk_srv_vrednost_map_vre_id FOREIGN KEY (vre_id) REFERENCES srv_vrednost (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE srv_vrednost_map ADD CONSTRAINT fk_srv_vrednost_map_spr_id FOREIGN KEY (spr_id) REFERENCES srv_spremenljivka (id) ON DELETE CASCADE ON UPDATE CASCADE;

UPDATE misc SET value='17.03.06' WHERE what="version";

DELETE FROM misc WHERE what LIKE '%new_if_%';

UPDATE misc SET value='17.03.07' WHERE what="version";

## POBRISEMO VSE STRANI, KI IMAJO ank_id=0, ker se to itak ne sme pojavit (zaradi bugov jih je kar nekaj)
DELETE FROM srv_grupa WHERE ank_id='0' AND vrstni_red>'0' AND id>'1';

UPDATE misc SET value='17.03.11' WHERE what="version";

ALTER TABLE srv_vrednost ADD INDEX vrednost_if (if_id);

UPDATE misc SET value='17.04.03' WHERE what="version";

ALTER TABLE srv_parapodatki ADD what2 VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL AFTER what;

UPDATE misc SET value='17.04.10' WHERE what="version";

ALTER TABLE srv_anketa ADD cookie_continue TINYINT NOT NULL DEFAULT '1' AFTER return_finished;

UPDATE misc SET value='17.04.20' WHERE what="version";

INSERT INTO srv_help (help, what) VALUES ('Če je vklopljena omejitev, da uporabnik ne more nadaljevati z izpolnjevanjem ankete brez sprejetja piškotka, mora biti v anketi obvezno prikazan uvod!', 'srv_cookie_continue');

UPDATE misc SET value='17.04.30' WHERE what="version";

ALTER TABLE srv_user_setting ADD showLanguageShortcut ENUM('0','1') NOT NULL DEFAULT '0';
ALTER TABLE srv_user_setting ADD showSAicon ENUM('0','1') NOT NULL DEFAULT '0';

UPDATE misc SET value='17.05.11' WHERE what="version";

ALTER TABLE srv_evoli_teammeter ADD date_from date NOT NULL default '0000-00-00';
ALTER TABLE srv_evoli_teammeter ADD date_to date NOT NULL default '0000-00-00';

CREATE TABLE srv_evoli_teammeter_department (
	id INT(11) NOT NULL AUTO_INCREMENT,
	tm_id INT(11) NOT NULL DEFAULT '0',
	department VARCHAR(255) DEFAULT '',
	PRIMARY KEY (id),
	CONSTRAINT fk_srv_evoli_teammeter_department_tm_id FOREIGN KEY (tm_id) REFERENCES srv_evoli_teammeter (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='17.05.26' WHERE what="version";

CREATE TABLE srv_evoli_teammeter_data_department (
	department_id INT(11) NOT NULL DEFAULT '0',
	usr_id INT(11) NOT NULL DEFAULT '0',
	PRIMARY KEY (department_id, usr_id),
	CONSTRAINT fk_srv_evoli_data_department_department_id FOREIGN KEY (department_id) REFERENCES srv_evoli_teammeter_department (id) ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT fk_srv_evoli_data_department_usr_id FOREIGN KEY (usr_id) REFERENCES srv_user (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='17.05.30' WHERE what="version";

CREATE TABLE srv_custom_report_share (
	ank_id int(11) NOT NULL default 0,
	profile_id int(11) NOT NULL default 0,
	author_usr_id int(11) NOT NULL default 0,
	share_usr_id int(11) NOT NULL default 0,
	PRIMARY KEY (ank_id, profile_id, author_usr_id, share_usr_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='17.07.14' WHERE what="version";

# za sledenje uporabe api-ja - ZE UPDATANO NA www.1ka.si!
CREATE TABLE srv_tracking_api (
	ank_id INT NOT NULL, 
	datetime DATETIME NOT NULL,
	ip VARCHAR(16) NOT NULL,
	user INT(20) NOT NULL,
	action TEXT NOT NULL,
	kategorija TINYINT(1) NOT NULL DEFAULT '0'
);

ALTER TABLE srv_tracking_api ADD INDEX ( ank_id , datetime , user ) ;
ALTER TABLE srv_tracking_api ENGINE = InnoDB;

UPDATE misc SET value='17.08.18' WHERE what="version";

# dodajanje modula mobilna aplikacija za anketirance
INSERT INTO srv_module (module_name, active) VALUES ('maza', '0');
# aktiviraj modul maza samo za test.1ka.si in www.1ka.si
# UPDATE srv_module SET active='1' WHERE module_name = 'maza';

UPDATE misc SET value='17.10.13' WHERE what="version";

ALTER TABLE srv_evoli_teammeter ADD datum_posiljanja DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00';

UPDATE misc SET value='17.10.16' WHERE what="version";

CREATE TABLE srv_evoli_landingPage_access (
	ank_id INT(11) NOT NULL DEFAULT '0',
	email VARCHAR(100) NOT NULL DEFAULT '',
	pass VARCHAR(100) NOT NULL DEFAULT '',
	time_created datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	used ENUM('0','1') NOT NULL DEFAULT '0',
	PRIMARY KEY (ank_id, email, pass),
	CONSTRAINT fk_srv_evoli_landingPage_access_ank_id FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='17.11.07' WHERE what="version";

CREATE TABLE maza_app_users(
	id INT(11) NOT NULL AUTO_INCREMENT,
	identifier VARCHAR(16) NOT NULL,
	datetime_inserted DATETIME DEFAULT CURRENT_TIMESTAMP,
	datetime_last_active DATETIME,
	deviceInfo TEXT,
	PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE maza_srv_users(
	maza_user_id INT(11) NOT NULL,
	srv_user_id INT(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE maza_srv_users ADD CONSTRAINT fk_maza_app_users_maza_srv_users FOREIGN KEY (maza_user_id) REFERENCES maza_app_users (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE maza_srv_users ADD CONSTRAINT fk_srv_user_maza_srv_users FOREIGN KEY (srv_user_id) REFERENCES srv_user (id) ON DELETE CASCADE ON UPDATE CASCADE;

CREATE TABLE maza_user_srv_access(
	maza_user_id INT(11) NOT NULL,
	ank_id INT(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE maza_user_srv_access ADD CONSTRAINT fk_maza_app_users_maza_user_srv_access FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE maza_user_srv_access ADD CONSTRAINT fk_srv_anketa_maza_user_srv_access FOREIGN KEY (maza_user_id) REFERENCES maza_app_users (id) ON DELETE CASCADE ON UPDATE CASCADE;

UPDATE misc SET value='17.11.21' WHERE what="version";

INSERT INTO srv_module (module_name, active) VALUES ('excell_matrix', '0');
# aktiviraj modul samo za test.1ka.si in www.1ka.si
# UPDATE srv_module SET active='1' WHERE module_name = 'excell_matrix';

UPDATE misc SET value='17.12.13' WHERE what="version";

CREATE TABLE maza_srv_alarms(
	id INT(11) NOT NULL AUTO_INCREMENT,
	ank_id INT(11) NOT NULL DEFAULT '0',
	alarm_on TINYINT(1) default '0',
  	alarm_notif_title varchar(100) default '',
	alarm_notif_message varchar(100) default '',
	alarm_notif_repeat INT(6) default '1440',
	alarm_notif_sound TINYINT(1) default '0',
	PRIMARY KEY (id),
	CONSTRAINT fk_maza_srv_ank_id FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE maza_app_users ADD registration_id VARCHAR(255) AFTER identifier;
ALTER TABLE maza_app_users ADD tracking_log TEXT NOT NULL DEFAULT '' AFTER deviceInfo;

UPDATE misc SET value='18.1.2' WHERE what="version";

CREATE TABLE srv_data_heatmap (
  id int(11) NOT NULL auto_increment,
  usr_id int(11) NOT NULL,
  spr_id int(11) NOT NULL,
  ank_id int(11) NOT NULL,
  loop_id int(11) DEFAULT NULL,
  lat float(19,15) NOT NULL,
  lng float(19,15) NOT NULL,
  address varchar(255) character set utf8 collate utf8_bin default '',
  PRIMARY KEY (id),
  KEY usr_id (usr_id),
  KEY loop_id (loop_id),
  KEY ank_id (ank_id),
  KEY spr_id (spr_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE srv_data_heatmap ADD CONSTRAINT fk_srv_data_heatmap_ank_id FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE srv_data_heatmap ADD CONSTRAINT fk_srv_data_heatmap_usr_id FOREIGN KEY (usr_id) REFERENCES srv_user (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE srv_data_heatmap ADD CONSTRAINT fk_srv_data_heatmap_loop_id FOREIGN KEY (loop_id) REFERENCES srv_loop_data (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE srv_data_heatmap ADD text TEXT CHARACTER SET utf8 COLLATE utf8_bin DEFAULT ''; 
ALTER TABLE srv_data_heatmap ADD vrstni_red INT;
ALTER TABLE srv_data_heatmap ADD vre_id int(11) AFTER loop_id;
ALTER TABLE srv_data_heatmap ADD INDEX (vre_id);
ALTER TABLE srv_data_heatmap ADD CONSTRAINT fk_srv_data_heatmap_vre_id FOREIGN KEY (vre_id) REFERENCES srv_vrednost (id) ON DELETE CASCADE ON UPDATE CASCADE;
INSERT INTO srv_data_heatmap(id, usr_id, spr_id, ank_id, loop_id, vre_id, lat, lng, address, text, vrstni_red) SELECT id, usr_id, spr_id, ank_id, loop_id, vre_id, lat, lng, address, text, vrstni_red FROM srv_data_map;

UPDATE misc SET value='18.1.19' WHERE what="version";

CREATE TABLE srv_panel_settings (
	ank_id INT(11) NOT NULL,
	user_id_name VARCHAR(100) NOT NULL DEFAULT 'SID',
	status_name VARCHAR(100) NOT NULL DEFAULT 'status',
	status_default VARCHAR(20) NOT NULL DEFAULT '0',
	PRIMARY KEY (ank_id),
	CONSTRAINT fk_srv_panel_settings_ank_id FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='18.02.05' WHERE what="version";

CREATE TABLE srv_panel_if (
	id INT(11) NOT NULL AUTO_INCREMENT,
	ank_id INT(11) NOT NULL,
	if_id INT(11) NOT NULL,
	value VARCHAR(100) NOT NULL DEFAULT '',
	PRIMARY KEY (id),
	UNIQUE KEY (ank_id, if_id),
	CONSTRAINT fk_srv_panel_if_ank_id FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT fk_srv_panel_if_if_id FOREIGN KEY (if_id) REFERENCES srv_if (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE srv_panel_status (
	id INT(11) NOT NULL AUTO_INCREMENT,
	ank_id INT(11) NOT NULL,
	usr_id INT(11) NOT NULL DEFAULT '0',
	value VARCHAR(100) NOT NULL DEFAULT '',
	PRIMARY KEY (id),
	UNIQUE KEY (ank_id, usr_id),
	CONSTRAINT fk_srv_panel_data_ank_id FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT fk_srv_panel_data_usr_id FOREIGN KEY (usr_id) REFERENCES srv_user (id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO srv_help (what, lang, help) VALUES ('srv_reminder_tracking_quality', '1', 'Kakovostni indeks = 1 - ( &sum;(&#353;tevilo spro&#382;enih opozoril/&#353;tevilo mo&#382;nih opozoril po vrsti opozorila) / &#353;tevilo respondentov ) </br> Haraldsen, G. (2005). Using Client Side Paradata as Process Quality Indicators in Web Surveys. Predstavljeno na delavnici ESF Workshop on Internet survey methodology, Dubrovnik, 26-28 September 2005.'), ('srv_reminder_tracking_quality', '2', 'Quality index = 1 - ( &sum;(Activated errors/Possible errors) / Number of respondents ) </br> Haraldsen, G. (2005). Using Client Side Paradata as Process Quality Indicators in Web Surveys. Presented at the ESF Workshop on Internet survey methodology, Dubrovnik, 26-28 September 2005.');

UPDATE misc SET value='18.02.07' WHERE what="version";

DROP TABLE srv_panel_status;

UPDATE misc SET value='18.02.08' WHERE what="version";

## ime in priimek v tabelah users, users_to_be in fb_users pretvorimo v utf8 zaradi tezav s čšž-ji
ALTER TABLE users_to_be MODIFY name VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT 'Nepodpisani';
ALTER TABLE users_to_be MODIFY surname VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';

ALTER TABLE users MODIFY name VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT 'Nepodpisani';
ALTER TABLE users MODIFY surname VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';

ALTER TABLE fb_users MODIFY first_name VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin;
ALTER TABLE fb_users MODIFY last_name VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin;

UPDATE misc SET value='18.02.15' WHERE what="version";

CREATE TABLE srv_quiz_settings (
	ank_id INT(11) NOT NULL,
	results ENUM('0','1') NOT NULL DEFAULT '1',
	results_chart ENUM('0','1') NOT NULL DEFAULT '0',
	PRIMARY KEY (ank_id),
	CONSTRAINT fk_srv_quiz_settings_ank_id FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='18.02.20' WHERE what="version";

# Drupal verzija je bila posodobljena in vpišemo na 1ka.si in te
INSERT INTO misc (value, what) VALUES ("7.57", "drupal version");

INSERT INTO misc (value, what) VALUES ("18.02.28", "version");

# dodajanje modula GORENJE
INSERT INTO srv_module (module_name, active) VALUES ('gorenje', '0');

DELETE FROM misc WHERE what='version';
INSERT INTO misc (value, what) VALUES ("18.03.09", "version");

## Brisanje neuporabnih polj iz tabele users in users_to_be
ALTER TABLE users DROP COLUMN pay_bid;
ALTER TABLE users DROP COLUMN pay_n;
ALTER TABLE users DROP COLUMN remember_default;
ALTER TABLE users DROP COLUMN avatar;
ALTER TABLE users DROP COLUMN ehistory;
ALTER TABLE users DROP COLUMN alert_freq;
ALTER TABLE users DROP COLUMN infomail;
ALTER TABLE users_to_be DROP COLUMN pay_bid;
ALTER TABLE users_to_be DROP COLUMN pay_n;
ALTER TABLE users_to_be DROP COLUMN avatar;
ALTER TABLE users_to_be DROP COLUMN ehistory;
ALTER TABLE users_to_be DROP COLUMN alert_freq;
ALTER TABLE users_to_be DROP COLUMN infomail;

UPDATE misc SET value='18.03.13' WHERE what="version";

# dodajanje modula za borzo - aktivirana samo na www.1ka.si in na testu
INSERT INTO srv_module (module_name, active) VALUES ('borza', '0');
##UPDATE srv_module SET active='1' WHERE module_name='borza';

#modul MAZA
ALTER TABLE maza_srv_alarms CHANGE alarm_notif_repeat repeat_by VARCHAR(30) DEFAULT 'everyday';
ALTER TABLE maza_srv_alarms ADD time_in_day VARCHAR(255);
ALTER TABLE maza_srv_alarms ADD day_in_week VARCHAR(255);
ALTER TABLE maza_srv_alarms ADD every_which_day INT(3);

CREATE TABLE maza_srv_repeaters(
	id INT(11) NOT NULL AUTO_INCREMENT,
	ank_id INT(11) NOT NULL DEFAULT '0',
	repeater_on TINYINT(1) DEFAULT '0',
	repeat_by VARCHAR(30) DEFAULT 'everyday',
	time_in_day VARCHAR(255),
	day_in_week VARCHAR(255),
	every_which_day INT(3),
	datetime_start DATETIME,
	datetime_end DATETIME,
	PRIMARY KEY (id),
	CONSTRAINT fk_maza_repeater_srv_ank_id FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE maza_user_srv_access ADD datetime_started DATETIME;
ALTER TABLE maza_srv_users ADD srv_version_datetime DATETIME;

UPDATE misc SET value='18.03.14' WHERE what="version";


# Tabela se ne potrebuje,ker bodo skupine kamor je uporabnik prijavljen posebej določene
ALTER TABLE users DROP COLUMN user_groups;

UPDATE misc SET value='18.03.15' WHERE what="version";

# Uporabniki se omogoče dodatne opcije in več emailov lahko dodaja
CREATE TABLE user_emails(
  id INT(11) NOT NULL AUTO_INCREMENT,
  user_id INT(11) NOT NULL,
  email VARCHAR(255) NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT '0',
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_user_emails_users_id FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE user_options(
  id INT(11) NOT NULL AUTO_INCREMENT,
  user_id INT(11) NOT NULL,
  option_name VARCHAR(255) NOT NULL,
  option_value VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_user_options_users_id FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO user_options (user_id, option_name, option_value, created_at) SELECT id, 'email_unsigned', '1', NOW() FROM users WHERE status='5';
UPDATE users SET status='1' WHERE status=5;

UPDATE misc SET value='18.03.15' WHERE what="version";

## Vsem uporabnikom ugasnemo star vmesnik za moje ankete
UPDATE srv_user_setting SET advancedMySurveys='0' WHERE advancedMySurveys='1';

UPDATE misc SET value='18.03.21' WHERE what="version";

# Pri posiljanju vabil lahko nastavimo tudi kdaj vabilo potece
ALTER TABLE srv_invitations_recipients ADD COLUMN date_expired DATETIME NOT NULL AFTER date_sent;

UPDATE misc SET value='18.03.28' WHERE what="version";

## Tabelo user_to_be uporabimo za vpis alternativnega emaiala, preden uporabnik dobi potrditev na email
ALTER TABLE users_to_be ADD user_id INT NULL AFTER hour;
ALTER TABLE users_to_be ADD CONSTRAINT fk_user_to_be_user_id FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE;

# Posodobljena drupal verzija na 7.58
UPDATE misc SET value='7.58' WHERE what="drupal version";

UPDATE misc SET value='18.03.29' WHERE what="version";

CREATE TABLE srv_gdpr_anketa(
  ank_id INT(11) NOT NULL,
  name ENUM('0','1') NOT NULL DEFAULT '0',
  email ENUM('0','1') NOT NULL DEFAULT '0',
  location ENUM('0','1') NOT NULL DEFAULT '0',
  invitation ENUM('0','1') NOT NULL DEFAULT '0',
  phone ENUM('0','1') NOT NULL DEFAULT '0',
  PRIMARY KEY (ank_id),
  CONSTRAINT fk_srv_gdpr_anketa_ank_id FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='18.04.03' WHERE what="version";

CREATE TABLE srv_gdpr_requests(
  id INT(11) NOT NULL AUTO_INCREMENT,
  usr_id INT(11) NOT NULL,
  ank_id INT(11) NOT NULL,
  url varchar(200) NOT NULL default '',
  text TEXT NOT NULL default '',
  PRIMARY KEY (id),
  CONSTRAINT fk_srv_gdpr_requests_usr_id FOREIGN KEY (usr_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_srv_gdpr_requests_ank_id FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='18.04.06' WHERE what="version";

ALTER TABLE srv_gdpr_requests ADD COLUMN ip VARCHAR(100) NOT NULL DEFAULT '' AFTER url;
ALTER TABLE srv_gdpr_requests ADD COLUMN recnum VARCHAR(50) NOT NULL DEFAULT '' AFTER ip;
ALTER TABLE srv_gdpr_requests ADD COLUMN datum DATETIME NOT NULL AFTER url;
ALTER TABLE srv_gdpr_requests ADD COLUMN status ENUM('0','1') NOT NULL DEFAULT '0';

CREATE TABLE srv_gdpr_user(
  usr_id INT(11) NOT NULL,
  organization varchar(255) NOT NULL DEFAULT '',
  firstname varchar(50) NOT NULL DEFAULT '',
  lastname varchar(50) NOT NULL DEFAULT '',
  email varchar(50) NOT NULL DEFAULT '',
  phone varchar(255) NOT NULL DEFAULT '',
  address varchar(255) NOT NULL DEFAULT '',
  country varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (usr_id),
  CONSTRAINT fk_srv_gdpr_user_usr_id FOREIGN KEY (usr_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='18.04.09' WHERE what="version";

ALTER TABLE srv_gdpr_requests ADD COLUMN type TINYINT(1) NOT NULL DEFAULT 0;

UPDATE misc SET value='18.04.22' WHERE what="version";

ALTER TABLE srv_gdpr_user ADD COLUMN type ENUM('0','1') NOT NULL DEFAULT '0' AFTER usr_id;
ALTER TABLE srv_gdpr_user ADD COLUMN dpo_firstname varchar(255) NOT NULL DEFAULT '' AFTER organization;
ALTER TABLE srv_gdpr_user ADD COLUMN dpo_lastname varchar(255) NOT NULL DEFAULT '' AFTER organization;
ALTER TABLE srv_gdpr_user ADD COLUMN dpo_email varchar(255) NOT NULL DEFAULT '' AFTER organization;
ALTER TABLE srv_gdpr_user ADD COLUMN dpo_phone varchar(255) NOT NULL DEFAULT '' AFTER organization;

ALTER TABLE srv_gdpr_anketa ADD COLUMN 1ka_template ENUM('0','1') NOT NULL DEFAULT '1' AFTER ank_id;

UPDATE misc SET value='18.04.23' WHERE what="version";

ALTER TABLE srv_gdpr_anketa MODIFY COLUMN 1ka_template ENUM('0','1') NOT NULL DEFAULT '0' AFTER ank_id;

UPDATE misc SET value='18.04.25' WHERE what="version";

ALTER TABLE srv_gdpr_anketa DROP COLUMN invitation;
ALTER TABLE srv_gdpr_anketa ADD COLUMN web ENUM('0','1') NOT NULL DEFAULT '0';
ALTER TABLE srv_gdpr_anketa ADD COLUMN other ENUM('0','1') NOT NULL DEFAULT '0';

#za modul MAZA
CREATE TABLE maza_srv_geofences (
  id int(11) NOT NULL auto_increment,
  ank_id int(11) NOT NULL,
  geofence_on TINYINT(1) default '0',
  lat float(19,15) NOT NULL,
  lng float(19,15) NOT NULL,
  radius float(21,13) NOT NULL,
  address varchar(255) character set utf8 collate utf8_bin default '',
  notif_title varchar(100) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT '',
  notif_message varchar(100) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT '',
  notif_sound TINYINT(1) default '0',
  on_transition varchar(30) DEFAULT 'dwell',
  after_seconds int DEFAULT '300',
  PRIMARY KEY (id),
  KEY ank_id (ank_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='18.04.26' WHERE what="version";

# Posodobljena drupal verzija na 7.59
UPDATE misc SET value='7.59' WHERE what="drupal version";

ALTER TABLE srv_gdpr_requests ADD COLUMN comment TEXT NOT NULL default '';

ALTER TABLE srv_gdpr_anketa ADD COLUMN about TEXT NOT NULL default '';

UPDATE misc SET value='18.05.14' WHERE what="version";

# modul MAZA
CREATE TABLE maza_srv_triggered_geofences (
  id int(11) NOT NULL auto_increment,
  geof_id int(11) NOT NULL,
  maza_user_id int(11) NOT NULL,
  triggered_timestamp DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY geof_id (geof_id),
  KEY maza_user_id (maza_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE maza_srv_triggered_geofences ADD CONSTRAINT fk_maza_srv_triggered_geofences_geof_id FOREIGN KEY (geof_id) REFERENCES maza_srv_geofences (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE maza_srv_triggered_geofences ADD CONSTRAINT fk_maza_srv_triggered_geofences_maza_user_id FOREIGN KEY (maza_user_id) REFERENCES maza_app_users (id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE maza_srv_users ADD COLUMN geof_id int(11);
ALTER TABLE maza_srv_users ADD CONSTRAINT fk_maza_srv_users_geof_id FOREIGN KEY (geof_id) REFERENCES maza_srv_geofences (id) ON DELETE CASCADE ON UPDATE CASCADE;

UPDATE misc SET value='18.05.15' WHERE what="version";

## Kar za vse disablamo to nastavitev - adminom jo bomo pustili preklopiti nazaj
UPDATE srv_user_setting SET autoActiveSurvey='0';

UPDATE misc SET value='18.05.17' WHERE what="version";

ALTER TABLE srv_gdpr_requests ADD COLUMN email VARCHAR(100) NOT NULL DEFAULT '' AFTER url;

# GRDP obveščanje
ALTER TABLE users_to_be ADD COLUMN gdrp_agree tinyint(1) NOT NULL DEFAULT -1 AFTER approved;
ALTER TABLE users ADD COLUMN gdrp_agree tinyint(1) NOT NULL DEFAULT -1 AFTER approved;

UPDATE misc SET value='18.05.18' WHERE what="version";

## Ostanek sispleta - lahko pobrisemo
DROP TABLE view_log;

UPDATE misc SET value='18.05.21' WHERE what="version";

ALTER TABLE srv_analysis_archive ADD COLUMN access_password VARCHAR(30) default NULL AFTER access;

UPDATE misc SET value='18.05.24' WHERE what="version";

ALTER TABLE srv_hash_url ADD COLUMN refresh int(2) NOT NULL default '0' AFTER comment;
ALTER TABLE srv_hash_url ADD COLUMN access_password VARCHAR(30) default NULL AFTER comment;

UPDATE misc SET value='18.05.28' WHERE what="version";

ALTER TABLE users CHANGE gdrp_agree gdpr_agree tinyint(1) NOT NULL DEFAULT -1;

UPDATE misc SET value='18.05.31' WHERE what="version";

UPDATE users AS u RIGHT JOIN user_options AS o ON u.id=o.user_id SET u.gdpr_agree=0 WHERE o.option_name="email_unsigned" AND o.option_value=1 AND u.gdpr_agree='-1';
DELETE FROM user_options WHERE option_name="email_unsigned" AND option_value=1;

UPDATE misc SET value='18.05.31' WHERE what="version";

ALTER TABLE users_to_be CHANGE gdrp_agree gdpr_agree tinyint(1) NOT NULL DEFAULT -1;

UPDATE misc SET value='18.06.19' WHERE what="version";

## Brisanje neuporabnih polj iz tabele users in users_to_be
ALTER TABLE users DROP COLUMN hour;
ALTER TABLE users DROP COLUMN phone1;
ALTER TABLE users DROP COLUMN phone2;
ALTER TABLE users DROP COLUMN alert_cats;
ALTER TABLE users DROP COLUMN my_url;
ALTER TABLE users DROP COLUMN about_me1;
ALTER TABLE users DROP COLUMN about_me2;
ALTER TABLE users DROP COLUMN rubrike;
ALTER TABLE users DROP COLUMN autologin;
ALTER TABLE users DROP COLUMN editing_mode;
ALTER TABLE users_to_be DROP COLUMN hour;
ALTER TABLE users_to_be DROP COLUMN phone1;
ALTER TABLE users_to_be DROP COLUMN phone2;
ALTER TABLE users_to_be DROP COLUMN alert_cats;
ALTER TABLE users_to_be DROP COLUMN my_url;
ALTER TABLE users_to_be DROP COLUMN about_me1;
ALTER TABLE users_to_be DROP COLUMN about_me2;
ALTER TABLE users_to_be DROP COLUMN rubrike;
ALTER TABLE users_to_be DROP COLUMN autologin;

UPDATE misc SET value='18.07.03' WHERE what="version";

## Omenjen popravek samo za www.1ka.si in virtualke
##UPDATE misc SET value='1KA' WHERE what="PageName";

UPDATE misc SET value='18.07.05' WHERE what="version";

## Tabela s predlogami anket iz knjhiznice
CREATE TABLE srv_anketa_template (
  id int(11) NOT NULL auto_increment,
  kategorija TINYINT(1) default '0',
  ank_id_slo int(11) NOT NULL,
  naslov_slo varchar(255) character set utf8 collate utf8_bin DEFAULT '',
  ank_id_eng int(11) NOT NULL,
  naslov_eng varchar(255) character set utf8 collate utf8_bin DEFAULT '',
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='18.07.09' WHERE what="version";

ALTER TABLE srv_anketa_template ADD COLUMN desc_slo TEXT NOT NULL DEFAULT '' AFTER naslov_slo;
ALTER TABLE srv_anketa_template ADD COLUMN desc_eng TEXT NOT NULL DEFAULT '' AFTER naslov_eng;

UPDATE misc SET value='18.09.07' WHERE what="version";

#za modul MAZA
CREATE TABLE maza_srv_activity (
  id int(11) NOT NULL auto_increment,
  ank_id int(11) NOT NULL,
  activity_on TINYINT(1) default '0',
  notif_title varchar(100) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT '',
  notif_message varchar(100) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT '',
  notif_sound TINYINT(1) default '0',
  activity_type varchar(30) DEFAULT 'path',
  after_seconds int DEFAULT '300',
  PRIMARY KEY (id),
  CONSTRAINT fk_maza_activity_srv_ank_id FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE maza_srv_users ADD COLUMN activity_id int(11);
ALTER TABLE maza_srv_users ADD CONSTRAINT fk_maza_srv_users_activity_id FOREIGN KEY (activity_id) REFERENCES maza_srv_activity (id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE maza_srv_geofences ADD CONSTRAINT fk_maza_geofences_srv_ank_id FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE;

UPDATE misc SET value='18.09.17' WHERE what="version";

## Drupal posodobitev jedra in modulov
UPDATE misc SET value='7.60' WHERE what="drupal version";
UPDATE misc SET value='18.10.19' WHERE what="version";

# Popravljen default skin
ALTER TABLE srv_anketa CHANGE skin skin VARCHAR( 100 ) NOT NULL DEFAULT '1kaBlue';
ALTER TABLE srv_anketa CHANGE mobile_skin mobile_skin VARCHAR( 100 ) NOT NULL DEFAULT 'MobileBlue';

# Popravimo se skin vseh anket s privzetim skinom - tega ne bomo delali
## UPDATE srv_anketa SET skin='1kaBlue' WHERE skin='1kaGrey';
## UPDATE srv_anketa SET mobile_skin='MobileBlue' WHERE mobile_skin='Mobile';

UPDATE misc SET value='18.10.23' WHERE what="version";

INSERT INTO srv_help VALUES('srv_create_survey_from_text', 1, 'Uvoz nove ankete iz besedila');

UPDATE misc SET value='18.10.24' WHERE what="version";

INSERT INTO srv_help VALUES('srv_gdpr_user_options', 1, 'Enkrat leto boste prejeli obvestilo o DSA dogodku.');

UPDATE misc SET value='18.10.24' WHERE what="version";

UPDATE misc SET value='18.10.27' WHERE what="version";


CREATE TABLE srv_language_slider (
ank_id INT NOT NULL,
spr_id INT NOT NULL ,
label_id INT NOT NULL ,
lang_id INT NOT NULL,
label TEXT NOT NULL ,
PRIMARY KEY ( spr_id, label_id, lang_id ),
CONSTRAINT fk_srv_language_slider_spr_id FOREIGN KEY (spr_id) REFERENCES srv_spremenljivka (id) ON DELETE CASCADE ON UPDATE CASCADE,
CONSTRAINT fk_srv_language_slider_ank_id_lang_id FOREIGN KEY (ank_id, lang_id) REFERENCES srv_language (ank_id, lang_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

UPDATE misc SET value='18.11.07' WHERE what="version";

ALTER TABLE maza_user_srv_access ADD COLUMN tracking_permitted TINYINT(1) DEFAULT NULL;

UPDATE misc SET value='18.11.14' WHERE what="version";

#za modul MAZA
CREATE TABLE maza_srv_triggered_activities (
  id int(11) NOT NULL auto_increment,
  act_id int(11) NOT NULL,
  maza_user_id int(11) NOT NULL,
  triggered_timestamp DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY act_id (act_id),
  KEY maza_user_id (maza_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE maza_srv_triggered_activities ADD CONSTRAINT fk_maza_srv_triggered_activities_act_id FOREIGN KEY (act_id) REFERENCES maza_srv_activity (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE maza_srv_triggered_activities ADD CONSTRAINT fk_maza_srv_triggered_activities_maza_user_id FOREIGN KEY (maza_user_id) REFERENCES maza_app_users (id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE maza_app_users ADD COLUMN nextpin_password varchar(32) DEFAULT NULL;

UPDATE misc SET value='18.11.15' WHERE what="version";

INSERT INTO srv_misc (what, value) VALUES ('timing_kategorija_max_3', '20');

UPDATE misc SET value='18.11.29' WHERE what="version";

# dodajanje modula za napredne podatke
INSERT INTO srv_module (module_name, active) VALUES ('advanced_paradata', '0');
# aktiviraj modul maza samo za test.1ka.si in www.1ka.si
# UPDATE srv_module SET active='1' WHERE module_name = 'advanced_paradata';

UPDATE misc SET value='18.12.03' WHERE what="version";

# Belezi parapodatke na nivoju posameznega prikaza strani
CREATE TABLE srv_advanced_paradata_page (
	id INT(11) NOT NULL AUTO_INCREMENT,
	ank_id INT(11) NOT NULL,
	usr_id INT(11) NOT NULL,
	load_time DATETIME(3) NOT NULL,
	post_time DATETIME(3) NOT NULL,
	user_agent VARCHAR(250) NOT NULL DEFAULT '',
	resolution_w INT(11) NOT NULL DEFAULT '0',
	resolution_h INT(11) NOT NULL DEFAULT '0',
	window_w INT(11) NOT NULL DEFAULT '0',
	window_h INT(11) NOT NULL DEFAULT '0',
	language INT(11) NOT NULL DEFAULT '1',
	PRIMARY KEY (id),
	CONSTRAINT fk_srv_advanced_paradata_page_ank_id FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT fk_srv_advanced_paradata_page_usr_id FOREIGN KEY (usr_id) REFERENCES srv_user (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8;

# Belezi parapodatke na nivoju vprasanj
CREATE TABLE srv_advanced_paradata_question (
	page_id INT(11) NOT NULL,
	spr_id INT(11) NOT NULL,
	vre_order VARCHAR(250) NOT NULL DEFAULT '',
	PRIMARY KEY (page_id, spr_id),
	CONSTRAINT fk_srv_advanced_paradata_question_page_id FOREIGN KEY (page_id) REFERENCES srv_advanced_paradata_page (id) ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT fk_srv_advanced_paradata_question_spr_id FOREIGN KEY (spr_id) REFERENCES srv_spremenljivka (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8;

# Belezi parapodatke na nivoju vrednosti znotraj vprasanj
CREATE TABLE srv_advanced_paradata_vrednost (
	page_id INT(11) NOT NULL,
	spr_id INT(11) NOT NULL,
	vre_id INT(11) NOT NULL,
	time DATETIME(3) NOT NULL,
	event VARCHAR(50) NOT NULL DEFAULT '',
	value VARCHAR(50) NOT NULL DEFAULT '',
	CONSTRAINT fk_srv_advanced_paradata_vrednost_page_id FOREIGN KEY (page_id) REFERENCES srv_advanced_paradata_page (id) ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT fk_srv_advanced_paradata_vrednost_spr_id FOREIGN KEY (spr_id) REFERENCES srv_spremenljivka (id) ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT fk_srv_advanced_paradata_vrednost_vre_id FOREIGN KEY (vre_id) REFERENCES srv_vrednost (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8;

# Belezi parapodatke ostalih dogodkov na nivoju strani (scroll, focus, click...)
CREATE TABLE srv_advanced_paradata_other (
	id INT(11) NOT NULL auto_increment,
	page_id INT(11) NOT NULL,
	time DATETIME(3) NOT NULL,
	event VARCHAR(50) NOT NULL DEFAULT '',
	value VARCHAR(50) NOT NULL DEFAULT '',
	pos_x INT(11) NOT NULL DEFAULT '0',
	pos_y INT(11) NOT NULL DEFAULT '0',
	PRIMARY KEY (id),
	CONSTRAINT fk_srv_advanced_paradata_other_page_id FOREIGN KEY (page_id) REFERENCES srv_advanced_paradata_page (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8;

UPDATE misc SET value='18.12.12' WHERE what="version";

ALTER TABLE srv_advanced_paradata_page ADD COLUMN gru_id TINYINT(11) NOT NULL AFTER ank_id;
ALTER TABLE srv_advanced_paradata_page ADD COLUMN recnum TINYINT(11) NOT NULL AFTER usr_id;
ALTER TABLE srv_advanced_paradata_page DROP FOREIGN KEY fk_srv_advanced_paradata_page_usr_id;
ALTER TABLE srv_advanced_paradata_vrednost ADD COLUMN id INT(11) AUTO_INCREMENT PRIMARY KEY FIRST;

UPDATE misc SET value='18.12.13' WHERE what="version";

INSERT INTO srv_help VALUES('srv_grid_var', 1, 'Vrednosti odgovorov so privzeto razvr&#353;&#269;ene nara&#353;&#269;ajo&#269;e in se pri&#269;nejo z 1. Vrednosti se lahko razvrstijo tudi padajo&#269;e (s klikom na checkbox Razvrsti vrednosti padajo&#269;e).<br /><br />Vrednosti odgovorov se lahko spremenijo. Pri tem velja upo&#353;tevati naslednja pravila:<ul><li>Vrednosti se ne smejo ponavljati (razen v primeru vklopljenega modula Kviz).</li><li>Uporabljajo se lahko samo cela &#353;tevila (brez decimalnih &#353;tevil).</li><li>Vrednosti -1, -2, -3, -4, -5, -6, -96, -97, -98, -99 so rezervirane za ozna&#269;evanje manjkajo&#269;ih vrednosti in se jih ne sme uporabljati za vrednotenje drugih odgovorov.</li></ul>');

UPDATE misc SET value='18.12.16' WHERE what="version";

ALTER TABLE srv_advanced_paradata_vrednost DROP FOREIGN KEY fk_srv_advanced_paradata_vrednost_vre_id;
ALTER TABLE srv_advanced_paradata_page MODIFY COLUMN gru_id INT(11) NOT NULL AFTER ank_id;
ALTER TABLE srv_advanced_paradata_page MODIFY COLUMN recnum INT(11) NOT NULL AFTER usr_id;

UPDATE misc SET value='18.12.18' WHERE what="version";

ALTER TABLE srv_advanced_paradata_page MODIFY COLUMN language VARCHAR(50) NOT NULL DEFAULT '';

UPDATE misc SET value='18.12.24' WHERE what="version";

ALTER TABLE srv_advanced_paradata_other ADD COLUMN div_id VARCHAR(100) NOT NULL DEFAULT '';
ALTER TABLE srv_advanced_paradata_other ADD COLUMN div_class VARCHAR(100) NOT NULL DEFAULT '';
ALTER TABLE srv_advanced_paradata_other ADD COLUMN div_type VARCHAR(100) NOT NULL DEFAULT '';

# Belezi parapodatke alertov
CREATE TABLE srv_advanced_paradata_alert (
	id INT(11) NOT NULL auto_increment,
	page_id INT(11) NOT NULL,
	time DATETIME(3) NOT NULL,
	type VARCHAR(50) NOT NULL DEFAULT '',
	trigger_id INT(11) NOT NULL,
	trigger_type VARCHAR(50) NOT NULL DEFAULT '',
	ignorable ENUM('0','1') NOT NULL DEFAULT '0',
	text VARCHAR(200) NOT NULL DEFAULT '',
	action VARCHAR(50) NOT NULL DEFAULT '',
	PRIMARY KEY (id),
	CONSTRAINT fk_srv_advanced_paradata_alert_page_id FOREIGN KEY (page_id) REFERENCES srv_advanced_paradata_page (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8;

UPDATE misc SET value='18.12.25' WHERE what="version";

## Drupal posodobitev jedra na 7.61 in modulov
UPDATE misc SET value='7.61' WHERE what="drupal version";
UPDATE misc SET value='18.12.26' WHERE what="version";

## Delayed posiljanje vabil za evoli tm
CREATE TABLE srv_evoli_teammeter_delayed (
	id INT(11) NOT NULL AUTO_INCREMENT,
	ank_id INT(11) NOT NULL,
	date_from date NOT NULL DEFAULT '0000-00-00',
	tm_group TEXT NOT NULL DEFAULT '',
	emails TEXT NOT NULL DEFAULT '',
	PRIMARY KEY (id),
	CONSTRAINT fk_srv_evoli_teammeter_delayed_ank_id FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='18.12.27' WHERE what="version";

ALTER TABLE srv_advanced_paradata_vrednost DROP INDEX fk_srv_advanced_paradata_vrednost_vre_id;
ALTER TABLE srv_advanced_paradata_vrednost DROP FOREIGN KEY fk_srv_advanced_paradata_vrednost_spr_id;
ALTER TABLE srv_advanced_paradata_vrednost DROP INDEX fk_srv_advanced_paradata_vrednost_spr_id;

UPDATE misc SET value='19.01.09' WHERE what="version";

#za modul MAZA
CREATE TABLE maza_srv_tracking (
  id int(11) NOT NULL auto_increment,
  ank_id int(11) NOT NULL,
  tracking_on TINYINT(1) default '0',
  activity_recognition TINYINT(1) default '0',
  tracking_accuracy varchar(30) DEFAULT 'high',
  interval_wanted int DEFAULT '30',
  interval_fastes int DEFAULT '10',
  displacement_min int DEFAULT '10',
  PRIMARY KEY (id),
  CONSTRAINT fk_maza_tracking_srv_ank_id FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE maza_user_srv_access CHANGE tracking_permitted nextpin_tracking_permitted tinyint(1) default '0';
ALTER TABLE maza_user_srv_access ADD tracking_permitted tinyint(1) DEFAULT NULL;

CREATE TABLE maza_user_locations (
  id int(11) NOT NULL auto_increment,
  maza_user_id int(11) NOT NULL,
  lat float(19,15) NOT NULL,
  lng float(19,15) NOT NULL,
  provider varchar(30),
  timestamp datetime NOT NULL,
  accuracy float(10,4),
  altitude float(10,4),
  bearing float(7,4),
  speed float(10,4),
  vertical_acc float(10,4),
  bearing_acc float(7,4),
  speed_acc float(10,4),
  extras varchar(255),
  is_mock TINYINT(1) default '0',
  PRIMARY KEY (id),
  CONSTRAINT fk_maza_user_locations_user_id FOREIGN KEY (maza_user_id) REFERENCES maza_app_users (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='19.01.10' WHERE what="version";

#za modul MAZA
ALTER TABLE maza_srv_tracking ADD COLUMN ar_interval_wanted int DEFAULT '30';

CREATE TABLE maza_user_activity_recognition (
  id int(11) NOT NULL auto_increment,
  maza_user_id int(11) NOT NULL,
  timestamp datetime NOT NULL,
  in_vehicle INT(3) DEFAULT '0', 
  on_bicycle INT(3) DEFAULT '0', 
  on_foot INT(3) DEFAULT '0', 
  still INT(3) DEFAULT '0', 
  unknown INT(3) DEFAULT '0', 
  tilting INT(3) DEFAULT '0', 
  running INT(3) DEFAULT '0', 
  walking INT(3) DEFAULT '0',
  PRIMARY KEY (id),
  CONSTRAINT fk_maza_user_activity_recognition_user_id FOREIGN KEY (maza_user_id) REFERENCES maza_app_users (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='19.01.17' WHERE what="version";

ALTER TABLE srv_advanced_paradata_page DROP COLUMN resolution_w;
ALTER TABLE srv_advanced_paradata_page DROP COLUMN resolution_h;
ALTER TABLE srv_advanced_paradata_page DROP COLUMN window_w;
ALTER TABLE srv_advanced_paradata_page DROP COLUMN window_h;
 
ALTER TABLE srv_advanced_paradata_page ADD COLUMN devicePixelRatio DECIMAL(4,2) NOT NULL DEFAULT '0';
ALTER TABLE srv_advanced_paradata_page ADD COLUMN width INT(11) NOT NULL DEFAULT '0';
ALTER TABLE srv_advanced_paradata_page ADD COLUMN height INT(11) NOT NULL DEFAULT '0';
ALTER TABLE srv_advanced_paradata_page ADD COLUMN availWidth INT(11) NOT NULL DEFAULT '0';
ALTER TABLE srv_advanced_paradata_page ADD COLUMN availHeight INT(11) NOT NULL DEFAULT '0';
ALTER TABLE srv_advanced_paradata_page ADD COLUMN jquery_windowW INT(11) NOT NULL DEFAULT '0';
ALTER TABLE srv_advanced_paradata_page ADD COLUMN jquery_windowH INT(11) NOT NULL DEFAULT '0';
ALTER TABLE srv_advanced_paradata_page ADD COLUMN jquery_documentW INT(11) NOT NULL DEFAULT '0';
ALTER TABLE srv_advanced_paradata_page ADD COLUMN jquery_documentH INT(11) NOT NULL DEFAULT '0';

ALTER TABLE srv_advanced_paradata_alert ADD COLUMN time_display DATETIME(3) NOT NULL AFTER page_id;
ALTER TABLE srv_advanced_paradata_alert DROP COLUMN time;
ALTER TABLE srv_advanced_paradata_alert ADD COLUMN time_close DATETIME(3) NOT NULL AFTER time_display;

# Posodobitev drupal jedra
UPDATE misc SET value='7.63' WHERE what="drupal version";

UPDATE misc SET value='19.01.18' WHERE what="version";

# Shranjevanje randomizirane vsebine blokov (random bloki oz. vprasanja)
CREATE TABLE srv_data_random_blockContent (
  id INT(11) NOT NULL auto_increment,
  usr_id INT(11) NOT NULL,
  block_id INT(11) NOT NULL,
  vrstni_red VARCHAR(255) DEFAULT '',
  PRIMARY KEY (id),
  UNIQUE KEY (usr_id, block_id),
  CONSTRAINT fk_srv_data_random_blockContent_usr_id FOREIGN KEY (usr_id) REFERENCES srv_user (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_srv_data_random_blockContent_block_id FOREIGN KEY (block_id) REFERENCES srv_if (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# Shranjevanje randomizirane vsebine vprasanj (vrednosti)
CREATE TABLE srv_data_random_spremenljivkaContent (
  id INT(11) NOT NULL auto_increment,
  usr_id INT(11) NOT NULL,
  spr_id INT(11) NOT NULL,
  vrstni_red VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (id),
  UNIQUE KEY (usr_id, spr_id),
  CONSTRAINT fk_srv_data_random_spremenljivkaContent_usr_id FOREIGN KEY (usr_id) REFERENCES srv_user (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_srv_data_random_spremenljivkaContent_spr_id FOREIGN KEY (spr_id) REFERENCES srv_spremenljivka (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='19.02.24' WHERE what="version";

#za modul MAZA
ALTER TABLE maza_srv_geofences ADD COLUMN name varchar(100) default NULL after address;
ALTER TABLE maza_srv_geofences ADD COLUMN location_triggered TINYINT(1) default '0';
ALTER TABLE maza_user_locations ADD COLUMN tgeof_id int(11) default NULL;
ALTER TABLE maza_user_locations ADD CONSTRAINT fk_maza_user_locations_tgeof_id FOREIGN KEY (tgeof_id) REFERENCES maza_srv_triggered_geofences (id) ON DELETE CASCADE ON UPDATE CASCADE;

UPDATE misc SET value='19.03.04' WHERE what="version";

INSERT INTO srv_help (help, what) VALUES ('Randomizacija vsebine bloka', 'srv_block_random');
INSERT INTO srv_help (help, what) VALUES ('Vpra&#154;anje je onemogo&#269;eno za respondente', 'srv_disabled_question');

UPDATE misc SET value='19.03.15' WHERE what="version";


UPDATE misc SET value='7.65' WHERE what="drupal version";
UPDATE misc SET value='19.03.21' WHERE what="version";

#modul MAZA
ALTER TABLE maza_srv_users ADD COLUMN loc_id int(11);
ALTER TABLE maza_srv_users ADD CONSTRAINT fk_maza_srv_users_loc_id FOREIGN KEY (loc_id) REFERENCES maza_user_locations (id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE maza_srv_users DROP FOREIGN KEY fk_maza_srv_users_activity_id;
ALTER TABLE maza_srv_users DROP INDEX fk_maza_srv_users_activity_id;
ALTER TABLE maza_srv_users DROP activity_id;
ALTER TABLE maza_srv_users ADD COLUMN tact_id int(11);
ALTER TABLE maza_srv_users ADD CONSTRAINT fk_maza_srv_users_tact_id FOREIGN KEY (tact_id) REFERENCES maza_srv_triggered_activities (id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE maza_srv_users DROP FOREIGN KEY fk_maza_srv_users_geof_id;
ALTER TABLE maza_srv_users DROP INDEX fk_maza_srv_users_geof_id;
ALTER TABLE maza_srv_users DROP geof_id;
ALTER TABLE maza_srv_users ADD COLUMN tgeof_id int(11);
ALTER TABLE maza_srv_users ADD CONSTRAINT fk_maza_srv_users_tgeof_id FOREIGN KEY (tgeof_id) REFERENCES maza_srv_triggered_geofences (id) ON DELETE CASCADE ON UPDATE CASCADE;

CREATE TABLE maza_srv_entry(
	id INT(11) NOT NULL AUTO_INCREMENT,
	ank_id INT(11) NOT NULL DEFAULT '0',
	entry_on TINYINT(1) DEFAULT '0',
	location_check TINYINT(1) DEFAULT '0',
	PRIMARY KEY (id),
	CONSTRAINT fk_entry_srv_ank_id FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE maza_srv_users ADD COLUMN mode VARCHAR(20) DEFAULT NULL;

UPDATE misc SET value='19.03.25' WHERE what="version";

# Belezi parapodatke premikanja miske
CREATE TABLE srv_advanced_paradata_movement (
	id INT(11) NOT NULL auto_increment,
	page_id INT(11) NOT NULL,
	time_start DATETIME(3) NOT NULL,
	time_end DATETIME(3) NOT NULL,
	pos_x_start INT(11) NOT NULL DEFAULT '0',
	pos_y_start INT(11) NOT NULL DEFAULT '0',
	pos_x_end INT(11) NOT NULL DEFAULT '0',
	pos_y_end INT(11) NOT NULL DEFAULT '0',
	PRIMARY KEY (id),
	CONSTRAINT fk_srv_advanced_paradata_movement_page_id FOREIGN KEY (page_id) REFERENCES srv_advanced_paradata_page (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8;

UPDATE misc SET value='19.04.05' WHERE what="version";

ALTER TABLE maza_user_srv_access ADD COLUMN datetime_unsubscribed DATETIME DEFAULT NULL;

UPDATE misc SET value='19.04.17' WHERE what="version";

ALTER TABLE maza_srv_geofences ADD COLUMN trigger_survey DATETIME DEFAULT CURRENT_TIMESTAMP;

UPDATE misc SET value='19.05.30' WHERE what="version";

UPDATE misc SET value='7.67' WHERE what="drupal version";
UPDATE misc SET value='19.06.07' WHERE what="version";

ALTER TABLE srv_advanced_paradata_movement ADD COLUMN distance INT(11) DEFAULT 0;

UPDATE misc SET value='19.07.18' WHERE what="version";

ALTER TABLE srv_gdpr_anketa ADD COLUMN other_text VARCHAR(255) NOT NULL DEFAULT '' AFTER other;
ALTER TABLE srv_gdpr_anketa ADD COLUMN expire ENUM('0','1') NOT NULL DEFAULT '0';
ALTER TABLE srv_gdpr_anketa ADD COLUMN expire_text VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE srv_gdpr_anketa ADD COLUMN other_users ENUM('0','1') NOT NULL DEFAULT '0';
ALTER TABLE srv_gdpr_anketa ADD COLUMN other_users_text VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE srv_gdpr_anketa ADD COLUMN export ENUM('0','1') NOT NULL DEFAULT '0';
ALTER TABLE srv_gdpr_anketa ADD COLUMN export_country VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE srv_gdpr_anketa ADD COLUMN export_user VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE srv_gdpr_anketa ADD COLUMN export_legal VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE srv_gdpr_anketa ADD COLUMN authorized VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE srv_gdpr_anketa ADD COLUMN contact_email VARCHAR(255) NOT NULL DEFAULT '';

UPDATE misc SET value='19.07.26' WHERE what="version";

ALTER TABLE srv_language_grid ADD podnaslov TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

UPDATE misc SET value='19.07.31' WHERE what="version";

ALTER TABLE srv_gdpr_user ADD COLUMN organization_maticna varchar(255) NOT NULL DEFAULT '' AFTER organization;
ALTER TABLE srv_gdpr_user ADD COLUMN organization_davcna varchar(255) NOT NULL DEFAULT '' AFTER organization_maticna;

UPDATE misc SET value='19.08.05' WHERE what="version";

#za modul MAZA
ALTER TABLE maza_srv_triggered_geofences ADD COLUMN enter_timestamp datetime, ADD COLUMN dwell_timestamp datetime;

UPDATE misc SET value='19.08.21' WHERE what="version";

ALTER TABLE srv_gdpr_anketa ADD COLUMN note TEXT NOT NULL DEFAULT '';

UPDATE misc SET value='19.09.09' WHERE what="version";

ALTER TABLE srv_gdpr_user ADD COLUMN has_dpo ENUM('0','1') NOT NULL DEFAULT '0' AFTER type;

UPDATE misc SET value='19.09.11' WHERE what="version";

#browser notifications for respondents
CREATE TABLE browser_notifications_respondents (
  id int(11) NOT NULL auto_increment,
  timestamp_joined datetime,
  endpoint_link VARCHAR(255) NOT NULL, 
  endpoint_key VARCHAR(255) NOT NULL, 
  public_key VARCHAR(255) NOT NULL, 
  auth VARCHAR(255) NOT NULL, 
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='19.09.15' WHERE what="version";

INSERT INTO srv_help (help, what) VALUES ('<a href="https://www.1ka.si/d/sl/pomoc/prirocniki/dvo-nivojsko-preverjanje-pristnosti-pri-prijavi-1ka" target="_blank">https://www.1ka.si/d/sl/pomoc/prirocniki/dvo-nivojsko-preverjanje-pristnosti-pri-prijavi-1ka</a>', 'srv_google_2fa_options');

UPDATE misc SET value='19.10.18' WHERE what="version";

RENAME TABLE srv_profileManager TO srv_profile_manager;

UPDATE misc SET value='19.10.21' WHERE what="version";

# dodajanje modula web push notification (WPN)
INSERT INTO srv_module (module_name, active) VALUES ('wpn', '0');
# aktiviraj modul wpn samo za test.1ka.si in www.1ka.si
#UPDATE srv_module SET active='1' WHERE module_name = 'wpn';

UPDATE misc SET value='19.10.22' WHERE what="version";

# Izgleda da v active tabeli texta manjka foreign key za usr_id - ZE NAREJENO NA WWW IN TUSU
#SET FOREIGN_KEY_CHECKS=0;
#ALTER TABLE srv_data_text_active ADD CONSTRAINT fk_srv_data_text_active_usr_id FOREIGN KEY ( usr_id ) REFERENCES srv_user( id ) ON DELETE CASCADE ON UPDATE CASCADE ;
#SET FOREIGN_KEY_CHECKS=1;

UPDATE misc SET value='19.10.25' WHERE what="version";

ALTER TABLE srv_if MODIFY COLUMN horizontal ENUM('0','1','2') NOT NULL DEFAULT '0';

UPDATE misc SET value='19.11.11' WHERE what="version";

ALTER TABLE srv_anketa CHANGE COLUMN naslov naslov VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL;
ALTER TABLE srv_anketa CHANGE COLUMN akronim akronim VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL;

UPDATE misc SET value='19.11.12' WHERE what="version";

CREATE TABLE srv_advanced_paradata_settings (
	ank_id INT(11) NOT NULL,
    collect_post_time ENUM('0','1') NOT NULL DEFAULT '1',
	PRIMARY KEY (ank_id),
	CONSTRAINT fk_srv_advanced_paradata_settings_ank_id FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='19.11.29' WHERE what="version";

CREATE TABLE countries_locations(
  id int(11) NOT NULL auto_increment,
  country_code VARCHAR(3) NOT NULL,
  latitude float(11,6) NOT NULL,
  longitude float(11,6) NOT NULL,
  name VARCHAR(64) NOT NULL,
  PRIMARY KEY (id)
)ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='19.11.30' WHERE what="version";

## Clanov nimamo - vsi clani se spremenijo v navadne narocnike
UPDATE users SET type='3' WHERE type='2';

UPDATE misc SET value='19.12.03' WHERE what="version";

INSERT INTO srv_module (module_name, active) VALUES ('evoli_employmeter', '0');
#UPDATE srv_module SET active='1' WHERE module_name = 'evoli_employmeter';

UPDATE misc SET value='19.12.05' WHERE what="version";

DROP TABLE srv_log_collect_data;
DROP TABLE srv_tracking_incremental;

UPDATE misc SET value='20.01.07' WHERE what="version";

UPDATE misc SET value='7.69' WHERE what="drupal version";
UPDATE misc SET value='20.01.16' WHERE what="version";

## Tabela pravic uporabnika glede na placan paket...
CREATE TABLE user_access(
  id int(11) NOT NULL auto_increment,
  usr_id int(11) NOT NULL,
  time_activate DATETIME(3) NOT NULL,
  time_expire DATETIME(3) NOT NULL,
  package INT(3) NOT NULL DEFAULT '0', 
  PRIMARY KEY (id),
  UNIQUE KEY (usr_id),
  CONSTRAINT fk_user_access_usr_id FOREIGN KEY (usr_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

## Tabela narocil uporabnikov
CREATE TABLE user_access_narocilo(
  id int(11) NOT NULL auto_increment,
  usr_id int(11) NOT NULL,
  status INT(3) NOT NULL DEFAULT '0',
  time DATETIME(3) NOT NULL,
  package INT(3) NOT NULL DEFAULT '0',
  payment_method INT(3) NOT NULL DEFAULT '0',
  price DECIMAL(7,2) NOT NULL DEFAULT '0',
  CONSTRAINT fk_user_access_narocilo_usr_id FOREIGN KEY (usr_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

## Opcijska tabela, ce bomo imeli dostop vezan na specificno anketo
CREATE TABLE user_access_anketa(
  id int(11) NOT NULL auto_increment,
  usr_id int(11) NOT NULL,
  ank_id int(11) NOT NULL,
  time_activate DATETIME(3) NOT NULL,
  time_expire DATETIME(3) NOT NULL,
  PRIMARY KEY (id),
  CONSTRAINT fk_user_access_anketa_usr_id FOREIGN KEY (usr_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_user_access_anketa_ank_id FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='20.01.27' WHERE what="version";

CREATE TABLE maza_survey(
	srv_id INT(11) NOT NULL,
	srv_description TEXT DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE maza_survey ADD CONSTRAINT fk_srv_id_maza_survey FOREIGN KEY (srv_id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE;

UPDATE misc SET value='20.02.03' WHERE what="version";

ALTER TABLE user_access_narocilo ADD COLUMN cebelica_id INT(11) NOT NULL DEFAULT '0';
ALTER TABLE user_access_narocilo ADD COLUMN phone VARCHAR(30) NOT NULL DEFAULT '';
ALTER TABLE user_access_narocilo ADD COLUMN podjetje_ime VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE user_access_narocilo ADD COLUMN podjetje_naslov VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE user_access_narocilo ADD COLUMN podjetje_postna VARCHAR(20) NOT NULL DEFAULT '';
ALTER TABLE user_access_narocilo ADD COLUMN podjetje_posta VARCHAR(100) NOT NULL DEFAULT '';
ALTER TABLE user_access_narocilo ADD COLUMN podjetje_davcna VARCHAR(20) NOT NULL DEFAULT '';

UPDATE misc SET value='20.02.16' WHERE what="version";

## Tabela z vsemi placljivimi paketi
CREATE TABLE user_access_paket(
    id INT(11) NOT NULL auto_increment,
    name VARCHAR(50) NOT NULL DEFAULT '',
    description VARCHAR(255) NOT NULL DEFAULT '',
    price DECIMAL(7,2) NOT NULL DEFAULT '0',
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

## Vstavimo pakete
INSERT INTO user_access_paket (name, description, price) VALUES ('1ka', '', '0');
INSERT INTO user_access_paket (name, description, price) VALUES ('2ka', '', '9.90');
INSERT INTO user_access_paket (name, description, price) VALUES ('3ka', '', '19.90');

ALTER TABLE user_access_narocilo CHANGE COLUMN price discount DECIMAL(7,2) NOT NULL DEFAULT '0';

ALTER TABLE user_access CHANGE COLUMN package package_id int(11) NOT NULL;
ALTER TABLE user_access ADD FOREIGN KEY (package_id) REFERENCES user_access_paket (id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE user_access_narocilo CHANGE COLUMN package package_id int(11) NOT NULL;
ALTER TABLE user_access_narocilo ADD FOREIGN KEY (package_id) REFERENCES user_access_paket (id) ON DELETE CASCADE ON UPDATE CASCADE;

UPDATE misc SET value='20.02.16' WHERE what="version";

#Ime, ki ga vpiše uporabnik in ga nato uporabimo na računu
ALTER TABLE user_access_narocilo ADD COLUMN ime VARCHAR(255) NOT NULL DEFAULT '';

UPDATE misc SET value='20.02.27' WHERE what="version";

ALTER TABLE user_access_narocilo ADD COLUMN trajanje INT(11) NOT NULL DEFAULT '1' AFTER package_id;

UPDATE misc SET value='20.02.28' WHERE what="version";

ALTER TABLE user_access_narocilo CHANGE COLUMN cebelica_id cebelica_id_predracun INT(11) NOT NULL DEFAULT '0';
ALTER TABLE user_access_narocilo ADD COLUMN cebelica_id_racun INT(11) NOT NULL DEFAULT '0' AFTER cebelica_id_predracun;

UPDATE misc SET value='20.03.02' WHERE what="version";

UPDATE srv_help set help='Prika&#382;e indikator napredka na vrhu ankete. Vklop je mo&#382;en samo, &#269;e ima anketa ve&#269; strani.' where what='srv_show_progressbar';

UPDATE misc SET value='20.03.08' WHERE what="version";

CREATE TABLE user_tracking (
    datetime DATETIME NOT NULL,
    ip VARCHAR(16) NOT NULL DEFAULT '',
    user INT NOT NULL  DEFAULT 0,
    `get` TEXT NOT NULL,
    `post` TEXT NOT NULL,
    status TINYINT( 1 ) NOT NULL DEFAULT '0',
    time_seconds FLOAT NOT NULL DEFAULT '0'
);
ALTER TABLE user_tracking ADD INDEX (datetime, user);
ALTER TABLE user_tracking ENGINE=InnoDB;

UPDATE misc SET value='20.03.18' WHERE what="version";

UPDATE user_access_paket SET price='11.90' WHERE name='2ka';
UPDATE user_access_paket SET price='21.90' WHERE name='3ka';

UPDATE misc SET value='23.03.18' WHERE what="version";

UPDATE misc SET value='20.05.14' WHERE what="version";

# Posodobljena drupal verzija na 7.70
UPDATE misc SET value='7.70' WHERE what="drupal version";
UPDATE misc SET value='20.05.20' WHERE what="version";

## Tabela placil uporabnikov
CREATE TABLE user_access_placilo(
  id int(11) NOT NULL auto_increment,
  narocilo_id int(11) NOT NULL DEFAULT 0,
  note VARCHAR(255) NOT NULL DEFAULT '',
  time DATETIME(3) NOT NULL,
  price DECIMAL(7,2) NOT NULL DEFAULT '0',
  payment_method INT(3) NOT NULL DEFAULT '0',
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='20.05.26' WHERE what="version";

# čćžđš na novejših SQL
# preizkušeno na test.1ka.si, dela OK
alter table srv_if change label label varchar(200) character set utf8;
alter table srv_spremenljivka change label label varchar(200) character set utf8;   
alter table srv_grid change naslov naslov varchar(250) character set utf8;

UPDATE misc SET value='20.05.28' WHERE what="version";

ALTER TABLE srv_anketa CHANGE intro_opomba intro_opomba VARCHAR(250) character set utf8;
ALTER TABLE srv_anketa CHANGE concl_opomba concl_opomba VARCHAR(250) character set utf8;

UPDATE misc SET value='20.06.04' WHERE what="version";

## Popravek charseta, da delujejo č-ji na mariadb
ALTER TABLE srv_anketa CHANGE introduction introduction TEXT character set utf8;
ALTER TABLE srv_anketa CHANGE conclusion conclusion TEXT character set utf8;
ALTER TABLE srv_anketa CHANGE statistics statistics TEXT character set utf8;

ALTER TABLE srv_spremenljivka CHANGE vsota vsota varchar(255) character set utf8 NOT NULL default '';
ALTER TABLE srv_spremenljivka CHANGE info info TEXT character set utf8;
ALTER TABLE srv_spremenljivka CHANGE note note TEXT character set utf8;
ALTER TABLE srv_spremenljivka CHANGE grid_subtitle1 grid_subtitle1 TEXT character set utf8;
ALTER TABLE srv_spremenljivka CHANGE grid_subtitle2 grid_subtitle2 TEXT character set utf8;

UPDATE misc SET value='20.06.05' WHERE what="version";

ALTER TABLE srv_alert CHANGE delete_text delete_text TEXT character set utf8 NOT NULL;
ALTER TABLE srv_alert CHANGE delete_subject delete_subject VARCHAR(250) character set utf8 NOT NULL;
ALTER TABLE srv_alert CHANGE expire_text expire_text TEXT character set utf8 NOT NULL;
ALTER TABLE srv_alert CHANGE expire_subject expire_subject VARCHAR(250) character set utf8 NOT NULL;
ALTER TABLE srv_alert CHANGE finish_text finish_text TEXT character set utf8 NOT NULL;
ALTER TABLE srv_alert CHANGE finish_subject finish_subject VARCHAR(250) character set utf8 NOT NULL;
ALTER TABLE srv_alert CHANGE active_text0 active_text0 TEXT character set utf8 NOT NULL;
ALTER TABLE srv_alert CHANGE active_subject0 active_subject0 VARCHAR(250) character set utf8 NOT NULL;
ALTER TABLE srv_alert CHANGE active_text1 active_text1 TEXT character set utf8 NOT NULL;
ALTER TABLE srv_alert CHANGE active_subject1 active_subject1 varchar(250) character set utf8 NOT NULL;

UPDATE misc SET value='20.06.05' WHERE what="version";

## POPRAVKI ČŠŽ-jev v komentarjih (ostanek sispleta)
## !!! NA WWW IN VIRTUALKAH NUJNO POGNATI SKRIPTO utils/1kaUtils/fix_comments.php, DRUGACE BO PORUSILO STARE KOMENTARJE !!!
ALTER TABLE post CHANGE naslov naslov VARCHAR(255) character set utf8 NOT NULL DEFAULT '';
ALTER TABLE post CHANGE vsebina vsebina TEXT character set utf8 NOT NULL DEFAULT '';
ALTER TABLE post CHANGE user user VARCHAR(40) character set utf8 NOT NULL DEFAULT '';

UPDATE misc SET value='20.06.15' WHERE what="version";

# Posodobljena drupal verzija na 7.72
UPDATE misc SET value='7.72' WHERE what="drupal version";
UPDATE misc SET value='20.06.19' WHERE what="version";

ALTER TABLE user_access_narocilo ADD COLUMN podjetje_drzava VARCHAR(255) NOT NULL DEFAULT 'Slovenija' AFTER podjetje_posta;
ALTER TABLE user_access_narocilo ADD COLUMN podjetje_zavezanec ENUM('0', '1') NOT NULL DEFAULT '0' AFTER podjetje_davcna;

UPDATE misc SET value='20.07.01' WHERE what="version";  

## Tabela placil preko strip-a
CREATE TABLE user_access_stripe_charge(
    id int(11) NOT NULL auto_increment,
    narocilo_id int(11) NOT NULL DEFAULT 0,
    charge_id int(11) NOT NULL DEFAULT 0,
    description VARCHAR(255) NOT NULL DEFAULT '',
    price DECIMAL(7,2) NOT NULL DEFAULT '0',
    amount_paid DECIMAL(7,2) NOT NULL DEFAULT '0',
    status VARCHAR(100) NOT NULL DEFAULT '',
    balance_transaction VARCHAR(255) NOT NULL DEFAULT '',
    time DATETIME(3) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY (charge_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='20.07.10' WHERE what="version";  

ALTER TABLE user_access_stripe_charge DROP COLUMN charge_id;

UPDATE misc SET value='20.07.13' WHERE what="version"; 

ALTER TABLE user_access_narocilo ADD COLUMN language VARCHAR(10) NOT NULL DEFAULT 'sl' AFTER time;

UPDATE misc SET value='20.07.17' WHERE what="version"; 

## 3 dodatni moduli za evoli (funkcionalno enaki kot teammeter)
INSERT INTO srv_module (module_name, active) VALUES ('evoli_quality_climate', '0');
INSERT INTO srv_module (module_name, active) VALUES ('evoli_teamship_meter', '0');
INSERT INTO srv_module (module_name, active) VALUES ('evoli_organizational_employeeship_meter', '0');
#UPDATE srv_module SET active='1' WHERE module_name = 'evoli_quality_climate';
#UPDATE srv_module SET active='1' WHERE module_name = 'evoli_teamship_meter';
#UPDATE srv_module SET active='1' WHERE module_name = 'evoli_organizational_employeeship_meter';


UPDATE misc SET value='20.07.29' WHERE what="version";

UPDATE srv_user_setting_for_survey SET value = '1ka' WHERE what = 'default_chart_profile_skin' AND value = '1ka';

UPDATE misc SET value='20.08.10' WHERE what="version";

## Tabela placil preko paypala
CREATE TABLE user_access_paypal_transaction(
    id int(11) NOT NULL auto_increment,
    transaction_id int(11) NOT NULL DEFAULT 0,
    narocilo_id int(11) NOT NULL DEFAULT 0,
    price DECIMAL(7,2) NOT NULL DEFAULT '0',
    currency_type VARCHAR(100) NOT NULL DEFAULT '',
    time DATETIME(3) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT '',
    PRIMARY KEY (id),
    UNIQUE KEY (transaction_id),
    UNIQUE KEY (narocilo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='20.09.07' WHERE what="version";


ALTER TABLE user_access_placilo ADD COLUMN canceled ENUM('0', '1') NOT NULL DEFAULT '0';

UPDATE misc SET value='20.09.11' WHERE what="version";

ALTER TABLE user_access_narocilo ADD COLUMN podjetje_eracun ENUM('0', '1') NOT NULL DEFAULT '0' AFTER podjetje_zavezanec;

UPDATE misc SET value='20.09.16' WHERE what="version";

INSERT INTO srv_help (help, what) VALUES ('Upload omejitev', 'srv_upload_limit');

# Posodobljena drupal verzija na 7.73
UPDATE misc SET value='7.73' WHERE what="drupal version";

UPDATE misc SET value='20.09.18' WHERE what="version";

## Tabela dostopov do izpolnjevanja - preverjanje stevila klikov na minuto
CREATE TABLE srv_clicks (
	ank_id INT(11) NOT NULL,
    click_count SMALLINT NOT NULL DEFAULT 0,
    click_time INT(11) NOT NULL,
	PRIMARY KEY (ank_id),
	CONSTRAINT fk_srv_clicks_ank_id FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='20.09.21' WHERE what="version";

ALTER TABLE srv_anketa ADD subsequent_answers ENUM('0', '1') NOT NULL DEFAULT '1' AFTER return_finished;
INSERT INTO srv_help (help, what) VALUES ('Uporabnik ne more nikoli naknadno urejati svojih odgovorov (npr. s klikom nazaj)', 'srv_subsequent_answers');

UPDATE misc SET value='20.10.20' WHERE what="version";

ALTER TABLE user_access_paypal_transaction CHANGE transaction_id transaction_id VARCHAR(100) NOT NULL DEFAULT '';

UPDATE misc SET value='20.10.25' WHERE what="version";

INSERT INTO srv_help (help, what) VALUES ('Pri po&#353;iljanju email vabil na ve&#269; naslovov je vklopljena zakasnitev, kar pomeni da med e-po&#353;tnim sporo&#269;ilom, poslanim enemu naslovniku, in e-po&#353;tnim sporo&#269;ilom, poslanim naslednjemu naslovniku, prete&#269;e najmanj 2 sekundi. Ta &#269;as lahko po potrebi spremenite (glede na zmogljivosti va&#353;ega stre&#382;nika). ', 'srv_inv_delay');

UPDATE misc SET value='20.10.29' WHERE what="version";

ALTER TABLE user_access_narocilo CHANGE COLUMN podjetje_zavezanec podjetje_no_ddv ENUM('0', '1') NOT NULL DEFAULT '0' AFTER podjetje_davcna;

UPDATE misc SET value='20.11.04' WHERE what="version";

ALTER TABLE user_access_stripe_charge ADD COLUMN session_id VARCHAR(100) NOT NULL DEFAULT '' AFTER id;

UPDATE misc SET value='20.11.11' WHERE what="version";

## RESTRICTION TABELE S FOREIGN KEYI VREDNOSTI, KI SE NIKOLI NE SMEJO POBRISATI
## Restrict brisanje sistemskih vrstic za srv_anketa
CREATE TABLE restrict_fk_srv_anketa (
    ank_id INT PRIMARY KEY,
    FOREIGN KEY (ank_id) REFERENCES srv_anketa (id) ON DELETE RESTRICT ON UPDATE CASCADE
);
INSERT INTO restrict_fk_srv_anketa (ank_id) VALUES (-1);
INSERT INTO restrict_fk_srv_anketa (ank_id) VALUES (0);

## Restrict brisanje sistemskih vrstic za srv_grupa
CREATE TABLE restrict_fk_srv_grupa (
    gru_id INT PRIMARY KEY,
    FOREIGN KEY (gru_id) REFERENCES srv_grupa (id) ON DELETE RESTRICT ON UPDATE CASCADE
);
INSERT INTO restrict_fk_srv_grupa (gru_id) VALUES (-2);
INSERT INTO restrict_fk_srv_grupa (gru_id) VALUES (-1);
INSERT INTO restrict_fk_srv_grupa (gru_id) VALUES (0);

## Restrict brisanje sistemskih vrstic za srv_spremenljivka
CREATE TABLE restrict_fk_srv_spremenljivka (
    spr_id INT PRIMARY KEY,
    FOREIGN KEY (spr_id) REFERENCES srv_spremenljivka (id) ON DELETE RESTRICT ON UPDATE CASCADE
);
INSERT INTO restrict_fk_srv_spremenljivka (spr_id) VALUES (-4);
INSERT INTO restrict_fk_srv_spremenljivka (spr_id) VALUES (-3);
INSERT INTO restrict_fk_srv_spremenljivka (spr_id) VALUES (-2);
INSERT INTO restrict_fk_srv_spremenljivka (spr_id) VALUES (-1);
INSERT INTO restrict_fk_srv_spremenljivka (spr_id) VALUES (0);

## Restrict brisanje sistemskih vrstic za srv_vrednost
CREATE TABLE restrict_fk_srv_vrednost (
    vre_id INT PRIMARY KEY,
    FOREIGN KEY (vre_id) REFERENCES srv_vrednost (id) ON DELETE RESTRICT ON UPDATE CASCADE
);
INSERT INTO restrict_fk_srv_vrednost (vre_id) VALUES (-4);
INSERT INTO restrict_fk_srv_vrednost (vre_id) VALUES (-3);
INSERT INTO restrict_fk_srv_vrednost (vre_id) VALUES (-2);
INSERT INTO restrict_fk_srv_vrednost (vre_id) VALUES (-1);
INSERT INTO restrict_fk_srv_vrednost (vre_id) VALUES (0);

## Restrict brisanje sistemskih vrstic za srv_if
CREATE TABLE restrict_fk_srv_if (
    if_id INT PRIMARY KEY,
    FOREIGN KEY (if_id) REFERENCES srv_if (id) ON DELETE RESTRICT ON UPDATE CASCADE
);
INSERT INTO restrict_fk_srv_if (if_id) VALUES (0);

UPDATE misc SET value='20.11.16' WHERE what="version";

## Spremenba cen paketov
UPDATE user_access_paket SET price='13.90' WHERE name='2ka';
UPDATE user_access_paket SET price='19.90' WHERE name='3ka';

UPDATE misc SET value='20.11.16' WHERE what="version";

# Posodobljena drupal verzija na 7.75
UPDATE misc SET value='7.75' WHERE what="drupal version";

UPDATE misc SET value='20.12.02' WHERE what="version";

## Popravek čšž-jev na MariaDB
ALTER TABLE srv_validation CHANGE reminder_text reminder_text VARCHAR(255) character set utf8 NOT NULL;

ALTER TABLE srv_language_spremenljivka CHANGE naslov naslov TEXT character set utf8 NOT NULL;
ALTER TABLE srv_language_vrednost CHANGE naslov naslov TEXT character set utf8 NOT NULL;
ALTER TABLE srv_language_grid CHANGE naslov naslov TEXT character set utf8 NOT NULL;
ALTER TABLE srv_language_slider CHANGE label label TEXT character set utf8 NOT NULL;

UPDATE misc SET value='20.12.03' WHERE what="version";

ALTER TABLE srv_language CHANGE language language VARCHAR(255) character set utf8 NOT NULL;

UPDATE misc SET value='20.12.03' WHERE what="version";

# Posodobljena drupal verzija na 7.77
UPDATE misc SET value='7.77' WHERE what="drupal version";

UPDATE misc SET value='20.12.06' WHERE what="version";

## Tabela uporabnikov za cronjob, ki se posilja vsak dan ob 9h zjutraj
CREATE TABLE user_cronjob (
	id int(11) NOT NULL auto_increment,
    usr_id int(11) NOT NULL,
    phase VARCHAR(100) NOT NULL DEFAULT '',
    phase_time DATETIME(3) NOT NULL,
    email_sent ENUM('0', '1') NOT NULL DEFAULT '0',
    PRIMARY KEY (id),
    UNIQUE KEY (usr_id),
    CONSTRAINT fk_user_cronjob_usr_id FOREIGN KEY (usr_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE misc SET value='20.12.06' WHERE what="version";

# Drupal verzija je bila posodobljena in vpišemo na 1ka.si in te
UPDATE misc SET value='7.78' WHERE what="drupal version";
UPDATE misc SET value='21.02.04' WHERE what="version";

## Dodana moznost prevodov za gdpr nastavitve
ALTER TABLE srv_gdpr_anketa CHANGE COLUMN other_text other_text_slo VARCHAR(255) character set utf8 NOT NULL DEFAULT '';
ALTER TABLE srv_gdpr_anketa CHANGE COLUMN expire_text expire_text_slo VARCHAR(255) character set utf8 NOT NULL DEFAULT '';
ALTER TABLE srv_gdpr_anketa CHANGE COLUMN other_users_text other_users_text_slo VARCHAR(255) character set utf8 NOT NULL DEFAULT '';
ALTER TABLE srv_gdpr_anketa CHANGE COLUMN export_country export_country_slo VARCHAR(255) character set utf8 NOT NULL DEFAULT '';
ALTER TABLE srv_gdpr_anketa CHANGE COLUMN export_user export_user_slo VARCHAR(255) character set utf8 NOT NULL DEFAULT '';
ALTER TABLE srv_gdpr_anketa CHANGE COLUMN export_legal export_legal_slo VARCHAR(255) character set utf8 NOT NULL DEFAULT '';
ALTER TABLE srv_gdpr_anketa CHANGE COLUMN note note_slo TEXT NOT NULL DEFAULT '';

ALTER TABLE srv_gdpr_anketa ADD COLUMN other_text_eng VARCHAR(255) character set utf8 NOT NULL DEFAULT '' AFTER other_text_slo;
ALTER TABLE srv_gdpr_anketa ADD COLUMN expire_text_eng VARCHAR(255) character set utf8 NOT NULL DEFAULT '' AFTER expire_text_slo;
ALTER TABLE srv_gdpr_anketa ADD COLUMN other_users_text_eng VARCHAR(255) character set utf8 NOT NULL DEFAULT '' AFTER other_users_text_slo;
ALTER TABLE srv_gdpr_anketa ADD COLUMN export_country_eng VARCHAR(255) character set utf8 NOT NULL DEFAULT '' AFTER export_country_slo;
ALTER TABLE srv_gdpr_anketa ADD COLUMN export_user_eng VARCHAR(255) character set utf8 NOT NULL DEFAULT '' AFTER export_user_slo;
ALTER TABLE srv_gdpr_anketa ADD COLUMN export_legal_eng VARCHAR(255) character set utf8 NOT NULL DEFAULT '' AFTER export_legal_slo;
ALTER TABLE srv_gdpr_anketa ADD COLUMN note_eng TEXT NOT NULL DEFAULT '' AFTER note_slo;

UPDATE srv_gdpr_anketa SET other_text_eng = other_text_slo;
UPDATE srv_gdpr_anketa SET expire_text_eng = expire_text_slo;
UPDATE srv_gdpr_anketa SET other_users_text_eng = other_users_text_slo;
UPDATE srv_gdpr_anketa SET export_country_eng = export_country_slo;
UPDATE srv_gdpr_anketa SET export_user_eng = export_user_slo;
UPDATE srv_gdpr_anketa SET export_legal_eng = export_legal_slo;
UPDATE srv_gdpr_anketa SET note_eng = note_slo;

UPDATE misc SET value='21.02.16' WHERE what="version";
