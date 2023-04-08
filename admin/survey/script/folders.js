

function folders () {
    
    $(function () {
        
        // nardimo ankete draggable
        $('.folder_container').draggable({revert: 'invalid', opacity:'0.7', zIndex:'1', handle:'.folder_left img', delay:100});
        
        // nardimo folderje draggable
        $('li .folder').draggable({revert: 'invalid', opacitiy:'0.7', zIndex:'1', handle:'.movable', delay:100});
        
        // nardimo folderje droppable
        $('.folderdrop').droppable({accept: '.folder_container, ul .folder', hoverClass: 'grupahover', tolerance: 'pointer', 
            drop: function (e, ui) {
                if ($(ui.draggable).attr('name') == 'folder') {
                    $('#folders').load('ajax.php?t=folders&a=folder_dropped', {drop: $(ui.draggable).attr('id'), folder: $(this).attr('id')});
                } else {
                    $('#folders').load('ajax.php?t=folders&a=anketa_dropped', {anketa: $(ui.draggable).attr('id'), folder: $(this).attr('id')});
                }
            }
        });
        
        // na folder damo click za spreminjanje imena in hover za nov folder
        $('.folderdrop strong').click(function () {
            $(this).parent().load('ajax.php?t=folders&a=folder_rename', {folder: $(this).parent().attr('id')},
                function () {
                    $('#naslov_'+$(this).parent().attr('id')).focus();
                }
            );
        })
        .hover(
            function () {
                $('#new_folder_'+$(this).parent().attr('id')).css({visibility: 'visible'});
                $('#delete_folder_'+$(this).parent().attr('id')).css({visibility: 'visible'});
            },
            function () {
                $('#new_folder_'+$(this).parent().attr('id')).css({visibility: 'hidden'});
                $('#delete_folder_'+$(this).parent().attr('id')).css({visibility: 'hidden'});
            }
        );

    });
           
}

function folder_newname (folder) {
    
    $('#folders').load('ajax.php?t=folders&a=folder_newname', {folder: folder, naslov: $('#naslov_'+folder).attr('value')});
    
}

function new_folder (folder) {
    
    $('#folders').load('ajax.php?t=folders&a=new_folder', {folder: folder});
    
}

function delete_folder (folder) {
    
    $('#folders').load('ajax.php?t=folders&a=delete_folder', {folder: folder});
    
}


function folders_plusminus (folder) {
    
    var sortable_if = document.getElementById('folder_'+folder).style;
    
    if (sortable_if.display != "none") {
    
        if ($.browser.msie)
            $('#folder_'+folder).hide();
        else
            $('#folder_'+folder).slideUp();
            
        //$('#f_pm_'+folder).html('<img src="img/plus.png" class="folder_plusminus" style="width:12px; height:12px">');
        
        $('#f_pm_'+folder).load('ajax.php?t=folders&a=folder_collapsed', {collapsed: 1, folder: folder});
        
    } else {
        
        if ($.browser.msie)
            $('#folder_'+folder).show();
        else
            $('#folder_'+folder).slideDown();
        
        //$('#f_pm_'+folder).html('<img src="img/minus.png" class="folder_plusminus" style="width:12px; height:12px">');
        
        $('#f_pm_'+folder).load('ajax.php?t=folders&a=folder_collapsed', {collapsed: 0, folder: folder});
    }
    
}

function folders_knjiznica (anketa) {
    $('#folders').load('ajax.php?t=folders&a=folders_knjiznica', {anketa: anketa});
}

function folders_myknjiznica (anketa) {
    $('#folders').load('ajax.php?t=folders&a=folders_myknjiznica', {anketa: anketa});
}

function language_change (lang) {
	$.post('ajax.php?t=surveyList&a=language_change', {lang: lang}, function () {
		/*window.location.reload();*/
		window.location = window.location.href.split("?")[0];
	});
}