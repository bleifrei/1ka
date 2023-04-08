# Created by Miha 27.5.2021
# Modified (sixth version) by Miha 7.6.2021


# Note: The formated banners of comments are generated with
#' @bannerCommenter package.


###########################################################################
###########################################################################
###                                                                     ###
###                            DESCRIPTION:                             ###
###                     RESPONSE TIME WINSORIZATION                     ###
###                                                                     ###
###########################################################################
###########################################################################
#' @param VVMM PRIPRAVA PODATKOV:
#' NOTE: tole kodo smo po VV zmišljevanjui tolikokrat spremenili 
#' da se mi ne da več pisat v angleščini in konstantno popravljati :)
#' 
# Za ustrezno obravnavo časov, je treba predhodno pripraviti podatke.
# Glavni problemi ki pri tem nastanejo so: respondenti, ki na določeni strani 
# niso odogovrili na vsa vprašanja zaradi česar so njihovi časi neupraviečno
# prekratki,potem nerespondenti, ki so priskočili določeno stran  3) respodneti,
# ki so na določeni strnai imeli notranji pogodj oz so izbrali vprašanje drugo.
# In 4) respondeti,  ki so prepočasni v smislu gausov eksponentre krivulje kar
# se vstorki obravnava z postopki in odstraniujo asimetrijo v normalbi porazdleitvi
# zaradi aktere pride do amanomalj vtestirnaju 5) popravek za čase respondentov,
# ki so prekinili izpolnjevanje

# Postopek metode

#' @1.Trunciranje enote, ki so na posamezni strani nad 95tim percentilom
#' @2.Strani, ki so nagovori se pri izračunu indeksa ne upošteva
#' @3.Preverimo ali je stran za respondenta mešana, torej ali vprašanja
#'  oziroma postavke, poleg veljavnih vrednosti respondenta na stran 
#'  (verdnosti večje od 1), vsebujejo še kakšno manjkajočo vrednost zaradi pogoja 
#'  (-2)
#' @4.V kolikor obstajajo mešane vrednosti, potem čas respondenta na tej mešani
#'  strani pomnožimo z deležem ocenjenega časa, ki bi na tej strnai sicer
#'   pripadal temu vprašanju. Privzeta tehnična meritev orodja za spletno anketirnaje, 
#'   ki ima algoritem( priloži sliko 1KA časov). Zmanjšaš čas, za 10 procentov
#'    (pomnožiš z 90 %).
#' @5.Preverimo ali so na strani respondenta manjkajoče vrednosti. Če manjkajoča
#'  vrednost obstaja, čas respondenta na tej strani delimo z 0.9
#' @6.Nato izračunamo indeks respondenta (Rti), ki je izračunan tako, da vsot
#' o strani respondenta (brez -2) delimo z vsoto median taistih strani. 
#' @7.	Ponovno izračunamo mediane strani.
#' @8.	Vrednosti, ki smo jih v prvem koraku truncirali imputiramo, in sicer so
#'  truncirane vrednosti zmnožek indeksa posameznega respondenta pomnožene z mediano
#'   strani oziroma Rti * mediana stranii

#------------------------- // DESCRIPTION // -----------------------------#


#------------ List of packages we need --------------#
library(data.table)
library(dplyr)
#------------ List of packages we need --------------#



############################################################################
############################################################################
###                                                                      ###
###                                DATA:                                 ###
###                        IMPORT AMD PREPARATION                        ###
###                                                                      ###
############################################################################
############################################################################

#setwd("E:\\Doktorat\\Modul kakovost 1ka/")

##---------------------
##  Input on 1KA side  
##---------------------
# To know for which survey we are calculating response time
params <- commandArgs(trailingOnly = TRUE)
ID <- params[1]
#ID <- 8699

##----------------------------
##  Import data and paradata  
##----------------------------
# We need thrtee files
#' @data: data frame with paradata (response time)
#' @questions: data about page ID nad number of
#' items/variables per page, in order to properly calculate our
#' index
#' @items: questions item info. Important part is char_count, which
#' represents the 1KA estimeted time (100 char_count == 10 sekund)
#' We will merge items and questions



## Data -----
# path
rt.file <- paste0("modules/mod_kakovost/temp/data_", ID, ".csv")
# Import
rt <-
  as.data.frame(fread(rt.file, header = TRUE), stringsAsFactors = FALSE)

## Questionns --------
# get question and item files
questions.file <-
  paste0("modules/mod_kakovost/temp/questions_", ID, ".csv")
