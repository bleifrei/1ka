var var_lt_timeout = 6000000;
//var var_lt_timeout = 60000;
var languageTechnologySettings = {};
//var languageTechnologyWordCustom = {};



function onload_init_language_technology() {
    if ($("div#language_technology.language_technology" ).length == 0) {
        return false;
    }
    $("div#language_technology.language_technology" ).attr("onselectstart","return false") 
         
    // prikažemo nastavitve prve spremenljivke
    showLanguageTechnologyFirst();
    
    $("div#language_technology.language_technology" ).on("click", 'div.spremenljivka_content', function() {
        spr_id = $(this).attr('spr_id');
        showLanguageTechnology(spr_id );
    });
    
//    runLanguageTechnology();   
}

function displaySpremenljivkaContainers(spremenljivka) {
    if ($('#lt_'+spremenljivka).length > 0) {
        // pobrišemo obstojece dive
        $('#lt_'+spremenljivka).remove();
    }

    $sprContainer = $('li#branching_'+spremenljivka);
    
    var ltLang = $.extend({}, lang);

    // narišemo vse nove potrebne dive
    $ltDiv = $("<div/>", {
            id: 'lt_'+spremenljivka,
            class: 'lt_div'
        })
        .append( 
            $("<div/>", {
                id: 'lt_word_data_'+spremenljivka,
                class: 'lt_word_data'
                })
                .append(
                    $("<div/>", {
                        id: 'lt_words_'+spremenljivka,
                        class: 'lt_words',
                    })
                    .append( $('<div/>', { 
                        class: 'lt_header',
                        text: ltLang['srv_language_technology_flagged_wordings']
                        })
                    )
                )
                .append( $("<div/>", { 
                        id: 'lt_synonyms_'+spremenljivka,
                        class: 'lt_word_synonym lt_words_synonyms' }
                        )
                )
                .append( $("<div/>", { class: 'clr' }))
                .append( $("<div/>", { 
                    id: 'lt_results_'+spremenljivka,
                    class: 'lt_word_hypernym_hyponym' })
                    .append(
                        $("<span/>", {
                        id: 'lt_hypernyms_'+spremenljivka,
                        class: 'lt_word_hypernym',
                        }).hide()
                        .append( $("<div/>", {class:'lt_header', text: 'Hypernyms'}) )
                        .append( $("<div/>", {class:'lt_box_content'}) )
                    )
                    .append(
                        $("<span/>", {
                            id: 'lt_hyponyms_'+spremenljivka,
                            class: 'lt_word_hyponym',
                        }).hide()
                        .append( $("<div/>", {class:'lt_header', text: 'Hyponyms'}) )
                        .append( $("<div/>", {class:'lt_box_content'}) )
                    )
                    .append(
                        $("<span/>", {
                            id: 'lt_chwo_'+spremenljivka,
                            class: 'lt_word_chosen_wording',
                        }).hide()
                        .append( $("<div/>", {class:'lt_header', text: 'Properties of alternative wordings:'}) )
                        .append( $("<div/>", {class:'lt_box_content'}) )
                    )                    
                )
        );

    $sprContainer.after($ltDiv);

};

function showLanguageTechnologyFirst() {
    // poiščemo prvo spremenljivko
    spr_id = $( "div#language_technology.language_technology div.spremenljivka_content" ).first().attr('spr_id');
    
    showLanguageTechnology(spr_id );    
}

function showLanguageTechnology(spr_id ) {

    settings = readLanguageTechnologySettings(spr_id);
    
    $("#vprasanje_float_editing.language_technology").find('#lt_min_FWD_spr').val(settings.lt_min_FWD);
    $("#vprasanje_float_editing.language_technology").find('#lt_special_setting').prop("checked", settings.lt_special_setting);
    $("#vprasanje_float_editing.language_technology").attr('spr_id', spr_id);
    vprasanje_pozicija(spr_id);
}


function runLanguageTechnology() {
    spr_id = $("#vprasanje_float_editing.language_technology").attr('spr_id');
    settings = readLanguageTechnologySettings(spr_id);
        
    // ali imamo zakeširano (ne delamo ajaxa)
    if (true || LT_Cache_response.isSet(spr_id) == false) {

        ///return false;
        var request = {
                        spremenljivka: spr_id, 
                        anketa: srv_meta_anketa_id,
                        settings: settings
                        };
             
            // nardimo ajax klic
        $.ajax({
            cache: false,
            timeout: var_lt_timeout,
            xhrFields: { withCredentials: true },
            url: 'ajax.php?a=runLanguageTechnology',
            type: 'post',
            //dataType: 'json',
            data: request,
            spremenljivka: spr_id,
            container: $('li#branching_'+spr_id),
            beforeSend: function() {
                // odstranimo morebitne predhodne higlighte
                //cleanLanguageTechnology()
                this.container.fadeTo(500, 0.4);
            },
            success: function (response) {
                
                if (response['error']['hasError'] == false && response['data'] != undefined) {
                    // shranimo celoten response spremenljivke da potem lovimo shranjene podatke
                    LT_Cache_response.set(this.spremenljivka, response['data']);
                    displayProblematicWords(this.spremenljivka);
                }
                if (response['error'] != undefined) {
                    if (response['error']['hasError'] == true) {
                        genericAlertPopup('alert_parameter_response',response['error']['msg']);
                        // cleanUpAndReturn();
                        return false;
                    }
                    delete response['error'];
                }
                if (response['setting'] != undefined) {
                    languageTechnologySettings = response['setting'];
                    delete response['setting'];
                }
                
                return true;
            },
            error: function(x, t, m) {
                if(t==="timeout") {
                    genericAlertPopup('alert_timeout');
                } else {
                    genericAlertPopup('alert_unknown_error');
                }
            },
            complete: function() {
                this.container.fadeTo(500, 1);
            }
        });
    } else {
        // imamo cache
        displayProblematicWords(spr_id);        
    }

}


