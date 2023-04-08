calc.usability <- function(m.all, return.type){
  # return.type:
  #   1: return only absolute
  #   2: return only %
  #   3: return both (even rows: absolute, odd rows: %)
  
  ## calculations
  m.all[, Prekinitve:=v3]
  m.all[, Neodgovori:=v1]
  m.all[, Nevsebinski:=v96+v97+v98+v99]
  m.all[, Izpostavljen:=allqs-(v2+v3+v4+v5)]
  setnames(m.all, "va", "Veljavni")
  
  m.all[, UNL:=Neodgovori/Izpostavljen]
  m.all[is.na(UNL)==T, UNL:=0]
  m.all[, UML:=(v3/allqs)+(1-(v3/allqs))*UNL]
  m.all[, UCL:=1-UML]
  m.all[, UIL:=v2/(v2+Izpostavljen)]
  m.all[is.na(UIL)==T, UIL:=0]
  m.all[, UAQ:=v4/allqs]
  
  m.all[, Uporabnost:=1-UML]
  
  #tidy up
  setcolorder(m.all, c("recnum", "allqs", "Veljavni", "Nevsebinski", "Neodgovori", 
                       "Izpostavljen", "Prekinitve", "Uporabnost",
                       "v1", "v2", "v3", "v4", "v5", "v96", "v97", "v98", "v99",
                       "UNL", "UML", "UCL", "UIL", "UAQ"))
  
  if(return.type==1){
    return(m.all)
  }else{
    m.all.p <- copy(m.all)
    
    m.all.p[, (c("Veljavni", "Nevsebinski", "Neodgovori")) := lapply(.SD, "/", m.all.p$Izpostavljen), .SDcols=c("Veljavni", "Nevsebinski", "Neodgovori")]
    m.all.p[, (c("Prekinitve", "v1", "v2", "v3", "v4", "v5", "v96", "v97", "v98", "v99")) := lapply(.SD, "/", m.all.p$allqs), .SDcols=c("Prekinitve", "v1", "v2", "v3", "v4", "v5", "v96", "v97", "v98", "v99")]
    m.all.p[, Izpostavljen:=1]
    
    if(return.type==2){
      return(m.all.p)
    }else{
      m.all[, Uporabnost:=Veljavni]
      m.all[, c("UNL", "UML", "UCL", "UIL", "UAQ"):=NA]
      m.all <- m.all[, lapply(.SD, as.character)]
      
      m.all.p[, allqs:=NA]
      m.all.p[, allqs:=as.character(allqs)]
      
      change.cols <- c("Veljavni", "Nevsebinski", "Neodgovori", "Izpostavljen", "Prekinitve", "Uporabnost",
                       "v1", "v2", "v3", "v4", "v5", "v96", "v97", "v98", "v99",
                       "UNL", "UML", "UCL", "UIL", "UAQ")
      m.all.p[, (change.cols):=lapply(.SD, function(x){paste0(round(x*100, 0), "%")}), .SD=change.cols]
      
      m.1ka <- data.table(matrix("", nrow=nrow(m.all)*2, ncol=ncol(m.all)))
      
      a.rows <- as.integer(seq(1, nrow(m.1ka), by=2))
      p.rows <- as.integer(seq(2, nrow(m.1ka), by=2))
      
      set(m.1ka, a.rows, 1:ncol(m.1ka), value=m.all)
      suppressWarnings(set(m.1ka, p.rows, 1:ncol(m.1ka), value=m.all.p))
      
      setnames(m.1ka, colnames(m.all))
      m.1ka[, Status:=NA_character_]
      setcolorder(m.1ka, c("recnum", "allqs", "Veljavni", "Nevsebinski", "Neodgovori", 
                           "Izpostavljen", "Prekinitve", "Uporabnost", "Status",
                           "v1", "v2", "v3", "v4", "v5", "v96", "v97", "v98", "v99",
                           "UNL", "UML", "UCL", "UIL", "UAQ"))
      
      return(m.1ka)
    }
  }
}