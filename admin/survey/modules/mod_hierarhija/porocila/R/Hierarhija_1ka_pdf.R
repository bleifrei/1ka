##################
# REPORTS IN WORD
##################

#-------------------------- PHP ----------------------------#
# Passing arguments to an R script from command lines
params <- commandArgs(trailingOnly=TRUE)
# CSV name
filename <- params[1]
# Name of produced doc file
fileOutputName <- params[3]
# Name of inserted logo in doc file
logoName <- params[4]
## //Passing arguments// ##
#---------------------- //PHP// ----------------------------#


#--------------------- SLOVENE ENCODING --------------------#
# For correct output of CZS in report
Sys.setlocale(category = "LC_ALL", locale = "slovenian")
#------------------- //SLOVENE ENCODING// ------------------#


#------------------- Necessary libraries -------------------#
libraries <- c('fmsb','car','plyr','matrixStats','magrittr',
               'ggplot2','scales','Hmisc','xtable')
lapply(libraries, FUN = function(y) {
  do.call('require', list(y))})
#------------------- //Necessary libraries// ---------------#


#--------------------- CUSTOM FUNCTIONS --------------------#
# Rounding up the value
round2 = function(x, n) {
  posneg = sign(x)
  z = abs(x)*10^n
  z = z + 0.5
  z = trunc(z)
  z = z/10^n
  z*posneg
}

# Factor to numeric
as.numeric.factor <- function(x) {as.numeric(levels(x))[x]}
#--------------------//CUSTOM FUNCTIONS// ------------------#


########################################
#--------------------------------------- DATA: importing and processing --------------------------------------------#
                                                                        #############################################

# Mapa, kjer se generira PDF  slike grafov
dir.create('modules/mod_hierarhija/porocila/results/slike', showWarnings=FALSE)

# Import data # For correct output of CZS in report (boath tables and ggplot graphs) we need to omit enocoding="UTF-8" in read.csv2 
Hierarhija <- read.csv2(paste0("modules/mod_hierarhija/porocila/temp/",filename), 
                        sep = ";", header = T, fill = T, stringsAsFactors = FALSE)

# Variable names (later for extracting possible comments of students or teacher)
Hierarhija_names <- Hierarhija[1, ]
# Omit the first row od the data base
if (Hierarhija[1,1]==("Ustreznost") | Hierarhija[1,1]==("Relevance")) {Hierarhija <- Hierarhija[2:nrow(Hierarhija),]}


