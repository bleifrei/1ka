#Sys.setlocale(category = "LC_ALL", locale = "slovenian")
#Sys.setlocale('LC_CTYPE', 'pl_PL.UTF-8')
Sys.setlocale(category = "LC_CTYPE", locale = "Slovenian")
# Potrebne knjižnjice
libraries <- c('tools', 'psych', 'Hmisc', 'reports', 
               'fmsb','car','Cairo','openxlsx','sqldf','data.table',
               'gridExtra','ggplot2','Rcpp','grid','scales')
lapply(libraries, FUN = function(y) {
  do.call('require', list(y))})

library(scales)
# //Potrebne knjižnjice// #

# Ustvarimo mape, kamor bomo shranjevali poroèilo in pdf grafov
dir.create('modules/mod_360_1KA/results', showWarnings=FALSE)
dir.create('modules/mod_360_1KA/results/slike', showWarnings=FALSE)



# Uvoz podatkov
podatki <- read.csv2("modules/mod_360_1KA/temp/test.csv",sep=";", header=T, fill=T, stringsAsFactors=FALSE,encoding='UTF-8')
if (podatki[1,1]==("Ustreznost") | podatki[1,1]==("Relevance")) {podatki <- podatki[2:nrow(podatki),]}
# Izberemo le veljavne enote (status=6)
podatki <- subset(podatki, status==6)


# Ker bomo raèunalni povpreèja iz baze odstranimo tudi vse manjkajolèe vrednosti
junk <- c("-1","-2", "-3", "-4", "-5")
# Izberemo vse stolpce, ki se zaènejo na èrko Q
sel <- grepl("Q",names(podatki))
podatki[sel] <- lapply(podatki[sel], function(x) replace(x,x %in% junk, NA))
podatki <- subset(podatki, !(is.na(Q1)))


