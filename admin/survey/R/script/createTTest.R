# preberemo parametre 1->folderpath
params <- commandArgs(trailingOnly=TRUE)

path <- params[1]

# preberemo sfiltrirano tabelo s podatki
dataTable <- read.table(
	file = paste(path, 'admin/survey/R/TempData/ttest_data.tmp', sep=''),
	sep = ',', 
	colClasses = 'numeric',
	comment.char = '',
	quote = '',
	header = FALSE
)



# ce ni checkbox moramo podatke preurediti (imamo 2 dodatna parametra)
if(params[3] > 1){
	
	val1 <- params[2]
	val2 <- params[3]

	dataTableClean <- dataTable[sapply(dataTable[,1], function(x) all((x == val1) || (x == val2))), ]
	
	dataTable[,1][dataTable[,1] != val1] <- 0
	dataTable[,1][dataTable[,1] == val1] <- 1
	
	dataTable[,2][dataTable[,2] != val2] <- 0
	dataTable[,2][dataTable[,2] == val2] <- 1
}


# pocistimo vse vrstice, ki imajo za numeric (col 3) missing (< 0)
dataTable <- dataTable[sapply(dataTable[,3], function(x) all(x > -1)), ]
dataTableClean <- dataTableClean[sapply(dataTableClean[,3], function(x) all(x > -1)), ]


# podmnozica vrednosti glede na 1. vrednost
col1 <- subset(dataTable, dataTable[,1] == 1)
# podmnozica vrednosti glede na 2. vrednost
col2 <- subset(dataTable, dataTable[,2] == 1)


# n
n1 <- nrow(col1)
n2 <- nrow(col2)

# avg(x)
avg1 <- mean(col1[,3])
if(is.nan(avg1))
	avg1 <- 0
avg2 <- mean(col2[,3])
if(is.nan(avg2))
	avg2 <- 0

# standardna deviacija (s^2??)
sd1 <- sd(col1[,3])
if(is.na(sd1))
	sd1 <- 0
sd2 <- sd(col2[,3])
if(is.na(sd2))
	sd2 <- 0

# standardna napaka
se1 = sd1 / sqrt(n1)
if(is.nan(se1))
	se1 <- 0
se2 = sd2 / sqrt(n2)
if(is.nan(se2))
	se2 <- 0

# kvadrat standardne napake
se21 <- se1^2
se22 <- se2^2

# margini (1,96 * se)
mar1 <- 1.96 * se1
mar2 <- 1.96 * se2



# izvedemo ttest (ce obstajata po vsaj dva primera vsake binarni vrednosti)
if(n1 > 1 && n2 > 1){
	ttest <- t.test(dataTableClean[,3]~dataTableClean[,1], var.equal=FALSE)

	# razlika povpreèij => $d = x1 -x2
	d <- avg1 - avg2

	# sed (std. error difference)
	#sed <- sqrt(se21 + se22)
	#sed <- ttest$estimate/ttest$statistic
	sed <- (-1 * diff(ttest$estimate) / ttest$statistic)
	
	#T <- d / sed
	T <- ttest$statistic
	
	# signifikanca
	sig <- ttest$p.value
	
} else{
	d <- 0
	sed <- 0
	T <- 0
	sig <- 0
}



# podatke vrnemo v x1_x2_x3...--y1_y2...
cat(paste(n1, avg1, sd1, se1, se21, mar1, sep="_"))
cat("--")
cat(paste(n2, avg2, sd2, se2, se22, mar2, sep="_"))
cat("--")
cat(paste(d, sed, T, sig, sep="_"))