# CREATE REPORTS ONLY IF THERE IS MORE THAN 1 ANSWER
if (nrow(Hierarhija) > 1) {
   
  # If these values are present in students comments we will delete them
  junk <- c("-1","-2", "-3", "-4", "-5")
  
  # Students grades/asnwers
  Hierarhija_ucenci <- subset(Hierarhija, vloga == 1)
  if(!nrow(Hierarhija_ucenci) > 0) {Hierarhija_ucenci <- NULL}
  
  # Teachers grades/asnwers (samoevalvacija)
  Hierarhija_ucitelj <- subset(Hierarhija, vloga == 2)
  if(!nrow(Hierarhija_ucitelj) > 0) {Hierarhija_ucitelj <- NULL}
  
  # Labeling hierarchy (poimenovanje hierarhije) 
  nivo <- unique(Hierarhija_ucenci[grep("nivo", names(Hierarhija_ucenci), value = TRUE)]) 
  # Course names (ime predmeta)
  nivo.predmet <- as.character(nivo[length(nivo)])
  # Paste hierarchy info in one row (we will use later for writing these info in the head of word document)
  nivo <- paste0(nivo, collapse=', ')
  #--------------------------------------- //DATA: importing and processing// --------------------------------------------#
  
  
  #------------------------------------- START PROCESSING DATA: TABLES AND GRAPHS ----------------------------------------#
  # If there are multiple content/segment questions (Q1, Q2, ...) in the questionnaire we would like to separate them for
  # tables and ggplots
  
  ###################################################################################  
  # Loop and find the multiple content questions (without comments if there were any)
  ###################################################################################
  # Extract segemnts (Q1, Q2, ...)
  data <- Hierarhija_ucenci[,grep("Q", colnames(Hierarhija_ucenci))]
  # Seperate this segments
  uniqueSegemnts <- unique(substr(colnames(data), 1, 2))
  # Assign values of unique segments (and colnames) to list
  uniqueSegemntsLS <- lapply(uniqueSegemnts, function(x) data[grepl(x, colnames(data))])
  
  # If there are any comments dont include them in calculating descriptive statistics form tables  
  uniqueSegemntsLS <- Filter(function(x)length(x)>=2, uniqueSegemntsLS )
  #listaSklopov[names(listaSklopov)[lengths(listaSklopov) < 2]] <- NULL
  
  # Define new vaariables to which we will assign the output of our loop
  ucitelji <- NULL # Tables
  
  ##############################################################################
  #START LOOP: CALCULATING DESCRIPTIVE STATISTICS NAD CRETING TABLES AND GGPLOTS
  ##############################################################################
  for (stSklopa in seq(uniqueSegemntsLS)) {
    
    # Multiple questions part
    indexi_sklopa <- colnames(uniqueSegemntsLS[[stSklopa]])
    
    ########################
    # DESCRIPTIVE STATISTICS
    ########################
    ocena <- sapply(Hierarhija_ucenci[,indexi_sklopa], as.numeric)
    ocena[ocena < 0] <- 0
    
    # Answers average
    predM <- round2(colMeans(NA^(ocena==0)*ocena, na.rm=TRUE),1)
    
    # MAX
    #colMax <- function(data) apply(data, 1, min) 
    #PredMed <- colMax(predmet)
    
    # SD
    PredSD <- round2(colSds(NA^(ocena==0)*ocena, na.rm=TRUE),1)
    
    # N
    PredN <- colSums(!is.na(ocena))
    
    ######################
    # START CREATING TABLE 
    ######################
    # Generating table of frequencies and percentages 
    table <- cbind.data.frame(
      mean=predM,
      n=PredN,
      #median=PredMed,
      sd=PredSD)
    
    # Adding row of total average
    table <- rbind(table, colMeans(table[1:ncol(table)], na.rm=TRUE))
    table <- round2(table,1)
    
    # Ime stolpca in Ocene u?itelja v stolpcu
    nameCOL <- "Učitelj" # Ime stolpca
    digitsUcitelj <- 0 # če se je ucitelj ocenil to zapišemo v xtable pri "digits", drugače ostane NULL
    if(!is.null(Hierarhija_ucitelj[,indexi_sklopa])) { # Samo ?e se je u?itelj ?e ocenil
      Hierarhija_ucitelPRED <-  as.numeric(Hierarhija_ucitelj[,indexi_sklopa])
      table <- cbind(table,c(Hierarhija_ucitelPRED,"."))
    } else {Hierarhija_ucitelPRED <- NULL
    nameCOL <- NULL
    digitsUcitelj <- NULL}
    
    # Rownames
    rownames(table) <- c(Hierarhija_names[indexi_sklopa], "Skupaj")
    
    # Colnames
    colnames(table) <- c(paste('Povprečje'), paste('Št. odgovorov'),"Standardni odklon",nameCOL)
    
    # Write multiple tables in a list    
    #tabele[[stSklopa]] <- c("\\renewcommand{\\arraystretch}{0.4}", table.tex)
    
    
    ##############################
    ## GGPLOT: DATA PREPARATION ##
    ##############################
    
    vloga_ucenec <- rep("u\u010Denec", nrow(table) - 1)
    kategorija_ucenec <- rownames(table)[rownames(table) != "Skupaj"]
    ocena_ucenec <- table[-nrow(table), 1]
    Vloga_Ocena_ucenec <- as.data.frame(cbind(vloga_ucenec, kategorija_ucenec, ocena_ucenec))
    colnames(Vloga_Ocena_ucenec) <- c("Vloga", "Kategorija", "Povprecje")
    
    if(!is.null(Hierarhija_ucitelj[,indexi_sklopa])) {
      vloga_ucitelj <- rep("u\u010Ditelj",nrow(table) - 1)
      kategorija_ucitelj <- rownames(table)[rownames(table) != "Skupaj"]
      ocena_ucitelj <- table[ncol(table)]
      ocena_ucitelj <-  as.numeric(ocena_ucitelj[ocena_ucitelj != "."])
      Vloga_Ocena_ucitelj <- as.data.frame(cbind(vloga_ucitelj, kategorija_ucitelj, ocena_ucitelj))
      colnames(Vloga_Ocena_ucitelj) <- c("Vloga", "Kategorija", "Povprecje")
    }else {Vloga_Ocena_ucitelj <- NULL}
    
    Skupna_ocena <- rbind(Vloga_Ocena_ucenec, Vloga_Ocena_ucitelj)
    # Order factor lables as same as they are in data frame
    Skupna_ocena$Povprecje <- as.numeric.factor(Skupna_ocena$Povprecje)
    
    # GGPLOT: text break in a certain number of characters in the graph
    Skupna_ocena$Kategorija <- gsub('(.{1,25})(\\s|$)', '\\1\n', Skupna_ocena$Kategorija) 
    # First we need to provide that CSZ in 1KA SERVER will be outputed correctly in ggplot graphs
    Skupna_ocena$Kategorija <- gsub('Č', '\u010C', Skupna_ocena$Kategorija)
    Skupna_ocena$Kategorija <- gsub('č', '\u010D', Skupna_ocena$Kategorija)
    Skupna_ocena$Kategorija <- gsub('Š', '\u0160', Skupna_ocena$Kategorija)
    Skupna_ocena$Kategorija <- gsub('š', '\u0161', Skupna_ocena$Kategorija)
    Skupna_ocena$Kategorija <- gsub('Ž', '\u017D', Skupna_ocena$Kategorija)
    Skupna_ocena$Kategorija <- gsub('ž', '\u017E', Skupna_ocena$Kategorija)
    
    # Dont forget to insert encoding in pdf()  encoding = 'CP1250'
    pdf(paste('modules/mod_hierarhija/porocila/results/slike/graf_',stSklopa,'.pdf', sep=""), pointsize=15, width=6.8, height=5.5, encoding = 'CP1250')
    # Creating ggplot
    bp <- ggplot(Skupna_ocena, aes(factor(Kategorija), Povprecje, fill = Vloga)) + 
      geom_bar(stat="identity", position = "dodge", width=0.5) + 
      scale_fill_brewer(palette = "Set1") + coord_flip() +
      theme_bw() +
      scale_y_continuous(expand=c(0,0), limits=c(1,5),oob = rescale_none) +
      scale_x_discrete(expand=c(0,0),limits=unique(rev(Skupna_ocena$Kategorija))) +
      theme(axis.title=element_blank(),axis.ticks.y=element_blank(),
            axis.text.x = element_text(angle = 0, vjust = 0.4, size = 12),
            axis.text.y = element_text(size = 12),
            legend.text=element_text(size=12)) +
      ggtitle("Grafikon povpre\u010Dij komponent glede na vlogo")
    
    print(bp)
    
    dev.off() # Save ggplot to pdf
 
    # Write multiple tables and ggplots   
    tabele.grafi <- c(paste0("\\section{POVPREČJE KOMPONENT GLEDE NA VLOGO}"),"V tabeli so prikazane deskriptivne statistike učitelja pri predmetu ",nivo.predmet,
                      print.xtable(xtable(table,  align=c('|p{3in}|',rep('c|', ncol(table))), # p{3in} text wrapping row.names
                                          digits = c(0,1,0,1,digitsUcitelj)), hline.after= -1:nrow(table), 
                                          scalebox=0.93),
                                          # GGPLOT
                                          paste0(
                                          "\\begin{figure}[H]",
                                          #"\\caption{ \\textbf{\\large{Oddelki_podatki}}}",
                                          paste0("\\centerline{\\includegraphics[width=0.6\\textwidth]{slike/graf_",stSklopa,".pdf}}"),
                                          "\\end{figure}"),"\\newpage")
              
              
    # Assign 
    ucitelji[[stSklopa]]  <- tabele.grafi
    
  }
 
  #----------------------------------- //START PROCESSING DATA: TABLES AND GRAPHS// --------------------------------------#
  
  
  #------------------------------------------ COMMENTS OF STUDENTS -------------------------------------------#
  if(!is.null(Hierarhija_ucenci)) {
    if("komentar" %in% Hierarhija_names[,1:ncol(Hierarhija_names)] | "Komentar" %in% Hierarhija_names[,1:ncol(Hierarhija_names)]) {
      komentarji <- Hierarhija_ucenci[which(apply(Hierarhija_names, 2, function(x) any(grepl("komentar|Komentar", x))))][,1]
      komentarji <- komentarji[!komentarji %in% junk]
      if (identical(komentarji, character(0)) == FALSE) {
        # Capture students comments for open ended question
        komentarji <- reports::LL(text=Hmisc::latexTranslate(komentarji), copy2clip=FALSE, enumerate=FALSE)
        komentarji <- c("\\section{Komentarji učencev na odprto vprašanje}", komentarji)
      }
    } else {komentarji <- NULL}
  } else {komentarji <- NULL}
  #---------------------------------------- //COMMENTS OF STUDENTS// -----------------------------------------#
  
  
  #------------------------------------------ COMMENTS OF TEACHERS -------------------------------------------#
  # if(!is.null(Hierarhija_ucitelj)) {
  #   if("komentar" %in% Hierarhija_names[,1:ncol(Hierarhija_names)] | "Komentar" %in% Hierarhija_names[,1:ncol(Hierarhija_names)]) {
  #     
  #     komentarji <- Hierarhija_ucitelj[which(apply(Hierarhija_names, 2, function(x) any(grepl("komentar|Komentar", x))))][,1]
  #     komentarji <- komentarji[!komentarji %in% junk]
  #     if (identical(komentarji, character(0)) == FALSE) {
  #       # Capture teacher comments for open ended question
  #       doc <- addParagraph(doc, c("", "")) # 2 line breaks
  #       doc <- addTitle(doc, "Komentar u\u010Ditelja na odprto vpra\u0161anje")
  #       #komentarji <- gsub('Č', '\u010C', komentarji)
  #       #komentarji<- gsub('č', '\u010D', komentarji)
  #       #komentarji <- gsub('Š', '\u0160', komentarji)
  #       #komentarji <- gsub('š', '\u0161', komentarji)
  #       #komentarji <- gsub('Ž', '\u017D', komentarji)
  #       #komentarji <- gsub('ž', '\u017E', komentarji)
  #       doc <- addParagraph(doc, value= komentarji, par.properties =  parProperties(list.style = 'unordered'))
  #     }
  #   }
  # }
  #---------------------------------------- //COMMENTS OF TEACHERS// ----------------------------------------#
  
  
  ########################################################################################################################

  
  ##################
  # Generiranje PDF
  ##################
  # Scan latex files where we defined structure od a document  
  # character(0) for a string  ,sep="\n" separate each line,  quiet=TRUE  will NOT print a line, saying how many items have been read.
  tex.glava <- scan("modules/mod_hierarhija/porocila/latexkosi/samoevalvacija_glava.tex", character(0), sep="\n", quiet=TRUE, encoding='UTF-8')
  # Check for logo
  img.file <- file.path("modules/mod_hierarhija/porocila/logo/", logoName)
  # If school logo exist
  if (file.exists(img.file)){
  # Insert scool logo in Latex: TOP LEFT MARGIN
  tex.glava <- gsub(pattern='!logotip!', replacement=Hmisc::latexTranslate(logoName), x=tex.glava) 
  }
  # Podatki o u?encih v galvo dokumenta
  tex.glava <- gsub(pattern='!glava!', replacement=Hmisc::latexTranslate(nivo), x=tex.glava) 
  # Zaklju?imo dokument z nogo
  tex.noga  <- scan("modules/mod_hierarhija/porocila/latexkosi/samoevalvacija_noga.tex", character(0), sep="\n", quiet=TRUE)
  Rdirektorij <- getwd()
  
  
  # Compiling file
  tex.izbor <-  c(tex.glava, ucitelji, komentarji, tex.noga)
  
  
  # Set working directory
  setwd(paste(Rdirektorij, "modules/mod_hierarhija/porocila/results", sep="/")) # File folder
  #copy-paste the output to latex 
  cat(unlist(tex.izbor), file=paste0(gsub("\\.pdf", "", fileOutputName),".tex"), sep="\n")
  # Convert latex to PDF
  tools::texi2pdf(file=paste0(gsub("\\.pdf", "", fileOutputName),".tex"), quiet=TRUE, clean=TRUE)
  setwd(Rdirektorij)
}