function readLanguageTechnologySettings(spr_id) {
    
    lt_special_setting = false;
    // če imamo lastne nastavitve za spremenljivko 
    if (typeof languageTechnologySettings[spr_id] != 'undefined') {
        lt_min_FWD = languageTechnologySettings[spr_id]['lt_min_FWD'];
        lt_special_setting = languageTechnologySettings[spr_id]['lt_special_setting'];
        lt_language = languageTechnologySettings[spr_id]['lt_language'];

    } else {
        // imamo globalne nastavitve
        lt_min_FWD = $('#lt_min_FWD').val();
        lt_special_setting = false;
        lt_language = $('#lt_language').val();
    }    

    
    var result = {
        lt_special_setting: lt_special_setting,
        lt_min_FWD: lt_min_FWD,
        lt_language: lt_language
    };
    return result;
}

function saveLanguageTechnologySetting(){
    lt_special_setting = $("#vprasanje_float_editing.language_technology").find('#lt_special_setting').prop("checked");
    lt_min_FWD =  $("#vprasanje_float_editing.language_technology").find('#lt_min_FWD_spr').val();
    spr_id = $("#vprasanje_float_editing.language_technology").attr('spr_id');

    // ce mamo globalne nastavitve
    if (lt_special_setting == false) {
        $('#lt_min_FWD').val(lt_min_FWD);
        // odstranimo lastne nastavitve
        delete(languageTechnologySettings[spr_id]);
    } else {
        // shranimo lastne nasravitve
        languageTechnologySettings[spr_id] = [];
        languageTechnologySettings[spr_id]['lt_min_FWD'] = lt_min_FWD;
        languageTechnologySettings[spr_id]['lt_special_setting'] = lt_special_setting;
        
    }
}


function displayProblematicWords(spremenljivka) {
    
    var ltLang = $.extend({}, lang); // deep copy

    var $divContainer = $('li#branching_'+spremenljivka);
    // počistimo predhodne označbe
    //$divContainer.find('span.highlight').unhighlight()
    $divContainer.unhighlight()
    
    displaySpremenljivkaContainers(spremenljivka);
    $('#lt_word_data_'+spremenljivka).show();
                            
    // dodamo seznam vseh besed
    var words = LT_Cache_response.get(spremenljivka);
   
    if (words.length > 0) {
        $LTWordsTable = $('<table/>', {id: 'LTWordsTable'+spremenljivka, class: 'LTWordsTable'})
                .append($('<tr/>')
                    .append($('<th/>', {text: 'Beseda'}))
                    .append($('<th/>', {text: 'FWD'}))
                    .append($('<th/>', {text: 'Tag'}))
                    .append($('<th/>', {text: 'NoM'}))
                )
                .appendTo($('#lt_words_'+spremenljivka))
                .on('click', 'tr td:not(select, option)', function(e) {
                    wordIndex = $(this).closest('tr').data('wordIndex');
                    word = $(this).closest('tr').data('word');
                    tag = $(this).closest('tr').data('tag');
                    if ($(e.target).is('select') || $(e.target).is('option')) {
                        e.stopPropagation();    
                        return false;
                    }
                    displayWord(spremenljivka, wordIndex, tag);
                });
                
        $.each(words, function(wi, $wordData) {
            var wordIndex = wi;
            
            var word = $wordData.word;
            var fwd = $wordData.FWD;
            var tag = $wordData.Tag.toLowerCase();
            var nom = $wordData.NoM;
            
            // obarvamo besedo
            
            //console.log($divContainer.find("*"))
            console.log(word,'word')
            //$divContainer.find("*").highlight(word);
            $('#spremenljivka_contentdiv'+spremenljivka).highlight(word);
            $LTWordRow = $('<tr/>')
                    .append($('<td/>')
                        .append($('<span/>', {class: 'sprites'}))
                        .append($('<span/>', {text: word}))
                    )
                    .append($('<td/>', {text: fwd}))
                    .append($('<td/>', {})
                        .append($("<select/>", {'data-word': word, 'data-tag': tag })
                        .append($("<option/>", {value: 'n', text: ltLang['srv_language_technology_noun'] + (tag == 'n' ? '*' : '')}))
                        .append($("<option/>", {value: 'v', text: ltLang['srv_language_technology_verb'] + (tag == 'v' ? '*' : '')}))
                        .append($("<option/>", {value: 'a', text: ltLang['srv_language_technology_adjective'] + (tag == 'a' ? '*' : '')}))
                        .append($("<option/>", {value: 'adv', text: ltLang['srv_language_technology_adverb'] + (tag == 'adv' ? '*' : '')}))
                        .append($("<option/>", {value: 'e', text: ltLang['srv_language_technology_existential'] + (tag == 'e' ? '*' : '')}))
                        .on('change', function() {
                            var word =$(this).data('word');
                            var wordType =  $(this).val();
                            changeWordType(spremenljivka, wordIndex, word, wordType, tag);
                        }).val(tag)
                        )                    
                    )
                    .append($('<td/>', {text: nom}))
                    .data('word', word)
                    .data('wordIndex', wordIndex)
                    .data('tag', tag)
                    .appendTo($LTWordsTable);
            
        });
    }
}



function displayWord(spremenljivka, wordIndex, tag) {

    displaySpremenljivkaWordings(spremenljivka, wordIndex);
            
    // holder ul =
    $LTWordsTable = $('div#lt_words_'+spremenljivka+' table.LTWordsTable');

    // deaktiviramo ostale checkboxe in liste
    $LTWordsTable.find('tr').removeClass('active');
    $LTWordsTable.find('tr td:first-child span.sprites').removeClass('arrow_small2_r');

    // aktiviramo izbran list in checkbox
    $LTWordsTable.find('tr').eq(wordIndex+1).addClass('active');
    $LTWordsTable.find('tr').eq(wordIndex+1).find('td:first-child span.sprites').addClass('arrow_small2_r');
 
    // izberemo pravilno opcijo
    $("#lt_word_detail_div_"+spremenljivka).find("select").val(tag);
    // prikažemo sopomenke besede
    displayWordSynsets(spremenljivka, wordIndex);
}

