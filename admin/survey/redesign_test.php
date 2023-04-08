<?php 

?>

<html>


    <head>
        
        <title>1KA | Spletne ankete</title>

        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <meta content="width=device-width; initial-scale=1.0;" name="viewport">

        <!-- stari css-ji -->
        <!--
        <link type="text/css" href="minify/g=css?v=22.09.30" media="screen" rel="stylesheet">
        <link type="text/css" href="minify/g=cssPrint?v=22.09.30" media="print" rel="stylesheet">
        <link type="text/css" href="https://fonts.googleapis.com/css?family=Montserrat:400,400i,500,500i,600,600i,700,700i&amp;subset=latin-ext" rel="stylesheet">    
        -->
    
        <!-- novi css -->
        <link type="text/css" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:400,400i,500,500i,600,600i,700,700i&amp;subset=latin-ext" rel="stylesheet">    
        <link type="text/css" href="../../public/css/admin_new.css" media="screen" rel="stylesheet">


        <link rel="shortcut icon" type="image/ico" href="http://localhost//favicon.ico">

    </head>


    <body class="mainBody body_anketa">


        <div id="main_holder">
            
            <header>
                Header
            </header>

            <div id="main">

                <div id="anketa">

                    <div id="anketa_edit" class="menu_left wide">

                        <div class="left">
                            
                            <span class="menu_left-title">
                                Naslov menija
                            </span>
                                    
                            <ul class="menu_left-list">
                                <li>
                                    Posamezna stran
                                </li>

                                <li class="active">
                                    Aktivna stran
                                </li>

                                <li>
                                    <a class="menu-left" href="https://www.1ka.si/">1ka.si</a>
                                </li>

                                <li>
                                    Posamezna stran
                                </li>
                            </ul>

                            <span class="menu_left-title paid">
                                Plačljivi moduli
                            </span>
                            <span class="faicon yellow lock_close yellow"></span>
                            
                                    
                            <ul class="menu_left-list paid">
                                <li>
                                    Posamezna stran
                                </li>

                                <li>
                                    Posamezna stran
                                </li>

                                <li>
                                    Posamezna stran 0123456789
                                </li>

                                <li>
                                    Posamezna stran z zelo dolgim imenom
                                </li>                                                                                          
                            </ul>
                            
                        </div>

                        <div class="right">

                            <fieldset>
                                <legend>Box z vsebino</legend>

                                <div class="setting_holder">
                                <label for="textarea1">Tekstovno polje:</label>
                                    
                                <form>
                                    <textarea class="textarea" id="textarea1" name="textarea1"></textarea>
                                </form>

                                </div>
                                
                                <div class="setting_holder">
                                <label for="textarea1" disabled>Disabled tekstovno polje:</label>
                                <form>
                                    <textarea class="textarea disabled" id="textarea1" name="textarea1">Besedilo v disabled polju.</textarea>
                                </form>

                                </div>

                                <div class="setting_holder">
                                    <label for="text1" class="InputLabel">Input text - large:</label>
                                    <form>
                                        <input id="text1" class="text large" type="text">
                                    </form>
                                </div>

                                <div class="setting_holder">
                                    <label for="text2"class="InputLabel">Input text - medium:</label>
                                    <form>
                                        <input id="text2" class="text medium" type="text">
                                    </form>
                                </div>

                                <div class="setting_holder">
                                    <label for="text3" class="InputLabel" disabled>Input text - small - disabled:</label>
                                    <form>
                                        <input id="text3" class="text small disabled" type="text">
                                    </form>
                                </div>

                                <div class="setting_holder">
                                    <label for="dropdown1"lass="input_label">Veliki dropdown meni:</label>
                                    <form>
                                        <select id="dropdown1" class="dropdown large">
                                            <option>Q1: veliko besedilaaaaa</option>
                                            <option>Q2: manj besedila</option>
                                            <option>Q3: malo</option>
                                        </select>
                                    </form> 
                                </div>

                                <div class="setting_holder">
                                    <label for="dropdown2" class="input_label">Srednji dropdown meni:</label>
                                    <form>
                                        <select id="dropdown2" class="dropdown medium">
                                            <option>Q1: veliko besedilaaaaa</option>
                                            <option>Q2: manj besedila</option>
                                            <option>Q3: malo</option>
                                        </select>
                                    </form> 
                                </div>

                                <div class="setting_holder">
                                    <label for="dropdown3" class="input_label" disabled>Mali dropdown meni - disabled:</label>
                                    <form>
                                        <select id="dropdown3" class="dropdown small disabled">
                                            <option>Q1: veliko besedilaaaaa</option>
                                            <option>Q2: manj besedila</option>
                                            <option>Q3: malo</option>
                                        </select>
                                    </form> 
                                </div>

                                <div class="setting_holder">

                                    <span class="setting_title">Radio gumbi:</span>

                                    <div class="setting_item">
                                        <input class="radio" type="radio" id="r1" name="radio">
                                        <label for="r1">Option 1</label>
                                    </div>

                                    <div class="setting_item">
                                        <input class="radio" type="radio" id="r2" name="radio">
                                        <label for="r2">Option 2</label>
                                    </div>

                                    <div class="setting_item disabled">
                                        <input class="radio" type="radio" id="r3" name="radio" disabled>
                                        <label for="r3" disabled>Option 3 - disabled</label>
                                    </div>

                                </div>

                                <div class="setting_holder">
                                    <span class="setting_title">Checkboxi - related:</span>
                                    <div class="setting_item">
                                        <input class="checkbox" type="checkbox" id="o1" name="checkbox"></input>
                                        <label for="o1">Option 1</label>
                                    </div>
                                    <div class="setting_item">    
                                        <input class="checkbox" type="checkbox" id="o2" name="checkbox" disabled></input>
                                        <label for="o2" disabled>Option 2 - disabled</label>
                                    </div>
                                </div>

                                
                                <p>Checkboxi - unrelated</p>

                                <div class="setting_holder">
                                    <input class="checkbox" type="checkbox" id="c1" name="checkbox"></input>
                                        <label for="c1">Ena nastavitev</label>
                                </div>
                                <div class="setting_holder">    
                                    <input class="checkbox" type="checkbox" id="c2" name="checkbox"></input>
                                    <label for="c2">Druga nastavitev</label>
                                </div>
                                <div class="setting_holder">    
                                    <input class="checkbox" type="checkbox" id="c3" name="checkbox"></input>
                                    <label for="c3">Tretja nastavitev</label>
                                </div>

                                

                                <div class="setting_holder">
                                    <label class="input_label">Gumbi:</label><br><br>
                                    
                                    <button class="small red">Gumb - majhen rdeč</button> <br><br>
                                    <button class="large blue">Gumb - velik moder</button> <br><br>
                                    <button class="small white-blue">Gumb - white-blue</button> <br><br>
                                    <button class="small white-black">Gumb - white-black</button> <br><br>
                                    <button class="medium gray">Gumb - siv</button> <br><br>
                                    <button class="small yellow">Gumb - rumen</button> <br><br>

                                </div>

                                <div class="setting_holder">
                                    Povezava: <a href="https://www.1ka.si/">www.1ka.si</a>
                                </div>

                                <div class="setting_holder">
                                <a class="noline" href="https://www.1ka.si/"><span class="faicon lock_close link-right"></span>Povezava z ikono: www.1ka.si</a>
                                </div>

                                <p>Tole je navaden normalen odstavek teksta.</p>
                                <p class="warning">To je opozorilo, ki je celo rdeče.</p>
                                <p><span class="warning">Opozorilo:</span> Tole je opozorilo, ki ni celo rdeče.<p>

                                
                                
                            </fieldset>

                        </div>

                    </div>
                </div>
            </div>


            <footer id="srv_footer">
                Footer
            </footer>

        </div>

    </body>

</html>


<?php 

?>