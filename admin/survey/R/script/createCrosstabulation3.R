# CROSSTABULACIJA 3 SPREMENLJIVK

# preberemo parametre 1->folderpath
params <- commandArgs(trailingOnly=TRUE)

path <- params[1]

# preberemo sfiltrirano tabelo s podatki
dataTable <- read.table(
	file = paste(path, 'admin/survey/R/TempData/crosstab_data.tmp', sep=''),
	sep = ',', 
	colClasses = 'numeric',
	comment.char = '',
	quote = '',
	header = FALSE
)


# iz tabele pobrisemo vrstice z vrednostmi manjsimi od 0 in sfiltriramo vse stolpce ki jih ne rabimo
dataTableClean <- dataTable[apply(dataTable[,1:3], MARGIN = 1, function(x) all(x > -1)), 1:3]



# izvedemo krostabulacijo
crosstabTable <- xtabs(~dataTableClean[,1]+dataTableClean[,2]+dataTableClean[,3], data=dataTableClean)



# Vrednosti katerim pripadajo frekvence
vars <- dimnames(crosstabTable)

# izracunamo vsoto
sumsVrstica <- apply(crosstabTable, c(2), sum)
sumsStolpec <- apply(crosstabTable, c(3), sum)
sums <- sum(crosstabTable)
	

delezCol <- 4

# POVPRECJE
if(params[2] == '1'){
	
	#dataTableAvg <- dataTableClean[sapply(dataTableClean[,4], function(x) all(x > -1)), ]
	dataTableAvg <- dataTableClean
	dataTableAvg[,4][dataTableAvg[,4] < 0] <- 0
	
	avgTable <- xtabs(dataTableAvg[,4]~dataTableAvg[,1]+dataTableAvg[,2]+dataTableAvg[,3], dataTableAvg) / crosstabTable
	
	delezCol <- delezCol + 1
}


# DELEZ
if(length(params) > 2){

	# array, ki vsebuje vrednosti stolpca, za katere racunamo delez
	delez <- params[3]
	delez <- unlist(strsplit(delez, ","))
	delez <- sapply(delez, strtoi)
	
	# ce je -1 gre za checxbox stolpce ki imajo samo vrednost 1 in jih je vec
	if(delez[1] == -1){
		dataTableDelez <- dataTableClean
		dataTableDelez[,delezCol][!(rowSums(dataTableDelez[delezCol:ncol(dataTableDelez)]) == ncol(dataTableDelez)-delezCol+1)] <- 0
		dataTableDelez[,delezCol][(rowSums(dataTableDelez[delezCol:ncol(dataTableDelez)]) == ncol(dataTableDelez)-delezCol+1)] <- 1
	}
	else{	
		#dataTableDelez <- dataTableClean[sapply(dataTableClean[,delezCol], function(x) all(x %in% delez)), ]
		dataTableDelez[,delezCol][!(dataTableDelez[,delezCol] %in% delez)] <- 0
		dataTableDelez[,delezCol][(dataTableDelez[,delezCol] %in% delez)] <- 1	
	}
	
	delezTable <- xtabs(dataTableDelez[,delezCol]~dataTableDelez[,1]+dataTableDelez[,2]+dataTableDelez[,3], dataTableDelez) / crosstabTable	
}


# vsako variablo (vrstico) pretvorimo v csv string
vars <- sapply(vars, paste, collapse=",")

# naredimo 2-d tabelo podatkov
crosstabTable <- ftable(crosstabTable)
crosstabTable <- sapply(crosstabTable, paste, collapse=", ")


# podatke vrnemo v obliki var11,var12,var13..._var2...DATAfreq1_freq2...
cat(paste(vars, sep="", collapse="_"))
cat("--")
cat(paste(crosstabTable, sep="", collapse="_"))
cat("--")
cat(paste(sumsVrstica, sep="", collapse="_"))
cat("--")
cat(paste(sumsStolpec, sep="", collapse="_"))
cat("--")
cat(paste(sums, sep="", collapse="_"))
cat("--")
cat(paste(x2, sep="", collapse="_"))

# rezultati povprecja
if(params[2] == '1'){
	cat("--")
	cat(paste(avgTable, sep="", collapse="_"))
}

# rezultati deleza
if(length(params) > 2){
	cat("--")
	cat(paste(delezTable, sep="", collapse="_"))
}