function displayWordSynsets(spremenljivka, wordIndex) {
    var ltLang = $.extend({}, lang);
    
    $LTWordSynonyms = $('div#lt_synonyms_' + spremenljivka);
    
    $LTWordSynonyms.html($('<div/>', { 
                        class: 'lt_header',
                        text: ltLang['srv_language_technology_relevant_meanings']
                        })
    );
                                
    var words = LT_Cache_response.get(spremenljivka);
    var synsets = words[wordIndex]['Synset'];
    var wordType = words[wordIndex]['Tag'];

    // izrišemo sopomenke - synonyms
    $SynsetUl = $("<ul/>", {
        class: 'lt_word_synset'
    })
        .appendTo($LTWordSynonyms)
        .on('click', 'li input', function(event ) {
            event.stopPropagation();
            synsetIndex = $(this).closest('li').index();

            checked = $(this).is(':checked') == true;
            clickWordSynset(spremenljivka, wordIndex, wordType, synsetIndex, checked);
                            
        })
        .on('click', 'li:not(input)', function(event ) {
            event.stopPropagation();
            synsetIndex = $(this).index();

            // change input
            checked = $(this).find('input').is(':checked') != true;
            $(this).find('input').prop("checked", checked);
            clickWordSynset(spremenljivka, wordIndex, wordType, synsetIndex, checked);

        });
    var index = 0;
    // počistimo predhodne
    
    $('#lt_hypernyms_'+spremenljivka).css('display','inline-block').find('div.lt_box_content').text('No hypernyms');
    $('#lt_hyponyms_'+spremenljivka).css('display','inline-block').find('div.lt_box_content').text('No hyponyms');

    
    if (synsets.length) {
        // ajax za hypernyme in hyponyme
        displaySynsetHypernymHyponym(spremenljivka, wordIndex, wordType);
        
        var selectedSynset = undefined;
        
        $.each(synsets, function(si, $synset) {
            index = si;
            // če imamo izbrano besedo, jo izrišemo wordIndex = selectedWordIndex
///            isSet = isSetSynset(spremenljivka, wordIndex, si);
            isSet = LT_Synonyms.isSet(spremenljivka, wordIndex, si);
            selectedClass = '';
            if (isSet) {
                selectedClass = ' selectedWord';
            }

            $SynsetLi = $("<li/>", {class: 'lt_relevant_meaning' + selectedClass})
                .append($('<span/>').append($('<input/>', {type: 'checkbox'})))
                .append($('<span/>', {text: cleanUpSynonym($synset.synonyms)}))
                .appendTo($SynsetUl);
            if (isSet) {
                $SynsetLi.find('input').prop('checked', true);
            }

        });
        index++;
    }
                    
    //if (!synsets.length) {
/*
    ///isSet = isSetSynset(spremenljivka, wordIndex, index);
    isSet = LT_Synonyms.isSet(spremenljivka, wordIndex, index);
    selectedClass = '';
    if (isSet) {
        selectedClass = 'selectedWord';
    }
    $SynsetLi = $("<li/>", {class: selectedClass})
    .append($('<span/>').append($('<input/>', {type: 'checkbox'})))
    .append($('<span/>').append(
            $('<input/>', {type:'text', value:getCustomWording(spremenljivka, wordIndex)})
            .on('change', function() {
                words = $(this).val();
                addCustomWording(spremenljivka, wordIndex, words);
            })
        ))
    .appendTo($SynsetUl)

    if (isSet) {
        $SynsetLi.find('input').prop('checked', true);
    }
    */
}
/*
function getCustomWording(spremenljivka, wordIndex) {
    result = '';
    if (languageTechnologyWordCustom['sp_'+spremenljivka] != undefined) {
        if (languageTechnologyWordCustom['sp_'+spremenljivka]['wi_'+wordIndex] != undefined) {
            result = languageTechnologyWordCustom['sp_'+spremenljivka]['wi_'+wordIndex];     
        }
    }
    //if (languageTechnologyWordCustom[spremenljivka+'_'+wordIndex] != undefined) {
    //    result = languageTechnologyWordCustom[spremenljivka+'_'+wordIndex];
    //}
    return result;
}

function addCustomWording(spremenljivka, wordIndex, words) {
    if (languageTechnologyWordCustom['sp_'+spremenljivka] == undefined) {
        languageTechnologyWordCustom['sp_'+spremenljivka] = {};
    }
    if (languageTechnologyWordCustom['sp_'+spremenljivka]['wi_'+wordIndex] == undefined) {
        languageTechnologyWordCustom['sp_'+spremenljivka]['wi_'+wordIndex] = words;
    }

    
    //languageTechnologyWordCustom[spremenljivka+'_'+wordIndex] = words;
}
*/

function cleanUpSynonym(synonyms) {
    return synonyms.substr(0, synonyms.indexOf('|')); 
}


function changeWordType(spremenljivka, wordIndex, word, wordType) {

    var wkey = spremenljivka +'_'+ wordIndex+'_' + word + '_' + wordType;

    //  preverimo cache
    if (LT_Cache_words.isSet(wkey) == false) {
        // nardimo request
        var request = {
                spremenljivka: spremenljivka, 
                anketa: srv_meta_anketa_id,
                lt_word: word,
                lt_tag: wordType,
                lt_language: $("#lt_language").val(),
                lt_min_FWD: $("#lt_min_FWD").val(),
                lt_min_nNoM: $("#lt_min_nNoM").val(),
                lt_min_vNoM: $("#lt_min_vNoM").val()
        };
        // nardimo ajax klic
        $.ajax({
            cache: false,
            timeout: var_lt_timeout,
            xhrFields: { withCredentials: true },
            url: 'ajax.php?a=runLanguageTechnologyWord',
            type: 'post',
            //dataType: 'json',
            data: request,
            spremenljivka: spremenljivka,
            wordIndex: wordIndex,
            container: $('li#branching_'+spremenljivka),
            beforeSend: function() {
            },
            success: function (response) {
                
                if (response['error'] != undefined) {
                    if (response['error']['hasError'] == true) {
                        genericAlertPopup('alert_parameter_response',response['error']['msg']);
                        
                        // cleanUpAndReturn();
                        return false;
                    }
                    delete response['error'];
                }
                if (response['setting'] != undefined) {
                    languageTechnologySettings = response['setting'];
                    delete response['setting'];
                }

                // zamenjamo besedo v cache responsu
                var _words = LT_Cache_response.get(this.spremenljivka);
                _words[this.wordIndex] = response[0];
                LT_Cache_response.set(this.spremenljivka, _words);
                
                LT_Cache_words.set(wkey, response[0]);
                
                displayNewWordType(spremenljivka, wordIndex, response[0], word, wordType);
                
                return true;
            },
            error: function(x, t, m) {
                if(t==="timeout") {
                    genericAlertPopup('alert_timeout');
                } else {
                    genericAlertPopup('alert_unknown_error');
                }
            },
            complete: function() {
            }
        });
    } else {
        _wordCache = LT_Cache_words.get(wkey);

        // zamenjamo besedo v responsu
        var _words = LT_Cache_response.get(spremenljivka);
        _words[wordIndex] = _wordCache;
        LT_Cache_response.set(spremenljivka, _words);   

        displayNewWordType(spremenljivka, wordIndex, _wordCache)
        return true;

    }
}

