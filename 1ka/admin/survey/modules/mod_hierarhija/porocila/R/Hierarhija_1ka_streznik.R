##################
# REPORTS IN WORD
##################

#-------------------------- PHP ----------------------------#
# Passing arguments to an R script from command lines
params <- commandArgs(trailingOnly=TRUE)
# CSV name
filename <- params[1]
# Name of produced doc file
fileOutputName <- params[2]
# Name of inserted logo in doc file
logoName <- params[3]
## //Passing arguments// ##
#---------------------- //PHP// ----------------------------#


#--------------------- SLOVENE ENCODING --------------------#
# For correct output of CZS in report
Sys.setlocale(category = "LC_ALL", locale = "slovenian")
#------------------- //SLOVENE ENCODING// ------------------#


#------------------- Necessary libraries -------------------#
libraries <- c('fmsb','car','plyr','matrixStats','ReporteRs',
               'magrittr','ggplot2','scales')
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
# Import data
Hierarhija <- read.csv2(paste0("modules/mod_hierarhija/porocila/temp/",filename), 
                        sep = ";", header = T, fill = T, stringsAsFactors = FALSE, encoding = "UTF-8")
# Variable names (later for extracting possible comments of students or teacher)
Hierarhija_names <- Hierarhija[1, ]
# Omit the first row od the data base
if (Hierarhija[1,1]==("Ustreznost") | Hierarhija[1,1]==("Relevance")) {Hierarhija <- Hierarhija[2:nrow(Hierarhija),]}