# Import
questions <-
  fread(
    questions.file,
    header = TRUE,
    data.table = FALSE
  )
# Check if there is question type "Nagovor", we want to omit
# this form analysis

# FOR NOW: later we will retunr back
# more testing is needed
questions$params <-
  ifelse(grepl("nagovor", questions$params), questions$params, "")


## Items --------
# We need ITems to calculate response time pe ritem
# and use it in calculation of response time
## Questionns --------
# get question and item files
items.file <-
  paste0("modules/mod_kakovost/temp/items_", ID, ".csv")
# Import
items <-
  fread(
    items.file,
    header = TRUE,
    data.table = FALSE
  )




# Important 
# 1KA računa čas na naslednji način
# Čas za vprašanje (na 100 znakov besedila) = 10 sekund
# Čas za kategorijo (na 100 znakov besedila) = 5 sekund
# Torej bomo znake  pretvorili v sekunde

# Vprašanje
questions$cas1KA <- questions$char_count * 10 / 100
# Kategorija
items$cas1KA <- items$char_count * 5 / 100


# Merge Questions and items in order to get number of character per
# item and per questions
Ques.item <-
  merge(questions,
        items,
        by = "ID QUESTION",
        all = TRUE,
        sort = FALSE)

# Sort from smallest to largest, so the first page is always
# in the beginning
Ques.item <- arrange(Ques.item, `ID PAGE`)


#--------------------------------------------------------------------------#


############################################################################
############################################################################
###                                                                      ###
###                            RESPONSE TIME                             ###
###           CALCULATE RESPONSE TIME IN SECONDS FOR EACH PAGE           ###
###                                                                      ###
############################################################################
############################################################################
## SUBSET COLNAMES "date_" ##
# Iz baze izberemo le stolpce, ki nas zanimajo: 
# Vse stolpce, ki v imenu vsebujejo Date_ (ker ra?unamo ?ase na strani)
times <- rt[, grepl("t_insert|date_" , colnames(rt))]

# čas v sekundah, ki ga je anketiranec preživel na x strani
# (ki se izračuna kot razlika med stolpcem date_x in date_x+1)
makeTime <- function(x) {
  as.POSIXct(x, format = "%d.%m.%Y %H:%M:%S")
}
dat <- apply(times, 2, makeTime)
response_times <- mapply(x = 2:ncol(dat),
                         y = 1:(ncol(dat) - 1),
                         function(x, y)
                           (dat)[, x] - (dat)[, y])


# Zamenjamo ure in minute s sekundami
rt[, grepl("t_insert|date_" , colnames(rt))] <- cbind(response_times, NA)
rt[, grepl("t_insert|date_" , colnames(rt))][rt[, grepl("t_insert|date_" , colnames(rt))]  < 0] <-
  NA


##################################################################
##                       RT preparation                         ##
##################################################################
# Nov we need to match Items/variables with survey pages
# so we will know which items match response time per page
# This is important in order to correctly calculate
# response times  and remove respondents (set missing) with 
# item nonresponse per item.

# First subset columns with time per page
rt.page <- rt[, grepl("t_insert|date_" , colnames(rt))]
# Zadnji stolpec je NA kot rezultat odštevanje stolpcev
rt.page[ncol(rt.page)] <- NULL
#rt.page[3,2] <- 1


# ROČNO!!!!!!!
# Popravimo vrednost na strani 4, ki ni mešana
# ampak -8, saj sta na eni strani dve vprašanji
#, ki pa sta bili porazdeljeni 50-50.
# rt$Q7a.1 <- ifelse(rt$Q7a.1== -2 & rt$Q7b.1 >=0, rt$Q7b.1, rt$Q7a.1)
# rt$Q7b.1 <- NULL
# questions <- questions[-26,]
# # Enako velja za stran 20 torej  "Q28a" "Q28b"
# rt$Q28a <- ifelse(rt$Q28a== -2 & rt$Q28b >=0, rt$Q28b, rt$Q28a)
# rt$Q28b <- NULL
# questions <- questions[-136,]
#----------------------------- // Data // --------------------------------#

#---------------------
test <- rt.page
miss1 <- vector()
miss2 <- vector()
mesanaStranR <- list()
find.na <- list()

#' @1.Trunciranje
for (i in 1:ncol(test)) {
  test[test < 0] <- NA
  quantiles <- quantile(test[, i], .95,  na.rm = TRUE)
  # pripraviš vektor, s katerim najdeš katere vrednosti si zamenjal szs NA
  find.na[[i]] <- which(test[, i] > quantiles)
  # najprej nadomestiš vrednosti, ki so večje od thresholda z NA
  # browser()
  test[, i][find.na[[i]]] <- NA
}