function displayNewWordType(spremenljivka, wordIndex, wordData) {

    // TODO počistimo morebitne izbrane ysnonyms, hyponyme, hypernyme
    LT_Synonyms.unSet(spremenljivka, wordIndex);
    LT_Hypernyms.unSet(spremenljivka, wordIndex);
    LT_Hyponym.unSet(spremenljivka, wordIndex);

    changeWordSynonym(spremenljivka, wordIndex)
    
    // popravimo podatke v tabeli besede
    $LTWordsTableTr = $('div#lt_words_'+spremenljivka+' table.LTWordsTable').find('tr').eq(wordIndex+1);
    $LTWordsTableTr.find('td:nth-child(2)').html(wordData.FWD)
    $LTWordsTableTr.find('td:nth-child(4)').html(wordData.NoM);
 
    
    // prikažemo na novo
    displaySpremenljivkaWordings(spremenljivka, wordIndex);
    
    /// changeWordSynonym(spremenljivka, wordIndex);
    displayWordSynsets(spremenljivka, wordIndex);
}

function clickWordSynset(spremenljivka, wordIndex, wordType, synsetIndex, checked) {
    
    selectWordSynset(spremenljivka, wordIndex, wordType, synsetIndex, checked);
    ///changeWordHypernym(spremenljivka, wordIndex, synsetIndex, checked);
    ///displayWordHypernym(spremenljivka, wordIndex);
    displaySpremenljivkaWordings(spremenljivka, wordIndex);
}

function clickWordHH(spremenljivka, wordIndex, type, hyponymIndex, checked) {
    selectWordHH(spremenljivka, wordIndex, type, hyponymIndex, checked);
    // todo clean
    if (checked) {
        $('#lt_'+type+'s_'+spremenljivka+' div.lt_box_content ul li').eq(hyponymIndex).addClass('selectedWord');
    } else {
        $('#lt_'+type+'s_'+spremenljivka+' div.lt_box_content ul li').eq(hyponymIndex).removeClass('selectedWord');
    }
    displaySpremenljivkaWordings(spremenljivka, wordIndex);
}

function displaySynsetHypernymHyponym(spremenljivka, wordIndex, wordType) {
    
    var _words = LT_Cache_response.get(spremenljivka);
    var synsets = _words[wordIndex]['Synset'];    
    // preverimo cache
    var wkey = spremenljivka +'_'+ wordIndex+'_' + wordType;

    if (true || LT_Cache_word_hypo_hyper_nyms.isSet(wkey) == false) {

        settings = readLanguageTechnologySettings(spremenljivka);

        var request = {
                        spremenljivka: spremenljivka, 
                        anketa: srv_meta_anketa_id,
                        synsets: synsets,
                        settings: settings
                        };
                        
                        
        // nardimo ajax klic
        $.ajax({
            cache: false,
            async: true,
            timeout: var_lt_timeout,
            xhrFields: { withCredentials: true },
            url: 'ajax.php?a=runLanguageTechnologyHypoHypernym',
            type: 'post',
            //dataType: 'json',
            data: request,
            spremenljivka: spr_id,
            container: $('li#branching_'+spr_id),
            success: function (response) {
                if (response['error']['hasError'] == false) {
                    var cleanhypernyms = $.map(response.data.hypernyms, function(el) { return el; });
                    var cleanhyponyms = $.map(response.data.hyponyms, function(el) { return el; });

                    var _cache = {};
                    _cache.cleanhypernyms = cleanhypernyms;
                    _cache.cleanhyponyms = cleanhyponyms;
          
                    LT_Cache_word_hypo_hyper_nyms.set(wkey, _cache);
                    showSynsetHypernymsHyponyms(spremenljivka, wordIndex, wordType, cleanhypernyms, cleanhyponyms);
                }
                
                if (response['error'] != undefined) {
                    if (response['error']['hasError'] == true) {
                        genericAlertPopup('alert_parameter_response',response['error']['msg']);
                        return false;
                    }
                }
                return true;
            },
            error: function(x, t, m) {
                if(t==="timeout") {
                    genericAlertPopup('alert_timeout');
                } else {
                    genericAlertPopup('alert_unknown_error');
                }
            },
            complete: function() {
                this.container.fadeTo(500, 1);
            }
        });
    } else {
        //LT_Cache_word_hypo_hyper_nyms
        _cache = LT_Cache_word_hypo_hyper_nyms.get(wkey);
        cleanhypernyms = _cache['cleanhypernyms'];
        cleanhyponyms = _cache['cleanhyponyms'];
        showSynsetHypernymsHyponyms(spremenljivka, wordIndex, wordType, cleanhypernyms, cleanhyponyms);
    }
}

