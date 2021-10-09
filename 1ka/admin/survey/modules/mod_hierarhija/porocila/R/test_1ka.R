library(ReporteRs)
Sys.setlocale(category = "LC_ALL", locale = "slovenian")
# Create a word document to contain R outputs
doc <- docx()

test12 <- c("Testna stran ČŽŠ šš žž čč")

test12<- gsub('Č', '\u010C', test12)
test12 <- gsub('č', '\u010D', test12)
test12 <- gsub('Š', '\u0160', test12)
test12<- gsub('š', '\u0161', test12)
test12 <- gsub('Ž', '\u017D', test12)
test12 <- gsub('ž', '\u017E', test12)
# Add a title to the document
doc <- addTitle(doc, test12, level=1)


# Write the Word document to a file 
writeDoc(doc, file = paste0("modules/mod_hierarhija/porocila/results/test.docx"))