# We do not start with 0 because it is introduction page
for(i in 1: ncol(test)) {
  #' @2.Strani, ki so nagovori se pri izračunu indeksa ne upošteva
  Ques.item <-
    Ques.item[!grepl("nagovorLine=0", Ques.item$params), ]
  
  #' @param 2: Set missing response time per page
  # Split variables acording to page
  var.per.page <- split(Ques.item, Ques.item$`ID PAGE`)
  # Find number of variables per page
  var.lab <- var.per.page[[i]][c("variable.x", "variable.y")]
  # Only valid items
  var.lab <- var.lab[var.lab > 1]
  
  #' @3.Preverimo ali obstaja mešana stran
  page.q <-  rt %>% select(any_of(var.lab))
  if(ncol(page.q) > 1) {
    page.q <- page.q[, order(colnames(page.q))]
  }
  mesanaStranR[[i]] <-
    data.frame(R=apply(page.q, 1, function(x)
      ifelse(-2 %in% x & any(x > 0), "YES", "NO")))
  
  #' @Vasja_2
  #' Za te »mešane strani« nato pogledate vsakega respondenta
  #'  in greste skozi vse njegove iteme na tej strani:
  
  #-        Če ima item -2, ga spremenite v -7.
  
  #-        Če ima item -1, ga pusite pri miru
  
  #-        Če item ni mešan, ga pustite pri miru.
  if (any(mesanaStranR[[i]] == "YES")) {
    # -7
    page.q[page.q == -2] <- -7
    
    for (j in 1:nrow(test)) {
      
      test[j, i] <-
        ifelse(any(page.q[j,] > 0) &
                 any(page.q[j,] == -7), test[j, i] * sum(
                   subset(
                     Ques.item,
                     variable.x == colnames(page.q)[page.q[j,] != -7] |
                       variable.y ==  colnames(page.q)[page.q[j,] != -7],
                     select = c("cas1KA.x", "cas1KA.y")
                   )
                 ), test[j, i])
      
    }
  }
  #' @5.Preverimo ali so na strnai manjkajole vrednosti
  for (j in 1:nrow(test)) {
    test[j, i] <-
      suppressWarnings(ifelse(any(page.q[j,] == -1) &
                                !is.na(test[j, i]), test[j, i] / sum(
                                  subset(
                                    Ques.item,
                                    variable.x == colnames(page.q)[page.q[j, ] == -1] |
                                      variable.y ==  colnames(page.q)[page.q[j, ] == -1],
                                    select = c("cas1KA.x", "cas1KA.y")
                                  )
                                ), test[j, i]))
  }
  
}


#' @6.Nato izračunamo indeks respondenta (Rti), ki je izračunan tako, da vsot
#' o strani respondenta (brez -2) delimo z vsoto median taistih strani. 
# Mediana
med.per.page <- apply(test, 2, function(x)
  median(x, na.rm = TRUE))

Rti <- NULL

Rt_i <-  lapply(seq_len(nrow(test)), function(y) {
  indx <-  which(!is.na(test[y, ]))
  if( length(indx) != 0) {
    Rti[y] <-
      round(sum(test[y, ][indx], na.rm = TRUE) / sum(med.per.page[indx], na.rm = TRUE), 3)
  } else {
    Rti[y] <- NA
  }
})
# Rti korak I
Rt.i <- do.call(rbind, Rt_i)



#' @8.	Vrednosti, ki smo jih v prvem koraku truncirali imputiramo, in sicer so
#'  truncirane vrednosti zmnožek indeksa posameznega respondenta pomnožene z mediano
#'   strani oziroma Rti * mediana stranii

imput.time <- test

for(i in 1:length(med.per.page)) {
  for (j in 1:nrow(test)) {
    imput.time[find.na[[i]], i] <- Rt.i[find.na[[i]]] * med.per.page[i]
  }
}


# Potem naredite novo datoteko z modificiranimi 
# PRAVIMI RT na stran ter dodamo imena stolpcev, ki
# odražajo strani
colnames(imput.time) <- paste("date_", 1:ncol(imput.time))
# Zapišemo za prikaz v tabeli in prenos s strani uporabnika.
write.csv2(imput.time, paste0("modules/mod_kakovost/results/rt_", ID, ".csv"), row.names = FALSE)
