

test <- c(paste("\\documentclass{article}",
         # % specifies document class (article) and point size (10pt)
           
           "\\begin{document}",            #   % starts document
           
           "TESTNA STRAN",      # % specifies big, fancy title)

            "\\end{document}"))





cat(test, file=paste0("modules/mod_hierarhija/porocila/results/test.tex"), sep="\n")
tools::texi2pdf(file=paste0("modules/mod_hierarhija/porocila/results/test.tex"), quiet=TRUE, clean=TRUE)
