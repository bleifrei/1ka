//var languageTechnology = [];
var var_timeout = 60000;
// test
var languageTechnologyResponse = {};
var languageTechnology = {};
var languageTechnologySettings = {};
var languageTechnologyWordCache = {};
var languageTechnologyWordCustom = {};

function cleanLanguageTechnology() {
    //languageTechnology = [];
    $('div#branching').find('.spremenljivka_content').each(function(){
        var spremenljivka = $(this).attr('spr_id');
        var $divContainer = $('li#branching_'+spremenljivka);

        $('#lt_'+spremenljivka).remove();
        $divContainer.find('span.highlight').tagRemover();
        
        $divContainer.fadeTo(500, 1);
        
    });
}
function runLanguageTechnology() {
    
    $('div#branching').find('.spremenljivka_content').each(function(){
        
        var spremenljivka = $(this).attr('spr_id'); 
        var request = {
                    spremenljivka: spremenljivka, 
                    anketa: srv_meta_anketa_id,
                    lt_language: $("#lt_language").val(),
                    lt_min_FWD: $("#lt_min_FWD").val(),
                    lt_min_nNoM: $("#lt_min_nNoM").val(),
                    lt_min_vNoM: $("#lt_min_vNoM").val()
        };
        // nardimo ajax klic
        $.ajax({
            cache: false,
            timeout: var_timeout,
            xhrFields: { withCredentials: true },
            url: 'ajax.php?a=runLanguageTechnology',
            type: 'post',
            //dataType: 'json',
            data: request,
            spremenljivka: spremenljivka,
            container: $('li#branching_'+spremenljivka),
            beforeSend: function() {
                // odstranimo morebitne predhodne higlighte
                cleanLanguageTechnology()
                this.container.fadeTo(500, 0.4);
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

                // shranimo celoten response spremenljivke da potem lovimo shranjene podatke
                languageTechnologyResponse[this.spremenljivka] = response;
                
                displayProblematicWords(this.spremenljivka);
                
                
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

        
    });
}

function displayProblematicWords(spremenljivka) {
    var $divContainer = $('li#branching_'+spremenljivka);
    // pobrišemo obstoječe dive
    $('#lt_'+spremenljivka).remove();
    
    // narišemo vse nove potrebne dive
    $divContainer.after(
            $("<div/>", {
                id: 'lt_'+spremenljivka,
                class: 'lt_div'
            })
            .append( 
                $("<div/>", {class: 'lt_word_data'})
                    .append(
                        $("<div/>", {
                            id: 'lt_words_'+spremenljivka,
                            class: 'lt_words',
                        })
                        .on('click', 'ul li', function() {
                            wordIndex = $(this).index();
                            displayProblematicWord(spremenljivka, wordIndex)
                        })
                        .append( $('<div/>', { 
                            text: lang['srv_language_technology_flagged_wordings'],
                            class: 'lt_header'
                                }))
                        .append(
                            $("<ul/>", {
                                id: 'lt_words_ul_' + spremenljivka,
                                class: 'lt_word_list',
                            })
                        )
                    )
            ).append( $("<div/>", { class: 'clr' })) // clear;
            .append( $("<div/>", { class: 'lt_word_synonym' }))
        );

    // dodamo seznam vseh besed
    var words = languageTechnologyResponse[spremenljivka];
    $.each(words, function(wi, $wordData) {

        var $word = $wordData.word.toLowerCase();
        
        // obarvamo besedo
        $divContainer.find("*").highlight($word);

        $LTWord = $("<li/>")
        
            .append($('<span/>', {class: 'sprites'}))
//            .append($('<input/>', {type: 'radio'}))
            .append($('<span/>', {text: $word}))
            .appendTo($("#lt_words_ul_" + spremenljivka));
            
    });
    
}

function displayProblematicWord(spremenljivka, wordIndex) {
    // odstranimo podatke o besedi
    $("#lt_word_hypernym_div_"+spremenljivka).remove();
    $("#lt_word_detail_div_"+spremenljivka).remove();
    
    // holder ul =
    $ul = $("#lt_words_ul_"+spremenljivka);

    // deaktiviramo ostale checkboxe in liste
    $ul.find('li').removeClass('active');
    $ul.find('li span:first-child').removeClass('arrow_small2_r');

    // aktiviramo izbran list in checkbox
    $ul.find('li').eq(wordIndex).addClass('active');
    $ul.find('li').eq(wordIndex).find('span:first-child').addClass('arrow_small2_r');
    //$ul.find('li').eq(wordIndex).addClass('active');
    /*
    wordHasSynonim = wordHasSynonims(spremenljivka, wordIndex);
    if (wordHasSynonim) {
    } else {
        $ul.find('li').eq(wordIndex).removeClass('active');
    }
    */
    // prikažemo podatke besede
    var word = languageTechnologyResponse[spremenljivka][wordIndex]['word'];
    var fwd = languageTechnologyResponse[spremenljivka][wordIndex]['FWD'];
    var nom = languageTechnologyResponse[spremenljivka][wordIndex]['NoM'];
    var tag = languageTechnologyResponse[spremenljivka][wordIndex]['Tag'];
    var tag_lang = "";
    switch(tag) {
    case 'n':
        tag_lang = lang['srv_language_technology_noun'];
        break;
    case 'v':
        tag_lang = lang['srv_language_technology_verb']; 
        break;
    case 'a':
        tag_lang = lang['srv_language_technology_adjective'];
        break;
    case 'ad':
        tag_lang = lang['srv_language_technology_adverb'];
        break;
    case 'e':
        tag_lang = lang['srv_language_technology_existential'];
        break;
    } 

    
    $LTWordDetailDiv = $("<div/>", {
        id: 'lt_word_detail_div_'+spremenljivka,
        class: 'lt_word_detail_div',
        
    })
    .append( $('<div/>', { 
            text: lang['srv_language_technology_wording_properites'],
            class: 'lt_header no_padding'}
            )
    )
    .append($("<div/>").append($('<span/>', { text: 'Beseda: ' })).append($('<span/>', { text: word, class: 'strong' })))
    .append($("<div/>").append($('<span/>', { text: 'FWD: ' })).append($('<span/>', { text: fwd })))
    .append($("<div/>").append($('<span/>', { text: 'Tag: ' })).append(
        // dodamo dropdown
        $('<span/>').append($("<select/>", {'data-word': word})
            .append($("<option/>", {value: 'n', text: lang['srv_language_technology_noun']}))
            .append($("<option/>", {value: 'v', text: lang['srv_language_technology_verb']}))
            .append($("<option/>", {value: 'a', text: lang['srv_language_technology_adjective']}))
            .append($("<option/>", {value: 'adv', text: lang['srv_language_technology_adverb']}))
            .append($("<option/>", {value: 'e', text: lang['srv_language_technology_existential']}))
            .on('change', function() {
                var word =$(this).data('word');
                var wordType =  $(this).val();
                changeWordType(spremenljivka, wordIndex, word, wordType);
            })
            )
        )
    )
    .append($("<div/>").append($('<span/>', { text: 'NoM: ' })).append($('<span/>', { text: nom })))
    .appendTo($("#lt_"+spremenljivka+" div.lt_word_data"));

    // izberemo pravilno opcijo
    $("#lt_word_detail_div_"+spremenljivka).find("select").val(tag);
    // prikažemo sopomenke besede
    displayWordSynonyms(spremenljivka, wordIndex);
}

function displayWordSynonyms(spremenljivka, wordIndex) {
    // če že obstaja izbrišemo
    $("#lt_words_synonyms_" + spremenljivka).remove();
    
    // naredimo div 
    $LTWordSynonyms = $("<div/>", {
        id: 'lt_words_synonyms_' + spremenljivka,
        class: 'lt_words_synonyms',
    }).appendTo($("#lt_"+spremenljivka+" div.lt_word_synonym"))
    .append( $('<div/>', { 
                            text: lang['srv_language_technology_relevant_meanings'],
                            class: 'lt_header'
                                }));

    var synsets = languageTechnologyResponse[spremenljivka][wordIndex]['Synset'];

    // izrišemo sopomenke - synonyms
    $SynsetUl = $("<ul/>", {
        class: 'lt_word_synset'
    })
        .appendTo($("#lt_words_synonyms_" + spremenljivka))
        .on('click', 'li input', function(event ) {
            event.stopPropagation();
            synonymIndex = $(this).closest('li').index();
            checked = $(this).is(':checked') == true;
            displaySynonimHypernim(spremenljivka, wordIndex, synonymIndex, checked);
        })
        .on('click', 'li:not(input)', function(event ) {
            event.stopPropagation();
            
            synonymIndex = $(this).index();
            // change input
            checked = $(this).find('input').is(':checked') != true;
            $(this).find('input').prop("checked", checked);
            
            displaySynonimHypernim(spremenljivka, wordIndex, synonymIndex, checked);
        });
    var index = 0;
    if (synsets.length) {
        var selectedSynonim = undefined;
        
        $.each(synsets, function(si, $synset) {
            index = si;
            // če imamo izbrano besedo, jo izrišemo wordIndex = selectedWordIndex
            isSet = isSetSynonim(spremenljivka, wordIndex, si);
            selectedClass = '';
            if (isSet) {
                selectedClass = 'selectedWord';
            }
            
            /*
             * "synonyms":"karakteristika, znacilnost, posebnost",
             * "FWDNoM":"karakteristika NoM: 3 Frek: 707, znacilnost NoM: 6 Frek: 7846, posebnost NoM: 8 Frek: 4549"},
             */
            $SynsetLi = $("<li/>", {class: selectedClass})
                .append($('<span/>').append($('<input/>', {type: 'checkbox'})))
                .append($('<span/>', {text: $synset.synonyms,}))
                .appendTo($SynsetUl);
            if (isSet) {
                $SynsetLi.find('input').prop('checked', true);
            }

        });
        index++;
    }
    
    //if (!synsets.length) {

    isSet = isSetSynonim(spremenljivka, wordIndex, index);
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

    // no wording
     //$("#lt_words_synonyms_" + spremenljivka).html('<p>' + lang['srv_language_technology_no_alternative'] + '</p>');
    //}
    displayWordHypernym(spremenljivka, wordIndex);
}

function displaySynonimHypernim(spremenljivka, wordIndex, synonymIndex) {
    changeWordHypernym(spremenljivka, wordIndex, synonymIndex, checked);

    displayWordHypernym(spremenljivka, wordIndex);
}

function displayWordHypernym(spremenljivka, wordIndex) {
    // odstranimo stare podatke
    $("#lt_word_hypernym_div_"+spremenljivka).remove();

    // div za hypernime
    $LTWordHypernymDiv = $("<div/>", {
        id: 'lt_word_hypernym_div_'+spremenljivka,
        class: 'lt_word_hypernym_div',
    });
    // polovimo vse hypernyme
    hypernyms = getWordHypernyms(spremenljivka, wordIndex);

    if (!$.isEmptyObject(hypernyms)) {
        $LTWordHypernymDiv
        .empty()
        .append(
            $("<div/>", {class: 'hypernym_header'})
            .append($('<span/>', { text: lang['srv_language_technology_alternative_wordings'] }))
            .append($('<span/>', { text: 'WF*'}))
            .append($('<span/>', { text: 'NoM'}))
        );        

        $.each(hypernyms, function(i, Synset) {
            $LTWordHypernymDiv.append($("<div/>", {class: 'hypernym_details'})
                    .append($('<span/>', { text: Synset['word'] }))
                    .append($('<span/>', { text: Synset['freq']}))
                    .append($('<span/>', { text: Synset['nom']}))
                );
        });


    } else {
        // ni hyperninov
        $LTWordHypernymDiv
        .empty()
        .append(
            $("<div/>", {class:'lt_padding', text: lang['srv_language_technology_no_alternative_selected']})
        );

    }

    $("#lt_words_synonyms_"+spremenljivka).after($LTWordHypernymDiv);

    return true;
    
    

    // odstranimo stare podatke
    $("#lt_word_hypernym_div_"+spremenljivka).remove();
    
    // div za hypernime
    $LTWordHypernymDiv = $("<div/>", {
        id: 'lt_word_hypernym_div_'+spremenljivka,
        class: 'lt_word_hypernym_div',
    })
    .empty()
    .append(
        $("<div/>", {class: 'hypernym_header'})
        .append($('<span/>', { text: lang['srv_language_technology_alternative_wordings'] }))
        .append($('<span/>', { text: 'WF*'}))
        .append($('<span/>', { text: 'NoM'}))
    );
    $("#lt_words_synonyms_"+spremenljivka).after($LTWordHypernymDiv);
}

function changeWordHypernym(spremenljivka, wordIndex, synonymIndex, checked) {
    
    if (checked) {
        setSynonim(spremenljivka, wordIndex, synonymIndex);
    } else {
        unsetSynonim(spremenljivka, wordIndex, synonymIndex);
    }
    // holder ul =
    $ul = $("#lt_words_synonyms_"+spremenljivka+" ul");
    isSet = isSetSynonim(spremenljivka, wordIndex, synonymIndex);
    if (isSet) {
        $ul.find('li').eq(synonymIndex).addClass('selectedWord');
    } else {
        $ul.find('li').eq(synonymIndex).removeClass('selectedWord');
    }

    changeWordSynonym(spremenljivka, wordIndex);
}

function changeWordSynonym(spremenljivka, wordIndex){
    wordHasSynonim = wordHasSynonims(spremenljivka, wordIndex);
    if (wordHasSynonim ) {
        $("#lt_words_ul_" + spremenljivka).find("li").eq(wordIndex).addClass('selectedWord');    
    } else {
        $("#lt_words_ul_" + spremenljivka).find("li").eq(wordIndex).removeClass('selectedWord');
    }
}

function parseHypernyms(synsetsText, language) {
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



function setSynonim(spremenljivka, wordIndex, synonymIndex) {
    if (languageTechnology['sp_'+spremenljivka] == undefined) {
        languageTechnology['sp_'+spremenljivka] = {};
    }
    if (languageTechnology['sp_'+spremenljivka]['wi_'+wordIndex] == undefined) {
        languageTechnology['sp_'+spremenljivka]['wi_'+wordIndex] = {};
    }
    if (languageTechnology['sp_'+spremenljivka]['wi_'+wordIndex]['si_'+synonymIndex] == undefined) {
        languageTechnology['sp_'+spremenljivka]['wi_'+wordIndex]['si_'+synonymIndex] = '1';
    }
    
    enableDisableExcelExport();
}

function unsetSynonim(spremenljivka, wordIndex, synonymIndex) {
    if (languageTechnology['sp_'+spremenljivka] != undefined) {
        if (languageTechnology['sp_'+spremenljivka]['wi_'+wordIndex] != undefined) {
            if (languageTechnology['sp_'+spremenljivka]['wi_'+wordIndex]['si_'+synonymIndex] != undefined ) {
                delete languageTechnology['sp_'+spremenljivka]['wi_'+wordIndex]['si_'+synonymIndex];
            }
            if ($.isEmptyObject(languageTechnology['sp_'+spremenljivka]['wi_'+wordIndex])) {
                delete languageTechnology['sp_'+spremenljivka]['wi_'+wordIndex];
            }
        }
        if ($.isEmptyObject(languageTechnology['sp_'+spremenljivka])) {
            delete languageTechnology['sp_'+spremenljivka];
        }
    }
    enableDisableExcelExport();
}
function resetWordSynonyms(spremenljivka, wordIndex) {
    if (languageTechnology['sp_'+spremenljivka] != undefined) {
        if (languageTechnology['sp_'+spremenljivka]['wi_'+wordIndex] != undefined) {
            wordHasSynonim = $.isEmptyObject(languageTechnology['sp_'+spremenljivka]['wi_'+wordIndex]) == false;
            if (wordHasSynonim) {
                $.each(languageTechnology['sp_'+spremenljivka]['wi_'+wordIndex], function(si, synonymIndex) {
                    unsetSynonim(spremenljivka, wordIndex, si.replace('si_',''))
                });
            }
        }
    }
}

function isSetSynonim(spremenljivka, wordIndex, synonymIndex) {
    isSet = false;
    if (languageTechnology['sp_'+spremenljivka] != undefined) {
        if (languageTechnology['sp_'+spremenljivka]['wi_'+wordIndex] != undefined) {
            if (languageTechnology['sp_'+spremenljivka]['wi_'+wordIndex]['si_'+synonymIndex] != undefined) {
                isSet = true;
            }
        }
    }
    return isSet;
}

function wordHasSynonims(spremenljivka, wordIndex) {
    wordHasSynonim = false;
    if (languageTechnology['sp_'+spremenljivka] != undefined) {
        if (languageTechnology['sp_'+spremenljivka]['wi_'+wordIndex] != undefined) {
            wordHasSynonim = $.isEmptyObject(languageTechnology['sp_'+spremenljivka]['wi_'+wordIndex]) == false;
        }
    }
    return wordHasSynonim;
}


function getWordHypernyms(spremenljivka, wordIndex) {
    result = {};
    var synsetArray = languageTechnologyResponse[spremenljivka][wordIndex]['Synset'];
    
    izbrani = [];
    if (wordHasSynonims(spremenljivka, wordIndex)) {
        izbrani = languageTechnology['sp_'+spremenljivka]['wi_'+wordIndex];
    }
    // jezik
    if (languageTechnologySettings['lt_language'] != undefined) {
        var language = languageTechnologySettings['lt_language'];    
    } else {
        var language = $("#lt_language").val()        
    }


    
    $.each(synsetArray, function(i, synsetText) {

        if (izbrani['si_'+i] != undefined) {
            hypernyms = parseHypernyms(synsetText['FWDNoM'], language);
            $.each (hypernyms, function (i, hypernym) {
                word = hypernym['word'];
                if (result[word] == undefined) {
                    result[word] = hypernym;
                }
            })
            
            //hypernyms.push();
        }
    });
    return result;
}

function enableDisableExcelExport() {
    // preverimo ali imamp kak synonym
    var has = false;
    $.each(languageTechnology, function(spremenljvkaKey, spremenljivkaData) {
        $.each(spremenljivkaData, function(wordKey, wordData) {
            $.each(wordData, function(synsetKey, synsetData) {
                has = true;
                return false; // break
            });
            if (has) {
                return false; // break
            }
        })
        if (has) {
            return false; // break
        }
    });
    if (has) {
        $("#lt_export_excel span").removeClass("xls_grey_16 xls_16").addClass("xls_16");
    } else {
        $("#lt_export_excel span").removeClass("xls_grey_16 xls_16").addClass("xls_grey_16");
    }
    return has;
}

function prepareExcelData() {
    result = [];
    izbrani = languageTechnology;
    // naredimo kopijo mustang responsa, drugače so težave z referencami
    var response = jQuery.extend(true, {}, languageTechnologyResponse);
    
    $.each(izbrani, function(spremenljvkaKey, spremenljivkaData) {
        spremenljivka = spremenljvkaKey.replace('sp_','');
        $.each(spremenljivkaData, function(wordKey, wordData) {
            word = wordKey.replace('wi_','');
            var wordSynonyms = [];
            $.each(wordData, function(synsetKey, synsetData) {
                sysnset = synsetKey.replace('si_','');
                wordSynonyms.push(response[spremenljivka][word]['Synset'][sysnset]);
            });
            
            var wd = jQuery.extend(true, {}, response[spremenljivka][word]);
            delete wd['Synset'];
            wd['synonyms'] = wordSynonyms; 
            
            result.push({spremenljivka:spremenljivka, data:wd});
        })
    });
    return result;
}
function lt_export_excel() {
    if (!enableDisableExcelExport()) {
//        return false;
    }

    var request = {
            anketa: srv_meta_anketa_id,
            mustangData: prepareExcelData(),
            language: $("#lt_language").val()
    };
    $.ajax({
        timeout: var_timeout,
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


function changeWordType(spremenljivka, wordIndex, word, wordType) {
    var wkey = spremenljivka +'_'+ wordIndex+'_' + word + '_' + wordType;
    //  preverimo cachež

    if (languageTechnologyWordCache[wkey] == undefined) {
        console.log('2');
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
            timeout: var_timeout,
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
                // zamenjamo besedo v responsu
                languageTechnologyResponse[this.spremenljivka][this.wordIndex] = response[0]; 
                // TODO počistimo morebitne izbrane indexe za to besedo
                resetWordSynonyms(spremenljivka, wordIndex);            
                // prikažemo na novo
                changeWordSynonym(this.spremenljivka, this.wordIndex);
                displayWordSynonyms(this.spremenljivka, this.wordIndex);
                
                
                
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