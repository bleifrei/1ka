<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <!--			<link rel="stylesheet" type="text/css" href="upload.css">-->


<?php


include_once('../../../function.php');

// Ce imamo v GET-u lang naložimo jezikovno datoteko
if(isset($_GET['lang'])){
    $language = ($_GET['lang'] == 'en') ? '2' : '1';
    $file = '../../../lang/'.$language.'.php';
    include($file);
}


//Prikaže možnost za IMAGE UPLOAD
if (!isset ($_POST['posted']) && (isset ($_GET['image']) && $_GET['image'] == 1)) {

    if (isset($_GET['error']) && $_GET['error'] == 1)
           echo "<strong style='font-family: Arial; font-size: 12px; color: red; font-weight: bold;'>" . $lang['upload_img_exe'] . "</strong>";
?>
    <body style="margin-top: 0px; top: 0px; margin-left: 0px; left: 0px; padding-top: 0px;">
    <form name="uploader" method="post" enctype="multipart/form-data"
          action="<?= $site_url ?>editors/ckeditor_4_4/uploader/EnkaUploader.php" style="height: 15px;">
        <input type="hidden" name="posted" value="1"/>
        <input type="hidden" name="urlsrc" value="<?= $_GET['url'] ?>"/>
        <input type="hidden" name="type" value="image"/>
        <!--	Podatki o sliki, ki jo nalagamo	-->
        <!--		<input type="hidden" value="eitorSlika" name="-->
        <?php //echo ini_get("session.upload_progress.name"); ?><!--">-->
        <strong style="font-family: Arial; font-size: 12px; font-weight: bold;"><?= $lang['upload_img'] ?></strong><input
            type="file" name="eitorSlika" onChange="submit();"/>
    </form>
    <!--	<script type="text/javascript" src="upload.js"></script>-->
<?php
}elseif (!isset ($_POST['posted']) && (!isset ($_GET['image']) || $_GET['image'] != "1")) {
?>
    <body style="margin-top: 0px; top: 0px; margin-left: 0px; left: 0px; padding-top: 0px;">
    <form name="uploader" method="post" enctype="multipart/form-data"
          action="<?= $site_url ?>editors/ckeditor_4_4/uploader/EnkaUploader.php" style="height: 15px;">
        <input type="hidden" name="posted" value="1"/>
        <input type="hidden" name="urlsrc" value="<?= $_GET['url'] ?>"/>
        <input type="hidden" name="type" value="file"/>
        <strong style="font-family: Arial; font-size: 12px; font-weight: bold;"><?= $lang['upload_select_file'] ?></strong>
        <br/>
        <input type="file" name="editorDatoteka" style="width: 100%;" onChange="submit();"/>
    </form>
<?php
}elseif ($_POST['type'] == "file") {

    if (isset ($_FILES['editorDatoteka']['name'])) {
        $ime = preg_replace ("/[^a-zA-Z0-9_\.\-]/", "", $_FILES['editorDatoteka']['name']);
        if (strpos (strtolower ($ime), ".exe")!==false || strpos (strtolower ($ime), ".bat")!==false || strpos (strtolower ($ime), ".com")!==false ||
                strpos (strtolower ($ime), ".vbs")!==false || strpos (strtolower ($ime), ".pl")!==false || strpos (strtolower ($ime), ".php")!==false || strpos (strtolower ($ime), ".php3")!==false) {
?>
                    <strong style="font-family: Arial; font-size: 12px; color: red; font-weight: bold;"><?=$lang['upload_exe']?></strong>
<?php
                }else {
                     $nakljucno = time();
                     $final = $nakljucno .$ime;
                    if (move_uploaded_file($_FILES['editorDatoteka']['tmp_name'], $site_path .'uploadi/editor/doc/' .$final)) {
?>
                    <body bgcolor="#ebebeb" onload="parent.document.getElementById(parent.urlsrc).value='<?=str_replace("http://", "", $site_url)?>uploadi/editor/doc/<?=$final?>'; window.location.href='<?= $site_url ?>editors/ckeditor_4_4/uploader/EnkaUploader.php'; ">
                    <strong style="font-family: Arial; font-size: 12px; font-weight: bold;"><?=$lang['upload_done']?></strong>
<?php
                    }else {
                        if (!file_exists($site_path .'uploadi/editor/doc/')) {
                            mkdir($site_path .'uploadi/editor/doc/', 0755, true);
                        }
?>
                    <strong style="font-family: Arial; font-size: 12px; color: red; font-weight: bold;"><?=$lang['upload_not_ok']?></strong>
<?php
                    }
                }

     }
     unset ($_POST['posted']);
     unset ($_POST['type']);
     unset ($_FILES['ul']['name']);
}else {
        //
        // IMG HANDLER
        //
        include_once('upload_class.php');
        include_once('imageresizer.class.php');

        if (isset ($_FILES['eitorSlika']['name'])) {
            $ime = preg_replace("/[^a-zA-Z0-9_\.\-]/", "", $_FILES['eitorSlika']['name']);
            $ime = strtolower($ime);
            if (strpos($ime, ".jpg") === false && strpos($ime, ".jpeg") === false && strpos($ime, ".gif") === false &&
            strpos($ime, ".png") === false && strpos($ime, ".bmp") === false && strpos($ime, ".svg") === false) {
?>
                <body onload="window.location.href='<?= $site_url ?>editors/ckeditor_4_4/uploader/EnkaUploader.php?image=1&error=1';">
 <?php
            }else {
               $nakljucno = time();
               $final = $nakljucno . $ime;
               $UF_obj = new Upload();
               $UF_obj->File = $_FILES['eitorSlika'];
               $UF_obj->SavePath = $site_path . '/uploadi/editor';
               $UF_obj->NewName = $_FILES['eitorSlika']['name'];

               //Širina in višina slike nastavimo
               $UF_obj->NewWidth = 600;
               $UF_obj->NewHeight = 600;

               $ime = $UF_obj->NameCase = 'lower';
               $UF_obj->OverWrite = false;
               $Error = $UF_obj->UploadFile();
               if (empty($Error)) {
 ?>
                    <body onload="parent.document.getElementById(parent.urlsrc).value='<?= $site_url ?>uploadi/editor/<?= $final ?>'; parent.document.querySelector('img[id$=_previewImage]').src='<?= $site_url ?>uploadi/editor/<?= $final ?>'; parent.document.querySelector('img[id$=_previewImage]').style.display='block'; window.location.href='<?= $site_url ?>editors/ckeditor_4_4/uploader/EnkaUploader.php?image=1';">
                    <strong style="font-family: Arial; font-size: 12px; font-weight: bold;"><?= $lang['upload_img_done'] ?></strong>
<?php
                }else {
?>
                    <strong style="font-family: Arial; font-size: 12px; color: blue; font-weight: bold;"><?= $lang['upload_img_not_ok'] ?></strong>
<?php
                }
            }
         }
    unset ($_POST['posted']);
    unset ($_POST['type']);
    unset ($_FILES['eitorSlika']);
    echo '<body style="margin-top: 0px; top: 0px; margin-left: 0px; left: 0px; padding-top: 0px;">';
}
?>

</body>
</html>
