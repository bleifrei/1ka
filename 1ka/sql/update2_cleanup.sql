# db cleanup

# ta skripta naj bi pocistila bazo in odstranila odvecne zapise (npr. vse childe brez parentov)
# zaenkrat se ni dovolj pretestirano, da se ne zbrise slucajno kaj prevec, tako da use with care!
# nebi skodilo, ce bi vsak preveril za svoje stvari, ki jih je dodajal da kaj ne pobrise (npr. kaksni 0 za foreign keye ipd)

# prosim brez tabov ker mi naredi darkota in markota v konzoli.

# stvari, ki se pobrisejo in postavijo v update2_FK.sql so zakomentirane. ostalo je se vedno tukaj

#//DELETE FROM srv_grupa WHERE ank_id NOT IN (SELECT id FROM srv_anketa);
#//DELETE FROM srv_alert WHERE ank_id NOT IN (SELECT id FROM srv_anketa);
#//DELETE FROM srv_tracking WHERE ank_id NOT IN (SELECT id FROM srv_anketa);
#//DELETE FROM srv_dostop WHERE ank_id NOT IN (SELECT id FROM srv_anketa);
#//DELETE FROM srv_user WHERE ank_id NOT IN (SELECT id FROM srv_anketa);

# gru_id=0 je za knjiznico
#//DELETE FROM srv_spremenljivka WHERE gru_id NOT IN (SELECT id FROM srv_grupa) AND gru_id > 0;
#//DELETE FROM srv_user_grupa WHERE gru_id NOT IN (SELECT id FROM srv_grupa);
#//DELETE FROM srv_user_grupa WHERE usr_id NOT IN (SELECT id FROM srv_user);
#//DELETE FROM srv_vrednost WHERE spr_id NOT IN (SELECT id FROM srv_spremenljivka);
#//DELETE FROM srv_data_vrednost WHERE spr_id NOT IN (SELECT id FROM srv_spremenljivka);

# vre_id<=0 imajo posebne vrednosti -1 do -4
#//DELETE FROM srv_data_vrednost WHERE vre_id NOT IN (SELECT id FROM srv_vrednost) AND vre_id>0;
#//DELETE FROM srv_data_vrednost WHERE usr_id NOT IN (SELECT id FROM srv_user);
#//DELETE FROM srv_grid WHERE spr_id NOT IN (SELECT id FROM srv_spremenljivka);
#//DELETE FROM srv_data_grid WHERE spr_id NOT IN (SELECT id FROM srv_spremenljivka);

# vre_id<=0 imajo posebne vrednosti -1 do -4
DELETE FROM srv_data_grid WHERE vre_id NOT IN (SELECT id FROM srv_vrednost) AND vre_id>0;            # tale se ni v FK
#//DELETE FROM srv_data_grid WHERE usr_id NOT IN (SELECT id FROM srv_user);

# za tega nism cist 100% (grd_id je lahko -1 do -4)
DELETE FROM srv_data_grid WHERE (grd_id, spr_id) NOT IN (SELECT id, spr_id FROM srv_grid) AND grd_id > 0;  # tale se ni v FK

# spr_id=0 imajo komentarji na spremenljivko
#//DELETE FROM srv_data_text WHERE spr_id NOT IN (SELECT id FROM srv_spremenljivka) AND spr_id>0;
#//DELETE FROM srv_data_text WHERE usr_id NOT IN (SELECT id FROM srv_user);

# komentarji na spremenljivko imajo v vre_id ID spremenljivke!
DELETE FROM srv_data_text WHERE vre_id NOT IN (SELECT id FROM srv_vrednost) AND vre_id>0 AND vre_id NOT IN (SELECT id FROM srv_spremenljivka);  # tale ni v FK
#//DELETE FROM srv_data_checkgrid WHERE spr_id NOT IN (SELECT id FROM srv_spremenljivka);

# vre_id<=0 imajo posebne vrednosti -1 do -4
#//DELETE FROM srv_data_checkgrid WHERE vre_id NOT IN (SELECT id FROM srv_vrednost) AND vre_id>0;
#//DELETE FROM srv_data_checkgrid WHERE usr_id NOT IN (SELECT id FROM srv_user);
#//DELETE FROM srv_userstatus WHERE usr_id NOT IN (SELECT id FROM srv_user);
#//DELETE FROM srv_branching WHERE ank_id NOT IN (SELECT id FROM srv_anketa);

# so ifi slucajno se kje??
DELETE FROM srv_if WHERE id NOT IN (SELECT element_if FROM srv_branching WHERE element_if > 0) AND id NOT IN (SELECT if_id FROM srv_filter_profiles WHERE if_id > 0) AND id NOT IN (SELECT if_id FROM srv_vrednost WHERE if_id > 0);  # tale ni v FK

# ce ni vec spremenljivke ali vrednosti iz condition ne brisemo
#//DELETE FROM srv_condition WHERE if_id NOT IN (SELECT id FROM srv_if) AND if_id>0;
#//DELETE FROM srv_condition_vre WHERE cond_id NOT IN (SELECT id FROM srv_condition);
#//DELETE FROM srv_condition_grid WHERE cond_id NOT IN (SELECT id FROM srv_condition);


