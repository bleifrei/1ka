# pozeni sele, ko je baza updatana vsaj na 10.06.08 !!!


# IDje 0 vstavimo, zato ker se nekje uporabljajo vrednosti 0 pri FK (tudi -1, -2, -3, -4)


INSERT INTO srv_anketa (id, naslov) VALUES ('', 'system');
UPDATE srv_anketa SET id='0' WHERE id=LAST_INSERT_ID(); #pri auto_increment poljih ne mores nastavit 0 pri insertu (ker to pomeni auto_increment), zato je treba z updatom popravit na 0
INSERT INTO srv_anketa (id, naslov) VALUES ('-1', 'system');
INSERT INTO srv_grupa (id, ank_id, naslov) VALUES ('', '0', 'system');
UPDATE srv_grupa SET id='0' WHERE id=LAST_INSERT_ID();
INSERT INTO srv_grupa (id, ank_id, naslov) VALUES ('-1', '0', 'system');
INSERT INTO srv_spremenljivka (id, gru_id, naslov) VALUES ('-1', '0', 'system');
INSERT INTO srv_spremenljivka (id, gru_id, naslov) VALUES ('-2', '0', 'system');
INSERT INTO srv_spremenljivka (id, gru_id, naslov) VALUES ('', '0', 'system');
UPDATE srv_spremenljivka SET id='0' WHERE id=LAST_INSERT_ID();
INSERT INTO srv_vrednost (id, spr_id, naslov) VALUES ('-1', '0', 'system'), ('-2', '0', 'system'), ('-3', '0', 'system'), ('-4', '0', 'system');
INSERT INTO srv_vrednost (id, spr_id, naslov) VALUES ('0', '0', 'system');
UPDATE srv_vrednost SET id='0' WHERE id=LAST_INSERT_ID();
INSERT INTO srv_if (id, label) VALUES ('', 'system');
UPDATE srv_if SET id='0' WHERE id=LAST_INSERT_ID();

#INSERT INTO srv_grid (id) VALUES ('-1'), ('-2'), ('-3'), ('-4');


# POSTAVLJANJE FOREIGN KEY-EV NA BAZO !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

# zadeva najprej pobrise vse prazne vrednosti (upam da ne kje prevec) in nato nastavi forein key (FK)

# pri dodajanju prosim upostevaj abecedni vrstni red !!!! Drzi se tudi poimenovanja FK kot je ze drugod! (fk_ime_tabele_ime_polja)






