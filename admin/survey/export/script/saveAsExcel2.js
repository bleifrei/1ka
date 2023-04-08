function exportTableToExcel(filename = '', export_subtype){
    
	var downloadLink;
    var dataType = 'application/vnd.ms-excel';
	var htmlStart = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>{worksheet}</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--><meta http-equiv="content-type" content="text/plain; charset=UTF-8"/></head><body>';
	var htmlStop = '</body></html>';
    var tableSelect;
	var naslovSelect = '';
	var tableHTML = '';
	var tableID = [];
	var titleClass = 'cmbx-10'
	
	
	var tableNumber = document.getElementsByTagName('table').length;	//belezi stevilo tabel v htmlju
	//console.log("Dolzina: "+tableNumber);
	
	var att = document.createAttribute("border");
	att.value = "1px";
	
	var tableList = document.getElementsByTagName('table');	
	for(var l=0; l<tableNumber; l++){	//naberi vse idje tabel v htmlju
		//console.log("Ime: "+tableList[l].id);
		tableID.push(tableList[l].id);
		tableList[l].setAttribute("border", "1px");	//dodaj obrobo html tabeli, da bo potem obraba tudi v xls datoteki
		
		//ureditev bold-anja
		if(export_subtype!="multicrosstab"){
			
		
			id1 = 'TBL-'+(l+1)+'-1-1';
			id2 = 'TBL-'+(l+1)+'-1-2';
			//console.log(id1);
			//console.log(id2);
			
			var tableTitle1 = document.getElementById(id1);		
			tableTitle1.style.fontWeight = "bold";
			//console.log("fontWeightnot: "+document.getElementById(id1).style.textAlign);
			//console.log("fontWeightnot: "+tableTitle1.style.textAlign);
			
			var tableTitle2 = document.getElementById(id2);
			tableTitle2.style.fontWeight = "bold";
			
			//za ostale izvoze, ki potrebujejo dodatna bold besedila
			if(export_subtype="break"){
				id3 = 'TBL-'+(l+1)+'-2-1';
				var tableTitle3 = document.getElementById(id3);
				tableTitle3.style.fontWeight = "bold";
			}
		
		}
		
		//za ostale izvoze, ki potrebujejo dodatna bold besedila - konec
		
	}
	
	//console.log("fontWeight 112: "+document.getElementById('TBL-1-1-2').style.textAlign);
	
	//sestava naslova
	var naslovSelectNumber = document.getElementsByClassName(titleClass).length;	//belezi stevilo delov naslova
	for(var l=0; l<naslovSelectNumber; l++){	//naberi vse idje tabel v htmlju
		document.getElementsByClassName(titleClass)[l].style.fontWeight = "bold"; //boldanje naslova analize
		naslovSelect = naslovSelect + document.getElementsByClassName(titleClass)[l].innerText;		
	}
	
	//console.log(naslovSelect);
	//sestava naslova - konec
	
	tableHTML = tableHTML + naslovSelect;
	//console.log(tableHTML);
	
 	for(var i=0;i<tableID.length;i++){
		//console.log(tableID[i]);
		tableSelect = document.getElementById(tableID[i]);
		tableHTML = tableHTML + '\n '+tableSelect.outerHTML.replace(/ /g, '%20');
	}
	
	//console.log(tableHTML);
	
    // Specify file name
    filename = filename?filename+'.xls':'excel_data.xls';
    
    // Create download link element
    downloadLink = document.createElement("a");
    
    document.body.appendChild(downloadLink);
    
    if(navigator.msSaveOrOpenBlob){
        var blob = new Blob(['\ufeff', tableHTML], {
            type: dataType
        });
        navigator.msSaveOrOpenBlob( blob, filename);
    }else{
        // Create a link to the file
        downloadLink.href = 'data:' + dataType + ', ' + htmlStart + tableHTML + htmlStop;
		
        // Setting the file name
        downloadLink.download = filename;
        
        //triggering the function
        downloadLink.click();
    }
	//console.log("zapri se");
	//window.close();	//zapri okno oz. zavihek z drugim korakom izvoza iz html v xls
}