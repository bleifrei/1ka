<?php

// funkcija, ki pridobi iz razreda VariableClass ustrezno vrednost spremenljivke
function get($variable_get)
{
    return \App\Controllers\VariableClass::get($variable_get);
}

// funkcija, ki shrani v VariableClass ustrezno vrednost spremenljivke
function save($variable, $value, $return = null)
{
    \App\Controllers\VariableClass::save($variable, $value);

    if(!is_null($return) && $return == 1)
        return $value;
}

// funkcija, ki shrani v VariableClass vrednost k že obstoječi spremenljivki
function add($variable, $value, $return = null)
{
    \App\Controllers\VariableClass::add($variable, $value, $return);

    return get($variable);
}

// Init naše razrede

function Helper()
{
    return new \App\Controllers\HelperController();
}

function Display()
{
    return new \App\Controllers\DisplayController();
}

function Language()
{
    return new \App\Controllers\LanguageController();
}

/************************************************
 * Vrnemo pot do datoteke z jeziki ali direkto pot ali pa preko urlja
 ************************************************/
function lang_path($izbrani_jezik = null, $use_site_url = null)
{
    global $site_path;
    global $site_url;

    if (is_null($izbrani_jezik))
        return $site_path . 'lang/';

    if (is_null($use_site_url))
        return $site_path . 'lang/' . $izbrani_jezik . '.php';

    // Vrnemo link do lang preko site urlja
    return $site_url . 'lang/' . $izbrani_jezik . '.php';
}

function survey_path($file = null)
{
    global $site_path;
    if(is_null($file))
        return $site_path . 'main/survey/';

    return $site_path . 'main/survey/'.$file;
}