DELETE FROM srv_activity WHERE sid NOT IN (SELECT id FROM srv_anketa);
ALTER TABLE srv_activity ADD CONSTRAINT fk_srv_activity_sid FOREIGN KEY ( sid ) REFERENCES srv_anketa( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

#DELETE FROM srv_activity WHERE uid NOT IN (SELECT id FROM users);
#ALTER TABLE srv_activity ADD CONSTRAINT fk_srv_activity_uid FOREIGN KEY ( uid ) REFERENCES users( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_alert WHERE ank_id NOT IN (SELECT id FROM srv_anketa);
ALTER TABLE srv_alert ADD CONSTRAINT fk_srv_alert_ank_id FOREIGN KEY ( ank_id ) REFERENCES srv_anketa( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_analysis_archive WHERE sid NOT IN (SELECT id FROM srv_anketa);
ALTER TABLE srv_analysis_archive ADD CONSTRAINT fk_srv_analysis_archive_sid FOREIGN KEY ( sid ) REFERENCES srv_anketa( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

#DELETE FROM srv_analysis_archive WHERE uid NOT IN (SELECT id FROM users);
#ALTER TABLE srv_analysis_archive ADD CONSTRAINT fk_srv_analysis_archive_uid FOREIGN KEY ( uid ) REFERENCES users( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

ALTER TABLE srv_anketa ADD CONSTRAINT fk_srv_anketa_folder FOREIGN KEY ( folder ) REFERENCES srv_folder( id ) ON DELETE RESTRICT ON UPDATE CASCADE ;

DELETE FROM srv_branching WHERE ank_id NOT IN (SELECT id FROM srv_anketa);
ALTER TABLE srv_branching ADD CONSTRAINT fk_srv_branching_ank_id FOREIGN KEY ( ank_id ) REFERENCES srv_anketa( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

# srv_branching : parent, element_spr, element_if - prevec zakompliciran da bi se dodajal fk (vprasanje kako bi sploh funkcioniral)

DELETE FROM srv_calculation WHERE spr_id NOT IN (SELECT id FROM srv_spremenljivka);
ALTER TABLE srv_calculation ADD CONSTRAINT fk_srv_calculation_spr_id FOREIGN KEY ( spr_id ) REFERENCES srv_spremenljivka( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_calculation WHERE vre_id NOT IN (SELECT id FROM srv_vrednost);
ALTER TABLE srv_calculation ADD CONSTRAINT fk_srv_calculation_vre_id FOREIGN KEY ( vre_id ) REFERENCES srv_vrednost( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

# srv_calculation : cnd_id ne morem dodat, ker gre tudi v minus (nekje se idji povezujejo v +, nekje pa v -)

DELETE FROM srv_call_current WHERE usr_id NOT IN (SELECT id FROM srv_user);
ALTER TABLE srv_call_current CHANGE usr_id usr_id INT( 11 ) NOT NULL;        # prej je bil unsigned INT(10) zarad cesar pol ne dela FK
ALTER TABLE srv_call_current ADD CONSTRAINT fk_srv_call_current_usr_id FOREIGN KEY ( usr_id ) REFERENCES srv_user( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

#DELETE FROM srv_call_current WHERE user_id NOT IN (SELECT id FROM users);
#ALTER TABLE srv_call_current CHANGE user_id user_id INT( 11 ) NOT NULL;
#ALTER TABLE srv_call_current ADD CONSTRAINT fk_srv_call_current_user_id FOREIGN KEY ( user_id ) REFERENCES users( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_call_history WHERE survey_id NOT IN (SELECT id FROM srv_anketa);
ALTER TABLE srv_call_history CHANGE survey_id survey_id INT( 11 ) NOT NULL;
ALTER TABLE srv_call_history ADD CONSTRAINT fk_srv_call_history_survey_id FOREIGN KEY ( survey_id ) REFERENCES srv_anketa( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_call_history WHERE usr_id NOT IN (SELECT id FROM srv_user);
ALTER TABLE srv_call_history CHANGE usr_id usr_id INT( 11 ) NOT NULL;
ALTER TABLE srv_call_history ADD CONSTRAINT fk_srv_call_history_usr_id FOREIGN KEY ( usr_id ) REFERENCES srv_user( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

#DELETE FROM srv_call_history WHERE user_id NOT IN (SELECT id FROM users);
#ALTER TABLE srv_call_history CHANGE user_id user_id INT( 11 ) NOT NULL;
#ALTER TABLE srv_call_history ADD CONSTRAINT fk_srv_call_history_user_id FOREIGN KEY ( user_id ) REFERENCES users( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_call_schedule WHERE usr_id NOT IN (SELECT id FROM srv_user);
ALTER TABLE srv_call_schedule CHANGE usr_id usr_id INT( 11 ) NOT NULL;
ALTER TABLE srv_call_schedule ADD CONSTRAINT fk_srv_call_schedule_usr_id FOREIGN KEY ( usr_id ) REFERENCES srv_user( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_call_setting WHERE survey_id NOT IN (SELECT id FROM srv_anketa);
ALTER TABLE srv_call_setting CHANGE survey_id survey_id INT( 11 ) NOT NULL;
ALTER TABLE srv_call_setting ADD CONSTRAINT fk_srv_call_setting_survey_id FOREIGN KEY ( survey_id ) REFERENCES srv_anketa( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_condition WHERE if_id NOT IN (SELECT id FROM srv_if);
ALTER TABLE srv_condition ADD CONSTRAINT fk_srv_condition_if_id FOREIGN KEY ( if_id ) REFERENCES srv_if( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_condition WHERE spr_id NOT IN (SELECT id FROM srv_spremenljivka);
ALTER TABLE srv_condition ADD CONSTRAINT fk_srv_condition_spr_id FOREIGN KEY ( spr_id ) REFERENCES srv_spremenljivka( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_condition WHERE vre_id NOT IN (SELECT id FROM srv_vrednost);
ALTER TABLE srv_condition ADD CONSTRAINT fk_srv_condition_vre_id FOREIGN KEY ( vre_id ) REFERENCES srv_vrednost( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_condition_grid WHERE cond_id NOT IN (SELECT id FROM srv_condition);
ALTER TABLE srv_condition_grid ADD CONSTRAINT fk_srv_condition_grid_cond_id FOREIGN KEY ( cond_id ) REFERENCES srv_condition( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

# FK more it u bistvu na (spr_id, grd_id)
#DELETE FROM srv_condition_grid WHERE grd_id NOT IN (SELECT id FROM srv_grid);
#ALTER TABLE srv_condition_grid ADD CONSTRAINT fk_srv_condition_grid_grd_id FOREIGN KEY ( grd_id ) REFERENCES srv_grid( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_condition_vre WHERE cond_id NOT IN (SELECT id FROM srv_condition);
ALTER TABLE srv_condition_vre ADD CONSTRAINT fk_srv_condition_vre_cond_id FOREIGN KEY ( cond_id ) REFERENCES srv_condition( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_condition_vre WHERE vre_id NOT IN (SELECT id FROM srv_vrednost);
ALTER TABLE srv_condition_vre ADD CONSTRAINT fk_srv_condition_vre_vre_id FOREIGN KEY ( vre_id ) REFERENCES srv_vrednost( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_data_checkgrid WHERE usr_id NOT IN (SELECT id FROM srv_user);
ALTER TABLE srv_data_checkgrid ADD CONSTRAINT fk_srv_data_checkgrid_usr_id FOREIGN KEY ( usr_id ) REFERENCES srv_user( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_data_checkgrid WHERE spr_id NOT IN (SELECT id FROM srv_spremenljivka);
ALTER TABLE srv_data_checkgrid ADD CONSTRAINT fk_srv_data_checkgrid_spr_id FOREIGN KEY ( spr_id ) REFERENCES srv_spremenljivka( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_data_checkgrid WHERE vre_id NOT IN (SELECT id FROM srv_vrednost);
ALTER TABLE srv_data_checkgrid ADD CONSTRAINT fk_srv_data_checkgrid_vre_id FOREIGN KEY ( vre_id ) REFERENCES srv_vrednost( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

# srv_data_checkgrid : grd_id
# FK je (spr_id, grd_id), ampak treba je najprej zrihtat minus vrednosti

DELETE FROM srv_data_glasovanje WHERE usr_id NOT IN (SELECT id FROM srv_user);
ALTER TABLE srv_data_glasovanje ADD CONSTRAINT fk_srv_data_glasovanje_usr_id FOREIGN KEY ( usr_id ) REFERENCES srv_user( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_data_glasovanje WHERE spr_id NOT IN (SELECT id FROM srv_spremenljivka);
ALTER TABLE srv_data_glasovanje ADD CONSTRAINT fk_srv_data_glasovanje_spr_id FOREIGN KEY ( spr_id ) REFERENCES srv_spremenljivka( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_data_grid WHERE usr_id NOT IN (SELECT id FROM srv_user);
ALTER TABLE srv_data_grid ADD CONSTRAINT fk_srv_data_grid_usr_id FOREIGN KEY ( usr_id ) REFERENCES srv_user( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_data_grid WHERE spr_id NOT IN (SELECT id FROM srv_spremenljivka);
ALTER TABLE srv_data_grid ADD CONSTRAINT fk_srv_data_grid_spr_id FOREIGN KEY ( spr_id ) REFERENCES srv_spremenljivka( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

# TODO: srv_data_grid : vre_id, grd_id
# FK more it na obe vrednosti, treba je prej zbrisat minus vrednosti
#DELETE FROM srv_data_grid WHERE (grd_id, spr_id) NOT IN (SELECT id, spr_id FROM srv_grid);
#ALTER TABLE srv_data_grid ADD CONSTRAINT fk_srv_data_grid_grd_id FOREIGN KEY ( grd_id ) REFERENCES srv_grid( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_data_grid_active WHERE usr_id NOT IN (SELECT id FROM srv_user);
ALTER TABLE srv_data_grid_active ADD CONSTRAINT fk_srv_data_grid_active_usr_id FOREIGN KEY ( usr_id ) REFERENCES srv_user( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_data_grid_active WHERE spr_id NOT IN (SELECT id FROM srv_spremenljivka);
ALTER TABLE srv_data_grid_active ADD CONSTRAINT fk_srv_data_grid_active_spr_id FOREIGN KEY ( spr_id ) REFERENCES srv_spremenljivka( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

# TODO: srv_data_grid_active : vre_id, grd_id

DELETE FROM srv_data_imena WHERE usr_id NOT IN (SELECT id FROM srv_user);
ALTER TABLE srv_data_imena ADD CONSTRAINT fk_srv_data_imena_usr_id FOREIGN KEY ( usr_id ) REFERENCES srv_user( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_data_imena WHERE spr_id NOT IN (SELECT id FROM srv_spremenljivka);
ALTER TABLE srv_data_imena ADD CONSTRAINT fk_srv_data_imena_spr_id FOREIGN KEY ( spr_id ) REFERENCES srv_spremenljivka( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_data_number WHERE usr_id NOT IN (SELECT id FROM srv_user);
ALTER TABLE srv_data_number ADD CONSTRAINT fk_srv_data_number_usr_id FOREIGN KEY ( usr_id ) REFERENCES srv_user( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_data_number WHERE spr_id NOT IN (SELECT id FROM srv_spremenljivka);
ALTER TABLE srv_data_number ADD CONSTRAINT fk_srv_data_number_spr_id FOREIGN KEY ( spr_id ) REFERENCES srv_spremenljivka( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_data_number WHERE vre_id NOT IN (SELECT id FROM srv_vrednost);
ALTER TABLE srv_data_number ADD CONSTRAINT fk_srv_data_number_vre_id FOREIGN KEY ( vre_id ) REFERENCES srv_vrednost( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_data_rating WHERE usr_id NOT IN (SELECT id FROM srv_user);
ALTER TABLE srv_data_rating ADD CONSTRAINT fk_srv_data_rating_usr_id FOREIGN KEY ( usr_id ) REFERENCES srv_user( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_data_rating WHERE spr_id NOT IN (SELECT id FROM srv_spremenljivka);
ALTER TABLE srv_data_rating ADD CONSTRAINT fk_srv_data_rating_spr_id FOREIGN KEY ( spr_id ) REFERENCES srv_spremenljivka( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_data_rating WHERE vre_id NOT IN (SELECT id FROM srv_vrednost);
ALTER TABLE srv_data_rating ADD CONSTRAINT fk_srv_data_rating_vre_id FOREIGN KEY ( vre_id ) REFERENCES srv_vrednost( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_data_text WHERE usr_id NOT IN (SELECT id FROM srv_user);
ALTER TABLE srv_data_text ADD CONSTRAINT fk_srv_data_text_usr_id FOREIGN KEY ( usr_id ) REFERENCES srv_user( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_data_text WHERE spr_id NOT IN (SELECT id FROM srv_spremenljivka);
ALTER TABLE srv_data_text ADD CONSTRAINT fk_srv_data_text_spr_id FOREIGN KEY ( spr_id ) REFERENCES srv_spremenljivka( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

# srv_data_text : vre_id (komentarji na spremenljivko imajo v vre_id ID spremenljivke!)

DELETE FROM srv_data_textgrid WHERE usr_id NOT IN (SELECT id FROM srv_user);
ALTER TABLE srv_data_textgrid ADD CONSTRAINT fk_srv_data_textgrid_usr_id FOREIGN KEY ( usr_id ) REFERENCES srv_user( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_data_textgrid WHERE spr_id NOT IN (SELECT id FROM srv_spremenljivka);
ALTER TABLE srv_data_textgrid ADD CONSTRAINT fk_srv_data_textgrid_spr_id FOREIGN KEY ( spr_id ) REFERENCES srv_spremenljivka( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_data_textgrid WHERE vre_id NOT IN (SELECT id FROM srv_vrednost);
ALTER TABLE srv_data_textgrid ADD CONSTRAINT fk_srv_data_textgrid_vre_id FOREIGN KEY ( vre_id ) REFERENCES srv_vrednost( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

# TODO: srv_data_textgrid : grd_id ? 
# FK je (spr_id, grd_id), ampak treba je najprej zrihtat minus vrednosti

DELETE FROM srv_data_vrednost WHERE usr_id NOT IN (SELECT id FROM srv_user);
ALTER TABLE srv_data_vrednost ADD CONSTRAINT fk_srv_data_vrednost_usr_id FOREIGN KEY ( usr_id ) REFERENCES srv_user( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_data_vrednost WHERE spr_id NOT IN (SELECT id FROM srv_spremenljivka);
ALTER TABLE srv_data_vrednost ADD CONSTRAINT fk_srv_data_vrednost_spr_id FOREIGN KEY ( spr_id ) REFERENCES srv_spremenljivka( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_data_vrednost WHERE vre_id NOT IN (SELECT id FROM srv_vrednost);
ALTER TABLE srv_data_vrednost ADD CONSTRAINT fk_srv_data_vrednost_vre_id FOREIGN KEY ( vre_id ) REFERENCES srv_vrednost( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_data_vrednost_active WHERE usr_id NOT IN (SELECT id FROM srv_user);
ALTER TABLE srv_data_vrednost_active ADD CONSTRAINT fk_srv_data_vrednost_active_usr_id FOREIGN KEY ( usr_id ) REFERENCES srv_user( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_data_vrednost_active WHERE spr_id NOT IN (SELECT id FROM srv_spremenljivka);
ALTER TABLE srv_data_vrednost_active ADD CONSTRAINT fk_srv_data_vrednost_active_spr_id FOREIGN KEY ( spr_id ) REFERENCES srv_spremenljivka( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_data_vrednost_active WHERE vre_id NOT IN (SELECT id FROM srv_vrednost);
ALTER TABLE srv_data_vrednost_active ADD CONSTRAINT fk_srv_data_vrednost_active_vre_id FOREIGN KEY ( vre_id ) REFERENCES srv_vrednost( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_dostop WHERE ank_id NOT IN (SELECT id FROM srv_anketa);
ALTER TABLE srv_dostop ADD CONSTRAINT fk_srv_dostop_ank_id FOREIGN KEY ( ank_id ) REFERENCES srv_anketa( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

#DELETE FROM srv_dostop WHERE uid NOT IN (SELECT id FROM users);
#ALTER TABLE srv_dostop ADD CONSTRAINT fk_srv_dostop_uid_id FOREIGN KEY ( uid ) REFERENCES users( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_filter_profiles WHERE sid NOT IN (SELECT id FROM srv_anketa);
ALTER TABLE srv_filter_profiles ADD CONSTRAINT fk_srv_filter_profiles_sid FOREIGN KEY ( sid ) REFERENCES srv_anketa( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

# tale ne dela pri vnosih ???
#DELETE FROM srv_filter_profiles WHERE if_id NOT IN (SELECT id FROM srv_if);
#ALTER TABLE srv_filter_profiles ADD CONSTRAINT fk_srv_filter_profiles_if_id FOREIGN KEY ( if_id ) REFERENCES srv_if( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

# srv_filter_profiles : uid (lahko je 0)

# srv_folder : creator_uid (lahko je 0)

DELETE FROM srv_glasovanje WHERE ank_id NOT IN (SELECT id FROM srv_anketa);
ALTER TABLE srv_glasovanje ADD CONSTRAINT fk_srv_glasovanje_ank_id FOREIGN KEY ( ank_id ) REFERENCES srv_anketa( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_glasovanje WHERE spr_id NOT IN (SELECT id FROM srv_spremenljivka);
ALTER TABLE srv_glasovanje ADD CONSTRAINT fk_srv_glasovanje_spr_id FOREIGN KEY ( spr_id ) REFERENCES srv_spremenljivka( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_grid WHERE spr_id NOT IN (SELECT id FROM srv_spremenljivka);
ALTER TABLE srv_grid ADD CONSTRAINT fk_srv_grid_spr_id FOREIGN KEY ( spr_id ) REFERENCES srv_spremenljivka( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_grupa WHERE ank_id NOT IN (SELECT id FROM srv_anketa);
ALTER TABLE srv_grupa ADD CONSTRAINT fk_srv_grupa_ank_id FOREIGN KEY ( ank_id ) REFERENCES srv_anketa( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

# srv_help nima nic

# srv_if : folder ? se sploh doda? sicer nima nic (ker je parent element razlicnim child tabelam...)

# srv_invitation_profiles : uid (0) ?

DELETE FROM srv_library_anketa WHERE ank_id NOT IN (SELECT id FROM srv_anketa);
ALTER TABLE srv_library_anketa ADD CONSTRAINT fk_srv_library_anketa_ank_id FOREIGN KEY ( ank_id ) REFERENCES srv_anketa( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

# srv_library_anketa : uid 0

# srv_library_folder : uid 0

# srv_misc nima nic

# srv_missing_profiles : uid 0

DELETE FROM srv_mising_profiles_values WHERE pid NOT IN (SELECT id FROM srv_mising_profiles);
ALTER TABLE srv_mising_profiles_values ADD CONSTRAINT fk_srv_mising_profiles_values_pid FOREIGN KEY ( pid ) REFERENCES srv_mising_profiles( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_respondents WHERE pid NOT IN (SELECT id FROM srv_respondent_profiles);
ALTER TABLE srv_respondents ADD CONSTRAINT fk_srv_respondents_pid FOREIGN KEY ( pid ) REFERENCES srv_respondent_profiles( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

#DELETE FROM srv_respondent_profiles WHERE uid NOT IN (SELECT id FROM users);
#ALTER TABLE srv_respondent_profiles ADD CONSTRAINT fk_srv_respondent_uid FOREIGN KEY ( uid ) REFERENCES users( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_specialdata_vrednost WHERE usr_id NOT IN (SELECT id FROM srv_user);
ALTER TABLE srv_specialdata_vrednost ADD CONSTRAINT fk_srv_specialdata_vrednost_usr_id FOREIGN KEY ( usr_id ) REFERENCES srv_user( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_specialdata_vrednost WHERE spr_id NOT IN (SELECT id FROM srv_spremenljivka);
ALTER TABLE srv_specialdata_vrednost ADD CONSTRAINT fk_srv_specialdata_vrednost_spr_id FOREIGN KEY ( spr_id ) REFERENCES srv_spremenljivka( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_specialdata_vrednost WHERE vre_id NOT IN (SELECT id FROM srv_vrednost);
ALTER TABLE srv_specialdata_vrednost ADD CONSTRAINT fk_srv_specialdata_vrednost_vre_id FOREIGN KEY ( vre_id ) REFERENCES srv_vrednost( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_spremenljivka WHERE gru_id NOT IN (SELECT id FROM srv_grupa);
ALTER TABLE srv_spremenljivka ADD CONSTRAINT fk_srv_spremenljivka_gru_id FOREIGN KEY ( gru_id ) REFERENCES srv_grupa( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

# srv_spremenljivka : folder - a se to doda?

# srv_statistic_profile : uid

# srv_status_casi : uid 0

# srv_status_profile : uid 0

DELETE FROM srv_survey_misc WHERE sid NOT IN (SELECT id FROM srv_anketa);
ALTER TABLE srv_survey_misc ADD CONSTRAINT fk_srv_survey_misc_sid FOREIGN KEY ( sid ) REFERENCES srv_anketa( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

# TODO : srv_sys_filters

DELETE FROM srv_tracking WHERE ank_id NOT IN (SELECT id FROM srv_anketa);
ALTER TABLE srv_tracking ADD CONSTRAINT fk_srv_tracking_ank_id FOREIGN KEY ( ank_id ) REFERENCES srv_anketa( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

# srv_tracking : user (uid 0)

DELETE FROM srv_user WHERE ank_id NOT IN (SELECT id FROM srv_anketa);
ALTER TABLE srv_user ADD CONSTRAINT fk_srv_user_ank_id FOREIGN KEY ( ank_id ) REFERENCES srv_anketa( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

# srv_user : user_id (uid 0) - to je povezava na sisplet userja

DELETE FROM srv_userbase WHERE usr_id NOT IN (SELECT id FROM srv_user);
ALTER TABLE srv_userbase ADD CONSTRAINT fk_srv_userbase_usr_id FOREIGN KEY ( usr_id ) REFERENCES srv_user( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

#DELETE FROM srv_userbase WHERE admin_id NOT IN (SELECT id FROM users);
#ALTER TABLE srv_userbase ADD CONSTRAINT fk_srv_userbase_admin_id FOREIGN KEY ( admin_id ) REFERENCES users( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

# srv_userbase_invitations nima nic

DELETE FROM srv_userbase_respondents WHERE list_id NOT IN (SELECT id FROM srv_userbase_respondents_lists);
ALTER TABLE srv_userbase_respondents ADD CONSTRAINT fk_srv_userbase_respondents_list_id FOREIGN KEY ( list_id ) REFERENCES srv_userbase_respondents_lists( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

# srv_userbase_respondents_lists nima nic

DELETE FROM srv_userbase_setting WHERE ank_id NOT IN (SELECT id FROM srv_anketa);
ALTER TABLE srv_userbase_setting ADD CONSTRAINT fk_srv_userbase_setting_ank_id FOREIGN KEY ( ank_id ) REFERENCES srv_anketa( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_userstatus WHERE usr_id NOT IN (SELECT id FROM srv_user);
ALTER TABLE srv_userstatus ADD CONSTRAINT fk_srv_userstatus_usr_id FOREIGN KEY ( usr_id ) REFERENCES srv_user( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_user_grupa WHERE gru_id NOT IN (SELECT id FROM srv_grupa);
ALTER TABLE srv_user_grupa ADD CONSTRAINT fk_srv_user_grupa_gru_id FOREIGN KEY ( gru_id ) REFERENCES srv_grupa( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_user_grupa WHERE usr_id NOT IN (SELECT id FROM srv_user);
ALTER TABLE srv_user_grupa ADD CONSTRAINT fk_srv_user_grupa_usr_id FOREIGN KEY ( usr_id ) REFERENCES srv_user( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_user_grupa_active WHERE gru_id NOT IN (SELECT id FROM srv_grupa);
ALTER TABLE srv_user_grupa_active ADD CONSTRAINT fk_srv_user_grupa_active_gru_id FOREIGN KEY ( gru_id ) REFERENCES srv_grupa( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_user_grupa_active WHERE usr_id NOT IN (SELECT id FROM srv_user);
ALTER TABLE srv_user_grupa_active ADD CONSTRAINT fk_srv_user_grupa_active_usr_id FOREIGN KEY ( usr_id ) REFERENCES srv_user( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

#DELETE FROM srv_user_setting WHERE usr_id NOT IN (SELECT id FROM users);
#ALTER TABLE srv_user_setting ADD CONSTRAINT fk_srv_user_setting_usr_id FOREIGN KEY ( usr_id ) REFERENCES users( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_user_setting_for_survey WHERE sid NOT IN (SELECT id FROM srv_anketa);
ALTER TABLE srv_user_setting_for_survey ADD CONSTRAINT fk_srv_user_setting_for_survey_sid FOREIGN KEY ( sid ) REFERENCES srv_anketa( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

# srv_user_setting_for_survey : uid (lahko je 0)

#DELETE FROM srv_user_setting_misc WHERE uid NOT IN (SELECT id FROM users);
#ALTER TABLE srv_user_setting_misc ADD CONSTRAINT fk_srv_user_setting_misc_uid FOREIGN KEY ( uid ) REFERENCES users( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

DELETE FROM srv_variable_profiles WHERE sid NOT IN (SELECT id FROM srv_anketa);
ALTER TABLE srv_variable_profiles ADD CONSTRAINT fk_srv_variable_profiles_sid FOREIGN KEY ( sid ) REFERENCES srv_anketa( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

# srv_variable_profiles : uid (lahko je 0)

DELETE FROM srv_vrednost WHERE spr_id NOT IN (SELECT id FROM srv_spremenljivka);
ALTER TABLE srv_vrednost ADD CONSTRAINT fk_srv_vrednost_spr_id FOREIGN KEY ( spr_id ) REFERENCES srv_spremenljivka( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

# srv_vrednost : if_id je referenca na srv_if id, ampak je veckrat, vprasanje ce je kul, ce se nastavi tukaj fk..

DELETE FROM srv_zanka_profiles WHERE sid NOT IN (SELECT id FROM srv_anketa);
ALTER TABLE srv_zanka_profiles ADD CONSTRAINT fk_srv_zanka_profiles_sid FOREIGN KEY ( sid ) REFERENCES srv_anketa( id ) ON DELETE CASCADE ON UPDATE CASCADE ;

# srv_zanka_profiles : uid (lahko je 0)




#################################

# komentarji na zgornje zadeve :

#################################


# reference na tabelo users (!pazi! to NI tabela srv_user - tam je vse ok)
#  - prvi problem je, da se pri vecini tabel uporablja tudi 0 pri FK (resitev bi bila, da se doda userja z IDjem 0 (ne najbolsa) oz. da se popravi vse 0 pri FK na NULL (potrebni popravki v kodo!))
#  - drugi problem je, da je users na www1kasi MyISAM (ne vem zakaj, jaz jo imam na InnoDB)
#  zato se tabele users zaenkrat ne bo referenciralo (zakomentirano tam, kjer ni problem z 0, komentar (uid 0) tam kjer je se ta problem)