function showSynsetHypernymsHyponyms(spremenljivka, wordIndex, wordType, cleanhypernyms, cleanhyponyms) {
    
    // dodamo k spremenljivki izbrane hypernyme
    sprData = LT_Cache_response.get(spremenljivka);
    sprData[wordIndex]['cleanhypernyms'] = cleanhypernyms;
    sprData[wordIndex]['cleanhyponyms'] = cleanhyponyms;
    LT_Cache_response.set(spremenljivka, sprData);
    // izrišemo hipernime in hyponime, ter po potrebi izberemo checkboxe
    $lt_word_hypernym = $('#lt_hypernyms_'+spremenljivka + ' div.lt_box_content');
    if (cleanhypernyms.length > 0) {
        $lt_word_hypernym.text('');
            // izrišemo sopomenke - synonyms
        $hypernymsUl = $("<ul/>", {
            class: 'lt_word_hypernym_ul'
        })
        .appendTo($lt_word_hypernym)
        .on('click', 'li input', function(event ) {
            event.stopPropagation();
            hypernymIndex = $(this).closest('li').index();
            
            checked = $(this).is(':checked') == true;
            clickWordHH(spremenljivka, wordIndex, 'hypernym', hypernymIndex, checked);                

        })
        .on('click', 'li:not(input)', function(event ) {
            event.stopPropagation();
            hypernymIndex = $(this).index();
            // change input
            checked = $(this).find('input').is(':checked') != true;
            $(this).find('input').prop("checked", checked);
            clickWordHH(spremenljivka, wordIndex, 'hypernym', hypernymIndex, checked);
        });        

        // todo check checkboxes
        $.each(cleanhypernyms, function(si, $hypernym) {
            isset = LT_Hypernyms.isSet(spremenljivka, wordIndex, si);

            $HypernymLi = $('<li' + (isset ? ' class="selectedWord"' : '') + '><span><input type="checkbox"' 
            + (isset ? 'checked="checked"' : '')+ '></span><span>'+$hypernym+'</span></li>')
            .appendTo($hypernymsUl);
        })
    } else {
        $lt_word_hypernym.text('No hypernyms');
    }
    
    $lt_word_hyponym = $('#lt_hyponyms_'+spremenljivka+' div.lt_box_content');
    if (cleanhyponyms.length > 0) {
        $lt_word_hyponym.text('');
        $hyponymsUl = $("<ul/>", {
            class: 'lt_word_hyponym_ul'
        })
        .appendTo($lt_word_hyponym)
        .on('click', 'li input', function(event ) {
            event.stopPropagation();
            hyponymIndex = $(this).closest('li').index();

            checked = $(this).is(':checked') == true;
            clickWordHH(spremenljivka, wordIndex, 'hyponym', hyponymIndex, checked);        
        })
        .on('click', 'li:not(input)', function(event ) {
            event.stopPropagation();
            hyponymIndex = $(this).index();

            // change input
            checked = $(this).find('input').is(':checked') != true;
            $(this).find('input').prop("checked", checked);
            clickWordHH(spremenljivka, wordIndex, 'hyponym', hyponymIndex, checked);
        });
     // todo check checkboxes
        $.each(cleanhyponyms, function(si, $hyponym) {
            isset = LT_Hyponym.isSet(spremenljivka, wordIndex, si);
            $HyponymLi = $('<li' + (isset ? ' class="selectedWord"' : '') + '><span><input type="checkbox"' + (isset ? ' checked="checked"' : '') + '></span><span>'+$hyponym+'</span></li>')
            .appendTo($hyponymsUl);
        })
    } else {
        $lt_word_hyponym.text('No hyponyms');   
    }
}

function selectWordSynset(spremenljivka, wordIndex, wordType, synsetIndex, checked) {
//console.log('selectWordSynset:'+spremenljivka+':'+wordIndex+':'+wordType+':'+synsetIndex+':'+checked)
 
    if (checked) {
///        setSynset(spremenljivka, wordIndex, synsetIndex);
        LT_Synonyms.set(spremenljivka, wordIndex, synsetIndex);

    } else {
///        unsetSynset(spremenljivka, wordIndex, synsetIndex);
        LT_Synonyms.unSet(spremenljivka, wordIndex, synsetIndex);

    }
    // holder ul =
    $ul = $("#lt_synonyms_"+spremenljivka+" ul");
    ///isSet = isSetSynset(spremenljivka, wordIndex, synsetIndex);
    isSet = LT_Synonyms.isSet(spremenljivka, wordIndex, synsetIndex);
    if (isSet) {
        $ul.find('li').eq(synsetIndex).addClass('selectedWord');
    } else {
        $ul.find('li').eq(synsetIndex).removeClass('selectedWord');
    }

    changeWordSynonym(spremenljivka, wordIndex);

}

function selectWordHH(spremenljivka, wordIndex, type, hyponymIndex, checked) {
        
    // get the word type
    if (checked) {
        if (type == 'hyponym') {
            LT_Hyponym.set(spremenljivka, wordIndex, hyponymIndex);           
        } else if (type == 'hypernym') {
            LT_Hypernyms.set(spremenljivka, wordIndex, hyponymIndex);
        }
    } else {
        if (type == 'hyponym') {
            LT_Hyponym.unSet(spremenljivka, wordIndex, hyponymIndex);           
        } else if (type == 'hypernym') {
            LT_Hypernyms.unSet(spremenljivka, wordIndex, hyponymIndex);
        }
    }        
    changeWordSynonym(spremenljivka, wordIndex);  
}

function changeWordSynonym(spremenljivka, wordIndex){
    //wordHasSynset = wordHasSynsets(spremenljivka, wordIndex);
    wordHasSynset = LT_Synonyms.hasSub(spremenljivka, wordIndex);

    wordHasHypernym = LT_Hypernyms.hasSub(spremenljivka, wordIndex);
    
    wordHasHyponym = LT_Hyponym.hasSub(spremenljivka, wordIndex);

    if (wordHasSynset || wordHasHypernym || wordHasHyponym) {
        $("#LTWordsTable" + spremenljivka).find('tr').eq(wordIndex+1).addClass('selectedWord');    
    } else {
        $("#LTWordsTable" + spremenljivka).find('tr').eq(wordIndex+1).removeClass('selectedWord');
    }
}



