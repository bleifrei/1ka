#uporabnost <- function(params){

#setwd("path od mape, kjer se nahaja ta glavna datoteka, npr. C:/mapa")

# Import data.table & functions ------------------------------------------------------
require("data.table")
source("modules/mod_uporabnost/R/gen.survey.str.R")
source("modules/mod_uporabnost/R/gen.usability.matrix.R")
source("modules/mod_uporabnost/R/calc.usability.R")

# Input data ------------------------------------------------------
params <- commandArgs(trailingOnly = TRUE)
ID <- params[1]

#get & import dsa: the main survey data file (containing only recnum, status, lurker and all variables relating to answers to survey questions)
dsa.file <- paste0("modules/mod_uporabnost/temp/data_", ID, ".csv")
dsa <- fread(dsa.file, header=T, drop=c(1:5, 7, 8))

#get question and item files
questions.file <- paste0("modules/mod_uporabnost/temp/questions_", ID, ".csv")
items.file <- paste0("modules/mod_uporabnost/temp/items_", ID, ".csv")

# Main & Output ------------------------------------------------------
#generate survey structure
survey.str <- gen.survey.str(colnames(dsa)[-(1)], questions.file, items.file)

if(any(!(is.data.table(survey.str)), nrow(survey.str)==0)){
  write(survey.str, paste0("modules/mod_uporabnost/results/usability_", ID, ".csv"))
}else{
  #delete invisible variables and types: 5, 9, 22, 23, 25
  survey.str <- survey.str[visible==1 & !(tip %in% c(5, 9, 22, 23, 25)),]
  
  #generate usability matrix
  m.all <- gen.usability.matrix(dsa, survey.str)

  if(any(!(is.data.table(m.all)), nrow(m.all)==0)){
    write(m.all, paste0("modules/mod_uporabnost/results/usability_", ID, ".csv"))
  }else{
    #calculate usability indexes
    m.final <- calc.usability(m.all, 3)
    
    #write to results
    write.csv2(m.final, paste0("modules/mod_uporabnost/results/usability_", ID, ".csv"), row.names = FALSE)
  }
}

#}