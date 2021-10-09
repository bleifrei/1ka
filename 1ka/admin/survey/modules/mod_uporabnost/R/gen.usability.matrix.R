gen.usability.matrix <- function(dsa, survey.str){
  #define special values to detect
  #order of this values is important: 
  #  in case of conflicts @ chk.t types of questions the order sets the priporty of which values to keep
  special.v <- c(-1, -3, -5, -96, -97, -98, -99, -4, -2)
  
  #define which variables belong to checkbox-like* questions
  #(* i.e.: check for special values @ ANY variable per question/item ID)
  # 2: normal checkbox
  # 16: multicheckbox
  # 17: ranking
  chkbox.t <- c(2, 16, 17)
  
  ##all other variables belong to normal** questions
  #(** i.e.: check for special values @ each variable per question/item ID)
  #if there are no normal questions, create 0 matrix, otherwise...
  if(nrow(survey.str[!(tip %in% chkbox.t),])==0){
    m.n <- matrix(0, nrow = nrow(dsa), ncol=length(special.v)+1)
  }else{
    #create list of all normal questions
    c.n <- colnames(dsa)[which(colnames(dsa) %in% survey.str[!(tip %in% chkbox.t), variable])]
    
    #...count all non-special values for each variable
    #... + count each special value for each variable
    m.n <- cbind(rowSums(sapply(dsa[, c.n, with=FALSE], function(x){!(x %in% special.v)})),
                 sapply(special.v, function(x){as.integer(rowSums(dsa[, c.n, with=FALSE]==x, na.rm=TRUE))}))
  }
  
  ##procedure for tip:2
  #only run if there is an at least one tip:2 variable
  if(survey.str[, any(tip==2)]){
    #get list of all unique tip:2 question ids
    q.2 <- unique(survey.str[tip==2, question.id])
    #get list of all corresponding variables for each q.2 id
    c.2 <- lapply(q.2, function(x){colnames(dsa)[which(colnames(dsa) %in% survey.str[question.id==x & tip==2, variable])]})
    
    #(do this for each instance in c.2):
    #for each set of variables:
    # check if any variable contains at least one non-special value
    # + (for each special value) check if any variable contains at least special value 
    m.2 <- lapply(c.2, function(x){
      cbind(apply(dsa[, x, with=FALSE], 1, function(q){any(!(q %in% special.v))}),
            sapply(special.v, function(y){
              apply(dsa[, x, with=FALSE], 1, function(q){any(q==y)})
            })
      )
    })
    
    # (do this for each instance in c.2)
    # if multiple special values per respondent exist, keep only the first one
    m.2 <- lapply(m.2, function(x){
      if(any(rowSums(x)>1)){
        p <- x[rowSums(x)>1,]
        for(i in 1:nrow(p)){
          a <- p[i,]
          f <- TRUE
          for(j in 1:length(a)){
            print(j)
            if(a[j] & f){
              f <- FALSE
            }else if(a[j] & !f){
              a[j] <- FALSE
            }
          }
          p[i,] <- a
        }
        x[rowSums(x)>1,] <- p
      }else{x}
    })
    
    
    #add to m.n
    m.n <- m.n + Reduce('+', m.2) 
  }
  
  ##procedure for tip:16
  #only run if there is an at least one tip:16 variable
  if(survey.str[, any(tip==16)]){
    #get list of all unique tip:16 item ids
    q.16 <- unique(survey.str[tip==16, item.id])
    
    #get list of all corresponding variables for each q.16 id
    c.16 <- lapply(q.16, function(x){colnames(dsa)[which(colnames(dsa) %in% survey.str[item.id==x & tip==16, variable])]})
    #(do this for each special value):
    #for each set of variables, check if any variable contains at least one special value
    # m.16 <- sapply(special.v, function(x){
    #   rowSums(sapply(c.16, function(y){
    #     apply(dsa[, y, with=FALSE], 1, function(q){any(q==x)})
    #   }))
    # })
    
    #(do this for each instance in c.16):
    #for each set of variables:
    # check if any variable contains at least one non-special value
    # + (for each special value) check if any variable contains at least special value 
    m.16 <- lapply(c.16, function(x){
      cbind(apply(dsa[, x, with=FALSE], 1, function(q){any(!(q %in% special.v))}),
            sapply(special.v, function(y){
              apply(dsa[, x, with=FALSE], 1, function(q){any(q==y)})
            })
      )
    })
    
    # (do this for each instance in c.16)
    # if multiple special values per respondent exist, keep only the first one
    m.16 <- lapply(m.16, function(x){
      if(any(rowSums(x)>1)){
        p <- x[rowSums(x)>1,]
        for(i in 1:nrow(p)){
          a <- p[i,]
          f <- TRUE
          for(j in 1:length(a)){
            print(j)
            if(a[j] & f){
              f <- FALSE
            }else if(a[j] & !f){
              a[j] <- FALSE
            }
          }
          p[i,] <- a
        }
        x[rowSums(x)>1,] <- p
      }else{x}
    })
    
    m.n <- m.n + Reduce('+', m.16)
  }
  
  ##procedure for tip:17
  #only run if there is an at least one tip:17 variable
  if(survey.str[, any(tip==17)]){
    #get list of all unique tip:17 question ids
    q.17 <- unique(survey.str[tip==17, question.id])
    
    #get list of all corresponding variables for each q.17 id
    c.17 <- lapply(q.17, function(x){colnames(dsa)[which(colnames(dsa) %in% survey.str[question.id==x & tip==17, variable])]})
    
    #similiar procedure as for tip:2 and tip:16....
    m.17 <- lapply(c.17, function(x){
      cbind(apply(dsa[, x, with=FALSE], 1, function(q){any(!(q %in% special.v))}),
            sapply(special.v, function(y){
              apply(dsa[, x, with=FALSE], 1, function(q){any(q==y)})
            })
      )
    })
    
    #... the only difference is that we are checking for all rowsums > 0, not > 1
    m.17 <- lapply(m.17, function(x){
      if(any(rowSums(x)>1)){
        p <- x[rowSums(x)>0,]
        for(i in 1:nrow(p)){
          a <- p[i,]
          f <- TRUE
          for(j in 1:length(a)){
            if(a[j] & f){
              f <- FALSE
            }else if(a[j] & !f){
              a[j] <- FALSE
            }
          }
          p[i,] <- a
        }
        x[rowSums(x)>0,] <- p
      }else{x}
    })
    
    m.n <- m.n + Reduce('+', m.17)
  } 
  
  m.n <- cbind(m.n, rowSums(m.n))
  
  if(all(m.n[, ncol(m.n)][1]==m.n[, ncol(m.n)])){
    m.n <- as.data.table(m.n)
    m.n[, recnum:=dsa$recnum]
    setnames(m.n, colnames(m.n)[-length(colnames(m.n))], c("va", "v1", "v3", "v5", "v96", "v97", "v98", "v99", "v4", "v2", "allqs"))
    setcolorder(m.n, c("recnum", colnames(m.n)[-length(colnames(m.n))]))
    return(m.n)
  }else{
    print("not all rowsums equal!") 
  }
}