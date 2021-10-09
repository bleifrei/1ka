params <- commandArgs(trailingOnly = TRUE)
ID <- params[1]

library(foreign) # Import csv or sav data

#########################
# SPEEDER INDEX FUNKCIJA
#########################
# funkcija je napisana s for zankami, da je lazje razumljivo, sicer pa je to poèansejši naèin, zato ce bos popravljal
# raje uporabi apply ali by ali kaj podobnega
speeder <- function(data){
  # izracuna mediane po stolpcih
  medians <- apply(X = data, 2, median, na.rm=T)
  # naredi novo matriko enakih dimenizij, kot je orig. podatkovna matrika
  news <- matrix(NA, nrow = dim(data)[1], ncol = dim(data)[2])
  
  # gre cez vse enote
  for (i in 1:dim(data)[1]){
    for (j in 1:dim(data)[2]){
      # ce je manjkajoca vrednost ne naredi nic
      if (is.na(data[i,j]) == T) {news[i,j] <- NA}
      if (is.na(data[i,j]) == F){
        # ce je vrednost pri enoti vecja ali enaka od mediane potem ji pripise 1
        if (data[i,j] >= medians[j]){news[i,j] <- 1}
        # ce je vrednost pri enoti manjsa, ji pripise vrednost pri enoti deljeno z vrednostjo mediane odgovarajaoce spr.
        if (data[i,j] < medians[j]){news[i,j] <- (data[i,j]/medians[j])} 
      }
    }
  }
  
  # izracuna povprecja (tocka 3 v algoritmu)
  povprecja <- rowMeans(news, na.rm=T)
  # ce je pod 10 procentov vseh, potem je speeder
  speed_no_speed <- as.numeric(povprecja < quantile(povprecja, 0.1))
  speed_no_speed[speed_no_speed == 1] <- "1"
  speed_no_speed[speed_no_speed == 0] <- "0"
  return(speed_no_speed)
}
## //SPEEDER INDEX FUNKCIJA// ##



# Preberemo vhodne podatke
datumi <-  read.csv2(paste0("modules/mod_SPEEDINDEX/temp/datum", ID, ".csv"), sep=";", header = T, fill = T, stringsAsFactors = FALSE)
#if (datumi[1,1]=="Ustreznost") {datumi <- datumi[2:nrow(datumi),]}
datumi <- subset(datumi, Status==6)

## SELECT APROPRIATE DATA ##
# Iz baze izberemo le stolpce, ki nas zanimajo: Vse stolpce, ki v imenu vsebujejo Datum (ker raèunamo èase na strani) ter Id = RECNUM
test <- datumi[  ,grepl("Datum|Id" , names( datumi ) ) ]

# Izberemo le stolpce, ki vsebujejo veè kot 10 znakov zato, ker so bile nekje v stolpcih vrednosti 0 ali 1
#test <- test[apply(test, MARGIN = 1, function(x) all(nchar(x) > 10)), ]

# Zapišemo RECNUm oz. ID
test_id <- test[1]


# Poèistimo še DATUM in izberemo le URO
test <- apply(test[2:ncol(test)], 2, function(y) gsub(".* ", "", y))
test <- test[ , ! apply( test , 2 , function(x) all(is.na(x)) ) ]
# Èe so na straneh prazne vrednosti, prepišemo vrednosti iz prejšnjega stolpca
#test <- ifelse(test=="", test[,-1], test)
## //SELECT APROPRIATE DATA// ##



# èas v sekundah, ki ga je anketiranec preživel na x strani (ki se izraèuna kot razlika med stolpcem date_x in date_x+1)
makeTime <- function(x) as.POSIXct(paste(Sys.Date(), x))
dat <- apply(test, 2, makeTime)
data <- mapply(x = 2:ncol(dat), 
       y = 1:(ncol(dat) -1), 
       function(x, y) dat[ , x] - dat[ , y])




# data <- read.spss("speederindex.sav", to.data.frame = T)
data <- as.matrix(data)
# Poženemo funkcijo speeder index na podatkih
#speeder(data)
speederindex <- speeder(data)
# Indeksu dodamo èase respondentov po straneh
speederindex <- cbind(speederindex, data)

df <- speederindex

colnames(df)[1] <- "Index hitrosti"

for(i in 2:ncol(df)){
  colnames(df)[i] <- paste0("Stran ", i-1)
}

df <- cbind(test_id,df)

# Zapišemo rezultat v csv
write.csv2(df, paste0("modules/mod_SPEEDINDEX/results/speederindex", ID, ".csv"),row.names=F)
#write.csv2(speederindex, file ="modules/mod_SPEEDINDEX/results/speederindex.csv",row.names=T)