# Povpreèja ocen agregirana glede na razmerje (nadrejeni, podrejeni, samoocenjevalec, sodelavec)
# Uporabimo v grafih posameznih kompetenc
razmerjeMean_Q2 <- sqldf("
                         select 
                         Q1
                         ,round(avg(Q2a),1) as Q2a
                         ,round(avg(Q2b),1) as Q2b
                         ,round(avg(Q2c),1) as Q2c
                         ,round(avg(Q2d),1) as Q2d
                         ,round(avg(Q2e),1) as Q2e
                         ,round(avg(Q2f),1) as Q2f
                         from podatki
                         group by 
                         Q1
                         ")

razmerjeMean_Q3 <- sqldf("
                         select 
                         round(avg(Q3a),1) as Q3a
                         ,round(avg(Q3b),1) as Q3b
                         ,round(avg(Q3c),1) as Q3c
                         ,round(avg(Q3d),1) as Q3d
                         ,round(avg(Q3e),1) as Q3e
                         ,round(avg(Q3f),1) as Q3f
                         ,round(avg(Q3g),1) as Q3g
                         ,round(avg(Q3h),1) as Q3h
                         ,round(avg(Q3i),1) as Q3i
                         from podatki
                         group by 
                         Q1
                         ")

razmerjeMean_Q4 <- sqldf("
                         select 
                         round(avg(Q4a),1) as Q4a
                         ,round(avg(Q4b),1) as Q4b
                         ,round(avg(Q4c),1) as Q4c
                         ,round(avg(Q4d),1) as Q4d
                         ,round(avg(Q4e),1) as Q4e
                         ,round(avg(Q4f),1) as Q4f
                         from podatki
                         group by 
                         Q1
                         ")

razmerjeMean_Q5 <- sqldf("
                         select 
                         round(avg(Q5a),1) as Q5a
                         ,round(avg(Q5b),1) as Q5b
                         ,round(avg(Q5c),1) as Q5c
                         ,round(avg(Q5d),1) as Q5d
                         ,round(avg(Q5e),1) as Q5e
                         ,round(avg(Q5f),1) as Q5f
                         ,round(avg(Q5g),1) as Q5g
                         from podatki
                         group by 
                         Q1
                         ")

razmerjeMean_Q6 <- sqldf("
                         select 
                         round(avg(Q6a),1) as Q6a
                         ,round(avg(Q6b),1) as Q6b
                         ,round(avg(Q6c),1) as Q6c
                         ,round(avg(Q6d),1) as Q6d
                         ,round(avg(Q6e),1) as Q6e
                         ,round(avg(Q6f),1) as Q6f
                         from podatki
                         group by 
                         Q1
                         ")

razmerjeMean <- cbind(razmerjeMean_Q2,razmerjeMean_Q3,razmerjeMean_Q4,razmerjeMean_Q5,razmerjeMean_Q6)
razmerjeMean <- as.data.frame(lapply(razmerjeMean, as.numeric))

povprecja.razmerij <- razmerjeMean 
#Izraèun skupnih povpreèij posameznih komponent
povprecja.razmerij$Q2_komuniciranje <- round(rowMeans(razmerjeMean[grepl("Q2",names(razmerjeMean))],na.rm=T),1)
povprecja.razmerij$Q3_odlocanje <- round(rowMeans(razmerjeMean[grepl("Q3",names(razmerjeMean))],na.rm=T),1)
povprecja.razmerij$Q4_vodenje_ravnanje <-round(rowMeans(razmerjeMean[grepl("Q4",names(razmerjeMean))],na.rm=T),1)
povprecja.razmerij$Q5_vodenje_projektov  <- round(rowMeans(razmerjeMean[grepl("Q5",names(razmerjeMean))],na.rm=T),1)
povprecja.razmerij$Q6_medosebne_vescine <- round(rowMeans(razmerjeMean[grepl("Q6",names(razmerjeMean))],na.rm=T),1)


# Povpreèja posameznih  kompetence glede na razmerje do ocenjevane osebe
# Skupna povpreèja po stolpcih za GRAFE
# Prikažemo posamezne barplote povpreène ocene glede na razmerje
Q2_komuniciranje <- round(colMeans(razmerjeMean[grepl("Q2",names(razmerjeMean))],na.rm=T),1)
Q3_odlocanje  <- round(colMeans(razmerjeMean[grepl("Q3",names(razmerjeMean))],na.rm=T),1)
Q4_vodenje_ravnanje <- round(colMeans(razmerjeMean[grepl("Q4",names(razmerjeMean))],na.rm=T),1)
Q5_vodenje_projektov <- round(colMeans(razmerjeMean[grepl("Q5",names(razmerjeMean))],na.rm=T),1)
Q6_medosebne_vescine <- round(colMeans(razmerjeMean[grepl("Q6",names(razmerjeMean))],na.rm=T),1)
## Povpreèja kompetenc glede na razmerje do ocenjevane osebe// ##


#Skupna povpreèja po stolpcih za posamezne TABELE # Skupne ocene
osnovni.podatki <- podatki
osnovni.podatki <- as.data.frame(suppressWarnings(lapply(osnovni.podatki, as.numeric)))
Q2_komuniciranje.skupaj <- round(colMeans(osnovni.podatki[grepl("Q2",names(osnovni.podatki))],na.rm=T),1)
Q3_odlocanje.skupaj <- round(colMeans(osnovni.podatki[grepl("Q3",names(osnovni.podatki))],na.rm=T),1)
Q4_vodenje_ravnanje.skupaj <- round(colMeans(osnovni.podatki[grepl("Q4",names(osnovni.podatki))],na.rm=T),1)
Q5_vodenje_projektov.skupaj <- round(colMeans(osnovni.podatki[grepl("Q5",names(osnovni.podatki))],na.rm=T),1)
Q6_medosebne_vescine.skupaj <- round(colMeans(osnovni.podatki[grepl("Q6",names(osnovni.podatki))],na.rm=T),1)

# //TABELE # Skupne ocene// #


# pretvorba imen stolpcev in vrstic tabel za latex znake
toLatex <- function(tabela) {
  if (!is.null(colnames(tabela))) {
    colnames(tabela) <- Hmisc::latexTranslate(colnames(tabela))
  }
  if (!is.null(rownames(tabela))) {
    rownames(tabela) <- Hmisc::latexTranslate(rownames(tabela))
  }
  return(tabela)
}
##

##############################
## Opisne statistike za tabele
##############################
# N oz. število odgovorov
predN <- apply(osnovni.podatki[c(grepl("Q",names(osnovni.podatki)))],2, FUN=function(x) sum(!is.na(x)))
names(predN) <- paste0(names(predN),"_N")
#SD
predSd <- apply(osnovni.podatki[c(grepl("Q",names(osnovni.podatki)))], 2, FUN=sd, na.rm=TRUE)
names(predSd) <- paste0(names(predSd),"_sd")
predSd <- round(predSd,1)
#MIN
predMin <- apply(osnovni.podatki[c(grepl("Q",names(osnovni.podatki)))], 2, FUN=min, na.rm=TRUE)
names(predMin) <- paste0(names(predMin),"_max")
predMin[predMin==Inf] <- NA
#MAX
predMax <- apply(osnovni.podatki[c(grepl("Q",names(osnovni.podatki)))],2, FUN=max, na.rm=TRUE)
names(predMax) <- paste0(names(predMax),"_max")
predMax[predMax==Inf] <- NA
## //Opisne statistike za tabele// ##


###########################
#### making latex file ####
###########################
## LATEX TABLE 1: Q2 KOMUNICIRANJE
mean.Q2_komuniciranje.skupaj <- round(mean(Q2_komuniciranje.skupaj),1)

tabela.Q2 <- cbind.data.frame(
  N = as.numeric(predN[grepl("Q2",names(predN))]),
  mean = Q2_komuniciranje.skupaj,
  sd = as.numeric(predSd[grepl("Q2",names(predSd))]),
  min = as.numeric(predMin[grepl("Q2",names(predMin))]),
  max = as.numeric(predMax[grepl("Q2",names(predMax))]))
# Èe je NA naj se v tabeli prikaže "."
tabela.Q2[is.na(tabela.Q2)] <- "."
# Dodamo skupno povpreèje komponent
tabela.Q2 <- rbind(tabela.Q2, c('',mean.Q2_komuniciranje.skupaj,'','',''))

# Dodamo imena vrstic, ki bodo imena anketnih vprašanj
namesQ2_komuniciranje <- c("Sposobnost besednega izražanja pri predstavitvi ideje.",
                           "Sposobnost pravoèasnega in toènega poroèanja.",
                           "Sposobnost informiranja drugih.",
                           "Sposobnost spodbujanja in uporabe odkrite komunikacije.",
                           "Sposobnost podajanja toènih in usklajenih informacij ali navodil.",
                           "Sposobnost uporabe primernih komunikacijskih orodij.")

rownames(tabela.Q2) <- c(Hmisc::latexTranslate(paste0("\\hline\n",namesQ2_komuniciranje)), "\\hline\n\\textbf{Skupaj}")
# Imena stolpcev
colnames(tabela.Q2) <- c("\\textbf{n}","\\textbf{povpreèje}","\\textbf{std. odklon}","\\textbf{min}","\\textbf{max}")

# Latex tabela
tabelatex1 <- capture.output(Hmisc::latex(toLatex(tabela.Q2),
                                          caption="Povpreèje komponent ",
                                          rowlabel='\\textbf{Kompetence komuniciranja}',
                                          file='',
                                          where='H',
                                          col.just=rep_len('|c', ncol(tabela.Q2)),
                                          rowlabel.just='m{8cm}'))

## //LATEX TABLE 1: Q2 KOMUNICIRANJE// ##


####################################################
## LATEX TABLE 2: Q3 Sposobnost odloèanja in presoje
####################################################
# Izraèunamo skupno povpreèje
mean.Q3_odlocanje.skupaj <- round(mean(Q3_odlocanje.skupaj),1)

tabela.Q3 <- cbind.data.frame(
  N = as.numeric(predN[grepl("Q3",names(predN))]),
  mean = Q3_odlocanje.skupaj,
  sd = as.numeric(predSd[grepl("Q3",names(predSd))]),
  min = as.numeric(predMin[grepl("Q3",names(predMin))]),
  max = as.numeric(predMax[grepl("Q3",names(predMax))]))

# Èe je NA naj se v tabeli prikaže "."
tabela.Q3[is.na(tabela.Q3)] <- "."
# Dodamo skupno povpreèje komponent
tabela.Q3 <- rbind(tabela.Q3, c('',mean.Q3_odlocanje.skupaj,'','',''))

# Dodamo imena vrstic, ki bodo imena anketnih vprašanj
namesQ3_sposobnost <- c("Sposobnost dobrih in pravoèasnih odloèitev.",
                        "Sposobnost samozavestnega in suverenega odloèanja.",
                        "Sposobnost uporabe sistematiènega in analitiènega pristopa.",
                        "Sposobnost sprejemanja dobrih odloèitev tudi pod pritiskom.",
                        "Sposobnost mediacije in iskanja konsenza med vpletenimi v konfliktu. ",
                        "Sposobnost sprejemanja odloèitev in ukrepov v težkih situacijah.",
                        "Sposobnost prevzemanja odgovornosti za svoje odloèitve.",
                        "Sposobnost uporabe preteklih izkušenj.",
                        "Sposobnost širokega razmišljanja.")

rownames(tabela.Q3) <- c(Hmisc::latexTranslate(paste0("\\hline\n",namesQ3_sposobnost)), "\\hline\n\\textbf{Skupaj}")
# Imena stolpcev
colnames(tabela.Q3) <- c("\\textbf{n}","\\textbf{povpreèje}","\\textbf{std. odklon}","\\textbf{min}","\\textbf{max}")

# Latex tabela
tabelatex2 <- capture.output(Hmisc::latex(toLatex(tabela.Q3),
                                          caption="Povpreèje komponent ",
                                          rowlabel='\\textbf{Kompetence sposobnosti odloèanja in presoje}',
                                          file='',
                                          where='H',
                                          col.just=rep_len('|c', ncol(tabela.Q3)),
                                          rowlabel.just='m{8cm}'))

## //LATEX TABLE 2: Q3 Sposobnost odloèanja in presoje// ##




#################################################
## LATEX TABLE 3: Q4 Vodenje in ravnanje z ljudmi
#################################################
# Izraèunamo še skupna povpreèja
mean.Q4_vodenje_ravnanje.skupaj <- round(mean(Q4_vodenje_ravnanje.skupaj),1)

tabela.Q4 <- cbind.data.frame(
  N = as.numeric(predN[grepl("Q4",names(predN))]),
  mean = Q4_vodenje_ravnanje.skupaj,
  sd = as.numeric(predSd[grepl("Q4",names(predSd))]),
  min = as.numeric(predMin[grepl("Q4",names(predMin))]),
  max = as.numeric(predMax[grepl("Q4",names(predMax))]))

# Èe je NA naj se v tabeli prikaže "."
tabela.Q4[is.na(tabela.Q4)] <- "."
# Dodamo skupno povpreèje komponent
tabela.Q4 <- rbind(tabela.Q4, c('',mean.Q4_vodenje_ravnanje.skupaj,'','',''))

# Dodamo imena vrstic, ki bodo imena anketnih vprašanj
namesQ4_vodenje <- c("Sposobnost spodbujanja in motiviranja zaposlenih.",
                     "Sposobnost definiranja nalog in odgovornost.",
                     "Sposobnost modrega in uèinkovitega delegiranja.",
                     "Sposobnost vzdrževanja dobrega, zabavnega in stimulativnega delovnega okolja.",
                     "Sposobnost nagrajevanja posamiènih in skupinskih dosežkov in dela.",
                     "Sposobnost razvijanja sodelovanja na vseh ravneh.")

rownames(tabela.Q4) <- c(Hmisc::latexTranslate(paste0("\\hline\n",namesQ4_vodenje)), "\\hline\n\\textbf{Skupaj}")
# Imena stolpcev
colnames(tabela.Q4) <- c("\\textbf{n}","\\textbf{povpreèje}","\\textbf{std. odklon}","\\textbf{min}","\\textbf{max}")

# Latex tabela
tabelatex3 <- capture.output(Hmisc::latex(toLatex(tabela.Q4),
                                          caption="Povpreèje komponent ",
                                          rowlabel='\\textbf{Kompetence vodenja in ravnanja z ljudmi}',
                                          file='',
                                          where='H',
                                          col.just=rep_len('|c', ncol(tabela.Q4)),
                                          rowlabel.just='m{8cm}'))

## //LATEX TABLE 3: Q4 Vodenje in ravnanje z ljudmi// ##




#################################################
## LATEX TABLE 4: Q5 Vodenje projektov
#################################################
# Izraèunamo še skupna povpreèja
mean.Q5_vodenje_projektov.skupaj <- round(mean(Q5_vodenje_projektov.skupaj),1)

tabela.Q5 <- cbind.data.frame(
  N = as.numeric(predN[grepl("Q5",names(predN))]),
  mean = Q5_vodenje_projektov.skupaj,
  sd = as.numeric(predSd[grepl("Q5",names(predSd))]),
  min = as.numeric(predMin[grepl("Q5",names(predMin))]),
  max = as.numeric(predMax[grepl("Q5",names(predMax))]))

# Èe je NA naj se v tabeli prikaže "."
tabela.Q5[is.na(tabela.Q5)] <- "."
# Dodamo skupno povpreèje komponent
tabela.Q5 <- rbind(tabela.Q5, c('',mean.Q5_vodenje_projektov.skupaj,'','',''))

# Dodamo imena vrstic, ki bodo imena anketnih vprašanj
namesQ5_vodenje_projektov <- c("Sposobnost postavljanja jasnih in merljivih ciljev in mejnikov.",
                               "Sposobnost sistematizacije dela in procesov.",
                               "Sposobnost opredeljevanja vloge in pristojnosti ter odgovornosti.",
                               "Sposobnost definiranja potrebnih virov.",
                               "Sposobnost koordinacije procesov v celotni organizaciji.",
                               "Sposobnost spremljanja projektov in doloèanja korektivnih akcij.",
                               "Sposobnost vodenja veè projektov hkrati.")

rownames(tabela.Q5) <- c(Hmisc::latexTranslate(paste0("\\hline\n",namesQ5_vodenje_projektov)), "\\hline\n\\textbf{Skupaj}")
# Imena stolpcev
colnames(tabela.Q5) <- c("\\textbf{n}","\\textbf{povpreèje}","\\textbf{std. odklon}","\\textbf{min}","\\textbf{max}")

# Latex tabela
tabelatex4 <- capture.output(Hmisc::latex(toLatex(tabela.Q5),
                                          caption="Povpreèje komponent ",
                                          rowlabel='\\textbf{Kompetence vodenja projektov}',
                                          file='',
                                          where='H',
                                          col.just=rep_len('|c', ncol(tabela.Q5)),
                                          rowlabel.just='m{8cm}'))

## //LATEX TABLE 4: Q5 Vodenje projektov// ##




#################################################
## LATEX TABLE 5: Q6 Medosebne vešèine
#################################################
# Izraèunamo še skupna povpreèja
mean.Q6_medosebne_vescine.skupaj <- round(mean(Q6_medosebne_vescine.skupaj),1)

tabela.Q6 <- cbind.data.frame(
  N = as.numeric(predN[grepl("Q6",names(predN))]),
  mean = Q6_medosebne_vescine.skupaj,
  sd = as.numeric(predSd[grepl("Q6",names(predSd))]),
  min = as.numeric(predMin[grepl("Q6",names(predMin))]),
  max = as.numeric(predMax[grepl("Q6",names(predMax))]))

# Èe je NA naj se v tabeli prikaže "."
tabela.Q6[is.na(tabela.Q6)] <- "."
# Dodamo skupno povpreèje komponent
tabela.Q6 <- rbind(tabela.Q6, c('',mean.Q6_medosebne_vescine.skupaj,'','',''))

# Dodamo imena vrstic, ki bodo imena anketnih vprašanj
namesQ6_vescine <- c("Sposobnost reševanja konfliktov.",
                     "Sposobnost poslušanja.",
                     "Sposobnost podajanja povratnih informacij in konstruktivne kritike.",
                     "Sposobnost grajenja neformalnih odnosov za doseganje ciljev.",
                     "Sposobnost prilagodljivosti in odprte glave.",
                     "Sposobnost pogajanja.")

rownames(tabela.Q6) <- c(Hmisc::latexTranslate(paste0("\\hline\n",namesQ6_vescine)),"\\hline\n\\textbf{Skupaj}")
# Imena stolpcev
colnames(tabela.Q6) <- c("\\textbf{n}","\\textbf{povpreèje}","\\textbf{std. odklon}","\\textbf{min}","\\textbf{max}")

# Latex tabela
tabelatex5 <- capture.output(Hmisc::latex(toLatex(tabela.Q6),
                                          caption="Povpreèje komponent",
                                          rowlabel='\\textbf{Medosebne vešèine}',
                                          file='',
                                          where='H',
                                          col.just=rep_len('|c', ncol(tabela.Q6)),
                                          rowlabel.just='m{8cm}'))

## //LATEX TABLE 5: Q6 Medosebne vešèine// ##



#########
#BARPLOT
#########
# Rangiranje kompetenc

tabela.cont <- cbind.data.frame(
  a = povprecja.razmerij$Q2_komuniciranje,
  b = povprecja.razmerij$Q3_odlocanje,
  c = povprecja.razmerij$Q4_vodenje_ravnanje,
  d = povprecja.razmerij$Q5_vodenje_projektov,
  e = povprecja.razmerij$Q6_medosebne_vescine)


tabela.cont1 <- t(tabela.cont)
tabela.cont2 <- rowMeans(tabela.cont1)
tabela.cont1.means <- colMeans(tabela.cont1)

tabela.cont <- rbind(tabela.cont1, tabela.cont1.means)
tabela.cont2 <- rowMeans(tabela.cont)
tabela.cont <- cbind(tabela.cont,tabela.cont2)
tabela.cont <- round(tabela.cont, 1)

tabela.odstopanje.max <- apply(tabela.cont, 1, max)
tabela.odstopanje.min <- apply(tabela.cont, 1, min)
tabela.odstop.skupaj <- tabela.odstopanje.max - tabela.odstopanje.min
tabela.cont <- cbind(tabela.cont, round(tabela.odstop.skupaj,1))
rownames(tabela.cont) <- c("Komuniciranje","Sposobnost odloèanja","Vodenje in ravnanje",
                           "Vodenje projektov","Medosebne vešèine",'\\hline\n\\textbf{Povpreèje}')
colnames(tabela.cont) <- c('Nadrejeni', 'Podrejeni', 'Sodelavec', 'Samoocenjevalec',"\\textbf{Skupaj}","\\textbf{Odstopanje}")

# Latex tabele
tabela.contR.tex <- capture.output(Hmisc::latex(toLatex(tabela.cont), 
                                                caption="Rangirane kompetence glede na povpreèje rezultatov",
                                                rowlabel='Kompetenca',
                                                file='',
                                                where='H',
                                                col.just=c("|c","|c","|c","|c|","|c","|c"), 
                                                rowlabel.just='m{5cm}'))


## TABELA ZA <- Rangiranje kompetenc ##
tabela.matrix <- cbind.data.frame(
  a = povprecja.razmerij$Q2_komuniciranje,
  b = povprecja.razmerij$Q3_odlocanje,
  c = povprecja.razmerij$Q4_vodenje_ravnanje,
  d = povprecja.razmerij$Q5_vodenje_projektov,
  e = povprecja.razmerij$Q6_medosebne_vescine)

tabela.matrix<-t(tabela.matrix)
rownames(tabela.cont) <- c("Komuniciranje","Sposobnost odloèanja","Vodenje in ravnanje",
                           "Vodenje projektov","Medosebne vešèine",'\\hline\n\\textbf{Povpreèje}')
colnames(tabela.matrix) <- c('Nadrejeni', 'Podrejeni', 'Enak nivo', 'Samoocenjevalec')
tabela.matrix <- round(tabela.matrix, 1)

## GRAF ##
# BARPLOT RANGIRANIH KOMPETENC
df <- as.data.frame(tabela.matrix)
rownames(df) <- c("Komuniciranje", "Sposobnost odlocanja", "Vodenje in ravnanje", "Vodenje projektov", "Medosebne vescine")
df$name <- rownames(df)

# Data.table
df2 <- melt(setDT(df), id="name")
df2[, difference := max(value) - min(value), by = name]



pdf(paste('modules/mod_360_1KA/results/slike/rangiranje_kompetenc.pdf', sep=''), family='sans', pointsize=11, width=8, height=7,encoding = 'CP1250')



# GGplot
p <- ggplot(df2, aes(x=name, y=value, fill=variable)) +
  theme_bw()+
  geom_bar(stat="identity",width=0.8, position="dodge") +
  geom_line(aes(x=name, y=difference, group=1), size=1.5, color="red") +
  scale_x_discrete(expand = c(0,0)) +
  scale_y_continuous(expand = c(0,0), limits = c(0,5)) +
  theme(legend.position = "bottom", axis.text.x = element_text(angle = 20,vjust = 0.3)) +
  scale_fill_manual(values = c("#d99694", "#c00000", "#632523","#7F7F7F")) +
  geom_text(aes(x = name, y = 0.15, label = round(value, 2), fill = variable), 
            angle = 90, position = position_dodge(width = 0.7), size = 5)

# Odstranimo še imena x in y osi ter naslov legende
p + labs(x="",y="")+ guides(fill=guide_legend(title=NULL)) 

dev.off()

tex.graf  <- c(paste0(
  "\\begin{figure}[H]", 
  "\\caption{Razlike med ocenjevalci za povpreèno oceno na \\textbf{vseh kompetencah}}",
  paste0("\\centerline{\\includegraphics[width=0.80\\textwidth]{slike/rangiranje_kompetenc.pdf}}"),
  "\\end{figure}")
  
)
## //RANGIRANJE KOMPETENC// ##



#####################################
# PRIPRAVA PDOATKOV ZA GRAFE: GGPLOT
####################################
# Najprej ustvarimo spremenljivke v katere zapišemo tekst vprašanj, ki se bo prikazal na grafih, na y osi 
# IMENA KOMPETENC ZA GGPLOT pod tabelami za posamenzo kompetenco. Prikaz CELOTNEGA TEKSTA ##
######
## TEKST Q2 KOMUNICIRANJE ##
#####
names(Q2_komuniciranje.skupaj) <- (namesQ2_komuniciranje)
# Za prikaz CELOTNEGA teksta v ggplotu
# Dodaj line break oz. nov odstavek za vsakih 30 znakov teksta.
names.komuniciranje <- gsub('(.{1,43})(\\s|$)', '\\1\n',names(Q2_komuniciranje.skupaj)) 

######
## TEKST Q3 SPOSOBNOST ODLOÈANJA ##
#####
names(Q3_odlocanje.skupaj) <- (namesQ3_sposobnost)
# Za prikaz CELOTNEGA teksta v ggplotu
# Dodaj line break oz. nov odstavek za vsakih 30 znakov teksta.
names.sposobnost <- gsub('(.{1,43})(\\s|$)', '\\1\n',names(Q3_odlocanje.skupaj)) 

######
## TEKST Q4 Vodenje in ravnanje z ljudmi ##
#####
names(Q4_vodenje_ravnanje.skupaj) <- (namesQ4_vodenje)
# Za prikaz CELOTNEGA teksta v ggplotu
# Dodaj line break oz. nov odstavek za vsakih 30 znakov teksta.
names.vodenje.ravnanje <- gsub('(.{1,43})(\\s|$)', '\\1\n',names(Q4_vodenje_ravnanje.skupaj))

######
## TEKST Q5 Vodenje projektov ##
#####
names(Q5_vodenje_projektov.skupaj) <- (namesQ5_vodenje_projektov)
# Za prikaz CELOTNEGA teksta v ggplotu
# Dodaj line break oz. nov odstavek za vsakih 30 znakov teksta.
names.vodenje.projektov <- gsub('(.{1,43})(\\s|$)', '\\1\n',names(Q5_vodenje_projektov.skupaj))

######
## TEKST Q6 Medosebne vešèine ##
#####
names(Q6_medosebne_vescine.skupaj) <- (namesQ6_vescine)
# Za prikaz CELOTNEGA teksta v ggplotu
# Dodaj line break oz. nov odstavek za vsakih 30 znakov teksta.
names.vescine <- gsub('(.{1,43})(\\s|$)', '\\1\n',names(namesQ6_vescine))

## //IMENA KOMPETENC// ##


##############################################################
## IZRAÈUN POVPREÈIJ POSAMEZNE SKUPINE ZA POSAMEZNO KOMPETENCO 
##############################################################
###########
# NADREJENI
###########
nadrejeni <- subset(razmerjeMean, Q1==1)
#1 Q2 Komuniciranje
nadrejeni.komuniciranje <- colMeans(nadrejeni[grepl("Q2",names(nadrejeni))],na.rm=T)
#2 Q3 Sposobnost odloèanja in presoje
nadrejeni.odlocanje <- colMeans(nadrejeni[grepl("Q3",names(nadrejeni))],na.rm=T)
#3 Q4 Vodenje in ravnanje z ljudmi
nadrejeni.vodenje.ravananje <- colMeans(nadrejeni[grepl("Q4",names(nadrejeni))],na.rm=T)
#5 Q5 Vodenje projektov
nadrejeni.vodenje.projektov <- colMeans(nadrejeni[grepl("Q5",names(nadrejeni))],na.rm=T)
#6 Q6 Medosebne vešèine
nadrejeni.vescine <- colMeans(nadrejeni[grepl("Q6",names(nadrejeni))],na.rm=T)
## //nadrejeni// ##


###########
# Podrejeni
###########
podrejeni <- subset(razmerjeMean, Q1==2)
#1 Q2 Komuniciranje
podrejeni.komuniciranje <- colMeans(podrejeni[grepl("Q2",names(podrejeni))],na.rm=T)
#2 Q3 Sposobnost odloèanja in presoje
podrejeni.odlocanje <- colMeans(podrejeni[grepl("Q3",names(podrejeni))],na.rm=T)
#3 Q4 Vodenje in ravnanje z ljudmi
podrejeni.vodenje.ravananje <- colMeans(podrejeni[grepl("Q4",names(podrejeni))],na.rm=T)
#5 Q5 Vodenje projektov
podrejeni.vodenje.projektov <- colMeans(podrejeni[grepl("Q5",names(podrejeni))],na.rm=T)
#6 Q6 Medosebne vešèine
podrejeni.vescine <- colMeans(podrejeni[grepl("Q6",names(podrejeni))],na.rm=T)


###########
# Sodelavec
###########
sodelavec <- subset(razmerjeMean, Q1==3)
#1 Q2 Komuniciranje
sodelavec.komuniciranje <- colMeans(sodelavec[grepl("Q2",names(sodelavec))],na.rm=T)
#2 Q3 Sposobnost odloèanja in presoje
sodelavec.odlocanje <- colMeans(sodelavec[grepl("Q3",names(sodelavec))],na.rm=T)
#3 Q4 Vodenje in ravnanje z ljudmi
sodelavec.vodenje.ravananje <- colMeans(sodelavec[grepl("Q4",names(sodelavec))],na.rm=T)
#5 Q5 Vodenje projektov
sodelavec.vodenje.projektov <- colMeans(sodelavec[grepl("Q5",names(sodelavec))],na.rm=T)
#6 Q6 Medosebne vešèine
sodelavec.vescine <- colMeans(sodelavec[grepl("Q6",names(sodelavec))],na.rm=T)

#################
# Samoocenjevalec
#################
samoocenjevalec <- subset(razmerjeMean, Q1==4)
#1 Q2 Komuniciranje
samoocenjevalec.komuniciranje <- colMeans(samoocenjevalec[grepl("Q2",names(samoocenjevalec))],na.rm=T)
#2 Q3 Sposobnost odloèanja in presoje
samoocenjevalec.odlocanje <- colMeans(samoocenjevalec[grepl("Q3",names(samoocenjevalec))],na.rm=T)
#3 Q4 Vodenje in ravnanje z ljudmi
samoocenjevalec.vodenje.ravananje <- colMeans(samoocenjevalec[grepl("Q4",names(samoocenjevalec))],na.rm=T)
#5 Q5 Vodenje projektov
samoocenjevalec.vodenje.projektov <- colMeans(samoocenjevalec[grepl("Q5",names(samoocenjevalec))],na.rm=T)
#6 Q6 Medosebne vešèine
samoocenjevalec.vescine <- colMeans(samoocenjevalec[grepl("Q6",names(samoocenjevalec))],na.rm=T)
## //IZRAÈUN POVPREÈIJ POSAMEZNE SKUPINE ZA POSAMEZNO KOMPETENCO// ##



#################GGPLOT#############################
##GGPLOT GRAF ZA POSAMEZNO SKUPINO POD TABELAMI#####
####################################################

####### GGPLOT Q2 KOMUNICIRANJE ########
library(reshape2)
kompetenc.Q2.KOMUNICIRANJE <- rbind(nadrejeni.komuniciranje,podrejeni.komuniciranje,sodelavec.komuniciranje,samoocenjevalec.komuniciranje)
kompetenc.Q2.KOMUNICIRANJE <- t(as.matrix(kompetenc.Q2.KOMUNICIRANJE))
colnames(kompetenc.Q2.KOMUNICIRANJE) <- c("Nadrejeni", "Podrejeni", "Enak nivo", "Samooc.")
test<- c("Sposobnost besednega izrazanja pri predstavitvi ideje.",
         "Sposobnost pravocasnega in tocnega porocanja.",
         "Sposobnost informiranja drugih.",
         "Sposobnost spodbujanja in uporabe odkrite komunikacije.",
         "Sposobnost podajanja tocnih in usklajenih informacij ali navodil.",
         "Sposobnost uporabe primernih komunikacijskih orodij.")
rownames(kompetenc.Q2.KOMUNICIRANJE) <- test



ggplot.Q2 <- melt(kompetenc.Q2.KOMUNICIRANJE)


pdf(paste('modules/mod_360_1KA/results/slike/ggplot_komuniciranje.pdf', sep=''), pointsize=10, width=7.5, height=6.5,encoding = 'CP1250')



p<-ggplot(ggplot.Q2, aes(y = value,x = Var1, fill = Var2)) + coord_flip()+
  theme_bw() +
  scale_y_continuous(expand=c(0,0), limits=c(0,5.4),oob = rescale_none) +
  scale_fill_manual(values = c("#d99694", "#c00000", "#632523","#7F7F7F")) + xlim(rev(levels(ggplot.Q2$Var1)))+
  theme(axis.title=element_blank(),axis.ticks.y=element_blank(),legend.position = "bottom",
        axis.text.x = element_text(angle = 0,vjust = 0.4)) +
  geom_bar(stat = "identity", width = 0.7, position = position_dodge(width=0.7)) +
  geom_text(aes(x = Var1, y=5.2, label = round(value, 2), fill = Var2), 
            angle = 0, position = position_dodge(width = 0.7), size = 4.2)
p <- p + labs(fill="")
#Nastavitve sirine in višine legende
#p + guides(fill=guide_legend(
#  keywidth=1,
#  keyheight=1,
#  default.unit="inch")
#)
p2 <- p +
  stat_summary(fun.y = mean, color = "red", geom = "line", aes(group = 1)) + 
  stat_summary(fun.y = mean, color = "black", geom ="point", aes(group = 1), size = 3,
               show.legend = FALSE)

# This is the data for your dots in the graph
foo <- as.data.frame(ggplot_build(p2)$data[[4]])
foo$y <- round(foo$y, 1)

p2 + annotate("text", x = foo$x, y = foo$y + 0.5, color = "black", label = foo$y)

dev.off()

# SLIKA GRAFA: Latex in PDF
tex.ggplot.Q2  <- c(paste0(
  "\\begin{figure}[H]",
  "\\caption{Povpreèje komponent po skupinah \\textbf{kompetence komuniciranja}}",
  paste0("\\centerline{\\includegraphics[width=0.85\\textwidth]{slike/ggplot_komuniciranje.pdf}}"),
  "\\end{figure}"))
## //GGPLOT Q2 KOMUNICIRANJE// ##

#########################

####### GGPLOT Q3 SPOSOBNOST ODLOÈANJA IN PRESOJE ########


pdf(paste('modules/mod_360_1KA/results/slike/ggplot_odlocanje.pdf', sep=''), family = 'sans', pointsize=10, width=7.5, height=7,encoding = 'CP1250')

kompetenc.Q3.SPOSOBNOST.OD <- rbind(nadrejeni.odlocanje,podrejeni.odlocanje,sodelavec.odlocanje,samoocenjevalec.odlocanje)
kompetenc.Q3.SPOSOBNOST.OD <- t(as.matrix(kompetenc.Q3.SPOSOBNOST.OD))
colnames(kompetenc.Q3.SPOSOBNOST.OD) <- c("Nadrejeni", "Podrejeni", "Enak nivo", "Samooc.")



#test22 <- c("Sposobnost dobrih in pravoèasnih odloèitev.",
 #           "odlo\u010Danja.",
  #          "Sposobnost uporabe sistemati\u010Dnega in analiti\u010Dnega pristopa.",
   #         "Sposobnost sprejemanja dobrih odloèitev tudi pod pritiskom.",
    #        "Sposobnost mediacije in iskanja konsenza med vpletenimi v konfliktu. ",
     #       "Sposobnost sprejemanja odloèitev in ukrepov v težkih situacijah.",
      #      "Sposobnost prevzemanja odgovornosti za svoje odloèitve.",
       #     "Sposobnost uporabe preteklih izkušenj.",
        #    "Sposobnost širokega razmišljanja.")
#rownames(kompetenc.Q3.SPOSOBNOST.OD) <- test22
rownames(kompetenc.Q3.SPOSOBNOST.OD) <- names.sposobnost
ggplot.Q3 <- melt(kompetenc.Q3.SPOSOBNOST.OD)
# Èe so manjkajoèi podatki (NA ali NaN) v vrsticah jih odstranimo
ggplot.Q3 <- ggplot.Q3[complete.cases(ggplot.Q3),]



p<-ggplot(ggplot.Q3, aes(x = Var1, y = value, fill = Var2)) + coord_flip()+
  theme_bw() + 
  scale_y_continuous(expand=c(0,0), limits=c(0,5.4),oob = rescale_none) +
  scale_fill_manual(values = c("#d99694", "#c00000", "#632523","#7F7F7F")) + xlim(rev(levels(ggplot.Q3$Var1)))+
  theme(axis.title=element_blank(),axis.ticks.y=element_blank(),legend.position = "bottom",
        axis.text.x = element_text(angle = 0,vjust = 0.4)) +
  geom_bar(stat = "identity", width = 0.7, position = position_dodge(width=0.7)) +
  geom_text(aes(x = Var1, y =5.2, label = round(value, 2), fill = Var2), 
            angle = 0, position = position_dodge(width = 0.8), size = 4.2)
p <- p + labs(fill="")

p2 <- p +
  stat_summary(fun.y = mean, color = "red", geom = "line", aes(group = 1)) + 
  stat_summary(fun.y = mean, color = "black", geom ="point", aes(group = 1), size = 3,
               show.legend = FALSE)

# This is the data for your dots in the graph
foo <- as.data.frame(ggplot_build(p2)$data[[4]])
foo$y <- round(foo$y, 1)

p2 + annotate("text", x = foo$x, y = foo$y + 0.5, color = "black", label = foo$y)

dev.off()

# SLIKA GRAFA: Latex in PDF
tex.ggplot.Q3  <- c(paste0(
  "\\begin{figure}[H]",
  "\\caption{Povpreèje komponent po skupinah \\textbf{kompetence sposobnosti odloèanja in presoje}}",
  paste0("\\centerline{\\includegraphics[width=0.75\\textwidth]{slike/ggplot_odlocanje.pdf}}"),
  "\\end{figure}"))
## //GGPLOT Q3 SPOSOBNOST ODLOÈANJA IN PRESOJE// ##

#########################

####### GGPLOT Q4 VODENJE IN RAVNANJE Z LJUDMI ########
kompetenc.Q4.VODENJE.LJUDI <- rbind(nadrejeni.vodenje.ravananje,podrejeni.vodenje.ravananje,
                                    sodelavec.vodenje.ravananje,samoocenjevalec.vodenje.ravananje)
kompetenc.Q4.VODENJE.LJUDI <- t(as.matrix(kompetenc.Q4.VODENJE.LJUDI))
colnames(kompetenc.Q4.VODENJE.LJUDI) <- c("Nadrejeni", "Podrejeni", "Enak nivo", "Samooc.")
rownames(kompetenc.Q4.VODENJE.LJUDI) <- names.vodenje.ravnanje


ggplot.Q4 <- melt(kompetenc.Q4.VODENJE.LJUDI)
# Èe so manjkajoèi podatki (NA ali NaN) v vrsticah jih odstranimo
ggplot.Q4 <- ggplot.Q4[complete.cases(ggplot.Q4),]


pdf(paste('modules/mod_360_1KA/results/slike/ggplot_vodenje_ravnanje.pdf', sep=''), family = 'sans', pointsize=10, width=7.5, height=7,encoding = 'CP1250')

p<-ggplot(ggplot.Q4, aes(x = Var1, y = value, fill = Var2)) + coord_flip()+
  theme_bw() + 
  scale_y_continuous(expand=c(0,0), limits=c(0,5.4),oob = rescale_none) +
  scale_fill_manual(values = c("#d99694", "#c00000", "#632523","#7F7F7F")) + xlim(rev(levels(ggplot.Q4$Var1)))+
  theme(axis.title=element_blank(),axis.ticks.y=element_blank(),legend.position = "bottom",
        axis.text.x = element_text(angle = 0,vjust = 0.4)) +
  geom_bar(stat = "identity", width = 0.7, position = position_dodge(width=0.7)) +
  geom_text(aes(x = Var1, y =5.2, label = round(value, 2), fill = Var2), 
            angle = 0, position = position_dodge(width = 0.8), size = 4.2)
p <- p + labs(fill="")

p2 <- p +
  stat_summary(fun.y = mean, color = "red", geom = "line", aes(group = 1)) + 
  stat_summary(fun.y = mean, color = "black", geom ="point", aes(group = 1), size = 3,
               show.legend = FALSE)

# This is the data for your dots in the graph
foo <- as.data.frame(ggplot_build(p2)$data[[4]])
foo$y <- round(foo$y, 1)

p2 + annotate("text", x = foo$x, y = foo$y + 0.5, color = "black", label = foo$y)

dev.off()

# SLIKA GRAFA: Latex in PDF
tex.ggplot.Q4  <- c(paste0(
  "\\begin{figure}[H]",
  "\\caption{Povpreèje komponent po skupinah \\textbf{kompetence vodenja in ravnanja z ljudmi}}",
  paste0("\\centerline{\\includegraphics[width=0.85\\textwidth]{slike/ggplot_vodenje_ravnanje.pdf}}"),
  "\\end{figure}"))
## //GGPLOT Q4 VODENJE IN RAVNANJE Z LJUDMI// ##

#########################  

####### GGPLOT Q5 VODENJE PROJEKTOV ########
kompetenc.Q5.VODENJE.PROJEKTOV <- rbind(nadrejeni.vodenje.projektov,podrejeni.vodenje.projektov,
                                        sodelavec.vodenje.projektov,samoocenjevalec.vodenje.projektov)
kompetenc.Q5.VODENJE.PROJEKTOV <- t(as.matrix(kompetenc.Q5.VODENJE.PROJEKTOV))
colnames(kompetenc.Q5.VODENJE.PROJEKTOV) <- c("Nadrejeni", "Podrejeni", "Enak nivo", "Samooc.")
rownames(kompetenc.Q5.VODENJE.PROJEKTOV) <- names.vodenje.projektov


ggplot.Q5 <- melt(kompetenc.Q5.VODENJE.PROJEKTOV)
# Èe so manjkajoèi podatki (NA ali NaN) v vrsticah jih odstranimo
ggplot.Q5 <- ggplot.Q5[complete.cases(ggplot.Q5),]
#
#
#
#

pdf(paste('modules/mod_360_1KA/results/slike/ggplot_vodenje_projektov.pdf', sep=''), family = 'sans', pointsize=10, width=7.5, height=7)

p<-ggplot(ggplot.Q5, aes(x = Var1, y = value, fill = Var2)) + coord_flip()+
  theme_bw() + 
  scale_y_continuous(expand=c(0,0), limits=c(0,5.4),oob = rescale_none) +
  scale_fill_manual(values = c("#d99694", "#c00000", "#632523","#7F7F7F")) + xlim(rev(levels(ggplot.Q5$Var1)))+
  theme(axis.title=element_blank(),axis.ticks.y=element_blank(),legend.position = "bottom",
        axis.text.x = element_text(angle = 0,vjust = 0.4)) +
  geom_bar(stat = "identity", width = 0.7, position = position_dodge(width=0.7)) +
  geom_text(aes(x = Var1, y =5.2, label = round(value, 2), fill = Var2), 
            angle = 0, position = position_dodge(width = 0.8), size = 4.2)
p <- p + labs(fill="")

p2 <- p +
  stat_summary(fun.y = mean, color = "red", geom = "line", aes(group = 1)) + 
  stat_summary(fun.y = mean, color = "black", geom ="point", aes(group = 1), size = 3,
               show.legend = FALSE)

# This is the data for your dots in the graph
foo <- as.data.frame(ggplot_build(p2)$data[[4]])
foo$y <- round(foo$y, 1)

p2 + annotate("text", x = foo$x, y = foo$y + 0.5, color = "black", label = foo$y)

dev.off()

# SLIKA GRAFA: Latex in PDF
tex.ggplot.Q5  <- c(paste0(
  "\\begin{figure}[H]",
  "\\caption{Povpreèje komponent po skupinah \\textbf{kompetence vodenja projektov}}",
  paste0("\\centerline{\\includegraphics[width=0.85\\textwidth]{slike/ggplot_vodenje_projektov.pdf}}"),
  "\\end{figure}"))
## //GGPLOT Q5 VODENJE PROJEKTOV// ##

###################  

####### GGPLOT Q6 MEDOSEBNE VEŠÈINE ########
kompetenc.Q6.MEDOSEBNE.VESCINE <- rbind(nadrejeni.vescine,podrejeni.vescine,
                                        sodelavec.vescine,samoocenjevalec.vescine)
kompetenc.Q6.MEDOSEBNE.VESCINE <- t(as.matrix(kompetenc.Q6.MEDOSEBNE.VESCINE))
colnames(kompetenc.Q6.MEDOSEBNE.VESCINE) <- c("Nadrejeni", "Podrejeni", "Enak nivo", "Samooc.")
rownames(kompetenc.Q6.MEDOSEBNE.VESCINE) <- names.vescine


ggplot.Q6 <- melt(kompetenc.Q6.MEDOSEBNE.VESCINE)
# Èe so manjkajoèi podatki (NA ali NaN) v vrsticah jih odstranimo
ggplot.Q6 <- ggplot.Q6[complete.cases(ggplot.Q6),]
#
#
#
#

pdf(paste('modules/mod_360_1KA/results/slike/ggplot_medosebne_vescine.pdf', sep=''), family = 'sans', pointsize=10, width=7.5, height=7)

p<-ggplot(ggplot.Q6, aes(x = Var1, y = value, fill = Var2)) + coord_flip()+
  theme_bw() + 
  scale_y_continuous(expand=c(0,0), limits=c(0,5.4),oob = rescale_none) +
  scale_fill_manual(values = c("#d99694", "#c00000", "#632523","#7F7F7F")) + xlim(rev(levels(ggplot.Q2$Var1)))+
  theme(axis.title=element_blank(),axis.ticks.y=element_blank(),legend.position = "bottom",
        axis.text.x = element_text(angle = 0,vjust = 0.4)) +
  geom_bar(stat = "identity", width = 0.7, position = position_dodge(width=0.7)) +
  geom_text(aes(x = Var1, y =5.2, label = round(value, 2), fill = Var2), 
            angle = 0, position = position_dodge(width = 0.8), size = 4.2)
p <- p + labs(fill="")

p2 <- p +
  stat_summary(fun.y = mean, color = "red", geom = "line", aes(group = 1)) + 
  stat_summary(fun.y = mean, color = "black", geom ="point", aes(group = 1), size = 3,
               show.legend = FALSE)

# This is the data for your dots in the graph
foo <- as.data.frame(ggplot_build(p2)$data[[4]])
foo$y <- round(foo$y, 1)

p2 + annotate("text", x = foo$x, y = foo$y + 0.5, color = "black", label = foo$y)

dev.off()

# SLIKA GRAFA: Latex in PDF
tex.ggplot.Q6  <- c(paste0(
  "\\begin{figure}[H]",
  "\\caption{Povpreèje komponent po skupinah \\textbf{kompetence medosebnih vešèin}}",
  paste0("\\centerline{\\includegraphics[width=0.85\\textwidth]{slike/ggplot_medosebne_vescine.pdf}}"),
  "\\end{figure}"))
## //GGPLOT Q6 MEDOSEBNE VEŠÈINE// ##



#######################
#RADAR AKA. SPIDER GRAF
#######################
## RADAR GRAF

pdf(paste('modules/mod_360_1KA/results/slike/radar.pdf', sep=''), family='sans', pointsize=11, width=8, height=7)

radar.data <- t(tabela.matrix)
# provide the data you want to plot, and the desired range 

myrange <- c(1, 5) 

# create a data frame with the max and min as the first two rows 
mydf <- data.frame(rbind(max=myrange[2], min=myrange[1], radar.data)) 

# create a radar chart 
colnames(mydf) <- c("Komuniciranje","Sposobnost odlocanja\nin presoje","Vodenje in ravnanje z ljudmi","Vodenje projektov", "Medosebne vescine")


radarchart(mydf, pcol=c("#d99694", "#c00000", "#632523","#7F7F7F"), cglcol='gray75', 
           plwd=2, plty=1, cglwd=1, cglty=1, seg=4, axistype=1, caxislabels=c(1:5),
           axislabcol='gray25', centerzero=TRUE)

legend('topright', legend=c("Nadrejeni","Podrejeni","Enak nivo","Samooc."), 
       col=c("#d99694", "#c00000", "#632523","#7F7F7F"), lty=1, lwd=2, bty='n')

dev.off()

#tekst nad radar grafom
radar.poj <- c("Kvantitativno pridobljene informacije kompetenc so zelo uporabne pri oblikovanju
               radar diagrama. Znotraj diagrama so ustrezno prikazane bolj/manj poudarjene lastnosti posameznega zaposlenega.\\
               Radar diagram prikazuje, kako se je ocenjevana oseba pri posameznih kompetencah ocenila, glede na to, kako so to osebo
               ocenili drugi. Slednje pa je zelo uporabno in hitro lahko opazimo ali med ocenjevano osebo
               in drugimi obstajajo razlike pri percepciji posameznih kompetenc.")

tex.radar <- c(radar.poj,"\\begin{figure}[H]", "\\caption{Radar diagram povpreèij kompetenc po skupinah}", 
               paste0('\\centerline{\\includegraphics[width=0.95\\textwidth]{slike/radar.pdf}}'), 
               "\\end{figure}")

##// RADAR GRAF//##

Sys.setlocale("LC_ALL", "Czech")



# Dodatno pojasnilo glede VAROVANJA OSEBNIH PODATKOV
varovanje.podatkov <- scan("modules/mod_360_1KA/latexkosi/varstvo_osebnih_podatkov.tex", character(0), sep="\n", quiet=TRUE,encoding='UTF-8')
varovanje.podatkov <- gsub('©', 'Š', varovanje.podatkov, fixed=T)
varovanje.podatkov <- gsub('¹', 'š', varovanje.podatkov,fixed=T)
varovanje.podatkov <- gsub('®', 'Ž', varovanje.podatkov,fixed=T)
varovanje.podatkov <- gsub('\u017E', 'ž', varovanje.podatkov,fixed=T)
varovanje.podatkov <- gsub('\u2013', '-', varovanje.podatkov,fixed=T)
################################################
## Sestavljanje latex datotek in Generiranje PDF
################################################
tex.glava <- scan("modules/mod_360_1KA/latexkosi/a-glava-1ka.tex", character(0), sep="\n", quiet=TRUE,encoding='UTF-8')
tex.pojasnilo <- scan("modules/mod_360_1KA/latexkosi/pojasnilo.tex", character(0), sep="\n", quiet=TRUE,encoding='UTF-8') # Uvod
tex.pojasnilo <- gsub('©', 'Š', tex.pojasnilo, fixed=T)
tex.pojasnilo <- gsub('¹', 'š', tex.pojasnilo,fixed=T)
tex.pojasnilo <- gsub('®', 'Ž', tex.pojasnilo,fixed=T)
tex.pojasnilo <- gsub('\u017E', 'ž', tex.pojasnilo,fixed=T)
tex.pojasnilo <- gsub('\u2013', '-', tex.pojasnilo,fixed=T)
tex.noga  <- scan("modules/mod_360_1KA/latexkosi/z-noga.tex", character(0), sep="\n", quiet=TRUE)
Rdirektorij <- getwd()


tex.izbor <- c(tex.glava,tex.pojasnilo, "\\newpage",
               "\\chapter{Kompetence komuniciranja}",tabelatex1,"\\newpage",tex.ggplot.Q2,
               "\\chapter{Kompetence sposobnosti odloèanja in presoje}", tabelatex2,"\\newpage",tex.ggplot.Q3,
               "\\chapter{Kompetence vodenja in ravnanja z ljudmi}",tabelatex3,"\\newpage", tex.ggplot.Q4, 
               "\\chapter{Kompetence vodenja projektov}",tabelatex4,"\\newpage",tex.ggplot.Q5,
               "\\chapter{Medosebne vešèine}",tabelatex5,"\\newpage",tex.ggplot.Q6,
               "\\chapter{Rangiranje kompetenc}",tabela.contR.tex,"\\newpage",tex.graf,
               "\\chapter{Radar diagram}",tex.radar,varovanje.podatkov,tex.noga)


setwd(paste(Rdirektorij, "modules/mod_360_1KA/results", sep="/"))
cat(tex.izbor, file=paste0("mod_360_CDI.tex"), sep="\n")
tools::texi2pdf(file=paste0("mod_360_CDI.tex"), quiet=TRUE, clean=TRUE)
setwd(Rdirektorij)

