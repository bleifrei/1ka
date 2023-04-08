#--------------- NEEDED LIBRARIES ---------------#
# devtools::install_github("dreamRs/esquisse")
library(shiny)
library(esquisse)
#------------- //NEEDED LIBRARIES// -------------#


#-------------------------- PHP ----------------------------#
# Passing arguments to an R script from command lines
# params <- commandArgs(trailingOnly = TRUE)
# # CSV name
# filename <- params[1]
#---------------------- //PHP// ----------------------------#


# ---------------------- LOAD AND PREPARE DATA FOR THE APP --------------------------#
df <-
  read.csv2(
    url("https://test.1ka.si/admin/survey/modules/mod_vizualizacija/temp/data.csv"),
    header = TRUE,
    fill = TRUE,
    stringsAsFactors = FALSE
  )

# Omit the first row od the data base (Text questions)
if (df[1, 1] == ("Ustreznost") |
    df[1, 1] == ("Relevance")) {
  df <- df[2:nrow(df), ]
}
# Label Missing values so the app will recognize them
df[df < 0] <- NA
# -------------------- //LOAD AND PREPARE DATA FOR THE APP// ------------------------#


#------------------------------- SHINY APP -----------------------------#

# CREATE USER INTERFACE (UI) #
#============================#
ui <- fluidPage(
  tags$div( # needs to be in fixed height container
    style = "position: fixed; top: 0; bottom: 0; right: 0; left: 0;", 
    esquisserUI(id = "esquisse")
  )
)

# Define server logic required to draw plots #
#============================================#
server <- function(input, output, session) {
  
  callModule(module = esquisserServer, id = "esquisse")
  
}
#----------------------------- //SHINY APP// ---------------------------#


# ShinyApp function to create a Shiny app object from the 
# UI/server pair that we defined above.
shinyApp(ui, server)