gen.survey.str <- function(colnames.dsa, questions.file, items.file){
  #import questions file
  questions <- fread(questions.file, skip=1, header=F,
                     select=c(2, 5, 6, 8, 9, 10),
                     col.names=c("question.id", "variable", "tip", "size", "visible", "params"))
  
  #create variable list from survey data file
  #remove "recnum" and "_text" fields
  var.data <- colnames.dsa[sapply(colnames.dsa, function(x){substr(x, nchar(x)-4, nchar(x))})!="_text"]
  
  #create variable list from questions file
  var.questions <- questions$variable
  
  #generate data.table from var.data list
  survey.str <- data.table(variable = var.data)
  
  setkey(questions, "variable")
  setkey(survey.str, "variable")
  
  #if all var.data in var.questions, do the simple merge and return file
  if(all(var.data %in% var.questions)){
    survey.str <- questions[survey.str,]
    return(survey.str)
  }else{   #if not, import items file and do additional merge with it...
    #import items file
    items <- fread(items.file, skip=1, header=F,
                   select=c(2, 3, 4),
                   col.names=c("question.id", "item.id", "variable"))
    
    setkey(items, "question.id")
    setkey(questions, "question.id")

    #bind variables from questions and items (for the later, only take instances with no match in the questions file...)
    survey.str.qi <- rbindlist(list(questions[var.questions %in% var.data,],
                                    items[questions[!(var.questions %in% var.data), -"variable", with=F], nomatch=0L]), 
                               fill=T)

    #merge questions+items with survey data...
    setkey(survey.str.qi, "variable")
    setkey(survey.str, "variable")
    survey.str <- survey.str.qi[survey.str,]
    
    #if all var.data is now matched, return the survey.str
    if(!(any(is.na(survey.str)))){
      return(survey.str)
    }else{  #if not, do additional merging...
      #create index of all NA instaces from survey.str...
      index <- apply(cbind(survey.str[, is.na(tip)], 
                           (sapply(survey.str[, variable], function(x){
                             substr(x, 1, regexpr("\\_[^\\_]*$", x)-1)
                           }) %in% survey.str.qi$variable)
      ), 
      1, all)
      
      #... using regex to find matches among unmatched instances from survey.str.qi
      add <- merge(survey.str[index, list(variable, substr(variable, 1, regexpr("\\_[^\\_]*$", variable)-1))], 
                   survey.str.qi[!(variable %in% survey.str$variable),], 
                   by.x="V2", by.y="variable", all.y=F)[, list(question.id, item.id, tip, visible, size, params)]

      #update survey.str with new values
      survey.str[index, c("question.id", "item.id", "tip", "visible", "size", "params") := as.list(add)]

      #if there is no NAs left, return survey.str, else return msg
      if(!(any(is.na(survey.str$tip)))){
        return(survey.str)
      }else{
        return(paste("No match found for: ", survey.str[is.na(tip), variable]))
      }
    }
  }
}