function stripAccents(str) { 
    var rExps=[ 
    {re:/[\xC0-\xC6]/g, ch:'A'}, 
    {re:/[\xE0-\xE6]/g, ch:'a'}, 
    {re:/[\xC8-\xCB]/g, ch:'E'}, 
    {re:/[\xE8-\xEB]/g, ch:'e'}, 
    {re:/[\xCC-\xCF]/g, ch:'I'}, 
    {re:/[\xEC-\xEF]/g, ch:'i'}, 
    {re:/[\xD2-\xD6]/g, ch:'O'}, 
    {re:/[\xF2-\xF6]/g, ch:'o'}, 
    {re:/[\xD9-\xDC]/g, ch:'U'}, 
    {re:/[\xF9-\xFC]/g, ch:'u'}, 
    {re:/[\xD1]/g, ch:'N'}, 
    {re:/[\xF1]/g, ch:'n'} ]; 
    for(var i=0, len=rExps.length; i<len; i++) 
            str=str.replace(rExps[i].re, rExps[i].ch);
    return str; 
};

jQuery.extend({
highlight: function (node, re, nodeName, className) {
    if (node.nodeType === 3) {
        //var match = node.data.match(re);
        var match = stripAccents(node.data).match(re);
        if (match) {
            var highlight = document.createElement(nodeName || 'span');
            highlight.className = className || 'highlight';
            var wordNode = node.splitText(match.index);
            wordNode.splitText(match[0].length);
            var wordClone = wordNode.cloneNode(true);
            highlight.appendChild(wordClone);
            wordNode.parentNode.replaceChild(highlight, wordNode);
            return 1; //skip added node in parent
        }
    } else if ((node.nodeType === 1 && node.childNodes) && // only element nodes that have children
            !/(script|style)/i.test(node.tagName) && // ignore script and style nodes
            !(node.tagName === nodeName.toUpperCase() && node.className === className)) { // skip if already highlighted
        for (var i = 0; i < node.childNodes.length; i++) {
            i += jQuery.highlight(node.childNodes[i], re, nodeName, className);
        }
    }
    return 0;
}
});

jQuery.fn.unhighlight = function (options) {
var settings = { className: 'highlight', element: 'span' };
jQuery.extend(settings, options);

return this.find(settings.element + "." + settings.className).each(function () {
    var parent = this.parentNode;
    parent.replaceChild(this.firstChild, this);
    parent.normalize();
}).end();
};

jQuery.fn.highlight = function (words, options) {
var settings = { className: 'highlight', element: 'span', caseSensitive: false, wordsOnly: false };
jQuery.extend(settings, options);

if (words.constructor === String) {
    words = [words];
}

words = jQuery.map(words, function(word, i) {
    return stripAccents(word);
});

var flag = settings.caseSensitive ? "" : "i";
var pattern = "(" + words.join("|") + ")";
if (settings.wordsOnly) {
    pattern = "\\b" + pattern + "\\b";
}

var re = new RegExp(pattern, flag);

return this.each(function () {
    jQuery.highlight(this, re, settings.element, settings.className);
});
};

/** Tag remover
 * $('div span').tagRemover();
 * 
 */
(function($) {   
    $.fn.tagRemover = function() {           
        return this.each(function() {
        var $this = $(this);
        var text = $this.text();
        $this.replaceWith(text);            
        });            
    }    
})(jQuery);


/****** LT OBJECT CONTROLS ******/


/**
*  IndexHolder plugin
*/
(function($) {

    $.indexHolder= $.indexHolder || {};
    $.indexHolder = function(options) {
        var defaults = {
            // size: 1 no steings
        };
        var plugin = this;
        var d = '*';
        plugin.settings = {};

        //local variables
        plugin.data = {};
        plugin.anythingSet = false;

        //constructor
        var init = function() {
            plugin.settings = $.extend({}, defaults, options);
            return plugin;
        };

        //private methods
        var set = function(args) {
            for(var i = 0; i < args.length; ++i) {
                args[i] = "" + args[i];
            }
            if (args.length >= 1) {
                if (plugin.data == undefined) {
                    plugin.data = {};
                }
                if (args.length == 1) {
                    plugin.data[args[0]] = d;
                } else {
                    // length > 1
                    if (plugin.data[args[0]] == undefined) {
                        plugin.data[args[0]] = {};
                    }
                    
                    if (args.length == 2) {
                        plugin.data[args[0]][args[1]] = d;
                    } else {
                        // length > 2
                        if (plugin.data[args[0]][args[1]] == undefined) {
                            plugin.data[args[0]][args[1]] = {};
                        }    
                        
                        if (args.length == 3) {
                            plugin.data[args[0]][args[1]][args[2]] = d;
                        } else {
                            genericAlertPopup('alert_too_many_arguments');
                        }
                    }
                }
            } else {
                genericAlertPopup('alert_missing_arguments');
            }
        };
  
        var getData = function(args) {
            result = null;
            for(var i = 0; i < args.length; ++i) {
                args[i] = "" + args[i];
            }
            data = plugin.data;
            if (args.length > 0) {
                arg0 = args[0];
                if (data[arg0] == undefined) {
                    return result;
                }
                result = data[arg0];
                data = result;
                if (args.length == 1) {
                    return result;
                }
                
                arg1 = args[1];
                if (data[arg1] == undefined) {
                    return result;
                }
                result = data[arg1];
                data = result;
                if (args.length == 2) {
                    return result;
                }
            } else {
                result = data;
            }
            return result;
        }
        
        var isSet = function(args) {
            for(var i = 0; i < args.length; ++i) {
                args[i] = "" + args[i];
            }
            res = false;
           
            if (args.length == 0) {
                genericAlertPopup('alert_missing_arguments');
                return res;
            }
            
            if (plugin.data[args[0]] != undefined) {
                if (args.length == 1 && plugin.data[args[0]] == d) {
                    res = true;
                } else {
                    if (plugin.data[args[0]][args[1]] != undefined) {
                        if (args.length == 2  && plugin.data[args[0]][args[1]] == d) {
                            res = true;
                        } else {
                            if (plugin.data[args[0]][args[1]][args[2]] != undefined) {
                                if (args.length == 3 && plugin.data[args[0]][args[1]][args[2]] == d) {
                                    res = true;
                                } else {
                                    genericAlertPopup('alert_too_many_arguments'); 
                                }
                            }
                        }
                    }
                }
            }
            return res;
        }  

        
        var hasSub = function(args) {
            for(var i = 0; i < args.length; ++i) {
                args[i] = "" + args[i];
            }
            res = false;

            if (args.length == 0) {
                res = $.isEmptyObject(plugin.data) == false || plugin.data == d;
                return res;
            }
            
            if(plugin.data[args[0]] == undefined) {
                return res;
            }
            
            if (args.length == 1) {
                res = $.isEmptyObject(plugin.data[args[0]]) == false || plugin.data[args[0]] == d;
            } else {
                if(plugin.data[args[0]][args[1]] == undefined) {
                    return res;
                }
                if (args.length == 2) {
                    res = $.isEmptyObject(plugin.data[args[0]][args[1]]) == false || plugin.data[args[0]][args[1]] == d;
                } else {
                    if(plugin.data[args[0]][args[1]][args[2]] == undefined) {
                        return res;
                    }
                    if (args.length == 3) {
                        res = $.isEmptyObject(plugin.data[args[0]][args[1]][args[2]]) == false || plugin.data[args[0]][args[1]][args[2]] == d;
                    } else {
                        genericAlertPopup('alert_too_many_arguments');   
                    }
                }
            }
         
            return res;
        }
        
        var unSet = function(args) {
            
            for(var i = 0; i < args.length; ++i) {
                args[i] = "" + args[i];
            }
            
            if (args.length == 1 && plugin.data[args[0]] != undefined) {
                delete plugin.data[args[0]];
            } else
            if (args.length == 2 && plugin.data[args[0]] != undefined && plugin.data[args[0]][args[1]] != undefined) {
                delete plugin.data[args[0]][args[1]];
            } else
            if (args.length == 3 && plugin.data[args[0]] != undefined && plugin.data[args[0]][args[1]] != undefined && plugin.data[args[0]][args[1]][args[2]] != undefined) {
                delete plugin.data[args[0]][args[1]][args[2]];
            }             
        }
                
       //public methods
        plugin.set = function(args) {
            set(arguments);
        }

        plugin.getData = function(args) { 
            return getData(arguments);
        }

        plugin.isSet = function(args) { 
            res = isSet(arguments);
            return res;
        }
        
        plugin.hasSub = function(args) { 
            res = hasSub(arguments);
            return res;
        }
        
        plugin.unSet = function(args) { 
            unSet(arguments);
        }

        init();
    };
})(jQuery);