# CREATE REPORTS ONLY IF THERE IS MORE THAN 1 ANSWER
if (nrow(Hierarhija) > 1) {
  
  # First we need to provide that CSZ in 1KA SERVER will be outputed correctly 
  Hierarhija_names[,-1] <- gsub('Č', '\u010C', as.matrix(Hierarhija_names[,-1]))
  Hierarhija_names[,-1] <- gsub('č', '\u010D', as.matrix(Hierarhija_names[,-1]))
  Hierarhija_names[,-1] <- gsub('Š', '\u0160', as.matrix(Hierarhija_names[,-1]))
  Hierarhija_names[,-1] <- gsub('š', '\u0161', as.matrix(Hierarhija_names[,-1]))
  Hierarhija_names[,-1] <- gsub('Ž', '\u017D', as.matrix(Hierarhija_names[,-1]))
  Hierarhija_names[,-1] <- gsub('ž', '\u017E', as.matrix(Hierarhija_names[,-1]))
  
  # # If these values are present in students comments we will delete them
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
  tabele <- NULL # Tables
  grafi <- NULL # GGplot 
  
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
    nameCOL <- "Ucitelj (samoocena)" # Ime stolpca
    if(!is.null(Hierarhija_ucitelj[,indexi_sklopa])) { # Samo ?e se je u?itelj ?e ocenil
      Hierarhija_ucitelPRED <-  as.numeric(Hierarhija_ucitelj[,indexi_sklopa])
      table <- cbind(table,c(Hierarhija_ucitelPRED,"."))
    } else {Hierarhija_ucitelPRED <- NULL
    nameCOL <- NULL}
    
    # Rownames
    rownames(table) <- c(Hierarhija_names[indexi_sklopa], "Skupaj")
    
    # Colnames
    colnames(table) <- c("Povprecne ocene", 'St. odgovorov',"Standardni odklon",nameCOL)
    
    # Write multiple tables in a list    
    tabele[[stSklopa]] <- table
    
    
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
    
    
    # Creating ggplot
    bp <- ggplot(Skupna_ocena, aes(factor(Kategorija), Povprecje, fill = Vloga)) + 
      geom_bar(stat="identity", position = "dodge",width=0.7) + 
      scale_fill_brewer(palette = "Set1") + coord_flip() +
      theme_bw() +
      scale_y_continuous(expand=c(0,0), limits=c(1,5),oob = rescale_none) +
      scale_x_discrete(expand=c(0,0),limits=unique(rev(Skupna_ocena$Kategorija))) +
      theme(axis.title=element_blank(),axis.ticks.y=element_blank(),
            axis.text.x = element_text(angle = 0,vjust = 0.4)) +
      ggtitle("Grafikon povpre\u010Dij komponent glede na vlogo")
    
    # Write multiple ggplots in a list  
    grafi[[stSklopa]] <- bp
  }
  #----------------------------------- //START PROCESSING DATA: TABLES AND GRAPHS// --------------------------------------#
  
  
  #------------------------------------------------ NOT USING THIS NOW ---------------------------------------------------#
  ########################
  # DATA FOR RADAR CHART #
  ########################
  #test <- cbind.data.frame(
  # mean=predM)
  
  # provide the data you want to plot, and the desired range 
  #radar.data <- t(test)
  #myrange <- c(1, 5) 
  
  # create a data frame with the max and min as the first two rows 
  #mydf <- data.frame(rbind(max=myrange[2], min=myrange[1], radar.data)) 
  
  # Insert colnames 
  #colnames(mydf) <- c(Hierarhija_names[grep("Q1", names(Hierarhija_names), value=TRUE)])
  #--------------------------------------------- //NOT USING THIS NOW// -------------------------------------------------#
  
  
  ########################################################################################################################
  
  
  ################################################
  #----------------------------------------------- COMPILING WORD REPORT -----------------------------------------------#
  #################################################
  #options("ReporteRs-default-font" = "Times New Roman")
  # Create a docx object
  doc = docx()
  # template with Head bookmark (in the head of word document we will print students info)
  template <- "modules/mod_hierarhija/porocila/R/Anketiranci.docx"  
  doc = docx( template = template )
  # Header / BOOKMARK :info about students (grade, program, etc.)
  doc = addParagraph(doc, nivo, bookmark = "Anketiranci")
  # School logo
  img.file <- file.path("modules/mod_hierarhija/porocila/logo", logoName)
  # If school logo exist
  if(file.exists(img.file)){
    # Insert scool logo in word: FIRST PAGE/ TOP MARGIN
    doc <- addImage(doc,img.file, width = 2.0, height = 1.5, par.properties = parLeft() )
  }
  # add a document title
  doc = addParagraph( doc, "SAMOEVALVACIJA V \u0160OLAH", stylename = "TitleDoc" )
  
  
  #---------------------------- CREATE AND WRITE MULTIPLE TABLES AND GGPLOTS -----------------------------------#
  for (i in seq_along(tabele)) {
    # some text
    # add a slide title
    doc <- addTitle(doc, "Povpre\u010Dje komponent glede na vlogo" )
    # Boldamo ime predmeta
    nivo.predmet.bold = pot("V tabeli so prikazane deskriptivne statistike ucitelja pri predmetu ", textProperties(font.weight = 'normal')) +
      pot(nivo.predmet, textProperties(font.weight = 'bold'))
    #doc <- addTitle(doc, "Oceni, kako pogosto naslednje trditve veljajo za u\u010Ditelja/-ico pri tem predmetu",level = 2)
    doc = addParagraph(doc, nivo.predmet.bold, stylename = "DocDefaults" )
    MyFTable = FlexTable(tabele[[i]], add.rownames = TRUE ) # Descriptive statistics for Oceni, kako pogosto naslednje trditve veljajo za u?itelja/-ico pri tem predmetu
    doc = addFlexTable(doc, MyFTable)
    # 2 line breaks after table
    doc <- addParagraph(doc, c("", ""))
    # A function for creating a box plot
    # Add an editable box plot
    doc <- addPlot(doc, function() print(grafi[[i]]) ,vector.graphic = TRUE, width = 5, height = 3.6)
    # add a page break
    doc <- addPageBreak(doc)
  }
  #--------------------------- //CREATE AND WRITE MULTIPLE TABLES AND GGPLOTS// ------------------------------#
  
  
  #------------------------------------------ COMMENTS OF STUDENTS -------------------------------------------#
  if(!is.null(Hierarhija_ucenci)) {
    if("Ima\u0161 \u0161e kak komentar?" %in% Hierarhija_names[,1:ncol(Hierarhija_names)] | "Komentar" %in% Hierarhija_names[,1:ncol(Hierarhija_names)]) {
      komentarji <- Hierarhija_ucenci[which(apply(Hierarhija_names, 2, function(x) any(grepl("Ima\u0161 \u0161e kak komentar?|Komentar", x))))][,1]
      komentarji <- komentarji[!komentarji %in% junk]
      if (identical(komentarji, character(0)) == FALSE) {
        # Capture students comments for open ended question
        doc <- addTitle(doc, "Komentarji u\u010Dencev na odprto vpra\u0161anje")
        komentarji <- gsub('\u010C', 'Č', komentarji)
        komentarji<- gsub('\u010D', 'č', komentarji)
        komentarji <- gsub('Š', '\u0160', komentarji)
        komentarji <- gsub('š', '\u0161', komentarji)
        komentarji <- gsub('Ž', '\u017D', komentarji)
        komentarji <- gsub('ž', '\u017E', komentarji)
        doc <- addParagraph(doc, value= komentarji, par.properties =  parProperties(list.style = 'unordered'))
      }
    }
  }
  #---------------------------------------- //COMMENTS OF STUDENTS// -----------------------------------------#
  
  
  #------------------------------------------ COMMENTS OF TEACHERS -------------------------------------------#
  if(!is.null(Hierarhija_ucitelj)) {
    if("komentar" %in% Hierarhija_names[,1:ncol(Hierarhija_names)] | "Komentar" %in% Hierarhija_names[,1:ncol(Hierarhija_names)]) {
      
      komentarji <- Hierarhija_ucitelj[which(apply(Hierarhija_names, 2, function(x) any(grepl("komentar|Komentar", x))))][,1]
      komentarji <- komentarji[!komentarji %in% junk]
      if (identical(komentarji, character(0)) == FALSE) {
        # Capture teacher comments for open ended question
        doc <- addParagraph(doc, c("", "")) # 2 line breaks
        doc <- addTitle(doc, "Komentar u\u010Ditelja na odprto vpra\u0161anje")
        komentarji <- gsub('Č', '\u010C', komentarji)
        komentarji<- gsub('č', '\u010D', komentarji)
        komentarji <- gsub('Š', '\u0160', komentarji)
        komentarji <- gsub('š', '\u0161', komentarji)
        komentarji <- gsub('Ž', '\u017D', komentarji)
        komentarji <- gsub('ž', '\u017E', komentarji)
        doc <- addParagraph(doc, value= komentarji, par.properties =  parProperties(list.style = 'unordered'))
      }
    }
  }
  #---------------------------------------- //COMMENTS OF TEACHERS// ----------------------------------------#
  
  #NOT DOING THIS NOW  Creating RADAR CHART with LEGEND
  #doc = addParagraph(doc, value = "Povpre?ne ocene komponent", stylename = "rPlotLegend")
  #doc <- addPlot(doc, function() print(c(radarchart(mydf, pcol=c("#e41a1c", "#377eb8", "#4daf4a","#984ea3"), cglcol='gray75', 
  #                                                 plwd=2, plty=1, cglwd=1, cglty=1, seg=4, axistype=1, caxislabels=c(1:5),
  #                                                axislabcol='gray25', centerzero=TRUE),
  #                                    legend('topright', legend=c("U?enci","U?itelj"), 
  #                                          col=c("#e41a1c", "#377eb8", "#4daf4a","#984ea3"), lty=1, lwd=2, bty='n')), vector.graphic = TRUE ))
  
  #------------------------------------------ WRITE AND SAVE DOCX ------------------------------------------#
  # write the doc
  writeDoc(doc, file = paste0("modules/mod_hierarhija/porocila/results/",fileOutputName))
  # open the Word doc
  #browseURL("Samoevalvacija1.docx") # This line is not needed, is only to open the Word doc from Rstudio
} else {
  # Create a docx object
  doc = docx()
  # add a document title
  doc = addParagraph( doc, "Zaradi premajhnega števila enot (1 ali manj) se poro\u010Dilo ni zgeneriralo.", stylename = "Normal" )
  writeDoc(doc, file = paste0("modules/mod_hierarhija/porocila/results/",fileOutputName))
  
}

#----------------------------------------------- //COMPILING WORD REPORT// -----------------------------------------------#
