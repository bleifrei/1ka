
# cccc - &#269;
# ssss - &#353;
# zzzz - &#382;
# CCCC - &#268;
# SSSS - &#352;
# ZZZZ - &#381;


## Backup stare tabele
ALTER TABLE srv_help RENAME srv_help_old;

## Ustvarimo novo help tabelo
CREATE TABLE srv_help LIKE srv_help_old;

#TRUNCATE TABLE srv_help;


INSERT INTO srv_help (what, lang, help) VALUES ('DataPiping', '1', '&#268;e respondent pri vpra&#353;anju npr. Q1 (npr. Katero je va&#353;e najljub&#353;e sadje) odgovori npr. "jabolka", lahko to vklju&#269;imo v vpra&#353;anje Q2, npr. "Kako pogosto kupujete #Q1# na tr&#382;nici?

Pri tem je treba upo&#353;tevati:

    * Vpra&#353;anje Q2, ki vklju&#269;i odgovor, mora biti na naslednji strani,
    * Ime spremenljivke, ki se prena&#353;a (Q1) je treba spremeniti, ker je lahko predmet avtomatskega pre&#353;tevil&#269;enja, npr. spremenimo "Q1" v "SADJE"');
INSERT INTO srv_help (what, lang, help) VALUES ('DataPiping', '2', 'if the respondent in the question e.g. Q1 (e.g. what is your favorite fruit) answers e.g. "apples", this can be included in question Q2, e.g. "How often do you buy #Q1# at the market?

The following must be taken into account:

    * Question Q2, which includes the answer, should be on the next page,
    * The name of the variable to be transmitted (Q1) needs to be changed because it can be subject to automatic renumbering, e.g. change "Q1" to "FRUIT"');
INSERT INTO srv_help (what, lang, help) VALUES ('displaychart_settings', '1', 'If a respondent\'s answer from a question, i.e. Q1 ("Which fruit is your favourite?") is "apple", we can include this to a subsequent question (i.e. Q2) with the #Q1# command, i.e. "How often do you by #Q1# at the market?"
 
It should be taken into account:

     * Question Q2, which includes the answer, should be on the next page
     * The name of the variable that is being piped (Q1) should be renamed because it may be subject to automatic enumeration, i.e. change "Q1" to "FRUIT" ');
INSERT INTO srv_help (what, lang, help) VALUES ('displaydata_checkboxes', '1', '<p>Kadar je izbrana opcija <b>"Podatki"</b>, se v tabeli prikazujejo podatki respondentov.</p>
<ul><b>"Status"</b> prikazuje kon&#269;ne statuse enot:
<li>6 - kon&#269;al anketo</li>
<li>5 - delno izpolnjena</li>
<li>4 - klik na anketo</li>
<li>3 - klik na nagovor</li>
<li>2 - epo&#353;ta - napaka</li>
<li>1 - epo&#353;ta - neodgovor</li>
<li>0 - epo&#353;ta - ni poslana)</li>
<li>lurker - prazna anketa (1 = da, 0 = ne)</li>
<li>Zaporedna &#353;tevilka vnosa</li>
</ul>
<p>Kadar je izbrana opcija <b>"Parapodatki"</b> prikazujemo meta podatke uporabnika: datum vnosa, datum popravljanja, &#269;ase po straneh, IP, JavaScript, podatke brskalnika, jezik.</p>
<p>Kadar je izbrana opcija <b>"Identifikatorji"</b> prikazujemo sistemske podatke respondenta, ki so bili vne&#353;eni preko sistema za po&#353;iljanje vabil: ime, priimek, email itd.</p>');
INSERT INTO srv_help (what, lang, help) VALUES ('displaydata_checkboxes', '2', '<p>If you select the "<b>Data</b>" option, the data of the respondents will be displayed in the table.</p>
<ul><b>"Status" option:</b>
<li>6 - survey completed</li>
<li>5 - partially completed</li>
<li>4 - entered first page</li>
<li>3 - click on the intro</li>
<li>2 - email - error</li>
<li>1 - email - non-response</li>
<li>0 - Email - not sent)</li>
<li>Lurker - empty survey (1 = yes, 0 = no)</li>
<li>Record number</li>
</ul>
<p>If you select the "<b>Para data</b>" option, users" para data will be displayed in the table: insert date, edit date, time per page, IP, browser, JavaScript etc.</p>
<p>If you select the "<b> System data</b>" option, the table will display respondents" system information, such as name, surname, email etc.</p>');
INSERT INTO srv_help (what, lang, help) VALUES ('displaydata_data', '1', 'Kadar je opcija izbrana, se v tabeli prika&#382;ejo podatki respondentov');
INSERT INTO srv_help (what, lang, help) VALUES ('displaydata_meta', '1', 'Priak&#382;e meta podatke uporabnika: datum vnosa, datum popravljanja, &#269;ase po straneh, IP, JavaScript, podatke brskalnika');
INSERT INTO srv_help (what, lang, help) VALUES ('displaydata_pdftype', '1', 'Raz&#353;irjen izpis pomeni izpis oblike, kakr&#353;ne je vpra&#353;alnik, skr&#269;en izpis pa izpi&#353;e vpra&#353;alnik z rezultati v skraj&#353;ani obliki. <span class="qtip-more"><a href="https://www.1ka.si/d/sl/pomoc/prirocniki/nastavitve-izvoza-pdfrtf-datotek-z-odgovori" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('displaydata_pdftype', '2', 'Long display means the display in a form of the questionnaire, a short version means that questionnaire results will be presented in an abbreviated form.');
INSERT INTO srv_help (what, lang, help) VALUES ('displaydata_status', '1', '<p>status (6-kon&#269;al anketo, 5-delno izpolnjena, 4-klik na anketo, 3-klik na nagovor, 2-epo&#353;ta-napaka, 1-epo&#353;ta-neodgovor, 0-epo&#353;ta-ni poslana)</p><p>lurker - prazna anketa (1 = da, 0 = ne)</p><p>Zaporedna &#353;tevilka vnosa</p>');
INSERT INTO srv_help (what, lang, help) VALUES ('displaydata_system', '1', 'Prika&#382;e sistemske podatke respondenta: ime, priimek, email...');
INSERT INTO srv_help (what, lang, help) VALUES ('dodaj_searchbox', '1', 'V zemljevid vklju&#269;i tudi iskalno okno, preko katerega lahko respondent tudi opisno poi&#353;&#269;e lokacijo na zemljevidu');
INSERT INTO srv_help (what, lang, help) VALUES ('edit_date_range', '1', 'Datum lahko navzdol omejimo z letnico, naprimer: 1951 ali kot obdobje -70, kar pomeni zadnjih 70 let. Podobno lahko omejimo datum tudi navzgor. Naprimer: 2013 ali kot obdobje +10, kar pomeni naslednjih 10 let');
INSERT INTO srv_help (what, lang, help) VALUES ('edit_date_range', '2', 'The date can be limited downward with a year, 1951 or as a period -70, which means the last 70 years. Similarly, we can also limit the date upward. For example: 2013 or as a period +10, which means the next 10 years.');
INSERT INTO srv_help (what, lang, help) VALUES ('edit_variable', '1', 'Tu lahko poljubno spremenite privzeto ime spremenljivke, kar se upo&#353;teva tudi pri kasnej&#353;em izvozu podatkov. Paziti morate, da se imena spremenljivk ne podvajajo. ');
INSERT INTO srv_help (what, lang, help) VALUES ('edit_variable', '2', 'You can change the default variable name, which is also taken into account in the subsequent export of data. You should make sure that variable names are not duplicated.');
INSERT INTO srv_help (what, lang, help) VALUES ('exportSettings', '1', 'Kadar izberete "Izvozi samo identifikatorje" se bodo izvozili samo identifikatorji (sistemski podatki repondenta), brez katerikoli drugih podatkov.<br>Kadar pa ne izva&#382;ate identifikatorjev pa lahko izvozite posamezne para podatke respondenta.');
INSERT INTO srv_help (what, lang, help) VALUES ('help-centre', '1', '1KA center za pomo&#269;  lahko pomaga uporabnikom tudi pri konkretni anketi, vendar potrebuje za&#269;asen dostop do ankete, za kar je potrebna va&#353;a oz. avtorjeva odobritev. Seveda pa za&#269;asen dostop velja le za tiste dele ankete, kjer se je te&#382;ava pojavila, in  je potreben za re&#353;itev problema. Dostop omogo&#269;ite  s klikom na povezavo "Dovoli dostop centu za pomo&#269;".<span class="qtip-more"><a href="https://www.1ka.si/db/24/439/Prirocniki/Dostop_do_moje_ankete_za__1KA_center_za_pomoc_uporabnikom/?&cat=309&p1=226&p2=735&p3=867&p4=0&id=867&from1ka=1" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('help-centre', '2', '1KA help centre can help users with a specific survey, but need a temporary access to the survey, which requires your approval. Of course, the temporary access only applies to those parts of the survey where the problem occurred and is necessary to solve the problem. You can enable the access by clicking the "Grant access to help centre".<span class="qtip-more"><a href="http://english.1ka.si/index.php?fl=2&lact=1&bid=438&parent=24" target="_blank">Read more</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('individual_invitation', '1', 'Z individualiziranimi vabili lahko preverite, kdo iz seznama je odgovoril na anketo in kdo ne, kar je podlaga za po&#353;iljanje opomnikov. <span class="qtip-more"><a href="https://www.1ka.si/db/19/435/Pogosta%20vprasanja/Sledenje_respondentom__prednost_ali_slabost/?&cat=270&p1=226&p2=735&p3=789&p4=793&p5=804&id=804&cat=270&page=1&from1ka=1" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('inv_recipiens_from_system', '1', 'Prejemniki bodo dodani iz obstoje&#269;ih podatkov v bazi, pri &#269;emer mora vpra&#353;alnik vsebovati sistemsko spremenljivko email.');
INSERT INTO srv_help (what, lang, help) VALUES ('inv_recipiens_from_system', '2', 'Recipients will be added from the existing data in the database, which means, that the questionnaire must include a system variable - email.');
INSERT INTO srv_help (what, lang, help) VALUES ('marker_podvprasanje', '1', 'V obla&#269;ek markerja dodaj podvpra&#353;anje');
INSERT INTO srv_help (what, lang, help) VALUES ('naslov_podvprasanja_map', '1', 'Besedilo podvpra&#353;anja v obla&#269;ku markerja');
INSERT INTO srv_help (what, lang, help) VALUES ('spremenljivka_reminder', '1', 'V primeru, da respondent ni odgovoril na predvideno vpra&#353;anje, imamo tri mo&#382;nosti:
<UL>
<LI><b>Brez opozorila </b> pomeni, da respondenti lahko, tudi &#269;e ne odgovorijo na dolo&#269;eno vpra&#353;anje, brez opozorila nadaljujejo z anketo.</LI>
<LI><b>Trdo opozorilo </b> pomeni, da respondenti, &#269;e ne odgovorijo na vpra&#353;anje s trdim opozorilom, dobijo obvestilo, da ne moreo nadaljevati z re&#353;evanjem ankete.</LI>
<LI><b>Mehko opozorilo </b> pomeni, da respondenti, &#269;e ne odgovorijo, dobijo opozorilo, vendar lahko kljub temu nadaljujejo z re&#353;evanjem.</LI>
</UL>');
INSERT INTO srv_help (what, lang, help) VALUES ('spremenljivka_sistem', '1', 'S klikanjem na nastavitve v lahko zbiramo med dvema vrstama integracije vpra&#353;anja v anketo:
<UL>
<LI><b>Navadno</b> vpra&#353;anje,</LI>
<LI><b>Sistemsko</b> vpra&#353;anje, ki omogo&#269;a uporabo vpra&#353;anja tudi izven samega vpra&#353;alnika. Gre za dva vidika:
(1) sistemsko vpra&#353;anje (npr. ime) lahko ozna&#269;ite in uporabite, tako da nastopa v elektronskem obvestilu respondentu, kjer spremenljivko z njegovim imenom uporabimo v elektronskem sporo&#269;ilu, da se mu zahvalimo ali ga obvestimo o drugem valu anketiranja,
(2) sistemsko vpra&#353;anje lahko neposredno uvozimo v bazo VNOSI mimo anketnega vpra&#353;alnika. Tako npr. lahko vnesemo ali nalo&#382;imo datoteko s telefonski &#353;tevilkami ali emaili respondentov (v takem primeru bomo spremenljivko ozna&#269;ili tudi kot skrito, saj respondentu ni treba vna&#353;ati emaila).</LI>
</UL>
V primeru uporabe email vabil preko 1KA email sistema, mora biti spremenljivka "email" ozna&#269;ena kot sistemska, ne glede, &#269;e je email vnesel respondent sam ali pa ga je pred anketiranjem vnesel administrator.');
INSERT INTO srv_help (what, lang, help) VALUES ('spremenljivka_visible', '1', 'S klikanjem na nastavitve vidnosti lahko zbiramo med dvema vrstama integrcije vpra&#353;anja v anketo:
<UL>
<LI><b>vidno</b> vpra&#353;anje, ki bo vidno respondentom v kon&#269;nem vpra&#353;alniku,
<LI><b>skrito</b> vpra&#353;anje, ki bo vidno le avtorju v urejanju vpra&#353;alnika. Mo&#382;nost uporabimo bodisi za skriti nagovor bodisi za sistemsko spremenljivko e-mail, ki je respondentom ni potrebno izpolniti.
');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_aapor_link', '1', 'AAPOR kalkulacije');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_activity_quotas', '1', 'Pri anketi lahko dolo&#269;ite kvoto (omejite &#353;tevilo odgovorov). Kvoto lahko postavite za vse odgovore ali samo na ustrezne enote. Ko bo na anketo odgovorilo toliko respondentov, kot ste dolo&#269;ili, se bo anketa deaktivirala za izpolnjevanje.

<span class="qtip-more"><a href="https://www.1ka.si/d/sl/pomoc/prirocniki/trajanje-ankete-glede-na-datum-ali-stevilo-odgovorov-kvote" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_activity_quotas_valid', '1', '<ul>Ustrezne enote: 
<li>delno izpolnjene ankete (status 5)</li>
<li>kon&#269;al anketo (status 6)</li>
</ul>
<ul>
Ostale enote:
<li>kliki na nagovor (status 3)</li>
<li>kliki na anketo (status 4)</li>
</ul>
<span class="qtip-more"><a href="https://www.1ka.si/d/sl/pomoc/prirocniki/statusi-enot-ustreznost-veljavnost-manjkajoce-vrednosti" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_alert_show_97', '1', 'Funkcija prikaz "Neustrezno" ob opozorilu, da se respondentu prika&#382;e odgovor "Neustrezno" &#353;ele po tem, ko ta ni odgovoril na vpra&#353;anje. Vpra&#353;anje mora biti obvezno ali imeti opozorilo.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_alert_show_98', '1', 'Funkcija prikaz "Zavrnil" ob opozorilu, da se respondentu prika&#382;e odgovor "Zavrnil" &#353;ele po tem, ko ta ni odgovoril na vpra&#353;anje. Vpra&#353;anje mora biti obvezno ali imeti opozorilo.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_alert_show_99', '1', 'Funkcija prikaz "Ne vem" ob opozorilu, da se respondentu prika&#382;e odgovor "Ne vem" &#353;ele po tem, ko ta ni odgovoril na vpra&#353;anje. Vpra&#353;anje mora biti obvezno ali imeti opozorilo. ');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_block_ip', '1', 'Tu lahko blokirate respondentov ponovni vnos vpra&#353;alnika, glede na minute (10, 20 ali 60 minut) ali glede na ure (12 ali 24 ur).<span class="qtip-more"><span class="qtip-more"><a href="https://www.1ka.si/d/sl/pomoc/prirocniki/uporaba-ip-naslovov-piskotkov-za-nadzor-nad-podvojenimi-vnosi" target="_blank">Preberi ve&#269;</a></span></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_block_ip', '2', 'Here you can block the responent\'s attempt to re-take the questionnare for 10, 20 or 60 minutes, or for 12 or 24 hours. <span class="qtip-more"><a href="https://www.1ka.si/d/en/help/manuals/using-the-ip-address-and-cookies-to-control-duplicate-entries" target="_blank">Read more</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_block_random', '1', 'Randomizacija vsebine bloka');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_branching_expanded', '1', 'Skr&#269;en pogled vpra&#353;anj omogo&#269;a bolj&#353;i pregled nad celotnim vpra&#353;alnikom in njegovo strukturo- prelomi strani, bloki, pogoji, zankami itd.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_branching_popup', '1', 'Nastavitev prikaza map v odprtem na&#269;inu (en stolpec) ali pa v zamaknjenem na&#269;inu (ve&#269; vzporednih stolpcev). ');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_branching_popup', '2', 'You can set the display of maps in open mode (one column) or in offset mode (several parallel columns).');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_calculation_missing', '1', 'Missing kot 0');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_choose_skin', '1', 'Tu lahko izberete eno od vnaprej pripravljenih vizualnih predlog ankete. Kasneje, ko je anketa &#382;e ustvarjena, lahko v nastavitvah to predlogo poljubno spreminjate in jo tudi dodatno prilagodite (npr. vrsta in velikost pisave, barva ozadja itd.). <span class="qtip-more"><a href="https://www.1ka.si/c/849/Oblika/?preid=849&from1ka=1" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_choose_skin', '2', 'You can select one of the pre-prepared visual survey designs. Later, when the survey has already been created, you can select a different design and customize it further (e.g. font type and size, background color, etc.). <span class="qtip-more"><a href=" http://english.1ka.si/c/849/Design/?preid=792" target="_blank">Read more</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_collect_all_status_0', '1', '<ul>Statusi ustrezni so:
<li>[6] Kon&#269;al anketo</li>
<li>[5] Delno izpolnjena</li>
</ul>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_collect_all_status_1', '1', '<ul>
<li>[6] Kon&#269;al anketo</li>
<li>[5] Delno izpolnjena</li>
<li>[4] Klik na anketo</li>
<li>[3] Klik na nagovor</li>
<li>[2] Napaka pri po&#353;iljanju e-po&#353;te</li>
<li>[1] E-po&#353;ta poslana (neodgovor)</li>
<li>[0] E-po&#353;ta &#353;e ni bila poslana</li>
<li>[-1] Neznan status</li>
</ul>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_collect_data_setting', '1', '<p>Generiranje tabele s podatki:</p>
<p>S poljem "le ustrezni" izbiramo med statusi enot, ki se bodo generirali kot potencialni za analize, izvoz podatkov in prikaz vnosov. Kadar je polje "le ustrezni" izbrano se upo&#353;tevajo samo enote z statusom: 6 - Kon&#269;al anketo in 5 - Delno izpolnjena.</p>
<p>S poljem "meta podatki" izbiramo ali se generirajo tudi meta podatki kot so: lastnosti ra&#269;unalnika, podrobni podatki o e-po&#353;tnih vabilih in telefonskih klicih.</p>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_collect_data_setting', '2', '<p>Generate table data:</p>
<p>With "only valid status" checkbox we choose between the status of the units that will be generated as the potential for analysis, data export and entries display.</p>
<p>If the "only valid status" field is checked, then only units with status 6 (completed survey) and 5 (partially completed) will be considered.</p> 
<p>With "meta data" field we decide, if meta data, such as computer properties, e-mail invitation details and telephone calls will also be generated.</p>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_comments_only_unresolved', '1', 'Vsa vpra&#353;anja s komentarji se prika&#382;ejo, &#269;e sta obe mo&#382;nosti izklopljeni (tako "Vsa vpra&#353;anja" kot "Samo nere&#353;eni komentarji"). <span class="qtip-more"><a href=" https://www.1ka.si/d/sl/pomoc/prirocniki/komentarji" target="_blank"> Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_comments_only_unresolved', '2', 'Any questions with comments appear if both options are turned off ("All questions" as well as "Display only unresolved comments"). <span class="qtip-more"><a href = "https://www.1ka.si/d/en/help/manuals/comments" target="_blank">Read more</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_concl_deactivation_text', '1', 'Obvestilo pri deaktivaciji');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_concl_PDF_link', '1', 'Na koncu ankete prika&#382;e ikono s povezavo do PDF dokumenta z odgovori respondenta.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_continue_later_setting', '1', 'Funkcija omogo&#269;a respondentu, da prekine z odgovarjanjem na anketo ter nadaljuje kasneje. Respondent vpi&#353;e svoj email naslov, na katerega prejme URL, preko katerega bo kasneje nadaljeval z odgovarjanjem.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_continue_later_setting', '2', 'This function allows the respondent to terminate survey completion and continue later. A respondent must enter their email address, where they receive a URL with which they continue the survey completion process.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_cookie', '1', 'Pi&#353;kotek (ang. <i>cookie</i>) je koda, ki se instalira v ra&#269;unalnik anketiranca, s &#269;imer lahko 1KA anketiranca identificira tudi pri moreitnem ponovljenem poskusu izpolnjevanja ankete.

-	"Do konca izpolnjevanja ankete" pomeni, da se pi&#353;kotek hrani le v &#269;asu trajanja izpolnjevanja vpra&#353;alnika, ob koncu izpolnjevanja pa se izbri&#353;e. Respondent lahko zato iz istega ra&#269;unalnika neovirano izpolnjuje anketo &#353;e enkrat.

-	"Do konca seje" pomeni, da se pi&#353;kotek hrani za &#269;as trajanja seje brskalnika, izbri&#353;e pa se &#353;ele, ko se zapre brskalnik. Respondent se bo v &#269;asu trajanja seje prepoznal in obravnaval bodisi tako, da bo dobil obvestilo, da je anketa zanj nedostopna, ker jo je &#382;e izpolnil, bodisi pa jo bo lahko popravil. Ko posameznik zapre brskalnik pa je ob ponovnem zagonu brskalnika obravnavan kot nov respondent.

-	"1 uro" ali "1 mesec": pi&#353;kotek se hrani &#353;e eno uro ali en mesec po zaklju&#269;ku ankete. V tem &#269;asu bo uporabnik ob ponovnem vra&#269;anju na anketo prepoznan in obravnavan v skladu z nadaljnjimi nastavitvami: bodisi bo dobil obvestilo, da je anketa zanj nedostopna, ker jo je &#382;e izpolnil, bodisi jo bo lahko popravljal.

<span class="qtip-more"><a href="https://www.1ka.si/d/sl/o-1ka/pogoji-uporabe-storitve-1ka/politika-piskotkov" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_cookie', '2', 'A cookie is a code that is stored on the respondent\'s computer, through which the 1KA tool can authenticate a possible re-attempt of the respondent to fill out the survey.
- "Until the end of questionnaire" means that the cookie is only stored while the respondent is filling out the questionnaire. After the survey is completed, the cookie is deleted. Therefore, the user can start the survey again from the same device. 

- "Until the end of the browser session" means that the cookie is stored for the duration of the browser session, i.e. also after the survey is completed. It is deleted when the respondent closes the current browser session. If the respondent returns to the survey page during the browser session they will be recognised in accordance with the following settings: they will either recieve a notification that the survey cannot be accessed because it has already been filled out or the survey can be altered by the respondent. Once the respondent closes the browser session they will be treated as a new respondent.

- "1 hour" or "1 month": the cookie is saved for one hour (or one month) after the survey is completed. During this time the respondent will be recognized when they return to the page in accordance with the following settings: they will either recieve a notification that the survey cannot be accessed because it has already been filled out or the survey can be altered by the respondent.


<span class="qtip-more"><a href="https://www.1ka.si/d/en/about/terms-of-use/cookie-policy" target="_blank">Read more</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_cookie_continue', '1', '&#268;e je vklopljena omejitev, da uporabnik ne morenadaljevati z izpolnjevanjem ankete brez sprejetja pi&#353;kotka, mora biti v anketi obvezno prikazan uvod.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_cookie_return', '1', '&#268;e &#382;elite, da respondent ob vrnitvi na anketo ponovno pri&#269;ne z re&#353;evanjem celotne ankete, potem ozna&#269;ite to mo&#382;nost. 

<span class="qtip-more"><a href="https://www.1ka.si/d/sl/pomoc/prirocniki/nastavitve-za-dostop-respondentov-piskotki-ip-naslovi-gesla-sistemsko-prepoznavanja" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_cookie_return', '2', 'Select this option if you wish that the respondent starts filling out the survey from the beginning when they return. <span class="qtip-more"><a href="https://www.1ka.si/d/en/help/manuals/settings-for-respondent-access-cookies-and-passwords" target="_blank">Read more</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_create_form', '1', 'Forma je enostavna anketa na samo eni strani (npr. obrazec, kratka anketa, registracija, email lista, prijava na dogodke itd.).

<span class="qtip-more"><a href="https://www.1ka.si/d/sl/o-1ka/splosen-opis/tipi-vprasalnikov/forme" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_create_form', '2', 'A form is a simple survey on a single page (e.g. form, short survey, registration form, mailing list,  event registration...). <span class="qtip-more"><a href=" http://english.1ka.si/c/828/Forms/?preid=879" target="_blank">Read more> </a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_create_poll', '1', 'Glasovanje je anketa z enim samim vpra&#353;anjem (peticija, volitve, potrditve, dolo&#269;eno mnenje/strinjanje itd.). <span class="qtip-more"><a href="https://www.1ka.si/d/sl/o-1ka/splosen-opis/tipi-vprasalnikov/glasovanje" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_create_poll', '2', 'Voting is a survey with a single question (petition, elections, confirmation, particular opinion/agreement...) <span class="qtip-more"><a href="http://english.1ka.si/c/835/Voting/?preid=828" target="_blank">Read more></a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_create_survey', '1', 'Anketa je splo&#353;en, poljuben vpra&#353;alnik, ki je lahko enostaven ali kompleksen (npr. pogoji, bloki, kvizi, testi, email vabila, telefonska anketa itd.). <span class="qtip-more"><a href="https://www.1ka.si/d/sl/o-1ka/splosen-opis/tipi-vprasalnikov/ankete" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_create_survey', '2', 'A survey is a general, arbitrary questionnaire, which can be simple or complex (e.g. conditions, blocks, quizzes, tests, email invitations, telephone surveys, etc.). <span class="qtip-more"><a href="http://english.1ka.si/c/879/Survey/?preid=835" target="_blank">Read more</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_create_survey_from_text', '1', 'Besedilo vpra&#353;anj in odgovorov prilepite oziroma vpi&#353;ete v kvadrat na levi strani, vzporedno pa se na desni strani prikazuje predogled vpra&#353;anj. <span class="qtip-more"><a href="https://www.1ka.si/d/sl/pomoc/prirocniki/uvoz-besedila-kopiranje-besedila-1ka" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_creport', '1', 'V prilagojenem poro&#269;ilu lahko:<ul><li>naredite poljuben izbor spremenljivk</li><li>jih urejate v poljubnem vrstnem redu</li><li>kombinirate grafe, frekvence, povpre&#269;ja...</li><li>dodajate komentarje</li></ul><span class="qtip-more"><a href="http://www.1ka.si/db/19/427/Pogosta%20vpra.anja/Porocila_po_meri/?&cat=286&p1=226&p2=735&p3=789&p4=794&p5=865&id=865" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_crosstab_inspect', '1', 'Mo&#382;nost "ZOOM" oz. "Kdo je to" omogo&#269;a da s klikom na &#382;eleno frekvenco, ogledamo katere enote se v njej nahajajo.<span class="qtip-more"><a href="https://www.1ka.si/db/24/338/Prirocniki/ZOOM/?&cat=309&p1=226&p2=735&p3=867&p4=0&id=867" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_crosstab_inspect', '2', 'Option "ZOOM" or "Who is this" allows, that with a click on the desired frequency, you can see all units (with all of the answers) in a given cell. <span class="qtip-more"><a href="http://english.1ka.si/db/24/338/Guides/ZOOM/?&p1=226&p2=735&p3=0&p4=0&id=735" target="_blank">Read more</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_crosstab_residual', '1', 'Obarvane celice - glede na prilagojene vrednosti rezidualov (Z) - ka&#382;ejo, ali in koliko je v celici ve&#269; ali manj enot v primerjavi z razmerami, ko celici nista povezani. Bolj temna barva (rdeca ali modra) torej pomeni, da se v celici nekaj dogaja. Natan&#269;ne vrednosti residualov dobimo, &#269;e tako izberemo v NASTAVITVAH. Nadaljnje podrobnosti o izra&#269;unavanju in interpetaciji rezidualov najdete v priro&#269;niku<span class="qtip-more"><a href="https://www.1ka.si/d/sl/pomoc/prirocniki/reziduali-tabelah" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_crosstab_residual', '2', 'The 1KA application uses and colours the values 1.0, 2.0 and 3.0 for values of adjusted residuals, which roughly signal the strength of the correlation in a particular cell, i.e. the strength of deviation from the assumptions of the null hypothesis. <span class="qtip-more"><a href="https://www.1ka.si/d/en/help/manuals/residuals-tables" target="_blank">Preberi ve&#269;</a></span> ');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_crosstab_residual2', '1', 'Reziduali omogo&#269;ajo izredno enostavno in u&#269;inkovito analizo dogajanja v tabeli, saj natan&#269;no poka&#382;ejo, kje to&#269;no prihaja do povezanosti med spremenljivkami. <span class="qtip-more"><a href="https://www.1ka.si/d/sl/pomoc/prirocniki/reziduali-tabelah" target="_blank">Preberi ve&#269;</a></span> ');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_data_filter', '1', 'Zbrane podatke lahko filtrirate glede na spremenljivke, statuse, pogoje ali obdobje.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_data_filter', '2', 'Data can be filtered according to variables, statuses, conditions, or time periods.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_data_onlyMySurvey', '1', 'Kadar anketo resujete kot uporabnik Sispleta in imate vklopljeno opcijo da anketa prepozna respondenta iz CMS, lahko z enostavnim klikom pregledate le vase ankete.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_data_onlyMySurvey', '2', 'When you fill out a survey as a Sisplet user and the "Recognize respondents from CMS" option is on, you can browse through only your survey with a simple click.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_data_only_valid', '1', 'Ustrezne enote so tiste ankete, kjer je respondent odgovoril vsaj na eno vpra&#353;anje. V vseh analizah so privzeto vklju&#269;ene le ustrezne enote. Ostale - za vsebinske analize neustrezne enote - namre&#269; vklju&#269;ujejo prazne ankete (npr. anketirance, ki so zgolj kliknili na nagovor) in so zanimive predvsem za analizo procesa odgovarjanja - njihov sumarni pregled pa je v zavihku STATUS.
<span class="qtip-more"><a href="https://www.1ka.si/d/sl/pomoc/prirocniki/statusi-enot-ustreznost-veljavnost-manjkajoce-vrednosti" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_data_only_valid', '2', 'Valid units are those surveys where the respondent filled out at least one question. Only valid units are included by default in all analyses. Other units - invalid for analysis of content - include empty surveys (for example, when somebody only clicked on the introduction) and are mainly only of interest in the context of analysis of response process. Their summary review is in the DASHBOARD tab. <span class="qtip-more"><a href = "https://www.1ka.si/d/en/help/manuals/status-of-units-relevance-validity-and-missing-values" target="_blank">Read more</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_data_print_preview', '1', 'V hitrem seznamu je izpisanih prvih pet spremenljivk. Primeren je za hiter izpis <a href="https://www.1ka.si/d/sl/pomoc/prirocniki/prijavnica" target="blank">prijavnic</a> in <a href="https://www.1ka.si/d/sl/o-1ka/splosen-opis/tipi-vprasalnikov/forme" target="_blank">form</a>. Za podrobne izpise uporabite obstoje&#269;e izvoze. Dodaten izbor spremenljivk lahko naredite v opciji "Spremenljivke". <span class="qtip-more"><a href="https://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/podatki/pregledovanje/?from1ka=1=" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_data_print_preview', '2', 'The "Quick list" option displays a list of responses for the first five questions. It is suitable for a quick display of <a href="https://www.1ka.si/d/en/help/manuals/registration-form" target="_blank"> registration forms</a> and <a href="https://www.1ka.si/d/en/about/general-description/questionnaire-types/forms" target="_blank"> forms</a>, which you can also export. You can determine which variables the list should use with "Variables" option. <span class="qtip-more"><a href="https://www.1ka.si/d/en/help/user-guide/data/browse" target="_blank">Read more</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_diag_complexity', '1', '<ul>Kompleksnost:
<li>brez pogojev ali blokov => zelo enostavna anketa</li>
<li>1 pogoj ali blok => enostavna anketa</li>
<li>1-10 pogojev ali blokov => zahtevna anketa</li>
<li>10-50 pogojev ali blokov => kompleksna anketa</li>
<li>ve&#269; kot 50 pogojev ali blokov => zelo kompleksna anketa</li>
</ul>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_diag_time', '1', '<ul>Predviden &#269;as izpolnjevanja::
<li>do 2 min => zelo kratka anketa</li>
<li>2-5 min => kratka anketa</li>
<li>5-10 min => srednje dolga anketa</li>
<li>10-15 min => dolga anketa</li>
<li>15-30 min => zelo dolga anketa</li>
<li>30-45 min => obse&#382;na anketa</li>
<li>ve&#269; kot 45 min => zelo obse&#382;na anketa</li>
</ul>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_disabled_question', '1', 'Vpra&#154;anje je onemogo&#269;eno za respondente');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_dostop', '1', 'Nastavitve glede urejanja ankete. Urejate jo vi kot avtor in ostali, glede na va&#353;e nastavitve.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_dostop_password', '1', 'Generirate lahko ve&#269; gesel in na ta na&#269;in ustvarjate skupine respondentov, ki jih lahko lo&#269;ite pri analizah ali postavite pogoje na vpra&#353;anja. Pomembno je, da opozorite respondenta, da se mu pi&#353;kotek shrani do konca seje brskalnika. 

<span class="qtip-more"><a href="https://www.1ka.si/d/sl/pomoc/prirocniki/nastavitve-za-dostop-respondentov-piskotki-ip-naslovi-gesla-sistemsko-prepoznavanja" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_dostop_password', '2', 'You can generate multiple passwords and thus create respondent groups, which can be separated in the analysis. It is important that you warn the respondents that the browser cookie is saved until the end of the browser session. <span class="qtip-more"><a href="https://www.1ka.si/d/en/help/manuals/settings-for-respondent-access-cookies-and-passwords" target="_blank">Read more</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_dostop_users', '1', 'Seznam uporabnikov, ki lahko urejajo anketo.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_dostop_users', '2', 'The list of users who can edit the survey. ');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_dropdown_quickedit', '1', 'Omogo&#269;a hitro urejanje podatkov v zavihku "PODATKI" s pomo&#269;jo rolete. ');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_dropdown_quickedit', '2', 'Allows you to quickly edit the data in "DATA" tab with the help of a dropdown menu.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_email_server_settings', '1', '<ul>
<li><strong>1KA - privzeto:</strong> vabila po&#353;ljete preko na&#353;ega stre&#382;nika, kjer je po&#353;iljatelj 1KA (info@1ka.si), v polju "Odgovor za" pa je privzeto vpisan email naslov, s katerim ste registrirani na 1KA.</li>
<li><strong>Gmail</strong>: v okviru sistema za po&#353;iljanje 1KA vabila po&#353;ljete prek va&#353;ega Gmail uporabni&#353;kega ra&#269;una.</li>
<li><strong>Lastne SMTP nastavitve</strong>: omogo&#269;a po&#353;iljanje vabil prek lastnega po&#353;tnega stre&#382;nika.</li>
</ul>
<span class="qtip-more"><a href="https://www.1ka.si/db/24/466/Prirocniki/Posiljanje_emailov_preko_poljubnega_streznika_npr_Gmail/?&cat=256&p1=226&p2=735&p3=789&p4=793&p5=0&id=793&from1ka=1" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_email_to_list', '1', 'V seznam za obve&#353;&#269;anje se dodajo tudi ro&#269;no vneseni email naslovi. Za pravilno delovanje tega postopka morate v anketo dodati sistemsko vpra&#353;anje "email", ki mora biti vidno (t.j. da ni v naprednih mo&#382;nostih nastavljeno kot skrito).');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_email_with_data', '1', 'Opcija povezovanje identifikatorjev je na voljo le administratorjem ter omogo&#269;a hkraten prikaz anketnih podatkov in identifikatorjev. Namenjena je predvsem v namene testiranja in interno uporabo na lastnih in&#353;talacijah, zato ponovno opozarjamo na skladnost z zakonom o varstvu osebnih podatkov.  <span class="qtip-more"><a href="https://www.1ka.si/d/sl/pomoc/prirocniki/anketni-podatki-parapodatki-identifikatorji-sistemske-spremenljivke" target="_blank">Preberi ve&#269;</a></span> ');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_embed_fixed', '1', 'Tu lahko skopirate kodo va&#353;e ankete (verzija brez JavaScripta in jo vdelate v va&#353;o spletno stran. Za modifikacijo kode je potrebno spremeniti parameter za vi&#353;ino "height".');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_embed_fixed', '2', 'You can copy the code of your survey (version without JavaScript) and embed it into your own website. To modificate the code you need to change the "height" parameter. ');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_embed_js', '1', 'Tu lahko skopirate kodo va&#353;e ankete (Javascript verzija) in jo vdelate v va&#353;o spletno stran.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_embed_js', '2', 'You can copy the code of your survey (Javascript version) and embed it into your own website.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_export_full_meta', '1', 'Izvozi podatke in parapodatke');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_export_full_meta', '2', 'Export data and paradata');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_gdpr_user_options', '1', 'Prejemanje obvestil o novostih, nadgradnjah in dogodku DSA (Dan spletnega anketiranja). <span class="qtip-more"><a href="https://www.1ka.si/d/sl/dan-spletnega-anketiranja" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_glasovanje_archive', '1', 'Dodaj anketo v arhiv glasovanja. ');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_google_2fa_options', '1', 'Vklop dvo-nivojskega preverjanja pristnosti pomeni, da pri prijavi v orodje 1KA poleg izbranega gesla vpi&#353;ete tudi posebno kodo, ki jo pridobite preko lo&#269;ene aplikacije za generiranje kod.<span class="qtip-more"><a href="https://www.1ka.si/d/sl/o-1ka/pogoji-uporabe-storitve-1ka/politika-piskotkov" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_grid_var', '1', '<p>Vrednosti odgovorov so privzeto razvr&#353;&#269;ene nara&#353;&#269;ajo&#269;e in se pri&#269;nejo z 1. Vrednosti se lahko razvrstijo tudi padajo&#269;e (s klikom na checkbox Razvrsti vrednosti padajo&#269;e).</p>
<ul>Vrednosti odgovorov se lahko spremenijo. Pri tem velja upo&#353;tevati naslednja pravila:
<li>Vrednosti se ne smejo ponavljati (razen v primeru vklopljenega modula Kviz).</li>
<li>Uporabljajo se lahko samo cela &#353;tevila (brez decimalnih &#353;tevil).</li>
<li>Vrednosti -1, -2, -3, -4, -5, -6, -96, -97, -98, -99 so rezervirane za ozna&#269;evanje manjkajo&#269;ih vrednosti in se jih ne sme uporabljati za vrednotenje drugih odgovorov.</li>
</ul>
<span class="qtip-more"><a href="https://www.1ka.si/d/sl/pomoc/prirocniki/urejanje-vrednosti-odgovorov" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_grid_var', '2', '<p>The response values are sorted ascending by default and starting with 1. Values can also be sorted descending (by clicking on the checkbox Sort values descending).</p>
<ul>The values of the answers can be changed. In doing so, the following rules apply:
<li>Values must not be repeated (except in the case when Quiz module is turned on).</li>
<li> Only integers (without decimal numbers) can be used. </li>
<li>The values -1, -2, -3, -4, -5, -6, -96, -97, -98, -99 are reserved for marking missing values and should not be used to evaluate other responses.</li>
</ul>
<span class="qtip-more"><a href="https://www.1ka.si/d/en/help/manuals/edit-responses-values" target="_blank">Read more</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_grupe', '1', 'Vpra&#353;alnik je razdeljen na posamezne strani. Vsaka stran naj vsebuje primerno &#353;tevilo vpra&#353;anj. Tukaj vidite izpisane vse strani vpra&#353;alnika, vklju&#269;no z uvodom in zaklju&#269;kom.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_grupe_branching', '1', '<b>ANKETA Z VEJITVAMI IN POGOJI:</b> anketni vpra&#353;alnik potrebuje  preskoke, bloke, gnezdenje pogojev ipd.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_grupe_recount_branching', '1', '<p>&#268;e spreminjate vrstni red vpra&#353;anj, lahko vrstni red ponovno vzpostavite s pre&#353;tevil&#269;enjem celotnega vpra&#353;alnika.</p>
<p>V primeru, da ste sami ro&#269;no preimenovali vpra&#353;anje, se to ne bo upo&#353;tevalo pri avtomatskem pre&#353;tevil&#269;evanju.</p>
<span class="qtip-more"><a href="https://www.1ka.si/d/sl/pomoc/pogosta-vprasanja/zakaj-vprasanja-niso-ostevilcena-zaporedno" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_grupe_recount_branching', '2', '<p>If you change the order of the questions, the questions can be renumbered for the entire questionnaire by clicking on the # icon.</p>
<span class="qtip-more"><a href="http://english.1ka.si/index.php?fl=2&lact=1&bid=393&parent=19" target="_blank">Read more</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_hierarchy_admin_help', '1', 'Tukaj lahko odstranjujete celotni nivo ali pa s klikom na checkbox izberete, &#269;e so &#353;ifranti znotraj polja unikatni.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_hierarchy_edit_elements', '1', 'Za vsak izbran nivo se lahko dodaja nove elemente. Z izbiro mo&#382;nosti brisanja se izbri&#353;e celoten nivo z vsemi &#353;ifranti. Lahko pa se omenejene elemente ureja in odstrani zgolj poljuben element nivoja.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_hierarhy_last_level_missing', '1', 'Na zadnjem nivoju manjka izbran element in elektronski naslov osebe, ki bo preko elektronske po&#353;te dobila kodo za re&#353;evanje ankete.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_hotspot_region_color', '1', 'Omogo&#269;a urejanje barve obmo&#269;ja, ko bo to izbrano.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_hotspot_tooltip', '1', 'Izberite mo&#382;nosti prikazovanja namigov z imeni obmo&#269;ij.

Prika&#382;i ob mouseover: namig je viden, ko je kurzor mi&#353;ke nad obmo&#269;jem;
Skrij: namig ni viden;
');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_hotspot_tooltip_grid', '1', 'Izberite mo&#382;nosti prikazovanja namigov s kategorijami odgovorov.

Prika&#382;i ob mouseover: kategorije odgovorov so vidne, ko je kurzor mi&#353;ke nad obmo&#269;jem;
Prika&#382;i ob kliku  mi&#353;ke na obmo&#269;je: kategorije odgovorov so vidne, ko se klikne na obmo&#269;je;
');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_hotspot_visibility', '1', 'Izberite tip osvetlitve oz. kako, so obmo&#269;ja vidna ali nevidna respondentom.

Skrij: obmo&#269;je ni vidno;
Prika&#382;i: obmo&#269;je je vidno;
Prika&#382;i ob mouseover: obmo&#269;je je vidno, ko je kurzor mi&#353;ke nad obmo&#269;jem;
');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_hotspot_visibility_color', '1', 'Omogo&#269;a urejanje barve osvetlitve obmo&#269;ja.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_if_operator', '1', '<ul>
<li>"AND" = pogoj je izpolnjen le, &#269;e je zado&#353;&#269;eno &#269;isto vsem kriterijem pogoja</li>
<li>"AND NOT" = pogoj je izpolnjen, &#269;e velja prvi kriterij ne pa tudi drugi kriterij</li>
<li>"OR" = pogoj je izpolnjen, &#269;e velja kateri koli od kriterijev (torej zadostuje, da je izpolnjen le en kriterij)</li>
<li>"OR NOT" = pogoj je izpolnjen, &#269;e je izpolnjen prvi kriterij ali, &#269;e ni izpolnjen drugi kriterij</li>
</ul>
<span class="qtip-more"><a href="https://www.1ka.si/d/sl/pomoc/prirocniki/uporaba-pogojev" target="_blank"> Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_invitation_rename_profile', '1', 'Vsak vne&#353;en email se privzeto shrani v za&#269;asen seznam, katerega pa lahko preimenujete tudi druga&#269;e. Nove emaile pa lahko dodate tudi v obstoje&#269;e sezname.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_invitation_rename_profile', '2', 'Each entered email is by default stored in a temporary list, which you can rename otherwise. You can also add new emails to existing lists. ');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_inv_activate_1', '1', 'Z izbiro "email vabil z individualiziranim URL" se avtomati&#269;no vklopi opcija "Da" za individualizirana vabila, za vnos kode pa "Avtomatsko v URL". Respondentom bo sistem 1KA lahko poslal email, v katerem bo individualiziran URL naslovom ankete. &#268;im bo respondent na URL kliknil, bo sistem 1KA sledil respondenta.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_inv_activate_2', '1', 'Z izbiro "ro&#269;ni vnos individualizirane kode" se avtomati&#269;no vklopi opcija "Da" za individulaizirana vabila ter opcija "Ro&#269;ni vnos" za vnos kode. Respondenti bodo prejeli enak URL, na za&#269;etku pa bodo morali ro&#269;no vnesti svojo individualno kodo. Vabilo s kodo se lahko respondentu po&#353;lje z emailom preko sistemom 1KA. Lahko pa se po&#353;lje  tudi eksterno (izven sistema 1KA): z dopisom preko po&#353;te, s SMS sporo&#269;ilom kako druga&#269;e; v takem primeru sistem 1KA zgolj zabele&#382;i kdo, kdaj in kako je poslal vabilo.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_inv_activate_3', '1', 'Z izbiro "uporabe splo&#353;nih vabil brez individulaizirane kode" opcija "email vabila z individualiziranim URL" ostaja izklopljena ("Ne"). Sistem 1KA bo respondenom lahko poslal emaile, ki pa ne bo imeli individulaizranega URL oziroma individualizirane kode.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_inv_activate_4', '1', 'Z izbiro "Email vabila z ro&#269;nim vnosom kode" se vklopi opcija "Da" za individualizirana vabila, za vnos kode pa "Ro&#269;ni vnos". Respondentom bo sistem 1KA lahko poslal email, v katerem bo individualizirana koda, URL naslov ankete pa bo enoten. Ko bo respondent kliknil na URL kliknil, se bo prikazal zahtevek za vnos kode, ki jo je prejel po emailu.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_inv_archive_sent', '1', 'Klik na &#353;tevilo poslanih vabil prika&#382;e podroben pregled poslanih vabil');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_inv_cnt_by_sending', '1', 'Tabela pove, koliko enotam email &#353;e ni bil poslan (0), koliko enot je dobilo email enkrat (1), koliko dvakrat (2), koliko trikrat (3) ...');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_inv_delay', '1', 'Pri po&#353;iljanju email vabil na ve&#269; naslovov je vklopljena zakasnitev, kar pomeni da med e-po&#353;tnim sporo&#269;ilom, poslanim enemu naslovniku, in e-po&#353;tnim sporo&#269;ilom, poslanim naslednjemu naslovniku, prete&#269;e najmanj 2 sekundi. Ta &#269;as lahko po potrebi spremenite (glede na zmogljivosti va&#353;ega stre&#382;nika). ');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_inv_general_settings', '1', 'V spletnih anketah obi&#269;ajno zado&#353;&#269;ata dve splo&#353;ni emaili vabili vsem enotam (drugo vabilo je hkrati zahvala respondentom). 

V primeru manj&#353;ega &#353;tevila enot (npr. nekaj sto) uporabimo kar privzeti email sistem (npr. Gmail, Outlook...), &#269;e je enot ve&#269;, pa orodja za masovno po&#353;iljanje (npr. SqualoMail, MailChimp...).

Ve&#269;je &#353;tevilo vabil in sledenje respondentom je ve&#269;inoma nepotrebno, hkrati pa tudi zahtevno.

Email vabila lahko po&#353;iljamo tudi preko sistema 1KA, in to tako splo&#353;na kot individualizirana vabila s kodo in sledenjem. Sistem 1KA poleg tega podpira (dokumentira) tudi po&#353;iljanje vabil na preko po&#353;ta, SMS, ipd.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_inv_message_title', '1', 'Sporo&#269;ilo, ki bo poslano po emailu. Vsebino sporo&#269;ila se lahko spreminja poljubno, vsako spreminjanje se shrani kot novo sporo&#269;ilo in do njega se lahko dostopa v levem oknu iz seznama sporo&#269;il.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_inv_message_title_noEmail', '1', 'Sporo&#269;ilo, ki bo poslano po navadni po&#353;ti ali SMS-u, ga lahko v 1KA dokumentirate. Dokumentirate lahko ve&#269; verzij sporo&#269;il, do njih pa dostopate v levem stolpcu seznama sporo&#269;il.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_inv_no_code', '1', 'V anketo se lahko vstopa tudi brez vnosa kode. Posebej priporo&#269;ljivo, da se ozna&#269;i le avtor za testiranje vpra&#353;alnika.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_inv_recipiens_add_invalid_note', '1', '&#268;e &#382;elite dodati enote, ki nimajo emailov, naredite lo&#269;en seznam.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_inv_sending_comment', '1', 'Zabele&#382;imo lahko kako posebnost ali zna&#269;ilnost (npr. preliminarno obvestilo, prvo vabilo, opomnik ipd). V primeru ro&#269;nega po&#353;iljanja je priporo&#269;ljivo navesti dejanski dan odpo&#353;iljanja (npr. preko po&#353;te), saj se lahko razlikuje od datuma priprave seznama.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_inv_sending_double', '1', 'Odstranjujejo se podvojeni zapisi glede na email');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_inv_sending_type', '1', 'Vabila je mogo&#269;e po&#353;iljati tudi preko po&#353;te, SMS ali kako druga&#269;e izven 1KA sistema. 

Uporaba individualizirane kode je mogo&#269;a pri obeh na&#269;inih po&#353;iljanja.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_item_nonresponse', '1', '<p>Neodgovor spremenljivke pomeni koliko ustreznih odgovorov smo dobili glede na skupno &#353;tevilo enot, ki so dobile dolo&#269;eno vpra&#353;anje. Ali druga&#269;e: od vseh ustreznih enot, ki so dobile to vpra&#353;anje, so izlo&#269;eni statusi (-1).</p>
<p>Izra&#269;unan je po formuli: (-1) * 100 / ( (veljavni) + (-1) + (-97) + (-98) + (-99) ).</p>
<span class="qtip-more"><a href="https://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/status/neodgovor-spremenljivke" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_item_nonresponse', '2', '<p>Neodgovor spremenljivke pomeni koliko ustreznih odgovorov smo dobili glede na skupno &#353;tevilo enot, ki so dobile dolo&#269;eno vpra&#353;anje. Ali druga&#269;e: od vseh ustreznih enot, ki so dobile to vpra&#353;anje, so izlo&#269;eni statusi (-1).</p>
<p>Izra&#269;unan je po formuli: (-1) * 100 / ( (veljavni) + (-1) + (-97) + (-98) + (-99) )</p>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_izpolnjujejo', '1', 'Tu lahko nastavite omejitev izpolnjevanja ankete za razli&#269;ne tipe uporabnikov aplikacije 1KA - registrirane uporabnike, &#269;lane, managerje in administratorje. 

<span class="qtip-more"><a href="https://www.1ka.si/d/sl/o-1ka/splosen-opis/nivoji-uporabnikov" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_izpolnjujejo', '2', 'Here you can set limitations for survey completion for different types of users - registered users, members, managers and administrators.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_mail_mode', '1', '<p>Za uporabo lastnega stre&#382;nika morate vpisati SMTP nastavitve.</p>
<p>Za podatke kontaktiraje administratorja va&#353;ega po&#353;tnega stre&#382;nika.</p>
<span class="qtip-more"><a href="https://www.1ka.si/d/sl/o-1ka/nacini-uporabe-storitve-1ka/lastna-namestitev" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_mail_mode', '2', '<p>To use your own server you must enter the SMTP settings.</p>
<p>Contact the administrator of your web server for data.</p>
<span class="qtip-more"><a href="https://www.1ka.si/d/en/about/uses-of-1ka-services/own-installation" target="_blank">Read more</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_menu_statistic', '1', 'Ve&#269; o osnovnih statisti&#269;nih analizah si poglejte v <a href="https://www.1ka.si/d/sl/pomoc/video/enostavna-anketa-7m-05s" target="_blank">video vodi&#269;u</a> in <a href="https://www.1ka.si/d/sl/pomoc/prirocniki/osnovne-analize-podatkov-0" target="_blank">priro&#269;niku</a>.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_menu_statistic', '2', 'For moreinformation on basic statistical analyses see the <a href=" http://english.1ka.si/db/24/412/Manuals/Basic_data_analysis/?&cat=309&p1=226&p2=735&p3=867&p4=0&id=867&cat=309" target="_blank">manual.</a>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_missing_values', '1', '<p><b>Manjkajo&#269;e vrednosti respondenta</b> imajo v bazi podatkov negativne vrednosti in so iz analiz privzeto izlo&#269;ene. To pomeni, se v statisti&#269;nih analizah ne upo&#353;tevajo (razen, &#269;e sami nastavite druga&#269;e).</p>
<p>Vrednosti odgovorov "ne vem", "zavrnil", "neustrezno", in "ostalo" se v bazo zapi&#353;ejo kot - 99, -98, -97 in -96.</p>
<span class="qtip-more"><a href="https://www.1ka.si/d/sl/pomoc/prirocniki/statusi-enot-ustreznost-veljavnost-manjkajoce-vrednosti" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_missing_values', '2', '<p>Nonsubstantive and missing responses: Respondents" missing values have negative values in the database and are by default excluded from the analysis. This means that they are not included in statistical analysis (unless you change the default settings).</p>
<p>Response values "do not know", "refused", "invalid", and "none of above" are labeled as - 99, -98, -97 and -96 in the database.</p>
<span class="qtip-more"><a href="https://www.1ka.si/d/en/help/manuals/status-of-units-relevance-validity-and-missing-values" target="_blank">Read more</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_moje_ankete_setting', '1', 'Mo&#382;nost omogo&#269;a, da anketo prenesete med svoje ankete v knji&#382;nico. 

<span class="qtip-more"><a href="https://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/moje-ankete/knjiznica" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_moje_ankete_setting', '2', 'This option allows you to save your survey in the "My surveys" category of the library.<span class="qtip-more"><a href="https://www.1ka.si/d/en/help/user-guide/my-surveys/library" target="_blank">Read more</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_namig_setting', '1', 'Ob vklopu mo&#382;nosti se respondentu skozi anketo sproti pojavljajo opozorila, ki ste jih nastavili.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_namig_setting', '2', 'When you turn on this option, notifications will appear throughout the survey completion process.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_nice_url', '1', 'V primeru, da boste povezavo (URL) do ankete po&#353;iljali preko pisnih dopisov, priporo&#269;amo uporabo mo&#382;nosti Lep URL. Namre&#269; namesto &#353;tevilk ankete izberete poljubno ime, kar omogo&#269;a respondentom la&#382;ji vpis URL-ja (npr.: www.1ka.si/imeankete). Bodite pozorni na velike in male &#269;rke, saj jih 1KA razlikuje. Ime ankete mora vsebovati minimalno 3 znake.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_nice_url', '2', 'If you are planning to send the survey link (URL) via mail, we recommend using the custom URL option. Namely, instead of survey number you can choose any name you want, allowing respondents to facilitate the entry of the URL (eg .: www.1ka.si/surveyname). Pay attention to uppercase and lowercase letters as 1KA is case sensitive. Name of the survey must contain a minimum of 3 characters.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_novagrupa', '1', 'S klikom na <b>Nova stran</b> dodate v vpra&#353;alnik novo stran, ki se postavi pred zaklju&#269;ek in jo nato lahko poljubno uredite in premikate. ');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_parapodatki', '1', 'Ob vklopu naprednih parapodatkov se lahko dostopa do informacij, kot so zaporedna &#353;tevilka zapisa, datum vnosa ankete, &#269;as vnosa do milisekunde natan&#269;no, katero napravo je uporabljal respondent itd. 

<span class="qtip-more"><a href="https://www.1ka.si/d/sl/pomoc/prirocniki/anketni-podatki-parapodatki-identifikatorji-sistemske-spremenljivke" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_para_graph_link', '1', '<span class="qtip-more"><a href="https://www.1ka.si/index.php?fl=2&lact=1&bid=477&parent=24" target="_blank">Preberi ve&#269;</a></span> ');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_para_neodgovori_link', '1', '<p>Neodgovor spremenljivke pomeni koliko ustreznih odgovorov smo dobili glede na skupno &#353;tevilo enot, ki so dobile dolo&#269;eno vpra&#353;anje. Ali druga&#269;e: od vseh ustreznih enot, ki so dobile to vpra&#353;anje, so izlo&#269;eni statusi (-1).</p>
<p>Izra&#269;unan je po formuli: (-1) * 100 / ( (veljavni) + (-1) + (-97) + (-98) + (-99) )</p>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_podatki_urejanje_inline', '1', '<p>Vklju&#269;ili ste tudi neposredno urejanje v pregledovalniku.</p>
<p>V kolikor &#382;elite vrednosti vpra&#353;anja izbirati iz rolete lahko to nastavite v urejanju kot napredno nastavitev vpra&#353;anja.</p>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_podatki_urejanje_inline', '2', '<p>You enabled direct editing in the data viewer.</p>
<p>If you want to select the values of the question from the blinds, you can set this in editing as an advanced setup of the question.</p>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_popup_js', '1', '');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_privacy_setting', '1', 'Tukaj se vklopi prikaz politike zasebnosti na za&#269;etku ankete. Respondent lahko nadaljuje z anketo le, &#269;e se strinja s pogoji.<span class="qtip-more"><a href="https://www.1ka.si/d/sl/o-1ka/pogoji-uporabe-storitve-1ka/politika-zasebnosti" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_privacy_setting', '2', 'Here you can turn on the privacy policy display at the beginning of the survey. Respondents can proceed with the survey only if they agree to the terms. <span class="qtip-more"><a href="https://www.1ka.si/d/en/about/terms-of-use/privacy-policy" target="_blank">Read more</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_recode_advanced_edit', '1', 'Napredno urejanje, kot je dodajanje, preimenovanje in brisanje kategorij je na voljo v urejanju vpra&#353;alnika.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_recode_chart_advanced', '1', 'Osnovno rekodiranje je primerno, da se starost, katera je ve&#269;ja od 100 rekodira v -97 katero je neustrezno. Oziroma da se odgovori 9 - ne vem rekodirajo v neustrezno.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_recode_h_actions', '1', '<ul>Funkcije rekodiranja so:
<li>Dodaj - odpre okno za dodajanje rekodiranje za posamezno variablo</li>
<li>Uredi - prika&#382;e okno za urejane rekodiranja posamezne variable</li>
<li>Odstrani - odstrani oziroma v celoti izbri&#353;e rekodiranje posamezne variable</li>
<li>Omogo&#269;eno - trenutno omogo&#269;i oziroma onemogo&#269;i rekodiranje posamezne variable</li>
<li>Vidna - nastavi variablo vidno oziroma nevidno v vpra&#353;alniku</li>
</ul>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_reminder_tracking_quality', '1', '<p>Kakovostni indeks = 1 - ( &#8721;(&#353;tevilo spro&#382;enih opozoril/&#353;tevilo mo&#382;nih opozoril po vrsti opozorila) / &#353;tevilo respondentov )</p>
<p>Haraldsen, G. (2005). Using Client Side Paradata as Process Quality Indicators in Web Surveys. Predstavljeno na delavnici ESF Workshop on Internet survey methodology, Dubrovnik, 26-28 September 2005.</p>
<span class="qtip-more"><a href="https://www.1ka.si/d/sl/pomoc/prirocniki/indeks-kakovosti-sledenje-opozorilom" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_reminder_tracking_quality', '2', 'Quality index = 1 - ( &#8721;(Activated errors/Possible errors) / Number of respondents ) </br> Haraldsen, G. (2005). Using Client Side Paradata as Process Quality Indicators in Web Surveys. Presented at the ESF Workshop on Internet survey methodology, Dubrovnik, 26-28 September 2005.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_return_finished', '1', 'Uporabniku, ki je &#382;e izpolnjeval ali &#382;e zaklju&#269;il z anketo, lahko z izbiro "Mo&#382;nost naknadnega urejanja odgovorov" omogo&#269;ite, da kasneje ureja svoje odgovore. Vendar to pomeni, da se bodo tudi podatki in analize naknadno spreminjale, zato velja dobro razmisliti, ali je taka mo&#382;nost primerna.

<span class="qtip-more"><a href="https://www.1ka.si/d/sl/pomoc/prirocniki/nastavitve-ankete-0" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_return_finished', '2', 'Here you can specify whether a user who has completed a survey can subsequently edit his responses. <span class="qtip-more"><a href="https://www.1ka.si/d/en/help/manuals/survey-settings" target="_blank">Read more</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_show_progressbar', '1', 'Funkcija omogo&#269;a, da se pri izpolnjevanju ankete respondentu na vrhu strani prika&#382;e graf, ki ponazarja dele&#382; ankete, ki jo je respondent v tistem trenutku &#382;e izpolnil. Vklop je mo&#382;en le, &#269;e ima anketa ve&#269; strani.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_show_progressbar', '2', 'Opcija omogo&#269;a, da se pri izpolnjevanju ankete respondentu na vrhu strani prika&#382;e graf, ki ponazarja dele&#382; ankete, ki jo je respondent v tistem trenutku &#382;e izpolnil. Vklop je mo&#382;en samo, &#269;e ima anketa ve&#269; strani.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_sistemska_edit', '1', 'Sistemsko spremenljivko lahko uporabljamo (pokli&#269;emo) v email komunikaciji, torej pri obve&#353;&#269;anju in po&#353;iljanju email vabil. 
 <span class="qtip-more"><a href="https://www.1ka.si/d/sl/pomoc/prirocniki/anketni-podatki-parapodatki-identifikatorji-sistemske-spremenljivke" target="_blank"> Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_sistemska_edit', '2', 'We can use (call) system variables in email communication, i.e. communication and sending of email invitations. <span class="qtip-more"><a href="https://www.1ka.si/d/en/help/manuals/survey-data-paradata-identifiers-and-system-variables" target="_blank">Read more</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_skala_edit', '1', '<p><b>Ordinalna skala:</b> Kategorije odgovorov je mogoce primerjati; racunamo lahko tudi povprecje. Npr. lestvice na skalah (strinjanje, zadovoljstvo,.)</p>
<p><b>Nominalna skala:</b> Kategorij odgovorov ni mogoce primerjati niti ni mogoce racunati povprecij. Npr. spol, barva, regija, dr&#382;ava.</p>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_skala_edit', '2', '<p><b>Ordinal scale:</b> Response categories can be compared; you can also compute the average. Eg. Measurement scales (acceptance, satisfaction.)</p>
<p><b>Nominal scale:</b> Response categories cannot be compared nor can we calculate averages. Eg. gender, color, region, or country.</p>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_skala_text_nom', '1', '<p><b>Ordinalna skala:</b> Kategorije odgovorov je mogoce primerjati; racunamo lahko tudi povprecje. Npr. lestvice na skalah (strinjanje, zadovoljstvo,.)</p>
<p><b>Nominalna skala:</b> Kategorij odgovorov ni mogoce primerjati niti ni mogoce racunati povprecij. Npr. spol, barva, regija, dr&#382;ava.</p>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_skala_text_ord', '1', 'Ordinalna skala');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_skala_text_ord', '2', '<p><b>Ordinal scale:</b> Response categories can be compared; you can also compute the average. Eg. Measurement scales (acceptance, satisfaction.)</p>
<p><b>Nominal scale:</b> Response categories cannot be compared nor can we calculate averages. Eg. gender, color, region, or country.</p>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_skins_Embed', '1', 'Za ankete, ki so vklju&#269;ene v drugo spletno stran.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_skins_Embed2', '1', 'Za ankete, ki so vklju&#269;ene v drugo spletno stran (o&#382;ja razli&#269;ica).');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_skins_Fdv', '1', 'Samo za uporabnike, ki imajo dovoljenje s strani FDV.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_skins_Slideshow', '1', 'Za prezentacijo.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_skins_Uni', '1', 'Samo za uporabnike, ki imajo dovoljenje s strani FDV.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_skupine', '1', '&#268;e &#382;elite analizirati posamezne skupine respondentov, ne &#382;elite pa jim postavljati vpra&#353;anja za identifikacijo, lahko naredite skupine &#353;e preden po&#353;ljete anketo v izpolnjevanje.

<span class="qtip-more"><a href="https://www.1ka.si/d/sl/pomoc/prirocniki/ustvarjanje-skupin-respondentov" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_skupine', '2', 'If you wish to analyse individual respondent groups without asking for identification, you can simply create groups before you send out a survey. <span class="qtip-more"><a href="https://www.1ka.si/d/en/help/manuals/creating-respondent-groups" target="_blank">Read more</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_spremenljivka_lock', '1', 'Zaklenjeno vpra&#353;anje lahko ureja samo avtor ankete.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_spremenljivka_lock', '2', 'Only the author of the survey can edit locked question. ');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_statistic_answer_state_title', '1', 'Stopnja odgovorov pove, kolik&#353;en odstotek vseh respondentov je pri navedenih kategorijah izpolnil anketo. 

<span class="qtip-more"><a href="https://www.1ka.si/d/sl/pomoc/prirocniki/stopnja-odgovorov" target="_blank">Preberi ve&#269;</a></span> ');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_statistic_answer_state_title', '2', 'The response rate tells you what percentage of all respondents filled out the survey according to the five categories. <span class="qtip-more"><a href="https://www.1ka.si/d/en/help/manuals/the-response-rate" target="_blank">Read more</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_statistic_info_title', '1', 'Tukaj so razvidne osnovne informacije o anketi (t.i. "hitre informacije"): ime ankete, &#353;tevilo enot in vpra&#353;anj, trajanje ankete, prvi in zadnji vnos, status aktivnosti ankete itd. ');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_statistic_info_title', '2', 'Here you can see the basic information about the survey (i.e. "quick overview"): survey name, number of units, survey timeline, the first and last entry, the activity status of the survey etc.
');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_statistic_pages_state_title', '1', 'Tu se lahko spremlja potek ankete po straneh in se dobi vpogled v to, kako dale&#269; v anketi pridejo respondenti.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_statistic_pages_state_title', '2', 'Here you can see the responses by pages, where you can get an insight into how many respondents finished your survey. ');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_statistic_redirection_title', '1', 'Spisek preusmeritev prikazuje od kje prihajajo preusmeritve na anketo, oziroma koliko respondentov je kliknilo na anketo iz dolo&#269;ene spletne strani.  

<span class="qtip-more"><a href="https://www.1ka.si/d/sl/pomoc/prirocniki/preusmeritve" target="_blank">Preberi ve&#269;</a></span> ');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_statistic_redirection_title', '2', 'The list of redirections shows from where the redirections to your survey come from. In other words: how many respondents clicked on your survey from a certain website. Category "Direct click" includes clicks from email invitations that have not been sent via 1KA, and all direct entries (type-in or cut-paste) of the survey URL. Surveys that are completed via the 1KA invitation can be viewed under 1KA "Email - response". <span class="qtip-more"><a href="https://www.1ka.si/d/en/help/manuals/referrals" target="_blank">Read more</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_statistic_status_title', '1', 'Tukaj je razvidno, koliko respondentov je kliknilo na anketo, koliko je ustreznih oziroma neustreznih enot (respondentov, ki so na anketo kliknili, vendar je niso izpolnili) ter skupno &#353;tevilo respondentov.

<span class="qtip-more"><a href="https://www.1ka.si/d/sl/pomoc/prirocniki/statusi-enot-ustreznost-veljavnost-manjkajoce-vrednosti" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_statistic_status_title', '2', 'Here you can see the number of respondents that clicked on the survey, the number of valid or invalid units (respondents that clicked on the survey, but did not fill out the survey) and the total number of respondents.<span class="qtip-more"><a href="https://www.1ka.si/d/en/help/manuals/status-of-units-relevance-validity-and-missing-values" target="_blank">Read more</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_statistic_timeline_title', '1', '&#268;asovni potek pove, koliko respondentov je na dolo&#269;eno &#269;asovno obdobje kliknilo ali izpolnilo anketo. &#268;asovni potek se lahko preveri po mesecih, dnevih, urah itd.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_statistic_timeline_title', '2', 'You can check the survey timeline according to months, days, hours etc. The timeline tells you how many respondents clicked or filled our your survey in a specific time period (month, day or hour).');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_statistika', '1', 'Opcija ki se sicer redko uporablja, prikaze rezultate odgovora na naslednji strani.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_statistika', '2', 'This option is rarely used; it displays the results of a response on the next page.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_subsequent_answers', '1', 'V primeru, da je izbrana druga mo&#382;nost, uporabnik ne morenikoli naknadno urejati svojih odgovorov (npr. s klikom nazaj).

<span class="qtip-more"><a href="https://www.1ka.si/d/sl/pomoc/prirocniki/nastavitve-ankete-0" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_survey_type', '1', '<p>1KA upo&#353;teva, da enostavne ankete zahtevajo druga&#269;en vmesnik kot kompleksne.</p>
<ul>1KA zato omogo&#269;a, da lahko vedno izberete optimalni vmesnik, pa&#269; glede na zahtevnost ankete, ki jo potrebujete: 
<li><b>GLASOVANJE:</b> anketa z enim samim vpra&#353;anjem, volitve, ipd., vendar z mo&#382;nostjo sprotnega prikaza rezultatov.</li>
<li><b>FORMA</b> kratek enostranski vpra&#353;alnik, forma, registracija, obrazec, email lista, prijava na dogodek ipd.</li>
<li><b>ANKETA S POGOJI IN BLOKI:</b> anketni vpra&#353;alnik potrebuje  preskoke, bloke, gnezdenje pogojev ipd.</li>
</ul>
<p>Med vmesniki lahko preklapljate tudi kasneje, razen seveda v primeru, ko bi prehod pomenil izbris dolo&#269;enih podatkov. Tako v primeru, ko imate pogoje, ni ve&#269; mogo&#269;e prehod na enostavnej&#353;e vmesnike. Podobno iz ve&#269;stranske ankete ni mogo&#269; prehod v formo, je pa mogo&#269; seveda prehod v anketo s pogoji.</p>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_telephone_help', '1', 'Izbrani modul nudi podporo pri telefonskem anketiranju. <span class="qtip-more"><a href="https://www.1ka.si/d/sl/pomoc/prirocniki/telefonska-anketa" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_toolbox_add_advanced', '1', 'Dodaj tip vpra&#353;anja.

S klikom na &#382;eljeni tip vpra&#353;anja se ta postavi za zadnje vpra&#353;anje v anketi.

Z uporabo funkcije "Drag&drop" lahko zagrabite tip vpra&#353;anja in ga prestavite na &#382;eljeno mesto.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_toolbox_add_advanced', '2', 'Add the question types.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_ttest_interpretation', '1', 'Z uporabo T-testa lahko preverite domneve o statisti&#269;no zna&#269;ilnih razlikah. <span class="qtip-more"><a href="https://www.1ka.si/db/24/433/Prirocniki/Ttest/?&p1=226&p2=735&p3=0&p4=0&id=735"target="_blank"> Preberi ve&#269;</a></span> ');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_ttest_interpretation', '2', 'With T-test you can verify assumptions about statistically significant differences. For moreinformation about the interpretation of the T-test, see guide <ahref="http://english.1ka.si/index.php?fl=2&lact=1&bid=436&parent=24" target="_blank"> T-test. </a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_upload_limit', '1', '<h1>Omejitev nalaganja datoteke</h1>
<ul>Dodatne omejitve za respondente pri nalaganju datoteke:
<li>najve&#269;ja velikost posamezne datoteke, ki jo&nbsp;nalo&#382;i respondent je <strong>16 MB;</strong></li>
<li>dovoljene vrste datotek so: <strong>"jpeg", "jpg", "png", "gif", "pdf", "doc", "docx", "xls", "xlsx";</strong></li>
<li><strong>v posameznem vpra&scaron;anju</strong> so dovoljena najve&#269; <strong>4 vnosna polja</strong>, torej najve&#269; 4 datoteke, ki jih lahko respondent nalo&#382;i pri enem vpra&scaron;anju.</li>
</ul>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_user_base_individual_invitaition_note', '1', 'Individualizirana vabila omogo&#269;ajo sledenje respondentom preko individualne kode oziroma gesla.

URL ankete vklju&#269;uje individualizirano kodo, respondent pa mora zgolj klikniti na URL ali pa ro&#269;no vpisati podano generirano kodo.
');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_user_base_individual_invitaition_note2', '1', '<p>Z izbiro "Ne" je modul individualiziranih vabil izklopljen. Anketira se lahko vsak, ki vidi ali pozna URL naslov. Respondentov v takem primeru ne moreo slediti; ne vemo kdo je odgovoril in kdo ne.</p>
<p>Sistem 1KA lahko kljub temu po&#353;lje (email) oziroma dokumentira (po&#353;ta, SMS, drugo) po&#353;iljanje splo&#353;nega ne-individualiziranega vabila, kjer vsi respondenti prejmejo enotni URL. To pomeni, da se zabele&#382;ilo, komu, kdaj in kako je bilo vabilo poslano, ne bo pa ozna&#269;eno, kdo je odgovoril in kdo ne.</p>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_user_from_cms', '1', 'Tu nastavite, ali naj se uporabnika sistema CMS (sistem za upravljanje vsebin) prepozna avtomatsko kot respondenta ali pa kot vna&#353;alca. 

<span class="qtip-more"><a href="https://www.1ka.si/d/sl/pomoc/prirocniki/nastavitve-ankete-0" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_user_from_cms', '2', 'Recognize CMS user: here you can set if the CMS (content management system) user is automatically recognized as a respondent. You can also select "No". <span class="qtip-more"><a href="https://www.1ka.si/d/en/help/manuals/survey-settings" target="_blank">Read more</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_vprasanje_max_marker_map', '1', '&#352;tevilo najve&#269; mo&#382;nih oddanih odgovorov/markerjev na zemljevidu');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_vprasanje_tracking_setting', '1', 'Omogo&#269;a verzije na nivoju posameznih vpra&#353;anj in ne le na nivoju celotnega vpra&#353;alnika. Uporabljajo jo lahko le managerji in administratorji. 

<span class="qtip-more"><a href="https://www.1ka.si/d/sl/pomoc/prirocniki/nastavitve-ankete-0" target="_blank">Preberi ve&#269;</a></span> ');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_vprasanje_tracking_setting', '2', 'Allows for versions at the level of individual questions and not only at the level of the entire questionnaire. Only administrators can use this option. <span class="qtip-more"><a href="https://www.1ka.si/d/en/help/manuals/survey-settings" target="_blank">Read more</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_vrednost_fastadd', '1', 'Hitro dodajanje kategorij je priporo&#269;ljivo uporabiti pri vpra&#353;anjih, kjer imamo ve&#269;je &#353;tevilo kategorij odgovorov. Kateogorije preprosto vnesemo tako, da  vsako kategorijo vnesemo v svojo vrstico. ');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_vrednost_fastadd', '2', 'Fast  add categories option is recommended to use on questions where we have a large number of categories, by simply entering each category into a new row. ');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_window_help', '1', 'Pri lastni in&#353;talaciji odsvetujemo urejanje oz. spreminjanje vsebine vpra&#353;aj&#269;kov, saj se bo z vsako posodobitvijo verzije vsebina prepisala. Za spremembo obvestite <a href="https://www.1ka.si//c/819/KONTAKT/" target="_blank">Center za pomo&#269;</a>.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_window_help', '2', '');
INSERT INTO srv_help (what, lang, help) VALUES ('toolbox_advanced', '1', 'Pri anketi z ve&#269;jim &#353;tevilom vpra&#353;anj vam priporo&#269;amo, da anketo razdelite na vsebinsko smiselne bloke. 

Bloke lahko po potrebi zapirate in razpirate ter si s tem omogo&#269;ite bolj&#353;i pregled nad anketo.');
INSERT INTO srv_help (what, lang, help) VALUES ('usercode_required', '1', '<p><b>Avtomatsko</b>: koda se samodejno prenese v anketo iz URL povezave email vabila.</p>
<p><b>Ro&#269;no</b>: uporabnik mora ro&#269;no vnesti kodo. Koda se generira in izvozi v zavihku "Preglej".</p>');
INSERT INTO srv_help (what, lang, help) VALUES ('usercode_skip', '1', '<ul>
<li><b>Ne</b>: Za izpolnjevanje ankete mora respondent bodisi prejeti email vabilo, kjer klikne na povezavo za avtomatski prenos kode v anketo, bodisi mora respondent poznati kodo in jo ro&#269;no vnesti v anketo.</li>
<li><b>Da</b>: Anketo lahko izpolnjujejo tudi uporabniki, ki niso prejeli kode.</li>
<li><b>Samo avtor</b>: Poleg uporabnikov, ki imajo kodo, lahko anketo brez kode izpolnjujejo tudi avtorji ankete (predvsem v testne namene).</li>
</ul>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_nastavitve_enklik', '1', 'Pri izboru te opcije se vam bo v zavihku "Moje ankete" prikazal gumb "Enklik kreiranje", s klikom nanj pa se bo ustvarila anketa, ki se vam bo prikazala v urejevalnem pogledu.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_nastavitve_API', '1', 'API je zbirka funkcij, ki omogo&#269;ajo, da uporabniki preko oddaljenega dostopa (torej ne preko spletnega vmesnika) izvajajo dolo&#269;ene operacije na 1KA. Kot je navedeno v navodilih, mora uporabnik najprej zgenerirati klju&#269; za uporabo API-ja, potem pa lahko z njim integrira funkcije v svoj program ali spletno stran. <span class="qtip-more"><a href="https://www.1ka.si/d/sl/o-1ka/1ka-api" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_nastavitve_zakleni', '1', 'Pri izboru te opcije, ankete po aktivaciji ne boste mogli urejati, saj se bo samodejno zaklenila, da med zbiranjem podatkov ne bi pri&#353;lo do ne&#382;elenih sprememb vpra&#353;alnika (Anketo lahko spet odklenete tako, da kliknete na ikono v obliki klju&#269;avnice poleg URL naslova ankete na vrhu zaslona).');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_nastavitve_jezik', '1', '<ol>Jezik je mo&#382;no prilagajati na treh nivojih:
<li><strong>Osnovni jezik spletne strani za avtorja ankete:&nbsp;</strong>Nastavite lahko osnovni jezik aplikacije 1KA, izbirate pa lahko med sloven&scaron;&#269;ino in angle&scaron;&#269;ino.</li>
<li><strong>Osnovni jezik ankete za respondente:&nbsp;</strong>Iz spustnega seznama izberete jezik za respondente.&nbsp;</li>
<li><strong>Nastavitve dodatnih jezikov za respondente:&nbsp;</strong>Prevod osnovnega vpra&scaron;alnika v razli&#269;ne jezike.</li>
</ol>
<span class="qtip-more"><a href="https://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/urejanje/nastavitve/jezik" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_status_koncni0', '1', 'V primeru, da je mo&#382;nost obkljukana, statusi, ki ne vsebujejo nobene enote, ne bodo prikazani.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_status_cas0', '1', 'V primeru, da je mo&#382;nost obkljukana, dnevi, ki ne vsebujejo nobene enote, ne bodo prikazani.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_status_cas', '1', 'Dejanski povpre&#269;en &#269;as, ki so ga respondenti porabili za izpolnitev ankete.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_nova_shrani', '1', 'Iz spodnjega seznama lahko izberete v katero mapo iz seznama "Moje ankete", naj se anketa shrani.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_mobilne_tabele', '1', '<h1>Prilagoditve za mobilno anketo</h1>  Spletno anketo, ustvarjeno z orodjem 1KA, lahko respondenti izpolnjujejo tudi preko mobilnega telefona. Zaradi manj&#353;ega zaslona so tako za optimalen prikaz ankete potrebne dolo&#269;ene prilagoditve. <span class="qtip-more"><a href="https://www.1ka.si/d/sl/pomoc/prirocniki/prilagoditve-za-mobilno-anketo" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_obvescanje_odgovorZa', '1', 'V primeru ve&#269;ih prejemnikov obvestila se lahko dolo&#269;i osebo na katero je naslovljen odgovor na obvestilo.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_izvozCSV_locitveni', '1', 'V csv format se bodo podatki zivozili z lo&#269;itvenim znakom ";" ali ",".');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_splosnenas_opozorilo', '1', '<h1>Opozorilo vpra&#353;anja</h1>
<ul>Za vsa vpra&scaron;anja v anketi lahko nastavite:
<li>Mehko opozorilo na vsa vpra&scaron;anja:respondentu se bo pojavilo opozorilo da ni odgovoril na vpra&scaron;anje, vendar pa mu za nadaljevanje ne bo potrebno odgovoriti.&nbsp;</li>
<li>Trdo opozorilo na vsa vpra&scaron;anja: Respondentu se pojavi opozorilo, da ni odgovoril na vpra&scaron;anje. Za nadaljevanje mora obvezno odgovoriti na vsa vpra&scaron;anja.</li>
<li>Odstranitev opozorila iz vseh vpra&scaron;anj:ODstranitev nastavljenega opozorila iz vpra&scaron;anj.</li>
</ul>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_izvozCSV_tekst', '1', 'V primeru, da je mo&#382;nost obkljukana se bodo izvozila tudi vpra&#353;anja in imena vpra&#353;anj.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_oblika_Ikone', '1', 'Sprememba barve in velikosti checkbox/radio button ikon za osebni ra&#269;unalnik.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_oblika_slovarSlovar', '1', 'Sprememba nastavitev za obla&#269;ek pri slovarju. <span class="qtip-more"><a href="https://www.1ka.si/d/sl/pomoc/prirocniki/slovar-glossary-definicije" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_oblika_slovarIKljucna', '1', ' Sprememba nastavitev klju&#269;nih besed pri slovarju. <span class="qtip-more"><a href="https://www.1ka.si/d/sl/pomoc/prirocniki/slovar-glossary-definicije" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_evalvacija_strani', '1', 'Ocenjujete lahko toliko razli&#269;nih spletnih strani, kolikor imate &#353;tevilo strani v va&#353;i anketi.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_telefon_anketarji', '1', 'Vklju&#269;ene so vse enote, ki jih je anketar poklical, tudi &#269;e se niso javile.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_arhiv_vprasalnik', '1', 'Ta mo&#382;nost se pogosto uporablja pri prenosu anket iz 1ka.si ali arnes.1ka.si na svojo lastno in&#353;talacijo, virtualno domeno, drug ra&#269;un in obratno.<span class="qtip-more"><a href="https://www.1ka.si/d/sl/pomoc/prirocniki/prenos-ankete-med-domenami-1ka" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_arhiv_podatki', '1', 'Ta mo&#382;nost se pogosto uporablja pri prenosu anket iz 1ka.si ali 1ka.arnes.si na svojo lastno in&#353;talacijo, virtualno domeno, drug ra&#269;un in obratno.<span class="qtip-more"><a href="https://www.1ka.si/d/sl/pomoc/prirocniki/prenos-ankete-med-domenami-1ka" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_vprasanje_trak', '1', 'Na&#269;in prikaza v obliki ocenjevalne lestvice, kjer sta podana opisa za skrajni dve vrednosti, med njima pa je linearen nabor &#353;tevilk. Npr. 1 - zelo nezadovoljen, 2, 3, 4, 5 - zelo zadovoljen.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_tabela_postopno', '1', 'Na&#269;in prikaza tabele, kjer se vpra&#353;anja iz tabele prikazujejo lo&#269;eno, ena za drugo. Ob odgovoru na vpra&#353;anje se prika&#382;e naslednje vpra&#353;anje iz tabele.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_nastavitve_enklik', '2', 'When you select this option, you will see an "Oneclick Survey" button in the "My Surveys" tab, and clicking on it will create a survey that will appear in the edit view.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_nastavitve_API', '2', 'The API is a collection of features that allow users to perform certain operations on 1KA via remote access (i.e. not via a web interface). As stated in the instructions, the user must first generate a key to use the API, and then use it to integrate features into his program or website. <span class = "qtip-more"> <a href="https://www.1ka.si/d/en/about/1ka-api" target="_blank"> Read more</a> </span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_nastavitve_zakleni', '2', 'If you select this option, you will not be able to edit the survey after activation, as it will be locked automatically to prevent further changes to the questionnaire during data collection (You can unlock the survey again by clicking on the lock icon next to the survey URL at the top of the screen).');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_nastavitve_jezik', '2', '<ol>The language can be customized on three levels:
<li><strong>Base language for the author of the survey: & nbsp; </strong> You can set the base language of the 1KA application, and you can choose between Slovenian and English.</li>
<li><strong>Respondent base language: & nbsp; </strong> Select a language for respondents from the drop-down list.</li>
<li><strong>Additional language settings for respondents: & nbsp; </strong> Translation of the basic questionnaire into different languages. </li>
</ol>
<span class="qtip-more"><a href="https://www.1ka.si/d/en/help/user-guide/edit/settings/language" target="_blank">Read more</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_status_koncni0', '2', 'If the option is checked, statuses that do not contain any units will not be displayed.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_status_cas0', '2', 'If the option is checked, days that do not contain any units will not be displayed.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_status_cas', '2', 'Actual average time spent by respondents completing the survey.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_nova_shrani', '2', 'From the list below, you can choose which folder from the "My Polls" list to save the poll to.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_mobilne_tabele', '2', '<h1> Mobile Survey Adjustments </h1> Respondents can also complete the online survey created with the 1KA tool via mobile phone. Due to the smaller screen, certain adjustments are needed for the optimal display of the survey. <span class = "qtip-more"> <a href="https://www.1ka.si/d/en/help/manuals/mobile-survey-adjustments" target="_blank"> Read more</a> </span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_obvescanje_odgovorZa', '2', 'In the case of several recipients of the notification, the person to whom the reply to the notification is addressed may be identified.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_izvozCSV_locitveni', '2', 'In csv format, data will be driven with a delimiter ";" or ",".');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_splosnenas_opozorilo', '2', '<h1>General question alert</h1>
<ul>For all survey questions you can set:
<li>Soft reminder for all questions: The respondent will be warned that he did not answer the question, but will not be required to answer. & nbsp;</li>
<li>Strong reminder to all questions: The respondent is warned that he did not answer the question. He must answer all the questions in order to continue.</li>
<li>Remove a remindes from all questions: Remove a set alert from a question.</li>
</ul>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_izvozCSV_tekst', '2', 'In case the option is checked, questions and variables names will also be exported.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_oblika_Ikone', '2', 'Change the color and size of checkbox / radio button icons for PC.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_oblika_slovarSlovar', '2', 'Change the balloon settings in the glossary pop-up. <span class = "q tip-more"> <a href="https://www.1ka.si/d/en/help/manuals/glossary" target="_blank"> Read more</a> </span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_oblika_slovarIKljucna', '2', 'Change keyword settings in the glossary.<span class="qtip-more"><a href="https://www.1ka.si/d/en/help/manuals/glossary" target="_blank">Preberi ve&#269;</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_evalvacija_strani', '2', 'You can rate as many different websites as you have the number of pages in your survey.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_telefon_anketarji', '2', 'All units called by the interviewer are included, even if they did not respond.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_arhiv_vprasalnik', '2', 'This option is often used when transferring surveys from 1ka.si or arnes.1ka.si to your own installation, virtual domain, other account and vice versa. <span class = "qtip-more"> <a href="https://www.1ka.si/d/en/help/manuals/transfer-survey-between-1ka-domains" target="_blank"> Read more </a> </span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_arhiv_podatki', '2', 'This option is often used when transferring surveys from 1ka.si or 1ka.arnes.si to your own installation, virtual domain, other account and vice versa.<span class="qtip-more"><a href="https://www.1ka.si/d/en/help/manuals/transfer-survey-between-1ka-domains" target="_blank">Read more</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_vprasanje_trak', '2', 'The method of display in the form of an evaluation scale, where descriptions are given for the extreme two values, and between them is a linear set of numbers. Eg 1 - very dissatisfied, 2, 3, 4, 5 - very satisfied.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_tabela_postopno', '2', 'A way of displaying a table, where the questions from the table are displayed separately, one after the other. When answering the question, the following question from the table appears.');
INSERT INTO srv_help (what, lang, help) VALUES ('Prevodi', '', '');
INSERT INTO srv_help (what, lang, help) VALUES ('displaydata_data', '2', 'When the option is selected, the respondents" data is displayed in the table');
INSERT INTO srv_help (what, lang, help) VALUES ('displaydata_meta', '2', 'Displays user meta data: entry date, correction date, times per page, IP, JavaScript, browser data');
INSERT INTO srv_help (what, lang, help) VALUES ('displaydata_status', '2', '<p>status (6-completed survey, 5-completed, 4-click on survey, 3-click on address, 2-email-error, 1-email-non-response, 0-email-not sent)</p><p>lurker - empty survey (1 = yes, 0 = no)</p><p>Sequence number of the entry</p>');
INSERT INTO srv_help (what, lang, help) VALUES ('displaydata_system', '2', 'Displays system data of the respondent: name, surname, email ....');
INSERT INTO srv_help (what, lang, help) VALUES ('dodaj_searchbox', '2', 'It also includes a search window in the map, through which the respondent can also search for a descriptive location on the map');
INSERT INTO srv_help (what, lang, help) VALUES ('individual_invitation', '2', 'With individualized invitations, you can check who on the list responded to the survey and who did not, which is the basis for sending reminders.<span class="qtip-more"><a href = "https://www.1ka.si/d/en/help/manuals/use-of-identification-codes-for-respondents" target="_blank">Read more</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('naslov_podvprasanja_map', '2', 'Sub-question text in the marker bubble');
INSERT INTO srv_help (what, lang, help) VALUES ('spremenljivka_reminder', '2', 'If the respondent did not answer the intended question, we have three options:
<UL>
<LI><b>No warning</b> means that respondents can continue the survey without warning, even if they do not answer a particular question.</LI>
<LI><b>Hard warning</b> means that if respondents do not answer the question with a hard warning, they are notified that they cannot continue solving the survey.</LI>
<LI><b>Soft Warning</b> means that respondents do not receive a warning if they do not respond, but can still proceed with the rescue.</LI>
</UL>');
INSERT INTO srv_help (what, lang, help) VALUES ('spremenljivka_sistem', '2', 'By clicking on the settings in we can collect between two types of integration of questions into the survey:
<UL>
<LI><b>Normal</b> question</LI>
<LI><b>System</b> question, which allows you to use the question outside the questionnaire itself. There are two aspects:
(1) a system question (eg name) can be marked and used by appearing in an electronic notification to the respondent, where the variable with his name is used in an email to thank him or her or inform him about the second wave of the survey,
(2) the system question can be imported directly into the ENTRY database via the survey questionnaire. Thus e.g. we can enter or upload a file with the telephone numbers or emails of the respondents (in this case we will also mark the variable as hidden, as the respondent does not have to enter the email).</LI>
</UL>
In case of using email invitations via 1KA email system, the variable " email " must be marked as system, regardless of whether the email was entered by the respondent himself or by the administrator before the survey.');
INSERT INTO srv_help (what, lang, help) VALUES ('spremenljivka_visible', '2', 'By clicking on visibility settings, we can collect between two types of integration of questions into the survey:
<UL>
<LI> <b> visible </b> question to be visible to respondents in the final questionnaire
<LI> <b> hidden </b> question that will only be visible to the author when editing the questionnaire. We use the option either for a hidden address or for the system variable e-mail, which respondents do not need to fill in.
');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_aapor_link', '2', 'AAPOR calculations');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_activity_quotas', '2', 'You can set a quota for the survey (limit the number of responses). You can set a quota for all responses or only for the appropriate units. When as many respondents as you specify respond to the survey, the survey will be deactivated for completion.

<span class = " qtip-more"> <a href = "https://www.1ka.si/d/en/help/manuals/survey-duration-based-on-date-or-the-number-of-responses" target="_blank">Read more </a> </span> ');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_activity_quotas_valid', '2', '<ul>Relevant units:
<li>partially completed surveys (status 5)</li>
<li>completed the survey (status 6)</li>
</ul>
<ul>
Other units:
<li>Clicks on speech (status 3)</li>
<li>survey clicks (status 4)</li>
</ul>
<span class="qtip-more"><a href = "https://www.1ka.si/d/en/help/manuals/status-of-units-relevance-validity-and-missing-values" target="_blank">Read more</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_alert_show_97', '2', 'The "Inappropriate" function is displayed when the respondent is warned that the answer "Inappropriate" is displayed only after the respondent has not answered the question. The question must be mandatory or have a warning.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_alert_show_98', '2', 'The "Reject" function is displayed when the respondent is warned that the "Rejected" answer is displayed only after the respondent has not answered the question. The question must be mandatory or have a warning.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_alert_show_99', '2', 'The "I don"t know" function is displayed when the respondent is warned that the answer "I don"t know" is displayed only after the respondent has not answered the question. The question must be mandatory or have a warning.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_block_random', '2', 'Randomization of block content');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_branching_expanded', '2', 'A concise view of the questions provides a better overview of the entire questionnaire and its structure - page breaks, blocks, conditions, loops, etc.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_calculation_missing', '2', 'Missing as 0');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_collect_all_status_0', '2', '<ul>The relevant statuses are:
<li>[6] Completed the survey</li>
<li>[5] Partially fulfilled</li>
</ul>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_collect_all_status_1', '2', '<ul><li>[6] Kon&#269;al survey</li>
<li>[5] Partially fulfilled</li>
<li>[4] Click on the survey</li>
<li>[3] Click to speak</li>
<li>[2] Error sending email</li>
<li>[1] Email sent (no reply)</li>
<li>[0] Email not yet sent</li>
<li>[-1] Unknown status</li>
</ul>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_concl_deactivation_text', '2', 'Deactivation notice');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_concl_PDF_link', '2', 'At the end of the survey, it displays an icon with a link to the respondent\'s PDF document.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_cookie_continue', '2', 'if the restriction is enabled that the user cannot continue filling in the survey without accepting a cookie, the introduction must be shown in the survey.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_create_survey_from_text', '2', 'Paste or enter the text of the questions and answers in the box on the left, and a preview of the questions is displayed in parallel on the right.

<span class="qtip-more"> <a href = "https://www.1ka.si/d/en/help/manuals/import-text-copying-text-to-1ka" target="_blank">Read more</a> </span> ');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_creport', '2', 'In a custom report, you can: <ul><li>make any selection of variables</li><li>edit them in any order</li><li>combine graphs, frequencies, averages...</li><li>add comments</li></ul><span class = "qtip-more"> <a href ="https://www.1ka.si/d/sl/pomoc/prirocniki/porocila-meri" target="_blank">Read more </a> </span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_crosstab_residual2', '2', 'Residuals make it extremely easy and efficient to analyze what’s going on in the table, as they show exactly where the correlation between the variables comes from. <span class="qtip-more"><a href="https://www.1ka.si/d/en/help/manuals/residuals-tables" target="_blank">Preberi ve&#269;</a></span> ');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_diag_complexity', '2', '<ul>Complexity:
<li>no conditions or blocks => very simple survey</li>
<li>1 condition or block => simple survey</li>
<li>1-10 conditions or blocks => demanding survey</li>
<li>10-50 conditions or blocks => complex survey</li>
<li>more than 50 conditions or blocks => very complex survey</li>
</ul>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_diag_time', '2', '<ul>Estimated completion time:
<li>up to 2 min => very short survey</li>
<li>2-5 min => short survey</li>
<li>5-10 min => medium length survey</li>
<li>10-15 min => long survey</li>
<li>15-30 min => very long survey</li>
<li>30-45 min => extensive survey</li>
<li>more than 45 min => very extensive survey</li>
</ul>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_disabled_question', '2', 'The question is disabled for respondents');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_dostop', '2', 'Survey editing settings. It is edited by you as the author and others, according to your settings.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_email_server_settings', '2', '<ul>
<li><strong> 1KA - default </strong>: invitations are sent via our server, where the sender is 1KA (info@1ka.si), and in the field "Reply to" is the default email address, with to which you are registered on 1KA. </li>
<li><strong> Gmail </strong>: Send 1KA invitations via your Gmail account within the 1KA system. </li>
<li><strong> Custom SMTP settings </strong>: Allows you to send invitations through your own mail server.</li>
</ul>
<span class = " qtip-more"> <a href = " https://www.1ka.si/d/en/help/manuals/sending-emails-via-an-arbitrary-server-eg-gmail" target="_blank">Read more</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_email_to_list', '2', 'Manually entered email addresses are also added to the notification list. For this procedure to work properly, you need to add a "email" system question to the survey, which must be visible (i.e. not set as hidden in advanced options).');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_email_with_data', '2', 'Connection option identifiers is only available to administrators and allows simultaneous display of survey data and identifiers. It is intended primarily for testing purposes and internal use on own installations, so we reiterate compliance with the Personal Data Protection Act. <span class="qtip-more"><a href="https://www.1ka.si/d/en/help/manuals/survey-data-paradata-identifiers-and-system-variables" target="_blank">Read more</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_gdpr_user_options', '2', 'Receive notifications of DSA news, upgrades and events (Online Survey Day). <span class = "qtip-more"> <a href="https://www.1ka.si/d/en/web-survey-day" target="_blank">Read more </a> </ span >');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_glasovanje_archive', '2', 'Add survey to voting archive');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_google_2fa_options', '2', 'Enabling two-level authentication means that when logging in to the 1KA tool, in addition to the selected password, you also enter a special code, which you obtain via a separate application for generating codes.

<span class = " qtip-more"> <a href = " https://www.1ka.si/d/sl/o-1ka/pogoji-uporabe-storitve-1ka/politika-piskotkov" target="_blank">Read more</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_grupe', '2', 'The questionnaire is divided into individual pages. Each page should contain an appropriate number of questions.

Here you can see all the pages of the questionnaire, including the introduction and conclusion. ');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_grupe_branching', '2', '<b>BRANCH AND CONDITIONS SURVEY:</b> The survey questionnaire requires skips, blocks, nesting conditions, etc.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_hierarchy_admin_help', '2', 'Here you can remove the entire level or click on the checkbox to choose if the code lists within the field are unique.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_hierarchy_edit_elements', '2', 'New items can be added for each selected level. Selecting the delete option deletes the entire level with all code lists. However, limited elements can be edited and only any level element can be removed.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_hierarhy_last_level_missing', '2', 'At the last level, the selected element and the e-mail address of the person who will receive the code for solving the survey via e-mail are missing.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_hotspot_region_color', '2', 'Allows you to edit the color of the area when it is selected.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_hotspot_tooltip', '2', 'Select options for displaying hints with area names.

Show next to mouseover: a hint is visible when the mouse cursor is over an area;
Hide: hint not visible;
');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_hotspot_tooltip_grid', '2', 'Choose options for displaying hints with answer categories.

Show next to mouseover: answer categories are visible when the mouse cursor is over an area;
Show when mouse clicks on an area: answer categories are visible when an area is clicked;
');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_hotspot_visibility', '2', 'Choose the type of lighting or how the areas are visible or invisible to the respondents.

Hide: the area is not visible;
Show: the area is visible;
Show next to mouseover: the area is visible when the mouse cursor is over the area;
');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_hotspot_visibility_color', '2', 'Allows you to edit the color of the area lighting.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_if_operator', '2', '<ul>
<li>"AND" = the condition is met only if all the criteria of the condition are met</li>
<li>"AND NOT" = condition met if the first criterion applies but not the second criterion</li>
<li>"OR" = condition met if any of the criteria are met (ie only one criterion is sufficient)</li>
<li>"OR NOT" = condition is met if the first criterion is met or if the second criterion is not met</li>
</ul>
<span class="qtip-more"><a href="https://www.1ka.si/d/sl/pomoc/prirocniki/uporaba-pogojev" target="_blank">Read more</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_inv_activate_1', '2', 'Selecting "email invitations with individualized URL" automatically turns on the "Yes" option for individualized invitations and "Automatically in URL" for entering the code. Respondents will be able to send an email to 1KA in which the URL of the survey will be individualized. As soon as the respondent clicks on the URL, the 1KA system will follow the respondent.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_inv_activate_2', '2', 'Selecting "manual entry of individualized code" automatically activates the "Yes" option for individualized invitations and the "Manual entry" option for code entry. Respondents will receive the same URL, and will initially need to enter their individual code manually. An invitation with a code can be sent to the respondent by email via the 1KA system. However, it can also be sent externally (outside the 1KA system): by letter by mail, by SMS in some other way; in such a case, the 1KA system merely records who, when and how sent the invitation.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_inv_activate_3', '2', 'By selecting "use general invitations without an individualized code", the option "email invitations with an individualized URL" remains disabled ("No"). The 1KA system will be able to send emails to respondents, but they will not have an individualized URL or individualized code.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_inv_activate_4', '2', 'Selecting "Email invitation with manual code entry" activates the "Yes" option for individualized invitations and "Manual entry" for code entry. Respondents will be able to send 1KA an email containing individualized code and a uniform URL of the survey. When the respondent clicks on the click URL, a request to enter the code received by email will be displayed.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_inv_archive_sent', '2', 'Clicking on the number of sent invitations displays a detailed overview of the sent invitations');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_inv_cnt_by_sending', '2', 'The table shows how many units the email has not yet been sent (0), how many units have received the email once (1), how many twice (2), how many three times (3) ...');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_inv_delay', '2', 'Delays are enabled when sending email invitations to multiple addresses, which means that at least 2 seconds elapse between an email sent to one recipient and an email sent to the next recipient. You can change this time as needed (depending on the capacity of your server).');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_inv_general_settings', '2', 'In online surveys, two general email invitations to all units are usually sufficient (the second invitation is also a thank you to the respondents).

In the case of a smaller number of units (eg a few hundred) we use the default email system (eg Gmail, Outlook ...), and if there are more units, we use mass sending tools (eg SqualoMail, MailChimp ...).

A large number of invitations and following the respondents is mostly unnecessary, but also demanding.

Email invitations can also be sent via the 1KA system, both general and individualized invitations with code and tracking. In addition, 1KA supports (documents) the sending of invitations by mail, SMS, etc. "');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_inv_message_title', '2', 'Message to be sent by email. The content of the message can be changed at will, each change is saved as a new message and can be accessed in the left window of the message list.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_inv_message_title_noEmail', '2', 'A message that will be sent by regular mail or SMS can be documented in 1KA. You can document multiple versions of messages and access them in the left column of the message list.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_inv_no_code', '2', 'You can also enter the survey without entering a code. It is especially recommended that only the author be identified for testing the questionnaire.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_inv_recipiens_add_invalid_note', '2', 'Make a separate list to add units that do not have emails.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_inv_sending_comment', '2', 'We can record a special feature or characteristic (eg preliminary notice, first invitation, reminder, etc.). In the case of manual transmission, it is advisable to indicate the actual day of dispatch (eg by post), as it may differ from the date of preparation of the list.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_inv_sending_double', '2', 'Duplicate records based on email are removed');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_inv_sending_type', '2', 'Invitations can also be sent by mail, SMS or otherwise outside the 1KA system.

Individualized code can be used for both transmission methods. ');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_novagrupa', '2', 'Clicking on <b> New Page </b> adds a new page to the questionnaire, which is placed before completion, and you can then edit and move it as you wish.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_parapodatki', '2', 'When activating advanced data, information can be accessed, such as the serial number of the record, the date of entry of the survey, the time of entry to the millisecond, exactly which device the respondent used, etc.

<span class = " qtip-more"> <a href = "https://www.1ka.si/d/en/help/manuals/survey-data-paradata-identifiers-and-system-variables" target="_blank">Read more</a></span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_para_neodgovori_link', '2', '<p>The non-response of a variable means how many relevant answers we got given the total number of units that received a particular question. Or otherwise: statuses (-1) are excluded from all relevant units that received this question.</p>
<p>Calculated according to the formula: (-1) * 100 / (valid) + (-1) + (-97) ) + (-98) + (-99))</p>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_recode_advanced_edit', '2', 'Advanced editing such as adding, renaming and deleting categories is available in editing the questionnaire.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_recode_chart_advanced', '2', 'Basic recoding is appropriate so that an age greater than 100 is recoded to -97 which is inappropriate. That is, to answer 9 - I do not know recode in inappropriate.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_recode_h_actions', '2', '<ul>The recoding functions are:
<li>Add If - opens a window to add decoding for each variable</li>
<li>Edit - displays a window for edited decoding of each variable</li> 
<li>Remove - removes or completely deletes the decoding of an individual variable</li> 
<li>Enabled - currently enables or disables the decoding of an individual variable</li> 
<li>Visible - sets the variable visible or invisible in the questionnaire</li> 
</ul>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_skins_Embed', '2', 'For surveys included in another website.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_skins_Embed2', '2', 'For surveys included in another website (this version).');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_skins_Fdv', '2', 'Only for users licensed by FDV.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_skins_Slideshow', '2', 'For the presentation.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_skins_Uni', '2', 'Only for users licensed by FDV.');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_subsequent_answers', '2', 'If the second option is selected, the user cannot edit their answers later (eg by clicking back).

<span class = " qtip-more"> <a href="https://www.1ka.si/d/en/help/manuals/survey-settings" target="_blank">Read more</a></span> ');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_survey_type', '2', '<p>1KA notes that simple surveys require a different interface than complex ones.</p>
<ul>1KA therefore allows you to always choose the optimal interface, depending on the complexity of the survey you need:
<li><b>VOTING:</b> single-question survey, elections, etc., but with the possibility of displaying the results online.</li>
<li><b>FORM:</b> short one-sided questionnaire, form, registration, form, email list, event registration, etc.</li>
<li><b>CONDITION AND CONDITIONS SURVEY:</b> The survey questionnaire requires skips, blocks, nesting conditions, etc.</li>
</ul>
<p>You can also switch between interfaces later, unless, of course, the transition would mean deleting certain data. Thus, once you have the conditions, it is no longer possible to switch to simpler interfaces. Similarly, it is not possible to move from a multilateral survey to a form, but it is of course possible to move to a conditional survey.</p>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_telephone_help', '2', 'The selected module provides support for telephone surveys. <span class = "qtip-more"> <a href="https://www.1ka.si/d/en/help/manuals/telephone-survey" target="_blank">Read more</a> </span>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_upload_limit', '2', '<h1>Upload restrictions</h1>
<ul>Additional restrictions for respondents when uploading a file:
<li>the maximum size of an individual file uploaded by the respondent is <strong>16 MB</strong>;</li>
<li>allowed file types are: <strong>"jpeg", "jpg", "png", "gif", "pdf", "doc", "docx", "xls", "xlsx"</strong>;</li>
<li>a maximum of <strong>4 input fields</strong> is allowed <strong>in an individual question</strong>, i.e. a maximum of 4 files that the respondent can upload for one question.</li>
</ul>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_user_base_individual_invitaition_note', '2', 'Individualized invitations enable tracking of respondents via individual code or password.

The survey URL includes individualized code, and the respondent only needs to click on the URL or manually enter the given generated code.
');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_user_base_individual_invitaition_note2', '2', '<p>Selecting "No" disables the individualized invitation module. Anyone who sees or knows the URL can be interviewed. Respondents in such a case could not follow; we do not know who answered and who did not.</p>
<p>1KA system can still send (email) or document (mail, SMS, other) the sending of a general non-individualized invitation, where all respondents receive a single URL. This means that it was recorded to whom, when and how the invitation was sent, but it will not be indicated who responded and who did not.</p>');
INSERT INTO srv_help (what, lang, help) VALUES ('srv_vprasanje_max_marker_map', '2', 'The number of possible responses / markers submitted on the map');
INSERT INTO srv_help (what, lang, help) VALUES ('toolbox_advanced', '2', '<p>In a survey with a larger number of questions, we recommend that you divide the survey into meaningful blocks.</p><p>You can close and open the blocks as needed, giving you a better overview of the survey.</p>');
INSERT INTO srv_help (what, lang, help) VALUES ('usercode_required', '2', '<p><b>Automatic</b>: The code is automatically transferred to the survey from the URL of the email invitation link.</p><p><b>Manual</b>: The user must enter the code manually. The code is generated and exported in the "Browse" tab.</p>');
INSERT INTO srv_help (what, lang, help) VALUES ('usercode_skip', '2', '<ul>
<li><b>No</b>: To complete the survey, the respondent must either receive an email invitation where he / she clicks on the link to automatically transfer the code to the survey, or the respondent must know the code and enter it manually in the survey.</li>
<li><b>Yes</b>: Users who did not receive the code can also take the survey.</li>
<li><b>Author only</b>: In addition to users who have the code, they can the code-free survey is also completed by the authors of the survey (mainly for test purposes).</li>
</ul>');
INSERT INTO srv_help (what, lang, help) VALUES ('user_location_map', '2', 'The browser will try to determine the current location of the respondent. The respondent will first be asked by the browser for permission to share his location.');
