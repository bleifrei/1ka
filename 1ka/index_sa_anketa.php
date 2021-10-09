<?php
include('settings.php');
?>
<!DOCTYPE html>
<html style="background-color:#FFFFFF;">
<head>
    <meta charset="utf-8">

    <link type="text/css"
          href="https://fonts.googleapis.com/css?family=Montserrat:400,400i,500,500i,600,600i,700,700i&amp;subset=latin-ext"
          rel="stylesheet"/>


    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        .btn{
            cursor: pointer;
            margin: 8px 10px;
            padding: 10px 20px;
            text-align: center;
            font-size: 12px;
            letter-spacing: 1px;
            font-weight: 600;
            color: #FFFFFF;
            border-radius: 20px;
        }
        /* moder */
        .btn-moder{
            color: #ffffff;
            background-color: #1e88e5;
            border: 1px solid #1e88e5;
        }
        .btn-moder:hover {
            background-color: #ffa608;
            border: 1px solid #ffa608;
        }
        #outercontainer {
            position: absolute;
            width: 100%;
            height: 80px;
            border-bottom: 6px #1e88e5 solid;
        }

        #outercontainer #container {
            position: relative;
            width: 100%;
            max-width: 1200px;
            margin: 0px auto 10px;
        }
        #logo {
            right: auto;
            left: 20px;
            top: 13px;
            background: url("../../../public/img/logo/1ka_slo.svg") no-repeat scroll 0 0 transparent;
            background-repeat: no-repeat;
            position: absolute;
        }
        #logo a {
            width: 250px;
            height: 56px;
            display: block;
            text-indent: -9999px;
            overflow: hidden;
        }
        h1 {
            /* margin: 0px auto 70px; */
            margin: 0px auto 30px;
            /* padding: 36px 0 20px 0; */
            padding: 110px 0 40px 0;
            font-size: 26px;
            color: #1e88e5;
            text-align: center;
            border-bottom: 1px solid #ddeffd;
        }
        .buttons {
            padding: 30px 20px 35px;
            margin: 0 10px;
        }
        .enter{
            cursor: pointer;
            margin: 8px 10px;
            padding: 10px 20px;
            text-align: center;
            font-size: 14px;
            letter-spacing: 1px;
            font-weight: 600;
            color: #FFFFFF;
            border-radius: 20px;;
            background-color: #1e88e5;
            border: 1px solid #1e88e5;
        }
        .enter:hover{
            background-color: #ffa608;
            border: 1px solid #ffa608;
        }


        #footer_survey {
            padding: 40px;
            background-color: #ffffff;
            border-top: 1px #ddeffd solid;
        }
        #footer_survey p, #footer_survey a, #footer_survey a:visited {
            color: #505050;
            font-size: 14px;
            line-height: 25px;
            font-weight: 400;
            text-decoration: none;
        }
        body{
            text-align: center;
            font-family: Montserrat, Arial, Sans-Serif !important;
        }
        .okencek{
            width: 185px;
            text-align: center;
            font-size: 40px;
            padding: 25px 20px;
            margin-bottom: 15px;
            border: 2px dashed #1e88e5;

        }
        #superkoda-obvestilo{
            padding-bottom: 16px;
            color: #455a64;
            font-weight: normal;
            visibility: hidden;
            opacity: 0;
            transition: visibility 0s, opacity 1s linear;
        }
        .active{
            visibility: visible !important;
            opacity: 1 !important;
        }
    </style>
</head>

<body style="background-color:#FFFFFF;background-image:none;">
<div id="outercontainer" class=""><div id="container">
        <div id="logo">
            <a href="<?php echo $site_url; ?>" title="1KA spletne ankete" target="_blank">1KA</a>
            <div id="logo_right"></div>
        </div>
        <h1>Vnos kode za dostop do samoevalvacije</h1>
        <h3 id="superkoda-obvestilo">Z omenjeno kodo vam bo ponujeno več zaporednih vprašalnikov za različne profesorje ali predmete.</h3>
        <form action="<?php echo $site_url; ?>koda/" method="post">
            <!-- Login okno -->
            <div style=" padding: 0 9px;    margin: 0 auto 0 auto;">
                <div style="text-align: center;">
                        <input class="okencek" type="text" name="koda" value="" onkeyup="preveriDolzino(this)" autocomplete="off"/>
                        <br/>
                </div>

            </div>
            <div class="buttons">
                <button type="submit" class="enter">Vnesite</button>
            </div>
        </form>
    </div>
    <div id="footer_survey">
        <p class="footer_1ka"><a href="http://www.1ka.si" target="_blank">1KA - spletne ankete</a></p>
        <p class="privacy"><a href="https://www.1ka.si/d/sl/o-1ka/pogoji-uporabe-storitve-1ka/politika-zasebnosti?from1ka=1" target="_blank">Anketa </a>     <a href="https://www.1ka.si/d/sl/o-1ka/pogoji-uporabe-storitve-1ka/politika-zasebnosti?from1ka=1#cookies" target="_blank">brez piškotkov</a>, <a href="https://www.1ka.si/d/sl/o-1ka/pogoji-uporabe-storitve-1ka/politika-zasebnosti?from1ka=1#ip" target="_blank">brez IP sledenja</a></p>
        <p class="privacy_link"><a href="https://www.1ka.si/d/sl/o-1ka/pogoji-uporabe-storitve-1ka/politika-zasebnosti?from1ka=1" target="_blank">Politika zasebnosti</a></p>
    </div>
</div>


<!-- end Login okno -->
<script>
    function preveriDolzino(ob) {
        var string = ob.value.trim();
        var superKoda = string.substring(0,2).toUpperCase();

        if(superKoda == 'SS'){
            document.getElementById('superkoda-obvestilo').classList.add('active');
        }else{
            document.getElementById('superkoda-obvestilo').classList.remove('active');
        }

        if (ob.value.length > 5 && superKoda != 'SS') {
            ob.value = string.replace(string, string.substring(0, 5));
        }else if(ob.value.length > 7){
            ob.value = string.replace(string, string.substring(0, 7));
        }
    }
</script>

</body>
</html>