var LT_Synonyms = new $.indexHolder({});
var LT_Hypernyms = new $.indexHolder({});
var LT_Hyponym = new $.indexHolder({});


/**
*  ltCache plugin
*/
(function($) {

    $.ltCache = $.ltCache || {};
    $.ltCache = function(options) {
        var defaults = {
            // size: 1 no steings
        };
        var plugin = this;
        plugin.settings = {};

        //local variables
        plugin.data = {};

        
        //constructor
        var init = function() {
            plugin.settings = $.extend({}, defaults, options);
            return plugin;
        };

        //private methods
        var set = function(key, value) {
            plugin.data[key] = value;
        };

        var get = function(key, value) {
            if (isSet(key)) {
                return plugin.data[key];
            } else {
                return plugin.data;
            }
            return plugin.data;
        };

        var isSet = function(key) {
            if ($.isEmptyObject(plugin.data[key]) || plugin.data[key] == undefined) {
                return false;
            } else {
                return true;
            } 
        };
        
        var clear = function(key) {
            if (key == undefined) {
               plugin.data = {} 
            } else {
                delete plugin.data[key];
            }
        }
        
        // public method
        plugin.set = function(key, value) { 
            set(key, value);
        }

        plugin.get = function(key, value) {
            return get(key, value);
        }
     
        plugin.isSet = function(key) {
            return isSet(key);
        }

        plugin.clear = function(key) {
            return clear(key);
        }

        init();
    };
})(jQuery);

var LT_Cache_response = new $.ltCache({});
var LT_Cache_words = new $.ltCache({});
var LT_Cache_word_hypo_hyper_nyms = new $.ltCache({});

function displaySpremenljivkaWordings(spremenljivka, wi) {
    response = LT_Cache_response.get(spremenljivka);
    // vedno smo na eni besedi
    response = response[wi];
    //polovimo synonyme
    wordsSynonyms = LT_Synonyms.getData(spremenljivka);    
    wordsHypernyms = LT_Hypernyms.getData(spremenljivka);
    wordsHyponyms = LT_Hyponym.getData(spremenljivka);
    
    cleanhypernyms = response['cleanhypernyms']
    cleanhyponyms = response['cleanhyponyms']
    
    var selectedSynsetWords = {};
    var selectedHypernyms = {};
    var selectedHyponyms = {};
    var cntSynsets = 0;
    var cntHypernyms = 0;
    var cntHyponyms = 0;

    cLang = 'eng';

    if (wordsSynonyms != undefined && wordsSynonyms[wi] != undefined) {
        
        $.each(wordsSynonyms[wi], function(si, $s) {
            if (response != undefined 
                && response['Synset'] != undefined
                && response['Synset'][si] != undefined
                && response['Synset'][si]['FWDNoM'] != undefined) 
            {
                data = response['Synset'][si]['FWDNoM'];
                _wordsObjects = parseSynonymWords(data, cLang);
                $.each(_wordsObjects, function (woi, $wo) {
                    if (selectedSynsetWords[$wo.word] == undefined) {
                        cntSynsets++;
                        selectedSynsetWords[$wo.word] = $wo;      
                    }
                });
            }
        })
    }
    
   // hypernymi
    if (wordsHypernyms != undefined && wordsHypernyms[wi] != undefined) {
        $.each(wordsHypernyms[wi], function(si, $s) {
            cntHypernyms++;
            selectedHypernyms[cleanhypernyms[si]] = cleanhypernyms[si];   
        })
    }
   // hyponymi
    if (wordsHyponyms != undefined && wordsHyponyms[wi] != undefined) {
        $.each(wordsHyponyms[wi], function(si, $s) {
            cntHyponyms++;
            selectedHyponyms[cleanhyponyms[si]] = cleanhyponyms[si];   
        })
    }

    
    //izrišemo synsete
    $w_holder = $('#lt_chwo_'+spremenljivka+'');
    $w_holder.css('display','inline-block');
    
    if (cntSynsets > 0 || cntHypernyms > 0 || cntHyponyms > 0) {   
 
        $h = $w_holder.find('div.lt_box_content').empty();
        if (cntSynsets > 0) {
            $lt_result_div = $("<div/>", {class: 'lt_result_div'})
                .append(
                    $("<div/>", {class: 'lt_result_hdr'})
                    .append($('<span/>', { text: 'Synonyms' }))
                    .append($('<span/>', { text: 'WF*'}))
                    .append($('<span/>', { text: 'NoM'}))
                )
            $.each(selectedSynsetWords, function(w, _wordsObject) {
              //console.log(_wordsObject);
              $lt_result_div.append(
                $("<div/>", {class: 'lt_result_dtls'})
                    .append($('<span/>', { text: _wordsObject.word}))
                    .append($('<span/>', { text: _wordsObject.freq}))
                    .append($('<span/>', { text: _wordsObject.nom}))
                )
            });  
            $h.append($lt_result_div);
        }
        
        if (cntHypernyms > 0) {
            $lt_result_div = $("<div/>", {class: 'lt_result_div'})
                .append(
                    $("<div/>", {class: 'lt_result_hdr'})
                    .append($('<span/>', { text: 'Hypernyms' }))
                )
            $.each(selectedHypernyms, function(w, _wordsObject) {
              //console.log(_wordsObject);
              $lt_result_div.append(
                $("<div/>", {class: 'lt_result_dtls'})
                    .append($('<span/>', { text: _wordsObject}))
                )
            });
            $h.append($lt_result_div);
        }
        
        if (cntHyponyms > 0) {
            $lt_result_div = $("<div/>", {class: 'lt_result_div'})
                .append(
                    $("<div/>", {class: 'lt_result_hdr'})
                    .append($('<span/>', { text: 'Hyponyms' }))
                )
            $.each(selectedHyponyms, function(w, _wordsObject) {
              //console.log(_wordsObject);
              $lt_result_div.append(
                $("<div/>", {class: 'lt_result_dtls'})
                    .append($('<span/>', { text: _wordsObject}))
                )
            });
            $h.append($lt_result_div);
        }
    } else {
        $w_holder.find('div.lt_box_content').text('Ni izbranih besed')
    }
        
    //omogočimo še izvoz v excel
    enableExcel(spremenljivka); 
}

function enableExcel(spremenljivka) {
    //polovimo synonyme
    wordsSynonyms = LT_Synonyms.getData(spremenljivka);    
    wordsHypernyms = LT_Hypernyms.getData(spremenljivka);
    wordsHyponyms = LT_Hyponym.getData(spremenljivka);

    cnt = 0;
    if (wordsSynonyms) {
        $.each(wordsSynonyms, function(i, x) { if (x) { $.each(x, function(j, y) { if (y == '*') { cnt++; } }) } })
    }
    if (wordsHypernyms) {
        $.each(wordsHypernyms, function(i, x) { if (x) { $.each(x, function(j, y) { if (y == '*') { cnt++; } }) } })
    }
    if (wordsHyponyms) {
        $.each(wordsHyponyms, function(i, x) { if (x) { $.each(x, function(j, y) { if (y == '*') { cnt++; } }) } })
    }
    
    if (cnt > 0){
        $("#lt_export_excel span").removeClass("xls_grey_16 xls_16").addClass("xls_16");
    } else {
        $("#lt_export_excel span").removeClass("xls_grey_16 xls_16").addClass("xls_grey_16");
    }
}

function parseSynonymWords(synsetsText, language) {
    synsetWords  = [];
    // angleščina
    if (language.toLowerCase() == 'eng') {
        synsetArray = synsetsText.split(";");
        $.each(synsetArray, function(i, synsetText) {
            if (synsetText.trim() != '' && synsetText != undefined) {
                // linguistic: FW = 2457, NoM = 2; lingual: FW <800 , NoM = 2; 
                tmp = synsetText.split(':');
                word = tmp[0].trim();
                tmp = tmp[1].trim().split(',');
                freq = tmp[0].replace('FW','').replace('= ', '').replace(';', '').trim(); 
                nom = tmp[1].replace('NoM','').replace('= ', '').replace(';', '').trim();
                synsetWords.push({word:word, nom:nom, freq:freq});
            }            
        });
    // slovenščina
    } else {
        synsetArray = synsetsText.split(";");
        $.each(synsetArray, function(i, synsetText) {
            if (synsetText.trim() != '' && synsetText != undefined) {
            tmp = synsetText.split(':');
            word = tmp[0].trim();
            tmp = tmp[1].trim().split(',');
            freq = tmp[0].replace('FW','').replace('= ', '').replace(';', '').trim(); 
            nom = tmp[1].replace('NoM','').replace('= ', '').replace(';', '').trim();
            synsetWords.push({word:word, nom:nom, freq:freq});
            }
            
        });
        
    }

    return synsetWords;
}

function lt_export_excel() {
    lt_data = {};
    response = LT_Cache_response.get();
    
    wordsSynonyms = LT_Synonyms.getData();    
    wordsHypernyms = LT_Hypernyms.getData();
    wordsHyponyms = LT_Hyponym.getData();

    lt_data['response'] = response;
    lt_data['wordsSynonyms'] = wordsSynonyms;
    lt_data['wordsHypernyms'] = wordsHypernyms;
    lt_data['wordsHyponyms'] = wordsHyponyms;


    var request = {
            anketa: srv_meta_anketa_id,
            lt_data: lt_data,
            language: $("#lt_language").val()
    };
    $.ajax({
        timeout: var_lt_timeout,
        cache: false,
        xhrFields: { withCredentials: true },
        url: 'ajax.php?a=exportLanguageTechnology',
        type: 'post',
        data: request,
        success: function (response) {
            if (response['error'] == true) {
                genericAlertPopup('alert_parameter_response',response['msg']);
                return false;
            }
            window.open(response['url']);
        },
        error: function(x, t, m) {
            if(t==="timeout") {
                genericAlertPopup('alert_timeout');
            } else {
                genericAlertPopup('alert_unknown_error');
            }
        },
        complete: function() {
        }
    });
}

